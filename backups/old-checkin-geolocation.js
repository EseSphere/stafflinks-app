(async function() {
    const dbName = "stafflinks";

    function logProgress(message) { console.log(message); }

    function openDB(name = dbName) {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(name);
            request.onupgradeneeded = e => {
                const db = e.target.result;
                if (!db.objectStoreNames.contains('tbl_daily_shift_records')) {
                    const store = db.createObjectStore('tbl_daily_shift_records', { keyPath: 'id' });
                    const columns = [
                        'id','shift_status','shift_date','planned_timeIn','planned_timeOut','shift_start_time',
                        'shift_end_time','client_name','uryyToeSS4','col_care_call','client_group','carer_Name',
                        'task_note','col_carer_Id','timesheet_date','col_area_Id','col_company_Id','col_call_status',
                        'col_carecall_rate','col_miles','col_mileage','col_worked_time','col_client_rate','col_client_payer',
                        'col_visit_status','col_visit_confirmation','col_care_call_Id','col_postcode','dateTime','col_synced'
                    ];
                    columns.forEach(c => store.createIndex(c, c, { unique: false }));
                }
                const stores = [
                    {name:'tbl_client_medical', cols:['client_id','medical_condition','medication','col_company_Id','dateTime']},
                    {name:'tbl_general_client_form', cols:['client_name','client_latitude','client_longitude','col_company_Id','client_poster_code','dateTime']},
                    {name:'tbl_schedule_calls', cols:['uryyToeSS4','first_carer_Id','dateTime_in','dateTime_out','Clientshift_Date']},
                    {name:'tbl_general_team_form', cols:['first_carer','col_mileage','col_company_Id']}
                ];
                stores.forEach(s=>{
                    if (!db.objectStoreNames.contains(s.name)) {
                        const store = db.createObjectStore(s.name,{keyPath:'id'});
                        s.cols.forEach(c=>store.createIndex(c,c,{unique:false}));
                    }
                });
            };
            request.onsuccess = e => resolve(e.target.result);
            request.onerror = e => reject(`Failed to open IndexedDB: ${e.target.error}`);
        });
    }

    function getAllFromStore(db, storeName) {
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, "readonly");
            const store = tx.objectStore(storeName);
            const req = store.getAll();
            req.onsuccess = () => resolve(req.result);
            req.onerror = e => reject(`Failed to get data from ${storeName}: ${e.target.error}`);
        });
    }

    function putRowInStore(db, storeName, row) {
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, "readwrite");
            const store = tx.objectStore(storeName);
            try { if (row.id === undefined || row.id === null) return reject('Row has invalid id'); store.put(row); }
            catch (err) { return reject(err); }
            tx.oncomplete = () => resolve();
            tx.onerror = e => reject(e.target.error);
        });
    }

    function getQueryParam(param) { return new URLSearchParams(window.location.search).get(param); }

    function getDistanceMiles(lat1, lon1, lat2, lon2) {
        const R = 3958.8, toRad = deg => deg * Math.PI / 180;
        const dLat = toRad(lat2 - lat1), dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    async function getRecordById(storeName, key) {
        const db = await openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, 'readonly');
            const store = tx.objectStore(storeName);
            const req = store.getAll();
            req.onsuccess = e => {
                const record = e.target.result.find(r => r.id == key || r.uryyToeSS4 == key || r.uryyTteamoeSS4 == key);
                resolve(record || null);
            };
            req.onerror = e => reject(e.target.error);
        });
    }

    async function updateCallStatus(id, status) {
        const db = await openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('tbl_schedule_calls', 'readwrite');
            const store = tx.objectStore('tbl_schedule_calls');
            const getAllReq = store.getAll();
            getAllReq.onsuccess = e => {
                const all = e.target.result || [];
                const rec = all.find(r => r.id == id || r.id == Number(id) || r.uryyToeSS4 == id || r.uryyTteamoeSS4 == id);
                if (rec) { rec.call_status = status; rec.col_call_status = status; store.put(rec); }
            };
            getAllReq.onerror = ev => reject(ev.target.error);
            tx.oncomplete = () => resolve(true);
            tx.onerror = ev => reject(ev.target.error);
        });
    }

    async function copyShiftRecord(id) {
        const visit = await getRecordById('tbl_schedule_calls', id);
        if (!visit) throw new Error('Visit not found.');
        const client = await getRecordById('tbl_general_client_form', visit.uryyToeSS4);
        if (!client) throw new Error('Client info not found.');
        const carer = await getRecordById('tbl_general_team_form', visit.first_carer_Id);
        if (!carer) throw new Error('Carer info not found.');

        const position = await new Promise((resolve, reject) => navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true }));
        const miles = getDistanceMiles(position.coords.latitude, position.coords.longitude, parseFloat(client.client_latitude||0), parseFloat(client.client_longitude||0));
        const totalMileage = (parseFloat(carer.col_mileage||0) * parseFloat(miles.toFixed(2))).toFixed(2);
        const now = new Date();
        const shiftStartTime = `${now.getHours().toString().padStart(2,'0')}:${now.getMinutes().toString().padStart(2,'0')}`;

        const record = {
            id: visit.id,
            shift_status: 'Checked in',
            shift_date: visit.Clientshift_Date || '',
            planned_timeIn: visit.dateTime_in || '',
            planned_timeOut: visit.dateTime_out || '',
            shift_start_time: shiftStartTime,
            shift_end_time: '',
            client_name: visit.client_name || '',
            uryyToeSS4: visit.uryyToeSS4 || '',
            col_care_call: visit.care_calls || '',
            client_group: visit.client_area || '',
            carer_Name: visit.first_carer || '',
            task_note: visit.task_note || '',
            col_carer_Id: visit.first_carer_Id || '',
            timesheet_date: '',
            col_area_Id: visit.col_area_Id || '',
            col_company_Id: visit.col_company_Id || '',
            col_call_status: 'in-progress',
            col_carecall_rate: visit.col_carecall_rate || '',
            col_miles: miles.toFixed(2),
            col_mileage: totalMileage,
            col_worked_time: '',
            col_client_rate: '',
            col_client_payer: '',
            sync_status: 'pending',
            col_visit_status: 'True',
            col_visit_confirmation: 'Unconfirmed',
            col_care_call_Id: visit.id,
            col_postcode: (client.client_poster_code||client.client_poster_code===0)?client.client_poster_code:'',
            dateTime: new Date().toISOString(),
            col_synced: false
        };

        const db = await openDB();
        await putRowInStore(db, 'tbl_daily_shift_records', record);
        await updateCallStatus(id, 'in-progress');

        await pushRecordToServer(record);

        return record;
    }

    async function pushRecordToServer(row) {
        try {
            if (row.col_miles) row.col_miles = parseFloat(row.col_miles);
            if (row.col_mileage) row.col_mileage = parseFloat(row.col_mileage);
            if (row.col_carer_Id) row.col_carer_Id = parseInt(row.col_carer_Id);

            const res = await fetch('insert_shift.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(row) });
            const result = await res.json();
            if (!result.success) console.error('Insert failed:', result.message);
            else {
                row.col_synced = true;
                const db = await openDB();
                await putRowInStore(db,'tbl_daily_shift_records',row);
            }
        } catch(err) { console.error('Error sending record to server:', err); }
    }

    async function checkOngoingCall() {
        const db = await openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('tbl_daily_shift_records', 'readonly');
            const store = tx.objectStore('tbl_daily_shift_records');
            const req = store.getAll();
            req.onsuccess = e => {
                const all = e.target.result || [];
                if (!all.length) return resolve(null);
                const recent = all.sort((a,b)=>new Date(b.dateTime)-new Date(a.dateTime))[0];
                resolve(recent.col_call_status==='in-progress'?recent:null);
            };
            req.onerror = ev => reject(ev.target.error);
        });
    }

    async function startShift() {
    const id = getQueryParam('id');
    if (!id) return console.error('No id provided.');

    try {
        const ongoing = await checkOngoingCall();
        if (ongoing) {
            const redirectUrl = (ongoing.id == id || ongoing.col_care_call_Id == id) ? 'activities.php' : 'ongoing-visit.php';
            window.location.href = `${redirectUrl}?uryyToeSS4=${encodeURIComponent(ongoing.uryyToeSS4)}&Clientshift_Date=${encodeURIComponent(ongoing.shift_date)}&care_calls=${encodeURIComponent(ongoing.col_care_call)}&id=${encodeURIComponent(ongoing.id)}&carerId=${encodeURIComponent(ongoing.col_carer_Id)}`;
            return;
        }

        const record = await copyShiftRecord(id);
        window.location.href = `activities.php?uryyToeSS4=${encodeURIComponent(record.uryyToeSS4)}&Clientshift_Date=${encodeURIComponent(record.shift_date)}&care_calls=${encodeURIComponent(record.col_care_call)}&id=${encodeURIComponent(record.id)}&carerId=${encodeURIComponent(record.col_carer_Id)}`;

    } catch (err) {
        console.error('Error starting shift:', err);
    }
}

    async function syncWithServer() {
        const db = await openDB();
        try {
            const res = await fetch('sync_shift.php');
            const result = await res.json();
            if (!result.success) throw new Error(result.message||'Fetch failed');
            for(const row of result.data) {
                if(row.dateTime) row.dateTime = new Date(row.dateTime).toISOString();
                row.col_synced = true;
                await putRowInStore(db,'tbl_daily_shift_records',row);
            }
            logProgress(`Fetched ${result.data.length} records from server`);
        } catch(err) { console.error('Error fetching from server:',err); }
    }

    await syncWithServer();
    console.log('Two-way sync active. Starting shift...');
    await startShift();
})();
