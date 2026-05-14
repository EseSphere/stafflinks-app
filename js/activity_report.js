const urlParams = new URLSearchParams(window.location.search);
    const clientId = urlParams.get('clientId');
    const taskId = urlParams.get('col_taskId');
    const id = urlParams.get('id');
    const carerId = urlParams.get('carerId');
    const careCallFromURL = urlParams.get('care_calls') || 'Morning';
    const urlDate = urlParams.get('task_date') || urlParams.get('med_date') || new Date().toISOString().split('T')[0];

    // Get activity type from URL ('task' or 'medication')
    const activityType = urlParams.get('type') || 'task';
    const activityTitle = decodeURIComponent(urlParams.get('title') || '--');
    const activityDetails = decodeURIComponent(urlParams.get('details') || '--');

    let statusSelected = '';

    // Status options based on type
    const allStatusOptions = {
        task: {
            'Completed': 'success',
            'Not Completed': 'danger',
            'Refused': 'warning',
            'Not Available': 'secondary',
            'Not Necessary': 'info'
        },
        medication: {
            'Refused': 'warning',
            'Not Available': 'secondary',
            'Not Necessary': 'info',
            'Prompt': 'success',
            'Given': 'primary',
            'Not Given': 'dark'
        }
    };

    async function openDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open('stafflinks');
            request.onsuccess = e => resolve(e.target.result);
            request.onerror = e => reject(e.target.error);
            request.onupgradeneeded = e => {
                const db = e.target.result;
                if (!db.objectStoreNames.contains('tbl_finished_tasks')) db.createObjectStore('tbl_finished_tasks', {
                    keyPath: 'id'
                });
                if (!db.objectStoreNames.contains('tbl_finished_meds')) db.createObjectStore('tbl_finished_meds', {
                    keyPath: 'id'
                });
            };
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
                const client = e.target.result.find(c => c.uryyToeSS4 === clientId);
                resolve(client || null);
            };
            req.onerror = e => reject(e.target.error);
        });
    }

    function calculateAge(dob) {
        if (!dob) return '--';
        const birthDate = new Date(dob);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        const dayDiff = today.getDate() - birthDate.getDate();
        if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) age--;
        return age;
    }

    function createInitialsCircle(fullName, fontSize = 2, diameter = 100) {
        if (!fullName) fullName = '--';
        const names = fullName.split(' ');
        const initials = ((names[0]?.charAt(0) || '') + (names[1]?.charAt(0) || '')).toUpperCase();
        const colors = ["#6c757d", "#0d6efd", "#198754", "#dc3545", "#ffc107", "#6f42c1", "#fd7e14"];
        const charCodeSum = (initials.charCodeAt(0) || 0) + (initials.charCodeAt(1) || 0);
        const bgColor = colors[charCodeSum % colors.length];

        const div = document.createElement('div');
        div.textContent = initials;
        div.style.width = `${diameter}px`;
        div.style.height = `${diameter}px`;
        div.style.borderRadius = '50%';
        div.style.display = 'flex';
        div.style.alignItems = 'center';
        div.style.justifyContent = 'center';
        div.style.fontSize = `${fontSize}rem`;
        div.style.fontWeight = 'bold';
        div.style.color = 'white';
        div.style.backgroundColor = bgColor;
        div.style.marginBottom = '5px';
        return div;
    }

    async function renderClientProfileAndHighlight() {
        if (!clientId) return;
        const client = await getClientDetails(clientId);
        if (!client) return;

        const firstName = client.client_first_name || '';
        const lastName = client.client_last_name || '';
        const initialsDiv = document.getElementById('clientInitials');
        const clientInitialsCircle = createInitialsCircle(`${firstName} ${lastName}`, 2, 100);
        initialsDiv.replaceWith(clientInitialsCircle);
        clientInitialsCircle.id = 'clientInitials';

        document.getElementById('clientName').textContent = `${firstName} ${lastName}`;
        document.getElementById('clientAge').textContent = `Age: ${calculateAge(client.client_date_of_birth)}`;
        document.getElementById('dnacprBtn').href = `health.php?uryyToeSS4=${client.uryyToeSS4}`;
        document.getElementById('allergiesBtn').href = `emergency.php?uryyToeSS4=${client.uryyToeSS4}`;

        const highlightDiv = document.getElementById('highlight');
        if (client.client_highlights) {
            const paragraphs = client.client_highlights.split(/\n\s*\n/);
            highlightDiv.innerHTML = paragraphs.map(p => `<p>${p.trim().replace(/\n/g,'<br>')}</p>`).join('');
        } else {
            highlightDiv.innerHTML = '<p>No highlights available.</p>';
        }
    }

    function renderStatusButtons(type, selected = '') {
        const container = document.getElementById('statusContainer');
        container.innerHTML = '';
        const statuses = allStatusOptions[type] || {};
        for (let status in statuses) {
            const color = statuses[status];
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `btn btn-outline-${color} flex-fill status-btn`;
            btn.textContent = status;
            if (status === selected) btn.classList.add('active', `btn-${color}`);
            btn.addEventListener('click', () => {
                document.querySelectorAll('.status-btn').forEach(b => {
                    const col = b.className.match(/btn-outline-(\w+)/)[1];
                    b.className = `btn btn-outline-${col} flex-fill status-btn`;
                });
                btn.classList.add('active', `btn-${color}`);
                statusSelected = status;
            });
            container.appendChild(btn);
        }
    }

    async function renderSelectedActivity() {
        const activityEl = document.getElementById('selectedActivity');
        const descriptionEl = document.getElementById('activityDescription');

        // Use URL params for name & description
        activityEl.innerHTML = activityTitle + (activityType === 'task' ?
            ' <span class="badge bg-info ms-2">Task</span>' :
            ' <span class="badge bg-warning ms-2">Medication</span>');
        descriptionEl.textContent = activityDetails;

        // Check for previous submission from IndexedDB (include care_calls in match)
        const db = await openDB();
        const storeName = activityType === 'task' ? 'tbl_finished_tasks' : 'tbl_finished_meds';
        if (!db.objectStoreNames.contains(storeName)) {
            renderStatusButtons(activityType);
            return;
        }

        const tx = db.transaction(storeName, 'readonly');
        const store = tx.objectStore(storeName);
        const allRecords = await new Promise((resolve, reject) => {
            const req = store.getAll();
            req.onsuccess = e => resolve(e.target.result);
            req.onerror = e => reject(e.target.error);
        });

        const prevSubmission = allRecords.find(r =>
            r.uniqueId === taskId &&
            r.uryyToeSS4 === clientId &&
            r.care_calls === careCallFromURL &&
            ((activityType === 'task' && r.task_date === urlDate) ||
                (activityType === 'medication' && r.med_date === urlDate))
        );

        if (prevSubmission) {
            document.getElementById('reportText').value = prevSubmission.note || '';
            statusSelected = prevSubmission.col_status || '';
        } else {
            document.getElementById('reportText').value = '';
            statusSelected = '';
        }

        renderStatusButtons(activityType, statusSelected);
    }

    document.getElementById('activityReportForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const notes = document.getElementById('reportText').value;

        const db = await openDB();
        const now = new Date();
        const storeName = activityType === 'task' ? 'tbl_finished_tasks' : 'tbl_finished_meds';
        const tx = db.transaction(storeName, 'readwrite');
        const store = tx.objectStore(storeName);
        const allRecords = await new Promise((resolve, reject) => {
            const req = store.getAll();
            req.onsuccess = e => resolve(e.target.result);
            req.onerror = e => reject(e.target.error);
        });

        // Include care_calls in match
        let record = allRecords.find(r =>
            r.uniqueId === taskId &&
            r.uryyToeSS4 === clientId &&
            r.care_calls === careCallFromURL &&
            ((activityType === 'task' && r.task_date === urlDate) ||
                (activityType === 'medication' && r.med_date === urlDate))
        );

        if (record) {
            record.note = notes;
            record.col_status = statusSelected || 'Not selected';
            record.dateTime = now.toISOString();
            record.timeIn = now.toLocaleTimeString();
            store.put(record);
        } else {
            const lastId = allRecords.length ? Math.max(...allRecords.map(r => Number(r.id))) : 0;
            record = {
                id: lastId + 1,
                uniqueId: taskId,
                uryyToeSS4: clientId,
                col_status: statusSelected || 'Not selected',
                carer_name: 'Current Carer',
                carer_Id: 'C001',
                care_calls: careCallFromURL,
                col_company_Id: 'COMP123',
                dateTime: now.toISOString(),
                timeIn: now.toLocaleTimeString(),
                note: notes
            };
            if (activityType === 'task') {
                record.task = activityTitle || '--';
                record.task_date = urlDate;
            } else {
                record.meds = activityTitle || '--';
                record.med_date = urlDate;
            }
            store.add(record);
        }

        window.location.href = `activities.php?uryyToeSS4=${clientId}&Clientshift_Date=${urlDate}&care_calls=${careCallFromURL}&id=${id}&carerId=${carerId}`;
    });

    document.getElementById('continueBtn').addEventListener('click', (e) => {
        e.preventDefault();
        const text = document.getElementById('reportText').value;
        navigator.clipboard.writeText(text).then(() => {
            window.location.href = `activities.php?uryyToeSS4=${clientId}&Clientshift_Date=${urlDate}&care_calls=${careCallFromURL}&id=${id}&carerId=${carerId}`;
        });
    });

    renderClientProfileAndHighlight();
    renderSelectedActivity();