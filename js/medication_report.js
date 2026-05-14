const urlParams = new URLSearchParams(window.location.search);
const clientId = urlParams.get('clientId');
const medId = urlParams.get('col_taskId');
const idFromURL = urlParams.get('id');
const carerId = urlParams.get('carerId');
const careCallFromURL = urlParams.get('care_calls') || 'Morning';
const urlDate = urlParams.get('med_date') || new Date().toISOString().split('T')[0];

const activityTitle = decodeURIComponent(urlParams.get('title') || '--');
const activityDetails = decodeURIComponent(urlParams.get('details') || '--');

let statusSelected = '';
let submitBtn;

/* ---------- MEDICATION STATUS OPTIONS ---------- */
const medStatusOptions = {
    'Refused': 'warning',
    'Not Available': 'secondary',
    'Not Necessary': 'info',
    'Prompt': 'success',
    'Given': 'primary',
    'Not Given': 'dark'
};

/* ---------- IndexedDB Open ---------- */
async function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('stafflinks');
        request.onsuccess = e => resolve(e.target.result);
        request.onerror = e => reject(e.target.error);
        request.onupgradeneeded = e => {
            const db = e.target.result;
            if (!db.objectStoreNames.contains('tbl_finished_meds')) {
                db.createObjectStore('tbl_finished_meds', { keyPath: 'id' });
            }
        };
    });
}

/* ---------- Client Profile ---------- */
async function getClientDetails(clientId) {
    if (!clientId) return null;
    const db = await openDB();
    if (!db.objectStoreNames.contains('tbl_general_client_form')) return null;

    return new Promise(resolve => {
        const tx = db.transaction('tbl_general_client_form', 'readonly');
        const store = tx.objectStore('tbl_general_client_form');
        const req = store.getAll();
        req.onsuccess = e =>
            resolve(e.target.result.find(c => c.uryyToeSS4 === clientId) || null);
    });
}

function calculateAge(dob) {
    if (!dob) return '--';
    const birthDate = new Date(dob);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    if (
        today.getMonth() < birthDate.getMonth() ||
        (today.getMonth() === birthDate.getMonth() &&
            today.getDate() < birthDate.getDate())
    ) age--;
    return age;
}

function createInitialsCircle(fullName, fontSize = 2, diameter = 100) {
    if (!fullName) fullName = '--';
    const names = fullName.split(' ');
    const initials =
        ((names[0]?.[0] || '') + (names[1]?.[0] || '')).toUpperCase();
    const colors = [
        "#6c757d","#0d6efd","#198754","#dc3545","#ffc107","#6f42c1","#fd7e14"
    ];
    const bgColor =
        colors[(initials.charCodeAt(0) + (initials.charCodeAt(1) || 0)) % colors.length];

    const div = document.createElement('div');
    div.textContent = initials;
    Object.assign(div.style, {
        width: `${diameter}px`,
        height: `${diameter}px`,
        borderRadius: '50%',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        fontSize: `${fontSize}rem`,
        fontWeight: 'bold',
        color: 'white',
        backgroundColor: bgColor,
        marginBottom: '5px'
    });
    return div;
}

async function renderClientProfileAndHighlight() {
    const client = await getClientDetails(clientId);
    if (!client) return;

    const initialsDiv = document.getElementById('clientInitials');
    const initialsCircle = createInitialsCircle(
        `${client.client_first_name} ${client.client_last_name}`
    );
    initialsDiv.replaceWith(initialsCircle);
    initialsCircle.id = 'clientInitials';

    document.getElementById('clientName').textContent =
        `${client.client_first_name} ${client.client_last_name}`;
    document.getElementById('clientAge').textContent =
        `Age: ${calculateAge(client.client_date_of_birth)}`;
    document.getElementById('dnacprBtn').href =
        `health.php?uryyToeSS4=${client.uryyToeSS4}`;
    document.getElementById('allergiesBtn').href =
        `emergency.php?uryyToeSS4=${client.uryyToeSS4}`;

    const highlightDiv = document.getElementById('highlight');
    highlightDiv.innerHTML = client.client_highlights
        ? client.client_highlights
            .split(/\n\s*\n/)
            .map(p => `<p>${p.trim().replace(/\n/g, '<br>')}</p>`)
            .join('')
        : '<p>No highlights available.</p>';
}

