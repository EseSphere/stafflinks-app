AOS.init();

const STORAGE_KEYS = {
    selectedDate: 'stafflinks.selectedDate',
    search: 'stafflinks.search',
    statusFilter: 'stafflinks.statusFilter',
    sortVisits: 'stafflinks.sortVisits',
    darkMode: 'stafflinks.darkMode'
};

const avatarColors = [
    '#f44336', '#e91e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3',
    '#03a9f4', '#00bcd4', '#009688', '#4caf50', '#8bc34a', '#cddc39',
    '#ff9800', '#ff5722', '#795548', '#607d8b'
];

const CLIENT_DP_BASE_URL = 'https://admin.stafflinks.co.uk/';

let userSpecialId = null;
let visitsCache = [];
let cancelledCallsCache = [];
let clientStatusCache = [];
let clientDpCache = {};
let cancelledCallsSet = new Set();
let clientStatusMap = new Map();
let dbPromise = null;
let avatarRefreshQueued = false;

function formatLocalISO(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function parseISODateLocal(dateStr) {
    if (!dateStr || !/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return null;
    const [y, m, d] = dateStr.split('-').map(Number);
    return new Date(y, m - 1, d, 0, 0, 0, 0);
}

function getTodayISO() {
    return formatLocalISO(new Date());
}

function isToday(dateStr) {
    return dateStr === getTodayISO();
}

function getInitialSelectedDate() {
    // Always start from the real current date on page load.
    // User can still navigate to any other date from the calendar.
    return getTodayISO();
}

let selectedDate = getInitialSelectedDate();
let calendarCursor = parseISODateLocal(selectedDate) || new Date();

const els = {
    dateStrip: document.getElementById('dateStrip'),
    visitsContainer: document.getElementById('visitsContainer'),
    totalHours: document.getElementById('totalHours'),
    progressBar: document.getElementById('progressBar'),
    progressText: document.getElementById('progressText'),
    countCalls: document.getElementById('countCalls'),
    completedCalls: document.getElementById('completedCalls'),
    pendingCalls: document.getElementById('pendingCalls'),
    runName: document.getElementById('runName'),
    alertsContainer: document.getElementById('alertsContainer'),
    todayClock: document.getElementById('today-clock'),
    searchVisits: document.getElementById('searchVisits'),
    statusFilter: document.getElementById('statusFilter'),
    sortVisits: document.getElementById('sortVisits'),
    clearFilters: document.getElementById('clearFilters'),
    nextVisitCard: document.getElementById('nextVisitCard'),
    lastRefreshTime: document.getElementById('lastRefreshTime'),
    selectedDateLabel: document.getElementById('selectedDateLabel'),
    connStatus: document.getElementById('connStatus'),
    offlineStatus: document.getElementById('offlineStatus')
};

els.searchVisits.value = localStorage.getItem(STORAGE_KEYS.search) || '';
els.statusFilter.value = localStorage.getItem(STORAGE_KEYS.statusFilter) || 'all';
els.sortVisits.value = localStorage.getItem(STORAGE_KEYS.sortVisits) || 'time-asc';

function parseDateTimeLocal(dateStr, timeStr) {
    const [y, m, d] = dateStr.split('-').map(Number);
    const [hh = 0, mm = 0] = (timeStr || '').split(':').map(Number);
    return new Date(y, m - 1, d, hh, mm, 0);
}

function formatPrettyDate(dateStr) {
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString(undefined, {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
}

function minutesBetween(start, end) {
    return Math.max(0, Math.round((end - start) / 60000));
}

function formatDuration(totalMinutes) {
    const h = Math.floor(totalMinutes / 60);
    const m = totalMinutes % 60;
    return `${h}h ${m}m`;
}

function escapeHtml(str = '') {
    return String(str)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function debounce(fn, delay = 250) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

function persistState() {
    localStorage.setItem(STORAGE_KEYS.selectedDate, selectedDate);
    localStorage.setItem(STORAGE_KEYS.search, els.searchVisits.value.trim());
    localStorage.setItem(STORAGE_KEYS.statusFilter, els.statusFilter.value);
    localStorage.setItem(STORAGE_KEYS.sortVisits, els.sortVisits.value);
}

function getInitials(name = '') {
    const parts = name.trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return '?';
    return ((parts[0]?.[0] || '') + (parts.at(-1)?.[0] || '')).toUpperCase();
}

function getColorForName(name = '') {
    let hash = 0;
    for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
    return avatarColors[Math.abs(hash) % avatarColors.length];
}

function setLastRefreshTime() {
    els.lastRefreshTime.textContent = new Date().toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit'
    });
}

function updateSelectedDateLabel() {
    els.selectedDateLabel.textContent = formatPrettyDate(selectedDate);
}

function getClientDpUrl(path = '') {
    if (!path) return '';
    if (/^https?:\/\//i.test(path)) return path;
    return CLIENT_DP_BASE_URL + path.replace(/^\/+/, '');
}

function normalizeClientDp(path = '') {
    if (!path) return '';
    if (/^https?:\/\//i.test(path)) return path;

    const cleaned = String(path).trim().replace(/^\/+/, '');

    if (cleaned.startsWith('uploads/client_dp/')) {
        return cleaned;
    }

    return 'uploads/client_dp/' + cleaned.replace(/^client_dp\/+/i, '');
}

function renderAvatarFallback(container, clientName = '') {
    const initials = getInitials(clientName);
    const color = getColorForName(clientName);
    container.innerHTML = `
        <div class="avatar-initials" style="background:${color};">
            ${escapeHtml(initials)}
        </div>
    `;
}

function renderClientAvatar(container, clientName = '', clientDp = '') {
    const normalizedPath = normalizeClientDp(clientDp);
    const imageUrl = getClientDpUrl(normalizedPath);

    if (!imageUrl) {
        renderAvatarFallback(container, clientName);
        return;
    }

    container.innerHTML = `
        <img
            src="${escapeHtml(imageUrl)}"
            alt="${escapeHtml(clientName)}"
            class="avatar-image"
            loading="lazy"
            decoding="async"
            fetchpriority="low"
            referrerpolicy="no-referrer"
        >
    `;

    const img = container.querySelector('img');
    img.onerror = () => renderAvatarFallback(container, clientName);
}

function getClientKey(value) {
    return String(value ?? '').trim();
}

function getVisitClientKeys(vis) {
    return [
        vis?.uryyToeSS4,
        vis?.client_id,
        vis?.client_Id,
        vis?.col_client_Id,
        vis?.id
    ].map(getClientKey).filter(Boolean);
}

function attachClientDpToVisits() {
    if (!Object.keys(clientDpCache).length || !visitsCache.length) return;

    let changed = false;

    visitsCache = visitsCache.map(vis => {
        if (vis.client_dp) return vis;

        let clientDp = '';
        const possibleKeys = getVisitClientKeys(vis);

        for (const key of possibleKeys) {
            if (clientDpCache[key]) {
                clientDp = clientDpCache[key];
                break;
            }
        }

        if (!clientDp) return vis;
        changed = true;

        return {
            ...vis,
            client_dp: clientDp
        };
    });

    if (changed) queueAvatarRefresh();
}

function queueAvatarRefresh() {
    if (avatarRefreshQueued) return;
    avatarRefreshQueued = true;

    requestAnimationFrame(() => {
        avatarRefreshQueued = false;
        renderFilteredVisits();
    });
}

function openDB() {
    if (dbPromise) return dbPromise;

    dbPromise = new Promise((resolve, reject) => {
        const req = indexedDB.open('stafflinks');
        req.onsuccess = e => resolve(e.target.result);
        req.onerror = e => reject(e.target.error);
    });

    return dbPromise;
}

async function safeReadStore(storeName, mode = 'readonly', reader) {
    try {
        const db = await openDB();
        const tx = db.transaction(storeName, mode);
        const store = tx.objectStore(storeName);
        return await reader(store);
    } catch (error) {
        console.error(`Error reading store ${storeName}:`, error);
        return [];
    }
}

function getAllFast(store) {
    return new Promise((resolve, reject) => {
        if (typeof store.getAll === 'function') {
            const req = store.getAll();
            req.onsuccess = e => resolve(e.target.result || []);
            req.onerror = e => reject(e.target.error);
            return;
        }

        const arr = [];
        const req = store.openCursor();
        req.onsuccess = e => {
            const cursor = e.target.result;
            if (cursor) {
                arr.push(cursor.value);
                cursor.continue();
            } else {
                resolve(arr);
            }
        };
        req.onerror = e => reject(e.target.error);
    });
}

function buildCancelledCallsSet(records) {
    cancelledCallsSet = new Set(
        records.map(c => `${c.clientId}__${c.date}__${c.careCall}`)
    );
}

function buildClientStatusMap(records) {
    const map = new Map();

    for (const r of records) {
        if (!r.clientId || !r.start) continue;
        if (!map.has(r.clientId)) map.set(r.clientId, []);
        map.get(r.clientId).push({
            start: r.start,
            end: r.end
        });
    }

    clientStatusMap = map;
}

function isCancelledVisit(v) {
    const visitClientId = getClientKey(v.uryyToeSS4 || v.client_id || v.col_client_Id);
    return cancelledCallsSet.has(`${visitClientId}__${v.Clientshift_Date}__${v.care_calls}`);
}

function hasBlockedStatus(v) {
    const visitClientId = getClientKey(v.uryyToeSS4 || v.client_id || v.col_client_Id);
    const ranges = clientStatusMap.get(visitClientId);
    if (!ranges?.length) return false;

    for (const r of ranges) {
        if (
            (v.Clientshift_Date >= r.start && v.Clientshift_Date <= r.end) ||
            (v.Clientshift_Date === r.start && r.end === 'TFN')
        ) {
            return true;
        }
    }

    return false;
}

async function loadUserSpecialId() {
    const result = await safeReadStore('tbl_team_account', 'readonly', async store => {
        const users = await getAllFast(store);
        return users.length ? users[0].user_special_Id : null;
    });

    userSpecialId = result;
    return result;
}

async function loadCancelledCalls() {
    cancelledCallsCache = await safeReadStore('tbl_cancelled_call', 'readonly', async store => {
        const rows = await getAllFast(store);
        return rows.map(c => ({
            clientId: getClientKey(c.col_client_Id),
            date: c.col_date,
            careCall: c.col_care_call
        }));
    });

    buildCancelledCallsSet(cancelledCallsCache);
    return cancelledCallsCache;
}

async function loadClientStatusRecords() {
    clientStatusCache = await safeReadStore('tbl_client_status_records', 'readonly', async store => {
        const rows = await getAllFast(store);
        return rows.map(r => ({
            clientId: getClientKey(r.col_client_Id),
            start: r.col_start_date,
            end: r.col_end_date
        }));
    });

    buildClientStatusMap(clientStatusCache);
    return clientStatusCache;
}

async function loadClientDpRecords() {
    clientDpCache = await safeReadStore('tbl_general_client_form', 'readonly', async store => {
        const rows = await getAllFast(store);
        const map = {};

        for (const row of rows) {
            const possibleKeys = [
                row.col_client_Id,
                row.client_id,
                row.client_Id,
                row.client_special_id,
                row.col_client_special_id,
                row.uryyToeSS4,
                row.id
            ].map(getClientKey).filter(Boolean);

            const clientDp = row.client_dp || row.client_dp_url || row.profile_image || '';
            if (!clientDp || !possibleKeys.length) continue;

            for (const key of possibleKeys) {
                map[key] = clientDp;
            }
        }

        return map;
    });

    attachClientDpToVisits();
    return clientDpCache;
}

async function loadVisits() {
    if (!userSpecialId) {
        visitsCache = [];
        return [];
    }

    visitsCache = await safeReadStore('tbl_schedule_calls', 'readonly', async store => {
        const rows = await getAllFast(store);
        return rows.filter(v => v.first_carer_Id === userSpecialId);
    });

    attachClientDpToVisits();
    return visitsCache;
}

function getFilteredVisits(dateStr) {
    return visitsCache.filter(v => {
        if (v.Clientshift_Date !== dateStr) return false;
        if (isCancelledVisit(v)) return false;
        if (hasBlockedStatus(v)) return false;
        return true;
    });
}

function applyClientFilters(visits) {
    const search = els.searchVisits.value.trim().toLowerCase();
    const status = els.statusFilter.value;
    const sort = els.sortVisits.value;

    let filtered = visits;

    if (search) {
        filtered = filtered.filter(v => (v.client_name || '').toLowerCase().includes(search));
    }

    if (status !== 'all') {
        filtered = filtered.filter(v => (v.call_status || 'scheduled').toLowerCase() === status);
    }

    filtered = [...filtered].sort((a, b) => {
        switch (sort) {
            case 'time-desc':
                return (b.dateTime_in || '').localeCompare(a.dateTime_in || '');
            case 'name-asc':
                return (a.client_name || '').localeCompare(b.client_name || '');
            case 'name-desc':
                return (b.client_name || '').localeCompare(a.client_name || '');
            case 'time-asc':
            default:
                return (a.dateTime_in || '').localeCompare(b.dateTime_in || '');
        }
    });

    return filtered;
}

function getStatusClass(status) {
    switch ((status || '').toLowerCase()) {
        case 'scheduled':
            return 'bg-info text-dark';
        case 'in-progress':
            return 'bg-warning text-dark';
        case 'completed':
            return 'bg-success text-white';
        case 'cancelled':
            return 'bg-danger text-white';
        default:
            return 'bg-secondary text-white';
    }
}

function getCalendarNow(dateStr) {
    // Keeps cards/alerts smart:
    // - today => real current time
    // - past date => end of that selected day
    // - future date => start of that selected day
    if (isToday(dateStr)) return new Date();

    const base = parseISODateLocal(dateStr) || new Date();
    const today = parseISODateLocal(getTodayISO()) || new Date();

    if (base < today) {
        return new Date(base.getFullYear(), base.getMonth(), base.getDate(), 23, 59, 59, 999);
    }

    return new Date(base.getFullYear(), base.getMonth(), base.getDate(), 0, 0, 0, 0);
}

function renderVisitsFiltered(visits) {
    els.visitsContainer.innerHTML = '';

    if (!visits.length) {
        els.visitsContainer.innerHTML = `
            <div class="empty-state">
                <div class="mb-2"><i class="bi bi-calendar-x fs-1"></i></div>
                <div class="fw-semibold">No visits found</div>
                <div class="small-muted">Try a different date or adjust your filters.</div>
            </div>
        `;
        els.totalHours.textContent = '0h 0m';
        els.runName.textContent = 'N/A';
        updateQuickStats([]);
        updateProgress([]);
        renderNextVisit([]);
        return;
    }

    const tpl = document.getElementById('visitTpl');
    const frag = document.createDocumentFragment();
    let totalMinutes = 0;
    const now = getCalendarNow(selectedDate);

    visits.forEach((vis, index) => {
        const node = document.importNode(tpl.content, true);
        const card = node.querySelector('.card');
        const avatar = node.querySelector('.avatar');
        const name = node.querySelector('.name');
        const service = node.querySelector('.service');
        const times = node.querySelector('.times');
        const statusBadge = node.querySelector('.status');
        const carers = node.querySelector('.carers-icons');
        const visitDate = node.querySelector('.visit-date');
        const visitDuration = node.querySelector('.visit-duration');

        const clientName = vis.client_name || 'Unknown Client';
        const start = parseDateTimeLocal(vis.Clientshift_Date, vis.dateTime_in);
        const end = parseDateTimeLocal(vis.Clientshift_Date, vis.dateTime_out);
        const durationMinutes = minutesBetween(start, end);
        const status = (vis.call_status || 'scheduled').toLowerCase();

        totalMinutes += durationMinutes;

        card.style.cursor = 'pointer';
        card.setAttribute('role', 'button');
        card.setAttribute('aria-label', `Open care plan for ${clientName}`);

        const openCarePlan = () => {
            const url = `care-plan?id=${encodeURIComponent(vis.id)}&clientId=${encodeURIComponent(vis.uryyToeSS4)}&care_calls=${encodeURIComponent(vis.care_calls || '')}&Clientshift_Date=${encodeURIComponent(vis.Clientshift_Date || '')}`;
            location.href = url;
        };

        card.addEventListener('click', openCarePlan);
        card.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openCarePlan();
            }
        });

        renderClientAvatar(avatar, clientName, vis.client_dp);

        const img = avatar.querySelector('img');
        if (img && index < 4) {
            img.loading = 'eager';
            img.fetchPriority = 'high';
        }

        name.textContent = clientName;
        service.textContent = vis.care_calls || 'Care visit';
        times.textContent = `${vis.dateTime_in || '--:--'} - ${vis.dateTime_out || '--:--'}`;
        visitDate.textContent = formatPrettyDate(vis.Clientshift_Date);
        visitDuration.textContent = formatDuration(durationMinutes);

        carers.innerHTML = '';
        const carersRequired = Number(vis.col_required_carers || 1);
        if (carersRequired === 2) {
            carers.innerHTML = '<span title="2 carers required">👥</span>';
        } else if (carersRequired > 2) {
            carers.innerHTML = `<span title="${carersRequired} carers required">${'👤'.repeat(Math.min(carersRequired, 4))}</span>`;
        } else {
            carers.innerHTML = '<span title="1 carer required">👤</span>';
        }

        statusBadge.textContent = status;
        statusBadge.className = `badge badge-status ${getStatusClass(status)}`;

        if (isToday(selectedDate) && start > now && start - now <= 3600000) {
            card.classList.add('visit-upcoming');
        }

        if (isToday(selectedDate) && start < now && status !== 'completed') {
            card.classList.add('visit-overdue');
        }

        if (vis.col_required_carers == 2 && vis.second_carer_name) {
            const secondCarerBtn = document.createElement('button');
            secondCarerBtn.className = 'btn btn-sm btn-outline-primary ms-2';
            secondCarerBtn.innerHTML = '<i class="bi bi-people"></i>';
            secondCarerBtn.title = 'View second carer';
            secondCarerBtn.addEventListener('click', e => {
                e.stopPropagation();
                document.getElementById('modalRunName').textContent = vis.col_run_name || 'N/A';
                document.getElementById('modalCarerName').textContent = vis.second_carer_name || 'N/A';
                new bootstrap.Modal(document.getElementById('secondCarerModal')).show();
            });
            carers.appendChild(secondCarerBtn);
        }

        frag.appendChild(node);
    });

    els.visitsContainer.appendChild(frag);
    els.totalHours.textContent = formatDuration(totalMinutes);
    els.runName.textContent = visits[0]?.col_run_name || 'N/A';

    updateQuickStats(visits);
    updateProgress(visits);
    renderNextVisit(visits);
}

function renderTimelineAndAlerts(visits) {
    els.alertsContainer.innerHTML = '';

    // Alerts only make sense for the real current date
    if (!isToday(selectedDate)) {
        els.alertsContainer.innerHTML = '<div class="small-muted">Alerts are shown for today only.</div>';
        return;
    }

    const now = new Date();
    const alerts = [];

    visits.forEach(v => {
        const start = parseDateTimeLocal(v.Clientshift_Date, v.dateTime_in);
        const end = parseDateTimeLocal(v.Clientshift_Date, v.dateTime_out);
        const status = (v.call_status || '').toLowerCase();

        if (start > now && start - now <= 3600000) {
            alerts.push({
                type: 'info',
                text: `Upcoming: ${v.client_name} at ${v.dateTime_in}`
            });
        } else if (end < now && status !== 'completed' && status !== 'cancelled') {
            alerts.push({
                type: 'danger',
                text: `Overdue: ${v.client_name} (${v.dateTime_in} - ${v.dateTime_out})`
            });
        }
    });

    if (!alerts.length) {
        els.alertsContainer.innerHTML = '<div class="small-muted">No alerts for this date.</div>';
        return;
    }

    const frag = document.createDocumentFragment();

    alerts.forEach(a => {
        const div = document.createElement('div');
        div.className = `alert-item ${a.type === 'danger' ? 'text-danger' : 'text-info'}`;
        div.textContent = a.text;
        frag.appendChild(div);
    });

    els.alertsContainer.appendChild(frag);
}

function renderNextVisit(visits) {
    const now = getCalendarNow(selectedDate);

    const upcoming = visits
        .map(v => ({
            ...v,
            start: parseDateTimeLocal(v.Clientshift_Date, v.dateTime_in)
        }))
        .filter(v => v.start >= now && (v.call_status || '').toLowerCase() !== 'completed')
        .sort((a, b) => a.start - b.start)[0];

    if (!upcoming) {
        els.nextVisitCard.innerHTML = '<span class="small-muted">No upcoming visit for this date.</span>';
        return;
    }

    els.nextVisitCard.innerHTML = `
        <div class="fw-semibold">${escapeHtml(upcoming.client_name || 'Unknown Client')}</div>
        <div class="small-muted">${escapeHtml(upcoming.care_calls || 'Care visit')}</div>
        <div class="mt-1"><strong>${escapeHtml(upcoming.dateTime_in || '--:--')}</strong></div>
    `;
}

function updateQuickStats(visits) {
    const completed = visits.filter(v => (v.call_status || '').toLowerCase() === 'completed').length;
    const pending = visits.length - completed;

    els.countCalls.textContent = visits.length;
    els.completedCalls.textContent = completed;
    els.pendingCalls.textContent = pending;
}

function updateProgress(visits) {
    if (!visits.length) {
        els.progressBar.style.width = '0%';
        els.progressText.textContent = '0%';
        return;
    }

    const done = visits.filter(v => (v.call_status || '').toLowerCase() === 'completed').length;
    const percentage = Math.round((done / visits.length) * 100);

    els.progressBar.style.width = percentage + '%';
    els.progressText.textContent = percentage + '%';
}

function scrollToActiveDate() {
    const active = document.querySelector('.date-pill.active');
    if (active) {
        active.scrollIntoView({
            behavior: 'smooth',
            inline: 'center',
            block: 'nearest'
        });
    }
}

function setSelectedDate(dateInput, options = {}) {
    const dateObj = typeof dateInput === 'string'
        ? parseISODateLocal(dateInput)
        : new Date(dateInput);

    if (!dateObj || Number.isNaN(dateObj.getTime())) return;

    selectedDate = formatLocalISO(dateObj);

    if (!options.keepCalendarMonth) {
        calendarCursor = new Date(dateObj.getFullYear(), dateObj.getMonth(), 1);
    }

    persistState();
    renderDatePills(calendarCursor);
    renderFilteredVisits();
}

function renderDatePills(centerDate = calendarCursor) {
    els.dateStrip.innerHTML = '';

    calendarCursor = new Date(centerDate.getFullYear(), centerDate.getMonth(), 1);

    const year = calendarCursor.getFullYear();
    const month = calendarCursor.getMonth();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const visitDates = new Set(visitsCache.map(v => v.Clientshift_Date));
    const todayIso = getTodayISO();
    const frag = document.createDocumentFragment();

    for (let i = 1; i <= daysInMonth; i++) {
        const d = new Date(year, month, i);
        const iso = formatLocalISO(d);
        const pill = document.createElement('button');

        pill.type = 'button';
        pill.className = 'date-pill';
        pill.dataset.date = iso;

        pill.innerHTML = `
            <div style="font-weight:600">${d.toLocaleDateString(undefined, { weekday: 'short' })}</div>
            <div style="font-size:.85rem;position:relative">
                ${d.getDate()} ${d.toLocaleString(undefined, { month: 'short' })}
            </div>
        `;

        if (visitDates.has(iso)) {
            const dot = document.createElement('div');
            dot.style.cssText = 'width:6px;height:6px;background:#bdc3c7;border-radius:50%;position:absolute;bottom:-5px;left:50%;transform:translateX(-50%)';
            pill.querySelector('div:nth-child(2)').appendChild(dot);
        }

        if (iso === todayIso) {
            pill.classList.add('today');
        }

        if (iso === selectedDate) {
            pill.classList.add('active');
            pill.setAttribute('aria-current', 'date');
        }

        pill.addEventListener('click', () => {
            setSelectedDate(iso, { keepCalendarMonth: true });
            setTimeout(scrollToActiveDate, 80);
        });

        frag.appendChild(pill);
    }

    els.dateStrip.appendChild(frag);
    setTimeout(scrollToActiveDate, 80);
}

function renderFilteredVisits() {
    persistState();
    updateSelectedDateLabel();

    const raw = getFilteredVisits(selectedDate);
    const filtered = applyClientFilters(raw);

    renderVisitsFiltered(filtered);
    renderTimelineAndAlerts(filtered);
}

function showErrorState(message = 'Unable to load visits.') {
    els.visitsContainer.innerHTML = `
        <div class="error-state">
            <div class="mb-2 text-danger"><i class="bi bi-exclamation-triangle fs-1"></i></div>
            <div class="fw-semibold">Something went wrong</div>
            <div class="small-muted">${escapeHtml(message)}</div>
        </div>
    `;
}

function updateClock() {
    els.todayClock.textContent = new Date().toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit'
    });
}

