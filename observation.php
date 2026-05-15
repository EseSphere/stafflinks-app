<?php include_once 'header.php'; ?>

<div class="main-wrapper container py-4">

    <!-- Client Profile Section -->
    <?php require_once 'client-profile-extension.php'; ?>

    <!-- Observation Form -->
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body">
            <h4 class="fw-bold mb-3">Daily Observation</h4>
            <p class="text-muted small mb-3 fs-5">
                Please provide a brief observation about the client’s condition, mood, or any significant events during
                this care call.
            </p>
            <form id="observationForm" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="observationText" class="form-label fw-semibold fs-5">Observation Notes</label>
                    <textarea class="form-control fs-5" id="observationText" rows="5"
                        placeholder="Write your observation here..." required></textarea>
                    <div class="invalid-feedback">Observation field cannot be empty.</div>
                </div>
                <button type="submit" class="btn btn-primary p-2 fs-6">
                    <i class="bi bi-send"></i> Check Out
                </button>
            </form>
        </div>
    </div>

    <!-- Highlight Section -->
    <?php require_once 'highlight-extention.php'; ?>

</div>

<script>
const urlParams = new URLSearchParams(window.location.search);
const recordId = parseInt(urlParams.get('id'));
const clientId = urlParams.get('uryyToeSS4');

/* ===================== IndexedDB ===================== */

async function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('stafflinks');
        request.onsuccess = e => resolve(e.target.result);
        request.onerror = e => reject(e.target.error);
    });
}

async function getClientDetails(clientId) {
    if (!clientId) return null;
    const db = await openDB();
    if (!db.objectStoreNames.contains('tbl_general_client_form')) return null;

    return new Promise((resolve, reject) => {
        const tx = db.transaction('tbl_general_client_form', 'readonly');
        const store = tx.objectStore('tbl_general_client_form');
        const req = store.getAll();
        req.onsuccess = e => {
            resolve(e.target.result.find(c =>
                c.uryyToeSS4.toString() === clientId.toString()
            ) || null);
        };
        req.onerror = e => reject(e.target.error);
    });
}

async function getDailyShiftRecord(recordId) {
    if (!recordId) return null;
    const db = await openDB();
    if (!db.objectStoreNames.contains('tbl_daily_shift_records')) return null;

    return new Promise((resolve, reject) => {
        const tx = db.transaction('tbl_daily_shift_records', 'readonly');
        const store = tx.objectStore('tbl_daily_shift_records');
        const req = store.get(recordId);
        req.onsuccess = e => resolve(e.target.result || null);
        req.onerror = e => reject(e.target.error);
    });
}

async function getScheduleCallForDailyShift(record) {
    const db = await openDB();
    if (!db.objectStoreNames.contains('tbl_schedule_calls')) {
        return {
            pay_rate: 0,
            client_rate: 0
        };
    }

    return new Promise((resolve, reject) => {
        const tx = db.transaction('tbl_schedule_calls', 'readonly');
        const store = tx.objectStore('tbl_schedule_calls');
        const req = store.getAll();
        req.onsuccess = e => {
            const r = e.target.result.find(r =>
                r.uryyToeSS4.toString() === record.uryyToeSS4.toString() &&
                r.Clientshift_Date.toString() === record.shift_date.toString() &&
                r.care_calls.toString() === record.col_care_call.toString() &&
                parseInt(r.first_carer_Id) === parseInt(record.col_carer_Id)
            );

            resolve({
                pay_rate: parseFloat(r?.pay_rate) || 0,
                client_rate: parseFloat(r?.client_rate) || 0
            });
        };
        req.onerror = e => reject(e.target.error);
    });
}

/* ===================== Calculations ===================== */

function calculateWorkedTime(start, end) {
    if (!start || !end) return '00:00';
    const [sh, sm] = start.split(':').map(Number);
    const [eh, em] = end.split(':').map(Number);

    let s = sh * 60 + sm;
    let e = eh * 60 + em;
    if (e < s) e += 1440;

    const diff = e - s;
    return `${String(Math.floor(diff / 60)).padStart(2,'0')}:${String(diff % 60).padStart(2,'0')}`;
}

/* ===================== SERVER SYNC (NEW) ===================== */