/* ---------- Status Buttons ---------- */
function renderStatusButtons(selected = '') {
    const container = document.getElementById('statusContainer');
    container.innerHTML = '';

    for (const status in medStatusOptions) {
        const color = medStatusOptions[status];
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `btn btn-outline-${color} flex-fill status-btn`;
        btn.textContent = status;

        if (status === selected) {
            btn.classList.add('active', `btn-${color}`);
        }

        btn.onclick = () => {
            document.querySelectorAll('.status-btn').forEach(b => {
                const col = b.className.match(/btn-outline-(\w+)/)[1];
                b.className = `btn btn-outline-${col} flex-fill status-btn`;
            });

            btn.classList.add('active', `btn-${color}`);
            statusSelected = status;

            /* ✅ ENABLE SUBMIT */
            if (submitBtn) submitBtn.disabled = false;
        };

        container.appendChild(btn);
    }
}

/* ---------- Render Selected Medication ---------- */
async function renderSelectedActivity() {
    document.getElementById('selectedActivity').innerHTML =
        `${activityTitle} <span class="badge bg-warning ms-2">Medication</span>`;
    document.getElementById('activityDescription').textContent = activityDetails;

    const db = await openDB();
    const store = db
        .transaction('tbl_finished_meds', 'readonly')
        .objectStore('tbl_finished_meds');

    const records = await new Promise(resolve => {
        const req = store.getAll();
        req.onsuccess = e => resolve(e.target.result);
    });

    const prev = records.find(r =>
        r.uniqueId === medId &&
        r.uryyToeSS4 === clientId &&
        r.care_calls === careCallFromURL &&
        r.med_date === urlDate
    );

    document.getElementById('reportText').value = prev?.note || '';
    statusSelected = prev?.col_status || '';
    renderStatusButtons(statusSelected);

    /* ✅ AUTO-ENABLE SUBMIT IF STATUS EXISTS */
    if (statusSelected && submitBtn) {
        submitBtn.disabled = false;
    }
}

/* ---------- Sync to MySQL ---------- */
async function syncMedToServer(record) {
    try {
        const formData = new FormData();
        for (const key in record) formData.append(key, record[key]);

        const response = await fetch('sync_finished_meds.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (!result.success) {
            console.error('Failed to sync:', result.message);
        }
    } catch (err) {
        console.error('Sync error:', err);
    }
}

/* ---------- Form Submit ---------- */
document.getElementById('activityReportForm').addEventListener('submit', async e => {
    e.preventDefault();

    /* 🚫 SAFETY CHECK */
    if (!statusSelected || statusSelected.trim() === '') {
        alert('Please select a medication status before submitting.');
        return;
    }

    const db = await openDB();
    const store = db
        .transaction('tbl_finished_meds', 'readwrite')
        .objectStore('tbl_finished_meds');

    const records = await new Promise(resolve => {
        const req = store.getAll();
        req.onsuccess = e => resolve(e.target.result);
    });

    const now = new Date();

    let record = records.find(r =>
        r.uniqueId === medId &&
        r.uryyToeSS4 === clientId &&
        r.care_calls === careCallFromURL &&
        r.med_date === urlDate
    );

    if (record) {
        record.note = document.getElementById('reportText').value;
        record.col_status = statusSelected;
        record.dateTime = now.toISOString();
        record.timeIn = now.toLocaleTimeString();
        store.put(record);
    } else {
        record = {
            id: records.length ? Math.max(...records.map(r => Number(r.id))) + 1 : 1,
            uniqueId: medId,
            uryyToeSS4: clientId,
            meds: activityTitle,
            med_date: urlDate,
            care_calls: careCallFromURL,
            col_status: statusSelected,
            note: document.getElementById('reportText').value,
            dateTime: now.toISOString(),
            timeIn: now.toLocaleTimeString(),
            carer_Id: carerId,
            carer_name: 'Current Carer',
            col_company_Id: 'COMP123'
        };
        store.add(record);
    }

    await syncMedToServer(record);

    window.location.href =
        `activities.php?uryyToeSS4=${clientId}&Clientshift_Date=${urlDate}` +
        `&care_calls=${careCallFromURL}&id=${idFromURL}&carerId=${carerId}`;
});

/* ---------- Continue Button ---------- */
document.getElementById('continueBtn').addEventListener('click', e => {
    e.preventDefault();
    navigator.clipboard.writeText(document.getElementById('reportText').value)
        .then(() => {
            window.location.href =
                `activities.php?uryyToeSS4=${clientId}&Clientshift_Date=${urlDate}` +
                `&care_calls=${careCallFromURL}&id=${idFromURL}&carerId=${carerId}`;
        });
});

/* ---------- Initialize ---------- */
renderClientProfileAndHighlight();
renderSelectedActivity();

/* 🔒 Disable submit on load */
submitBtn = document.querySelector('#activityReportForm button[type="submit"]');
if (submitBtn) submitBtn.disabled = true;