function applySavedTheme() {
    const darkMode = localStorage.getItem(STORAGE_KEYS.darkMode) === '1';
    document.body.classList.toggle('dark-mode', darkMode);
}

async function loadCoreData() {
    await loadUserSpecialId();

    await Promise.all([
        loadCancelledCalls(),
        loadClientStatusRecords(),
        loadVisits()
    ]);
}

async function loadEnhancementData() {
    try {
        await loadClientDpRecords();
    } catch (error) {
        console.error('Client DP load failed:', error);
    }
}

document.getElementById('themeBtn').addEventListener('click', () => {
    const enabled = !document.body.classList.contains('dark-mode');
    document.body.classList.toggle('dark-mode', enabled);
    localStorage.setItem(STORAGE_KEYS.darkMode, enabled ? '1' : '0');
});

setInterval(updateClock, 1000);
updateClock();
applySavedTheme();

document.getElementById('prevDay').onclick = () => {
    const d = parseISODateLocal(selectedDate) || new Date();
    d.setDate(d.getDate() - 1);
    setSelectedDate(d);
};

document.getElementById('nextDay').onclick = () => {
    const d = parseISODateLocal(selectedDate) || new Date();
    d.setDate(d.getDate() + 1);
    setSelectedDate(d);
};

document.getElementById('todayBtn').onclick = () => {
    setSelectedDate(new Date());
};