async function syncDailyShiftToServer(record) {
    const payload = {
        col_care_call_Id: record.col_care_call_Id || record.col_care_call,
        shift_end_time: record.shift_end_time,
        col_call_status: record.col_call_status,
        col_worked_time: record.col_worked_time,
        col_carecall_rate: record.col_carecall_rate,
        col_client_rate: record.col_client_rate,
        task_note: record.task_note
    };

    try {
        const response = await fetch('update_daily_shift.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const text = await response.text();

        let json;
        try {
            json = JSON.parse(text);
        } catch {
            console.error('❌ Non-JSON response:', text);
            return false;
        }

        if (!response.ok || !json.success) {
            console.error('❌ Server error:', json);
            return false;
        }

        console.log('✅ Server synced:', json.message);
        return true;

    } catch (err) {
        console.error('❌ Sync failed:', err.message);
        return false;
    }
}

/* ===================== UPDATE DAILY SHIFT ===================== */

async function updateDailyShiftRecord(record, note) {
    const db = await openDB();
    if (!db.objectStoreNames.contains('tbl_daily_shift_records')) return;

    record.task_note = note;
    record.col_call_status = 'completed';

    const now = new Date();
    record.shift_end_time = `${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;

    record.col_worked_time = calculateWorkedTime(
        record.planned_timeIn,
        record.planned_timeOut
    );

    const [h, m] = record.col_worked_time.split(':');
    const workedDecimal = parseInt(h) + parseInt(m) / 60;

    const {
        pay_rate,
        client_rate
    } = await getScheduleCallForDailyShift(record);

    record.col_carecall_rate = +(workedDecimal * pay_rate).toFixed(2);
    record.col_client_rate = +(workedDecimal * client_rate).toFixed(2);

    /* ---- Save locally ---- */
    await new Promise((resolve, reject) => {
        const tx = db.transaction('tbl_daily_shift_records', 'readwrite');
        tx.objectStore('tbl_daily_shift_records').put(record).onsuccess = resolve;
        tx.onerror = e => reject(e.target.error);
    });

    /* ---- Update schedule call locally ---- */
    if (db.objectStoreNames.contains('tbl_schedule_calls')) {
        const tx = db.transaction('tbl_schedule_calls', 'readwrite');
        const store = tx.objectStore('tbl_schedule_calls');
        store.getAll().onsuccess = e => {
            const r = e.target.result.find(r =>
                r.uryyToeSS4.toString() === record.uryyToeSS4.toString() &&
                r.Clientshift_Date.toString() === record.shift_date.toString() &&
                r.care_calls.toString() === record.col_care_call.toString() &&
                parseInt(r.first_carer_Id) === parseInt(record.col_carer_Id)
            );
            if (r) {
                r.call_status = 'completed';
                store.put(r);
            }
        };
    }

    /* ---- Sync to server (NEW) ---- */
    await syncDailyShiftToServer(record);
}

/* ===================== UI HELPERS ===================== */

function calculateAge(dob) {
    if (!dob) return '--';
    const b = new Date(dob),
        t = new Date();
    let a = t.getFullYear() - b.getFullYear();
    if (t.getMonth() < b.getMonth() || (t.getMonth() === b.getMonth() && t.getDate() < b.getDate())) a--;
    return a;
}

function createInitialsCircle(name, size = 2, d = 100) {
    const n = (name || '--').split(' ');
    const i = ((n[0]?. [0] || '') + (n[1]?. [0] || '')).toUpperCase();
    const colors = ["#6c757d", "#0d6efd", "#198754", "#dc3545", "#ffc107", "#6f42c1", "#fd7e14"];
    const div = document.createElement('div');
    div.textContent = i;
    div.style.cssText = `
            width:${d}px;height:${d}px;border-radius:50%;
            display:flex;align-items:center;justify-content:center;
            font-size:${size}rem;font-weight:bold;color:#fff;
            background:${colors[(i.charCodeAt(0)||0)%colors.length]};
        `;
    return div;
}

/* ===================== RENDER ===================== */

async function renderClientProfileAndObservation() {
    if (!clientId) return;
    const client = await getClientDetails(clientId);

    if (client) {
        const c = createInitialsCircle(`${client.client_first_name} ${client.client_last_name}`);
        document.getElementById('clientInitials').replaceWith(c);
        c.id = 'clientInitials';

        document.getElementById('clientName').textContent =
            `${client.client_first_name} ${client.client_last_name}`;
        document.getElementById('clientAge').textContent =
            `Age: ${calculateAge(client.client_date_of_birth)}`;
    }

    const record = await getDailyShiftRecord(recordId);
    if (record?.task_note) {
        document.getElementById('observationText').value = record.task_note;
    }
}

document.getElementById('observationForm').addEventListener('submit', async e => {
    e.preventDefault();
    const note = observationText.value.trim();
    if (!note) return;

    const record = await getDailyShiftRecord(recordId);
    if (!record) return alert('Daily shift record not found');

    await updateDailyShiftRecord(record, note);
    window.location.href = 'visits.php';
});

renderClientProfileAndObservation();
</script>


<?php include_once 'footer.php'; ?>