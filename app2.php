<?php include_once 'header1.php'; ?>
<style>
    :root {
        --accent: #EB6F46;
        --accent2: #dc3545;
        --card-bg: #ffffff;
        --muted: #6c757d;
        --bg1: #f7faff;
        --bg2: #eef5ff;
    }

    body {
        background: linear-gradient(180deg, var(--bg1) 0%, var(--bg2) 100%);
        font-family: Inter, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
        color: #333;
        font-size: 18px;
        overflow-x: hidden;
        padding-bottom: 80px;
        overscroll-behavior: none;
        /* footer space */
    }

    html {
        overscroll-behavior: none;
        /* footer space */
    }

    body.dark-mode {
        background: #1e1e2f;
        color: #ccc;
    }

    body.dark-mode .card {
        background: #2a2a3d;
        color: #ccc;
    }

    body.dark-mode .topbar {
        background: #222;
    }

    body.dark-mode .date-pill.active {
        background: #50C9C3;
        color: #fff;
    }

    body.dark-mode .footer {
        background: #222;
        color: #ccc;
    }

    .date-strip {
        overflow-x: auto;
        white-space: nowrap;
        padding: 0.5rem 0;
        border-radius: 12px;
    }

    .date-pill {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-width: 88px;
        padding: 10px;
        border-radius: 10px;
        margin: 6px;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all .2s ease;
        color: #333;
        background: transparent;
    }

    .date-pill:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 18px rgba(43, 140, 255, .08);
    }

    .date-pill.active {
        background: var(--accent);
        color: #fff;
        box-shadow: 0 10px 30px rgba(43, 140, 255, .14);
    }

    .visits-list .card {
        border-radius: 16px;
        border: 0;
        box-shadow: 0 6px 20px rgba(30, 40, 60, .06);
        overflow: hidden;
        background: #fff;
        transition: transform .2s;
    }

    .visits-list .card:hover {
        transform: translateY(-3px);
    }

    .visit-row {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .avatar {
        flex: 0 0 80px;
        height: 80px;
        border-radius: 12px;
        overflow: hidden;
        background: #eee;
    }

    .avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .visit-details {
        flex: 1;
    }

    .small-muted {
        color: var(--muted);
        font-size: .9rem;
    }

    .badge-status {
        font-weight: 600;
        border-radius: 8px;
        padding: .25rem .5rem;
        cursor: pointer;
    }

    @media (max-width:576px) {
        .avatar {
            flex: 0 0 64px;
            height: 64px
        }

        .date-pill {
            min-width: 72px;
            padding: 8px
        }
    }

    .fade-in-up {
        animation: fadeUp .6s ease both;
    }

    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(8px);
        }

        to {
            opacity: 1;
            transform: none;
        }
    }

    .topbar {
        position: sticky;
        top: 0;
        z-index: 20;
        background: linear-gradient(90deg, var(--accent), var(--accent2));
        padding: 12px 0;
        border-bottom-right-radius: 12px;
        border-bottom-left-radius: 12px;
        color: white;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .carers-icons span {
        font-size: 1rem;
        margin-left: 2px;
    }

    .footer {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 70px;
        background: #fff;
        display: flex;
        justify-content: space-around;
        align-items: center;
        box-shadow: 0 -3px 10px rgba(0, 0, 0, 0.1);
        z-index: 30;
        border-top: 1px solid #eee;
    }

    .footer button,
    .footer a {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: rgba(72, 126, 176, 1.0);
        font-size: 1.2rem;
        color: #fff;
        transition: background .2s, color .2s;
        box-shadow: rgba(50, 50, 93, 0.25) 0px 50px 100px -20px, rgba(0, 0, 0, 0.3) 0px 30px 60px -30px;
    }

    .footer button,
    .footer a:hover {
        background: #4A90E2;
        color: white;
        box-shadow: rgba(50, 50, 93, 0.25) 0px 50px 100px -20px, rgba(0, 0, 0, 0.3) 0px 30px 60px -30px;
    }

    #sideNav {
        position: fixed;
        top: 0;
        left: -280px;
        width: 280px;
        height: 100%;
        background: #fff;
        box-shadow: 2px 0 12px rgba(0, 0, 0, .2);
        z-index: 50;
        transition: left .3s ease;
        padding: 1.5rem;
    }

    #sideNav.open {
        left: 0;
    }

    #sideNav h5 {
        color: var(--accent);
        margin-bottom: 1rem;
        font-weight: 600;
    }

    #sideNav .user-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 2rem;
        text-align: center;
    }

    #sideNav .user-info img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        margin-bottom: 0.5rem;
    }

    #sideNav .user-info .name {
        font-weight: 600;
        margin-bottom: 0;
    }

    #sideNav .user-info .email,
    .phone {
        font-size: .85rem;
        color: var(--muted);
        margin-bottom: 0.25rem;
    }

    #sideNav .logout-btn {
        margin-top: 0.5rem;
        width: 100%;
    }

    #sideNav ul li a {
        text-decoration: none;
        display: block;
        padding: 10px 0;
        color: #333;
        font-weight: 500;
        transition: color .2s;
    }

    #sideNav ul li a:hover {
        color: var(--accent2);
    }

    #overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, .4);
        z-index: 40;
        display: none;
    }

    #overlay.show {
        display: block;
    }

    .menu-btn {
        font-size: 1.4rem;
        cursor: pointer;
        background: none;
        border: none;
        color: white;
    }


    .connector-line {
        position: absolute;
        left: 50px;
        /* Adjust to align with avatar or desired position */
        width: 2px;
        background-color: var(--accent2, #dc3545);
        z-index: 0;
    }

    /* Label with car icon and miles */
    .connector-label {
        position: absolute;
        left: 60px;
        background-color: #fff;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.85rem;
        font-weight: 500;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
        z-index: 1;
    }

    .connector-label i {
        font-size: 1rem;
        color: var(--accent2, #dc3545);
    }


    .timeline-container {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .timeline-item {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem;
        border-radius: 0.5rem;
        border-left: 4px solid;
        background-color: var(--bs-light);
        transition: background-color 0.2s;
    }

    .timeline-item.upcoming {
        border-color: var(--accent2);
    }

    .timeline-item.in-progress {
        border-color: #ffc107;
    }

    .timeline-item.completed {
        border-color: #28a745;
        background-color: #e6f4ea;
    }

    .alerts-container {
        margin-top: 0.5rem;
        font-size: 0.85rem;
    }

    .alert-item {
        margin-bottom: 0.25rem;
    }
</style>

<div id="overlay"></div>

<div class="topbar mb-3 p-2">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <button class="menu-btn fs-1" id="menuBtn"><i class="bi bi-list"></i></button>
        <h4 class="mb-0">StaffLinks</h4>
        <div class="d-flex align-items-center gap-2">
            <div class="chip"><span id="today-clock">--:--</span></div>
            <button class="btn btn-sm btn-light" id="refreshBtn" title="Refresh"><i class="bi bi-arrow-clockwise"></i></button>
            <button class="btn btn-sm btn-light" id="todayBtn" title="Today"><i class="bi bi-calendar-check"></i></button>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-light" id="prevDay">‹</button>
            <button class="btn btn-sm btn-light" id="nextDay">›</button>
        </div>
        <div class="date-strip w-100" id="dateStrip" aria-label="pick a date"></div>
        <div class="text-end">
            <div class="small-muted">Hours</div>
            <div class="hour-total" id="totalHours">0h 0m</div>
        </div>
    </div>
    <div class="mt-2 mb-2">
        <div class="small-muted">Completion Progress</div>
        <div class="progress" style="height:8px;">
            <div class="progress-bar" role="progressbar" id="progressBar" style="width:0%;"></div>
        </div>
    </div>
</div>

<div class="container py-3">
    <div class="row mb-3">
        <div class="col-12 col-lg-8">
            <input type="text" class="form-control mb-2" id="searchVisits" placeholder="Search visits by client name">
            <div class="card p-3 visits-list" id="visitsContainer"></div>
        </div>
        <div class="col-12 col-lg-4 mt-3 mt-lg-0">
            <div class="card p-3 fade-in-up">
                <h6>Quick stats</h6>
                <ul class="list-unstyled small-muted mb-0">
                    <li>Care Calls: <strong id="countCalls">0</strong></li>
                    <li>Connected: <span id="connStatus" class="badge bg-success">Online</span></li>
                    <li id="offlineStatus" style="display:none; color:red;">Offline</li>
                    <li>Run name: <strong id="runName">N/A</strong></li>
                </ul>
            </div>
            <div class="card p-3 mt-3">
                <h6>Alerts</h6>
                <div id="alertsContainer" class="alerts-container small-muted"></div>
            </div>
        </div>
    </div>
</div>

<template id="visitTpl">
    <div class="card mb-3 visit-item fade-in-up">
        <div class="card-body p-3">
            <div class="visit-row">
                <div class="avatar"><img src="" alt="user"></div>
                <div class="visit-details">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="h6 mb-0 name"></div>
                            <div class="small-muted service"></div>
                        </div>
                        <div class="text-end carers-icons"></div>
                    </div>
                    <div class="mt-2 d-flex justify-content-between align-items-center">
                        <div class="small-muted times">09:00 - 10:00</div>
                        <div><span class="badge badge-status status">Scheduled</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<div class="footer">
    <button onclick="history.back()" title="Back" id="btn-back"><i class="bi bi-arrow-left"></i></button>
    <a href="./home" title="Home"><i class="bi bi-house"></i></a>
    <a href="./visit-logs" title="Log"><i class="bi bi-journal-text"></i></a>
    <a href="./settings" title="User"><i class="bi bi-person"></i></a>
</div>

<!-- Second Carer Modal -->
<div class="modal fade" id="secondCarerModal" tabindex="-1" aria-labelledby="secondCarerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 shadow-sm">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="secondCarerModalLabel">Second Carer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <p class="fw-bold mb-1">Run Name: <span id="modalRunName" class="text-primary"></span></p>
                <p class="mb-0">Second Carer: <span id="modalCarerName" class="fw-semibold"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="./js/jquery-3.7.0.min.js"></script>
<script src="./js/sync_visits.js?v=<?php echo time(); ?>"></script>
<!--<script src="./js/home.js?v=<?php echo time(); ?>"></script>-->
<script>
    AOS.init();

    // --- Local date helpers ---
    function formatLocalISO(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }

    function parseDateTimeLocal(dateStr, timeStr) {
        const [y, m, d] = dateStr.split('-').map(Number);
        const [hh = 0, mm = 0] = (timeStr || '').split(':').map(Number);
        return new Date(y, m - 1, d, hh, mm, 0);
    }

    // --- Initial setup ---
    let selectedDate = formatLocalISO(new Date());
    const dateStrip = document.getElementById('dateStrip');
    const visitsContainer = document.getElementById('visitsContainer');
    const avatarColors = ['#f44336', '#e91e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3', '#03a9f4', '#00bcd4', '#009688', '#4caf50', '#8bc34a', '#cddc39', '#ffeb3b', '#ffc107', '#ff9800', '#ff5722', '#795548', '#607d8b'];

    function getInitials(name) {
        const parts = name.split(' ');
        return (parts[0]?.[0] || '').toUpperCase() + (parts.at(-1)?.[0] || '').toUpperCase();
    }

    function getColorForName(name) {
        let hash = 0;
        for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
        return avatarColors[Math.abs(hash) % avatarColors.length];
    }

    // --- IndexedDB helper ---
    function openDB() {
        return new Promise((res, rej) => {
            const req = indexedDB.open('stafflinks');
            req.onsuccess = e => res(e.target.result);
            req.onerror = e => rej(e.target.error);
        });
    }

    // --- Cached data ---
    let userSpecialId = null;
    let visitsCache = [];
    let cancelledCallsCache = [];
    let clientStatusCache = [];

    // --- Load userSpecialId ---
    async function loadUserSpecialId() {
        const db = await openDB();
        const tx = db.transaction('tbl_team_account', 'readonly');
        const store = tx.objectStore('tbl_team_account');
        const req = store.getAll();
        return new Promise((res, rej) => {
            req.onsuccess = e => {
                const users = e.target.result;
                userSpecialId = users.length ? users[0].user_special_Id : null;
                res(userSpecialId);
            };
            req.onerror = e => rej(e.target.error);
        });
    }

    // --- Load cancelled calls ---
    async function loadCancelledCalls() {
        const db = await openDB();
        return new Promise((res, rej) => {
            const tx = db.transaction('tbl_cancelled_call', 'readonly');
            const store = tx.objectStore('tbl_cancelled_call');
            const arr = [];
            const req = store.openCursor();
            req.onsuccess = e => {
                const cursor = e.target.result;
                if (cursor) {
                    const c = cursor.value;
                    arr.push({
                        clientId: c.col_client_Id,
                        date: c.col_date,
                        careCall: c.col_care_call
                    });
                    cursor.continue();
                } else {
                    cancelledCallsCache = arr;
                    res(arr);
                }
            };
            req.onerror = e => rej(e.target.error);
        });
    }

    // --- Load client status records ---
    async function loadClientStatusRecords() {
        const db = await openDB();
        return new Promise((res, rej) => {
            const tx = db.transaction('tbl_client_status_records', 'readonly');
            const store = tx.objectStore('tbl_client_status_records');
            const arr = [];
            const req = store.openCursor();
            req.onsuccess = e => {
                const cursor = e.target.result;
                if (cursor) {
                    const r = cursor.value;
                    arr.push({
                        clientId: r.col_client_Id,
                        start: r.col_start_date,
                        end: r.col_end_date
                    });
                    cursor.continue();
                } else {
                    clientStatusCache = arr;
                    res(arr);
                }
            };
            req.onerror = e => rej(e.target.error);
        });
    }

    // --- Load visits ---
    async function loadVisits() {
        if (!userSpecialId) return;
        const db = await openDB();
        return new Promise((res, rej) => {
            const tx = db.transaction('tbl_schedule_calls', 'readonly');
            const store = tx.objectStore('tbl_schedule_calls');
            const arr = [];
            const req = store.openCursor();
            req.onsuccess = e => {
                const cursor = e.target.result;
                if (cursor) {
                    const v = cursor.value;
                    if (v.first_carer_Id === userSpecialId) arr.push(v);
                    cursor.continue();
                } else {
                    visitsCache = arr;
                    res(arr);
                }
            };
            req.onerror = e => rej(e.target.error);
        });
    }

    // --- Filter visits by date, cancelled and client status ---
    function getFilteredVisits(dateStr) {
        return visitsCache.filter(v => {
            const isCancelled = cancelledCallsCache.some(c =>
                c.clientId === v.uryyToeSS4 && c.date === v.Clientshift_Date && c.careCall === v.care_calls
            );
            const hasStatus = clientStatusCache.some(r =>
                r.clientId === v.uryyToeSS4 &&
                ((v.Clientshift_Date >= r.start && v.Clientshift_Date <= r.end) || (v.Clientshift_Date === r.start && r.end === 'TFN'))
            );
            return !isCancelled && !hasStatus && v.Clientshift_Date === dateStr;
        }).sort((a, b) => (a.dateTime_in || '').localeCompare(b.dateTime_in || ''));
    }

    // --- Render visits ---
    function renderVisitsFiltered(v) {
        visitsContainer.innerHTML = '';
        if (!v.length) {
            visitsContainer.innerHTML = '<div class="text-center small-muted p-5">No visits found</div>';
            document.getElementById('totalHours').textContent = '0h 0m';
            updateQuickStats([]);
            updateProgress([]);
            document.getElementById('runName').textContent = 'N/A';
            return;
        }

        const tpl = document.getElementById('visitTpl');
        let total = 0;

        v.forEach(vis => {
            const node = document.importNode(tpl.content, true);
            const card = node.querySelector('.card');
            card.style.cursor = 'pointer';

            // ✅ Updated care-plan URL with clientId, care_calls, and Clientshift_Date
            card.addEventListener('click', () => {
                const url = `care-plan?id=${vis.id}&clientId=${vis.uryyToeSS4}&care_calls=${encodeURIComponent(vis.care_calls)}&Clientshift_Date=${vis.Clientshift_Date}`;
                location.href = url;
            });

            const initials = getInitials(vis.client_name);
            const color = getColorForName(vis.client_name);
            node.querySelector('.avatar').innerHTML = `
        <div class="avatar-initials" style="background:${color};color:#fff;font-weight:bold;border-radius:.5rem;width:4rem;height:4rem;display:flex;align-items:center;justify-content:center;font-size:1.8rem">${initials}</div>`;

            node.querySelector('.name').textContent = vis.client_name;
            node.querySelector('.service').textContent = vis.care_calls;
            node.querySelector('.times').textContent = `${vis.dateTime_in} - ${vis.dateTime_out}`;

            const carers = node.querySelector('.carers-icons');
            carers.innerHTML = '';
            if (vis.col_required_carers == 2) carers.innerHTML = '👥';
            else if (vis.col_required_carers > 2) carers.innerHTML = '👤'.repeat(vis.col_required_carers);

            const badge = node.querySelector('.status');
            const status = (vis.call_status || 'scheduled').toLowerCase();
            badge.textContent = status;
            badge.className = 'badge badge-status ms-1';
            switch (status) {
                case 'scheduled':
                    badge.classList.add('bg-info', 'text-dark');
                    break;
                case 'in-progress':
                    badge.classList.add('bg-warning', 'text-dark');
                    break;
                case 'completed':
                    badge.classList.add('bg-success');
                    break;
                case 'cancelled':
                    badge.classList.add('bg-danger');
                    break;
                default:
                    badge.classList.add('bg-secondary');
            }

            const start = parseDateTimeLocal(vis.Clientshift_Date, vis.dateTime_in);
            const end = parseDateTimeLocal(vis.Clientshift_Date, vis.dateTime_out);
            total += (end - start) / 60000;

            const now = new Date();
            if (start > now && start - now <= 3600000) card.style.border = '2px solid var(--accent2)';
            if (start < now && vis.call_status !== 'completed') card.style.border = '2px solid red';

            visitsContainer.appendChild(node);
        });

        const h = Math.floor(total / 60),
            m = Math.floor(total % 60);
        document.getElementById('totalHours').textContent = `${h}h ${m}m`;
        updateQuickStats(v);
        updateProgress(v);
        document.getElementById('runName').textContent = v[0]?.col_run_name || 'N/A';
    }


    // --- Render timeline alerts ---
    function renderTimelineAndAlerts(v) {
        const alerts = document.getElementById('alertsContainer');
        alerts.innerHTML = '';
        const now = new Date();
        v.forEach(x => {
            const s = parseDateTimeLocal(x.Clientshift_Date, x.dateTime_in);
            const e = parseDateTimeLocal(x.Clientshift_Date, x.dateTime_out);
            const div = document.createElement('div');
            if (s > now && s - now <= 3600000) {
                div.className = 'alert-item text-info';
                div.textContent = `Upcoming: ${x.client_name} at ${x.dateTime_in}`;
            } else if (e < now && x.call_status !== 'completed') {
                div.className = 'alert-item text-danger';
                div.textContent = `Overdue: ${x.client_name} (${x.dateTime_in}-${x.dateTime_out})`;
            }
            if (div.textContent) alerts.appendChild(div);
        });
        if (!alerts.hasChildNodes()) alerts.textContent = 'No alerts for today';
    }

    // --- Quick stats and progress ---
    function updateQuickStats(v) {
        document.getElementById('countCalls').textContent = v.length;
    }

    function updateProgress(v) {
        const bar = document.getElementById('progressBar');
        if (!v.length) {
            bar.style.width = '0%';
            return;
        }
        const done = v.filter(x => x.call_status === 'completed').length;
        bar.style.width = Math.round(done / v.length * 100) + '%';
    }

    // --- Scroll to active date ---
    function scrollToActiveDate() {
        const active = document.querySelector('.date-pill.active');
        if (active) active.scrollIntoView({
            behavior: 'smooth',
            inline: 'center'
        });
    }

    // --- Render date pills ---
    function renderDatePills(centerDate) {
        dateStrip.innerHTML = '';
        const y = centerDate.getFullYear(),
            m = centerDate.getMonth();
        const daysInMonth = new Date(y, m + 1, 0).getDate();
        for (let i = 1; i <= daysInMonth; i++) {
            const d = new Date(y, m, i);
            const iso = formatLocalISO(d);
            const pill = document.createElement('div');
            pill.className = 'date-pill';
            pill.dataset.date = iso;
            pill.innerHTML = `<div style="font-weight:600">${d.toLocaleDateString(undefined,{weekday:'short'})}</div>
        <div style="font-size:.85rem;position:relative">${d.getDate()} ${d.toLocaleString(undefined,{month:'short'})}</div>`;
            if (visitsCache.some(v => v.Clientshift_Date === iso)) {
                const dot = document.createElement('div');
                dot.style.cssText = 'width:6px;height:6px;background:#bdc3c7;border-radius:50%;position:absolute;bottom:-5px;left:50%;transform:translateX(-50%)';
                pill.querySelector('div:nth-child(2)').appendChild(dot);
            }
            if (iso === selectedDate) pill.classList.add('active');
            pill.addEventListener('click', () => {
                selectedDate = iso;
                renderDatePills(centerDate);
                renderFilteredVisits();
                setTimeout(scrollToActiveDate, 100);
            });
            dateStrip.appendChild(pill);
        }
        setTimeout(scrollToActiveDate, 100);
    }

    // --- Render filtered visits ---
    function renderFilteredVisits() {
        const filtered = getFilteredVisits(selectedDate);
        renderVisitsFiltered(filtered);
        renderTimelineAndAlerts(filtered);
    }

    // --- Clock ---
    function updateClock() {
        document.getElementById('today-clock').textContent = new Date().toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    setInterval(updateClock, 1000);
    updateClock();

    // --- Navigation buttons ---
    document.getElementById('prevDay').onclick = () => {
        const d = new Date(selectedDate + 'T00:00');
        d.setDate(d.getDate() - 1);
        selectedDate = formatLocalISO(d);
        renderDatePills(d);
        renderFilteredVisits();
    };
    document.getElementById('nextDay').onclick = () => {
        const d = new Date(selectedDate + 'T00:00');
        d.setDate(d.getDate() + 1);
        selectedDate = formatLocalISO(d);
        renderDatePills(d);
        renderFilteredVisits();
    };
    document.getElementById('todayBtn').onclick = () => {
        selectedDate = formatLocalISO(new Date());
        renderDatePills(new Date());
        renderFilteredVisits();
    };
    document.getElementById('refreshBtn').onclick = () => renderFilteredVisits();
    document.getElementById('searchVisits').addEventListener('input', e => {
        const t = e.target.value.toLowerCase();
        const filtered = getFilteredVisits(selectedDate).filter(v => v.client_name.toLowerCase().includes(t));
        renderVisitsFiltered(filtered);
        renderTimelineAndAlerts(filtered);
    });

    // --- Offline indicators ---
    window.addEventListener('offline', () => document.getElementById('offlineStatus').style.display = 'inline-block');
    window.addEventListener('online', () => {
        document.getElementById('offlineStatus').style.display = 'none';
        localStorage.removeItem('offlineQueue');
    });

    // --- Side menu ---
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

    // --- Initialize ---
    (async () => {
        await loadUserSpecialId();
        await Promise.all([loadCancelledCalls(), loadClientStatusRecords(), loadVisits()]);
        renderDatePills(new Date());
        renderFilteredVisits();
    })();
</script>

<?php include_once 'footer.php'; ?>