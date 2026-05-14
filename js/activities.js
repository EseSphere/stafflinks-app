 const urlParams = new URLSearchParams(window.location.search);
    const clientId = urlParams.get('uryyToeSS4');
    const clientshift_date = urlParams.get('Clientshift_Date');
    const careCall = urlParams.get('care_calls');
    const id = urlParams.get('id');
    const carerId = urlParams.get('carerId');
    let totalTasks = 0;
    let totalMeds = 0;

    document.getElementById('continueBtn').addEventListener('click', e => {
        e.preventDefault();
        window.location.href =
            `processing-tasks.php?uryyToeSS4=${clientId}&Clientshift_Date=${clientshift_date}` +
            `&care_calls=${careCall}&id=${id}&carerId=${carerId}` +
            `&totaltask=${totalTasks}&totalmeds=${totalMeds}`;
    });

    function calculateAge(dob) {
        if (!dob) return '--';
        const birth = new Date(dob),
            today = new Date();
        let age = today.getFullYear() - birth.getFullYear();
        if (
            today.getMonth() < birth.getMonth() ||
            (today.getMonth() === birth.getMonth() && today.getDate() < birth.getDate())
        ) age--;
        return age;
    }

    async function openDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open('stafflinks');
            request.onsuccess = e => resolve(e.target.result);
            request.onerror = e => reject(e.target.error);
        });
    }

    async function fetchRecords(store, clientId, careCall) {
        const db = await openDB();
        return new Promise(resolve => {
            if (!db.objectStoreNames.contains(store)) return resolve([]);
            const req = db.transaction(store, 'readonly').objectStore(store).getAll();
            req.onsuccess = () =>
                resolve(
                    req.result.filter(
                        r =>
                        r.uryyToeSS4 === clientId && ['care_call1', 'care_call2', 'care_call3', 'care_call4',
                            'extra_call1', 'extra_call2', 'extra_call3', 'extra_call4'
                        ]
                        .some(k => r[k] === careCall)
                    )
                );
            req.onerror = () => resolve([]);
        });
    }

    async function fetchFinished(store, clientId, date, careCall) {
        const db = await openDB();
        return new Promise(resolve => {
            if (!db.objectStoreNames.contains(store)) return resolve([]);
            const req = db.transaction(store, 'readonly').objectStore(store).getAll();
            req.onsuccess = () =>
                resolve(
                    req.result.filter(
                        r =>
                        r.uryyToeSS4 === clientId &&
                        r.care_calls === careCall &&
                        (r.task_date === date || r.med_date === date)
                    )
                );
            req.onerror = () => resolve([]);
        });
    }

    async function getClientDetails(id) {
        const db = await openDB();
        return new Promise(resolve => {
            const req = db
                .transaction('tbl_general_client_form', 'readonly')
                .objectStore('tbl_general_client_form')
                .getAll();
            req.onsuccess = e =>
                resolve(e.target.result.find(c => c.uryyToeSS4 === id) || null);
            req.onerror = () => resolve(null);
        });
    }

    function createInitialsCircle(name, fontSize = 2, diameter = 100) {
        if (!name) name = '--';
        const initials = name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
        const colors = ["#6c757d", "#0d6efd", "#198754", "#dc3545", "#ffc107", "#6f42c1", "#fd7e14"];
        const bgColor = colors[(initials.charCodeAt(0) + (initials.charCodeAt(1) || 0)) % colors.length];
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
        if (!clientId) return;
        const client = await getClientDetails(clientId);
        if (!client) return;

        const initialsCircle = createInitialsCircle(
            `${client.client_first_name} ${client.client_last_name}`
        );
        const initialsDiv = document.getElementById('clientInitials');
        initialsDiv.replaceWith(initialsCircle);
        initialsCircle.id = 'clientInitials';

        document.getElementById('clientName').textContent =
            `${client.client_first_name} ${client.client_last_name}`;
        document.getElementById('clientAge').textContent =
            `Age: ${calculateAge(client.client_date_of_birth)}`;
        document.getElementById('dnacprBtn').href = `health.php?uryyToeSS4=${clientId}`;
        document.getElementById('allergiesBtn').href = `emergency.php?uryyToeSS4=${clientId}`;

        const highlightDiv = document.getElementById('highlight');
        highlightDiv.innerHTML = client.client_highlights ?
            client.client_highlights
            .split(/\n\s*\n/)
            .map(p => `<p>${p.trim().replace(/\n/g,'<br>')}</p>`)
            .join('') :
            '<p>No highlights available.</p>';
    }

    async function renderActivities() {
        const container = document.getElementById('careActivitiesContainer');
        container.innerHTML = '<p>Loading activities...</p>';

        try {
            const meds = await fetchRecords('tbl_clients_medication_records', clientId, careCall);
            const tasks = await fetchRecords('tbl_clients_task_records', clientId, careCall);
            totalTasks = tasks.length;
            totalMeds = meds.length;

            const finishedMeds = await fetchFinished('tbl_finished_meds', clientId, clientshift_date, careCall);
            const finishedTasks = await fetchFinished('tbl_finished_tasks', clientId, clientshift_date, careCall);

            const activities = [
                ...tasks.map(t => ({
                    ...t,
                    type: 'task',
                    title: t.client_taskName,
                    recordId: t.id || t.col_taskId,
                    details: t.client_task_details
                })),
                ...meds.map(m => ({
                    ...m,
                    type: 'medication',
                    title: `${m.med_name} (${m.med_dosage})`,
                    recordId: m.id || m.uniqueId || m.col_taskId,
                    details: m.med_details
                }))
            ];

            // DATE-AWARE finished matching
            const matchingFinished = activities.filter(a => {
                if (a.type === 'task') {
                    return finishedTasks.some(f =>
                        f.uryyToeSS4 === clientId &&
                        f.care_calls === careCall &&
                        f.task_date === clientshift_date &&
                        f.task === a.title
                    );
                }
                return finishedMeds.some(f =>
                    f.uryyToeSS4 === clientId &&
                    f.care_calls === careCall &&
                    f.med_date === clientshift_date &&
                    f.meds === a.title
                );
            });

            console.log('Matching Finished Records (Date Matched):');
            matchingFinished.forEach(m =>
                console.log(
                    `ClientId: ${clientId}, CareCall: ${careCall}, Date: ${clientshift_date}, Name: ${m.title}`
                )
            );

            // Pending first, Updated last
            activities.sort((a, b) => {
                const aDone = matchingFinished.some(f => f.title === a.title);
                const bDone = matchingFinished.some(f => f.title === b.title);
                return aDone === bDone ? 0 : aDone ? 1 : -1;
            });

            container.innerHTML = '';
            activities.forEach(c => {
                const isFinished = matchingFinished.some(f => f.title === c.title);
                const icon = c.type === 'task' ? 'bi-list-task' : 'bi-capsule';
                const baseBg = c.type === 'task' ? '#cfe2ff' : '#fff3cd';
                const borderColor = c.type === 'task' ? '#0d6efd' : '#ffc107';

                const div = document.createElement('div');
                div.className = 'care-item';
                div.style.backgroundColor = isFinished ? '#d1e7dd' : baseBg;
                div.style.borderLeft = `5px solid ${borderColor}`;

                div.onclick = () => {
                    if (!c.recordId) return;
                    window.location.href =
                        `${c.type === 'task' ? 'task-report.php' : 'medication-report.php'}` +
                        `?col_taskId=${c.recordId}&clientId=${clientId}` +
                        `&care_calls=${careCall}&date=${clientshift_date}` +
                        `&id=${id}&carerId=${carerId}` +
                        `&title=${encodeURIComponent(c.title)}` +
                        `&details=${encodeURIComponent(c.details)}`;
                };

                div.innerHTML =
                    `<div><i class="bi ${icon} care-icon" style="color:${borderColor}"></i>` +
                    `${c.title}${isFinished ? ' ✅' : ''}</div>` +
                    `<span class="status-not-updated">${isFinished ? 'Updated' : 'Pending'}</span>`;

                container.appendChild(div);
            });

        } catch (e) {
            container.innerHTML = `<p class="text-danger">Failed to load activities</p>`;
        }
    }

    renderClientProfileAndHighlight();
    renderActivities();
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') renderActivities();
    });