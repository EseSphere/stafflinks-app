// checkin-geolocation.js
(async function () {
    const dbName = "stafflinks";
    let geoCache = null;
    let geoWatchId = null;

    const GEO_STORAGE_KEY = "stafflinks_last_geo";
    const GEO_PERMISSION_KEY = "stafflinks_geo_permission";
    const GEO_MAX_AGE_MS = 5 * 60 * 1000; // 5 minutes

    // --- Utils ---
    const logProgress = msg => console.log(msg);
    const getQueryParam = param => new URLSearchParams(window.location.search).get(param);

    const getDistanceMiles = (lat1, lon1, lat2, lon2) => {
        const R = 3958.8; // miles
        const toRad = deg => deg * Math.PI / 180;
        const dLat = toRad(lat2 - lat1), dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat / 2) ** 2 +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
            Math.sin(dLon / 2) ** 2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    };

    const formatDateOnly = value => {
        if (!value) return '';

        const date = new Date(value);
        if (!isNaN(date.getTime())) {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }

        const str = String(value).trim();

        // dd/mm/yyyy or dd-mm-yyyy
        let match = str.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
        if (match) {
            const [, dd, mm, yyyy] = match;
            return `${yyyy}-${String(mm).padStart(2, '0')}-${String(dd).padStart(2, '0')}`;
        }

        // yyyy/mm/dd or yyyy-mm-dd
        match = str.match(/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/);
        if (match) {
            const [, yyyy, mm, dd] = match;
            return `${yyyy}-${String(mm).padStart(2, '0')}-${String(dd).padStart(2, '0')}`;
        }

        return '';
    };

    const todayDateOnly = () => formatDateOnly(new Date());

    // --- Geolocation storage helpers ---
    function saveGeoToStorage(pos) {
        try {
            if (!pos?.coords) return;
            const payload = {
                latitude: pos.coords.latitude,
                longitude: pos.coords.longitude,
                accuracy: pos.coords.accuracy ?? null,
                timestamp: pos.timestamp || Date.now()
            };
            localStorage.setItem(GEO_STORAGE_KEY, JSON.stringify(payload));
            localStorage.setItem(GEO_PERMISSION_KEY, 'granted');
        } catch (err) {
            console.warn('Failed to save geolocation cache:', err);
        }
    }

    function readGeoFromStorage(maxAge = GEO_MAX_AGE_MS) {
        try {
            const raw = localStorage.getItem(GEO_STORAGE_KEY);
            if (!raw) return null;

            const parsed = JSON.parse(raw);
            if (
                !parsed ||
                typeof parsed.latitude !== 'number' ||
                typeof parsed.longitude !== 'number' ||
                !parsed.timestamp
            ) {
                return null;
            }

            const age = Date.now() - parsed.timestamp;
            if (age > maxAge) return null;

            return {
                coords: {
                    latitude: parsed.latitude,
                    longitude: parsed.longitude,
                    accuracy: parsed.accuracy
                },
                timestamp: parsed.timestamp
            };
        } catch (err) {
            console.warn('Failed to read geolocation cache:', err);
            return null;
        }
    }

    function clearStoredGeoPermission() {
        try {
            localStorage.removeItem(GEO_PERMISSION_KEY);
        } catch (err) {
            console.warn('Failed to clear geo permission cache:', err);
        }
    }

    function setStoredGeoPermission(state) {
        try {
            localStorage.setItem(GEO_PERMISSION_KEY, state);
        } catch (err) {
            console.warn('Failed to store geo permission state:', err);
        }
    }

    function getStoredGeoPermission() {
        try {
            return localStorage.getItem(GEO_PERMISSION_KEY);
        } catch (err) {
            return null;
        }
    }

    function cachePosition(pos) {
        geoCache = pos;
        saveGeoToStorage(pos);
    }

    // Start a silent watcher once permission is granted
    function startGeoWatcher() {
        if (geoWatchId !== null) return;
        if (!navigator.geolocation) return;

        try {
            geoWatchId = navigator.geolocation.watchPosition(
                pos => {
                    cachePosition(pos);
                },
                err => {
                    console.warn('watchPosition failed:', err.message);
                    if (err.code === 1) {
                        clearStoredGeoPermission();
                    }
                },
                {
                    enableHighAccuracy: true,
                    maximumAge: 60000,
                    timeout: 15000
                }
            );
        } catch (err) {
            console.warn('Unable to start geolocation watcher:', err);
        }
    }

    async function getGeoPermissionState() {
        try {
            if (!navigator.permissions || !navigator.permissions.query) {
                return getStoredGeoPermission() || 'unknown';
            }

            const status = await navigator.permissions.query({ name: 'geolocation' });
            const state = status.state;

            setStoredGeoPermission(state);

            status.onchange = () => {
                setStoredGeoPermission(status.state);
                if (status.state === 'granted') {
                    startGeoWatcher();
                } else if (status.state === 'denied') {
                    clearStoredGeoPermission();
                }
            };

            return state;
        } catch (err) {
            console.warn('Unable to read geolocation permission:', err);
            return getStoredGeoPermission() || 'unknown';
        }
    }

    function requestFreshPosition(options = {}) {
        return new Promise(resolve => {
            if (!navigator.geolocation) {
                console.warn('Geolocation not supported');
                return resolve(null);
            }

            navigator.geolocation.getCurrentPosition(
                pos => {
                    cachePosition(pos);
                    resolve(pos);
                },
                err => {
                    console.warn('Geolocation failed:', err.message);
                    if (err.code === 1) {
                        clearStoredGeoPermission();
                    }
                    resolve(null); // IMPORTANT: never reject
                },
                {
                    enableHighAccuracy: true,
                    timeout: 12000,
                    maximumAge: 60000,
                    ...options
                }
            );
        });
    }

    // --- Safe + Cached Geolocation ---
    async function getPositionSafe() {
        if (geoCache) return geoCache;

        const storedPos = readGeoFromStorage();
        if (storedPos) {
            geoCache = storedPos;

            // silently refresh in background if permission already granted
            getGeoPermissionState().then(state => {
                if (state === 'granted') {
                    startGeoWatcher();
                    requestFreshPosition({ maximumAge: 0 }).catch(() => { });
                }
            });

            return storedPos;
        }

        if (!navigator.geolocation) {
            console.warn('Geolocation not supported');
            return null;
        }

        const permissionState = await getGeoPermissionState();

        // If already granted, fetch silently and keep watcher alive
        if (permissionState === 'granted') {
            startGeoWatcher();
            return await requestFreshPosition();
        }

        // If denied, do not keep asking
        if (permissionState === 'denied') {
            console.warn('Geolocation permission denied');
            return null;
        }

        // First-time prompt only when permission is not yet decided
        const pos = await requestFreshPosition();
        if (pos) {
            setStoredGeoPermission('granted');
            startGeoWatcher();
        }
        return pos;
    }

    // Prefetch location in background once app loads if already allowed
    async function warmUpGeolocation() {
        try {
            const permissionState = await getGeoPermissionState();
            if (permissionState === 'granted') {
                const storedPos = readGeoFromStorage();
                if (storedPos) {
                    geoCache = storedPos;
                }
                startGeoWatcher();
                requestFreshPosition().catch(() => { });
            }
        } catch (err) {
            console.warn('Geolocation warm-up failed:', err);
        }
    }

    // --- Open IndexedDB ---
    function openDB(name = dbName) {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(name);
            request.onupgradeneeded = e => {
                const db = e.target.result;

                if (!db.objectStoreNames.contains('tbl_daily_shift_records')) {
                    const store = db.createObjectStore('tbl_daily_shift_records', { keyPath: 'id' });
                    [
                        'id', 'shift_status', 'shift_date', 'planned_timeIn', 'planned_timeOut', 'shift_start_time',
                        'shift_end_time', 'client_name', 'uryyToeSS4', 'col_care_call', 'client_group', 'carer_Name',
                        'task_note', 'col_carer_Id', 'timesheet_date', 'col_area_Id', 'col_company_Id', 'col_call_status',
                        'col_carecall_rate', 'col_miles', 'col_mileage', 'col_worked_time', 'col_client_rate', 'col_client_payer',
                        'col_visit_status', 'col_visit_confirmation', 'col_care_call_Id', 'col_postcode', 'dateTime', 'col_synced'
                    ].forEach(c => store.createIndex(c, c, { unique: false }));
                }

                const stores = [
                    { name: 'tbl_client_medical', cols: ['client_id', 'medical_condition', 'medication', 'col_company_Id', 'dateTime'] },
                    { name: 'tbl_general_client_form', cols: ['client_name', 'client_latitude', 'client_longitude', 'col_company_Id', 'client_poster_code', 'dateTime'] },
                    { name: 'tbl_schedule_calls', cols: ['uryyToeSS4', 'first_carer_Id', 'dateTime_in', 'dateTime_out', 'Clientshift_Date'] },
                    { name: 'tbl_general_team_form', cols: ['first_carer', 'col_mileage', 'col_company_Id'] }
                ];

                stores.forEach(s => {
                    if (!db.objectStoreNames.contains(s.name)) {
                        const store = db.createObjectStore(s.name, { keyPath: 'id' });
                        s.cols.forEach(c => store.createIndex(c, c, { unique: false }));
                    }
                });
            };
            request.onsuccess = e => resolve(e.target.result);
            request.onerror = e => reject(`Failed to open IndexedDB: ${e.target.error}`);
        });
    }

    // --- IndexedDB helpers ---
    const getAllFromStore = (db, storeName) =>
        new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, "readonly");
            const store = tx.objectStore(storeName);
            const req = store.getAll();
            req.onsuccess = () => resolve(req.result);
            req.onerror = e => reject(e.target.error);
        });

    async function getRecordById(db, storeName, key) {
        key = key != null && !isNaN(key) ? parseInt(key) : key;

        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, 'readonly');
            const store = tx.objectStore(storeName);

            const req = store.get(key);
            req.onsuccess = e => {
                if (e.target.result) return resolve(e.target.result);

                // fallback scan (safe, early exit)
                store.openCursor().onsuccess = e2 => {
                    const cursor = e2.target.result;
                    if (!cursor) return resolve(null);

                    const r = cursor.value;
                    if (
                        r.id == key ||
                        r.uryyToeSS4 == key ||
                        r.uryyTteamoeSS4 == key ||
                        r.first_carer_Id == key
                    ) {
                        return resolve(r);
                    }
                    cursor.continue();
                };
            };
            req.onerror = e => reject(e.target.error);
        });
    }

    const putRowInStore = (db, storeName, row) =>
        new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, 'readwrite');
            try {
                const store = tx.objectStore(storeName);
                store.put(row);
            } catch (err) { return reject(err); }
            tx.oncomplete = () => resolve();
            tx.onerror = e => reject(e.target.error);
        });

    const updateCallStatus = async (db, id, status) => {
        const record = await getRecordById(db, 'tbl_schedule_calls', id);
        if (record) {
            record.call_status = status;
            record.col_call_status = status;
            await putRowInStore(db, 'tbl_schedule_calls', record);
        }
    };

    // --- Main shift logic ---
    async function copyShiftRecord(db, id) {
        const visit = await getRecordById(db, 'tbl_schedule_calls', id);
        if (!visit) throw new Error('Visit not found.');

        const [client, carer, position] = await Promise.all([
            getRecordById(db, 'tbl_general_client_form', visit.uryyToeSS4),
            getRecordById(db, 'tbl_general_team_form', visit.first_carer_Id),
            getPositionSafe()
        ]);

        let miles = 0;
        if (
            position &&
            client &&
            client.client_latitude != null &&
            client.client_longitude != null
        ) {
            miles = getDistanceMiles(
                position.coords.latitude,
                position.coords.longitude,
                parseFloat(client.client_latitude || 0),
                parseFloat(client.client_longitude || 0)
            );
        }

        const totalMileage = (parseFloat(carer?.col_mileage || 0) * miles).toFixed(2);
        const now = new Date();
        const shiftStartTime = `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`;

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
            col_postcode: client?.client_poster_code ?? '',
            dateTime: now.toISOString(),
            col_synced: false
        };

        await putRowInStore(db, 'tbl_daily_shift_records', record);
        await updateCallStatus(db, id, 'in-progress');
        await pushRecordToServer(db, record);

        return record;
    }

    const pushRecordToServer = async (db, row) => {
        try {
            const payload = { ...row };
            if (payload.col_miles) payload.col_miles = parseFloat(payload.col_miles);
            if (payload.col_mileage) payload.col_mileage = parseFloat(payload.col_mileage);
            if (payload.col_carer_Id) payload.col_carer_Id = parseInt(payload.col_carer_Id);

            const res = await fetch('insert_shift.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            if (result.success) {
                row.col_synced = true;
                await putRowInStore(db, 'tbl_daily_shift_records', row);
            }
        } catch (err) {
            console.error('Error sending record to server:', err);
        }
    };

    const checkOngoingCall = async (db, shiftDate = null) => {
        const all = await getAllFromStore(db, 'tbl_daily_shift_records');
        let latest = null;
        const normalizedShiftDate = shiftDate ? formatDateOnly(shiftDate) : null;

        for (const r of all) {
            const recordShiftDate = formatDateOnly(r.shift_date);

            if (
                r.col_call_status === 'in-progress' &&
                (!normalizedShiftDate || recordShiftDate === normalizedShiftDate) &&
                (!latest || new Date(r.dateTime) > new Date(latest.dateTime))
            ) {
                latest = r;
            }
        }

        return latest;
    };

    const startShift = async db => {
        const id = getQueryParam('id');
        if (!id) return console.error('No id provided.');

        try {
            const visit = await getRecordById(db, 'tbl_schedule_calls', id);
            if (!visit) {
                return console.error('Visit not found.');
            }

            const visitDate = formatDateOnly(visit.Clientshift_Date);
            const today = todayDateOnly();

            // Start shift only if visit date is today
            if (visitDate !== today) {
                console.warn('Shift cannot be started because visit date is not today.', {
                    visitDate,
                    today
                });
                return;
            }

            // Check only for ongoing calls on the current date
            const ongoing = await checkOngoingCall(db, today);

            if (ongoing) {
                const redirectUrl =
                    (ongoing.id == id || ongoing.col_care_call_Id == id)
                        ? 'activities.php'
                        : 'ongoing-visit.php';

                return window.location.href =
                    `${redirectUrl}?uryyToeSS4=${encodeURIComponent(ongoing.uryyToeSS4)}&Clientshift_Date=${encodeURIComponent(ongoing.shift_date)}&care_calls=${encodeURIComponent(ongoing.col_care_call)}&id=${encodeURIComponent(ongoing.id)}&carerId=${encodeURIComponent(ongoing.col_carer_Id)}`;
            }

            const record = await copyShiftRecord(db, id);

            window.location.href =
                `activities.php?uryyToeSS4=${encodeURIComponent(record.uryyToeSS4)}&Clientshift_Date=${encodeURIComponent(record.shift_date)}&care_calls=${encodeURIComponent(record.col_care_call)}&id=${encodeURIComponent(record.id)}&carerId=${encodeURIComponent(record.col_carer_Id)}`;

        } catch (err) {
            console.error('Error starting shift:', err);
        }
    };

    const syncWithServer = async db => {
        try {
            const res = await fetch('sync_shift.php');
            const result = await res.json();
            if (!result.success) throw new Error(result.message || 'Fetch failed');

            await Promise.all(result.data.map(row =>
                putRowInStore(db, 'tbl_daily_shift_records', {
                    ...row,
                    dateTime: row.dateTime ? new Date(row.dateTime).toISOString() : undefined,
                    col_synced: true
                })
            ));
            logProgress(`Fetched ${result.data.length} records from server`);
        } catch (err) {
            console.error('Error fetching from server:', err);
        }
    };

    // --- Execute ---
    const db = await openDB();
    await syncWithServer(db);
    await warmUpGeolocation();
    console.log('Two-way sync active. Starting shift...');
    await startShift(db);
})();