document.getElementById('refreshBtn').onclick = async () => {
    try {
        await loadCoreData();

        // After refresh, keep the real calendar behavior.
        if (!selectedDate) {
            selectedDate = getTodayISO();
        }

        calendarCursor = parseISODateLocal(selectedDate) || new Date();

        renderDatePills(calendarCursor);
        renderFilteredVisits();
        setLastRefreshTime();

        loadEnhancementData();
    } catch (e) {
        console.error(e);
        showErrorState('Refresh failed. Please try again.');
    }
};

els.searchVisits.addEventListener('input', debounce(() => {
    renderFilteredVisits();
}, 220));

els.statusFilter.addEventListener('change', renderFilteredVisits);
els.sortVisits.addEventListener('change', renderFilteredVisits);

els.clearFilters.addEventListener('click', () => {
    els.searchVisits.value = '';
    els.statusFilter.value = 'all';
    els.sortVisits.value = 'time-asc';
    renderFilteredVisits();
});

window.addEventListener('offline', () => {
    els.offlineStatus.style.display = 'inline-block';
    els.connStatus.classList.remove('bg-success');
    els.connStatus.classList.add('bg-secondary');
    els.connStatus.textContent = 'Offline mode';
});

window.addEventListener('online', () => {
    els.offlineStatus.style.display = 'none';
    els.connStatus.classList.remove('bg-secondary');
    els.connStatus.classList.add('bg-success');
    els.connStatus.textContent = 'Online';
    localStorage.removeItem('offlineQueue');
});

const menuBtn = document.getElementById('menuBtn');
const sideNav = document.getElementById('sideNav');
const overlay = document.getElementById('overlay');

menuBtn.onclick = () => {
    sideNav.classList.add('open');
    overlay.classList.add('show');
};

overlay.onclick = () => {
    sideNav.classList.remove('open');
    overlay.classList.remove('show');
};

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        sideNav.classList.remove('open');
        overlay.classList.remove('show');
    }
});

(async () => {
    try {
        // Always open on the current date
        selectedDate = getTodayISO();
        calendarCursor = parseISODateLocal(selectedDate) || new Date();

        await loadCoreData();

        renderDatePills(calendarCursor);
        renderFilteredVisits();
        setLastRefreshTime();
        updateSelectedDateLabel();

        loadEnhancementData();
    } catch (error) {
        console.error('Initialization error:', error);
        showErrorState('Failed to initialize local visit data.');
    }
})();