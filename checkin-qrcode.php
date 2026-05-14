<style>
    #loadingOverlay {
        position: fixed;
        z-index: 9999;
        inset: 0;
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(4px);
        display: none;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 15px;
    }

    #loadingOverlay img {
        width: 350px;
        height: 350px;
    }

    #loadingOverlay h4,
    #loadingOverlay h1 {
        margin: 0;
        font-weight: bold;
        color: #333;
    }

    .overlay {
        pointer-events: none;
    }
</style>

<script src="https://unpkg.com/html5-qrcode"></script>
<link rel="stylesheet" type="text/css" href="./css/qrcode.css">

<div class="scanner-header">
    <h2>QR Code Check-In</h2>
    <p>Align the QR code within the square to check in</p>
</div>

<div id="qr-reader"></div>

<div class="overlay">
    <div class="scanner-frame"></div>
</div>

<div id="qr-reader-results"></div>

<div id="loading-indicator" class="loading" style="display:none;">
    <span></span><span></span><span></span>
</div>

<div id="loadingOverlay">
    <img src="./images/logo-gif.gif" alt="Loading...">
    <h1 class="fw-bold">Starting Shift...</h1>
</div>

<script>
(async function() {
    const dbName = "stafflinks";
    let html5QrCode = null;
    let isProcessingScan = false;
    let scannerStarted = false;

    const resultBox = document.getElementById("qr-reader-results");
    const loader = document.getElementById("loading-indicator");
    const loadingOverlay = document.getElementById("loadingOverlay");

    const logProgress = msg => console.log(msg);
    const getQueryParam = param => new URLSearchParams(window.location.search).get(param);

    const normalizeQrValue = value => {
        if (value == null) return '';
        return String(value).trim().replace(/\.png$/i, '');
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

        let match = str.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
        if (match) {
            const [, dd, mm, yyyy] = match;
            return `${yyyy}-${String(mm).padStart(2, '0')}-${String(dd).padStart(2, '0')}`;
        }

        match = str.match(/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/);
        if (match) {
            const [, yyyy, mm, dd] = match;
            return `${yyyy}-${String(mm).padStart(2, '0')}-${String(dd).padStart(2, '0')}`;
        }

        return '';
    };

    const todayDateOnly = () => formatDateOnly(new Date());

    function redirectToShiftPage(record, redirectUrl = 'activities.php') {
        window.location.href =
            `${redirectUrl}?uryyToeSS4=${encodeURIComponent(record.uryyToeSS4)}&Clientshift_Date=${encodeURIComponent(record.shift_date)}&care_calls=${encodeURIComponent(record.col_care_call)}&id=${encodeURIComponent(record.id)}&carerId=${encodeURIComponent(record.col_carer_Id)}`;
    }

    function openDB(name = dbName) {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(name);

            request.onupgradeneeded = e => {
                const db = e.target.result;

                if (!db.objectStoreNames.contains('tbl_daily_shift_records')) {
                    const store = db.createObjectStore('tbl_daily_shift_records', { keyPath: 'id' });
                    [
                        'id','shift_status','shift_date','planned_timeIn','planned_timeOut','shift_start_time',
                        'shift_end_time','client_name','uryyToeSS4','col_care_call','client_group','carer_Name',
                        'task_note','col_carer_Id','timesheet_date','col_area_Id','col_company_Id','col_call_status',
                        'col_carecall_rate','col_miles','col_mileage','col_worked_time','col_client_rate','col_client_payer',
                        'col_visit_status','col_visit_confirmation','col_care_call_Id','col_postcode','dateTime','col_synced'
                    ].forEach(c => store.createIndex(c, c, { unique: false }));
                }

                const stores = [
                    {
                        name: 'tbl_client_medical',
                        cols: ['client_id','medical_condition','medication','col_company_Id','dateTime']
                    },
                    {
                        name: 'tbl_general_client_form',
                        cols: [
                            'client_name','client_latitude','client_longitude','col_company_Id',
                            'client_poster_code','dateTime','client_area','uryyToeSS4','col_qrcode_path'
                        ]
                    },
                    {
                        name: 'tbl_schedule_calls',
                        cols: [
                            'uryyToeSS4','first_carer_Id','dateTime_in','dateTime_out','Clientshift_Date',
                            'pay_rate','client_rate','client_name','client_area','col_area_Id',
                            'col_company_Id','care_calls','call_status','col_call_status',
                            'first_carer','task_note','col_carecall_rate'
                        ]
                    },
                    {
                        name: 'tbl_general_team_form',
                        cols: ['first_carer','col_mileage','col_company_Id']
                    }
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

    const getAllFromStore = (db, storeName) =>
        new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, "readonly");
            const store = tx.objectStore(storeName);
            const req = store.getAll();
            req.onsuccess = () => resolve(req.result || []);
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
            } catch (err) {
                return reject(err);
            }
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

    const pushRecordToServer = async (db, row) => {
        try {
            const payload = { ...row };

            if (payload.col_miles) payload.col_miles = parseFloat(payload.col_miles);
            if (payload.col_mileage) payload.col_mileage = parseFloat(payload.col_mileage);
            if (payload.col_carer_Id) payload.col_carer_Id = parseInt(payload.col_carer_Id);

            const res = await fetch('insert_shift.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
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

    const syncWithServer = async db => {
        try {
            const res = await fetch('sync_shift.php');
            const result = await res.json();

            if (!result.success) {
                throw new Error(result.message || 'Fetch failed');
            }

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

    async function copyShiftRecord(db, visitId) {
        const visit = await getRecordById(db, 'tbl_schedule_calls', visitId);
        if (!visit) throw new Error('Visit not found.');

        const client = await getRecordById(db, 'tbl_general_client_form', visit.uryyToeSS4);

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
            col_miles: '0.00',
            col_mileage: '0.00',
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
        await updateCallStatus(db, visitId, 'in-progress');
        await pushRecordToServer(db, record);

        return record;
    }

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

    async function startShiftByVisitId(db, visitId) {
        if (!visitId) {
            throw new Error('No visit id provided.');
        }

        const visit = await getRecordById(db, 'tbl_schedule_calls', visitId);
        if (!visit) {
            throw new Error('Visit not found.');
        }

        const visitDate = formatDateOnly(visit.Clientshift_Date);
        const today = todayDateOnly();

        if (visitDate !== today) {
            throw new Error('Shift cannot be started because visit date is not today.');
        }

        const ongoing = await checkOngoingCall(db, today);

        if (ongoing) {
            const redirectUrl =
                (ongoing.id == visitId || ongoing.col_care_call_Id == visitId)
                    ? 'activities.php'
                    : 'ongoing-visit.php';

            redirectToShiftPage(ongoing, redirectUrl);
            return;
        }

        const record = await copyShiftRecord(db, visitId);
        redirectToShiftPage(record, 'activities.php');
    }

    async function findClientByScannedQr(db, scannedText) {
        const scannedValue = normalizeQrValue(scannedText);
        const clients = await getAllFromStore(db, 'tbl_general_client_form');

        const client = clients.find(c => {
            const qrPathValue = normalizeQrValue(c.col_qrcode_path);
            const clientSpec = normalizeQrValue(c.uryyToeSS4);

            return (
                scannedValue === qrPathValue ||
                scannedValue === clientSpec ||
                (qrPathValue && clientSpec && qrPathValue === clientSpec && scannedValue === clientSpec)
            );
        });

        return client || null;
    }

    async function processScannedQr(db, decodedText) {
        const urlVisitId = getQueryParam('id');

        if (!urlVisitId) {
            throw new Error('No selected visit id found in the URL.');
        }

        const scannedClient = await findClientByScannedQr(db, decodedText);
        if (!scannedClient) {
            throw new Error('QR code does not match any client record.');
        }

        const selectedVisit = await getRecordById(db, 'tbl_schedule_calls', urlVisitId);
        if (!selectedVisit) {
            throw new Error('Selected visit not found.');
        }

        if (String(selectedVisit.uryyToeSS4) !== String(scannedClient.uryyToeSS4)) {
            throw new Error('Scanned QR code does not match the selected visit client.');
        }

        await startShiftByVisitId(db, selectedVisit.id);
    }

    function getScannerConfig() {
        const qrBoxSize = Math.floor(Math.min(window.innerWidth, window.innerHeight) * 0.65);

        return {
            fps: 10,
            qrbox: { width: qrBoxSize, height: qrBoxSize },
            aspectRatio: window.innerWidth / window.innerHeight,
            rememberLastUsedCamera: true,
            supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
            formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE]
        };
    }

    async function stopScanner() {
        try {
            if (html5QrCode && scannerStarted) {
                await html5QrCode.stop();
                await html5QrCode.clear();
            }
        } catch (e) {
            console.error('Stop scanner error:', e);
        } finally {
            scannerStarted = false;
        }
    }

    async function startQRScanner(db) {
        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("qr-reader");
        }

        const onScanSuccess = async decodedText => {
            if (isProcessingScan) return;
            isProcessingScan = true;

            resultBox.style.display = "block";
            resultBox.innerText = "Scanned: " + decodedText;
            loader.style.display = "flex";
            loadingOverlay.style.display = "flex";

            try {
                await stopScanner();
                await processScannedQr(db, decodedText);
            } catch (err) {
                console.error('QR check-in failed:', err);

                loader.style.display = "none";
                loadingOverlay.style.display = "none";
                resultBox.innerText = "Error: " + err.message;
                isProcessingScan = false;

                try {
                    await startQRScanner(db);
                } catch (restartErr) {
                    console.error('Restart scanner failed:', restartErr);
                }
            }
        };

        const onScanFailure = () => {};

        try {
            const cameras = await Html5Qrcode.getCameras();

            if (!cameras || !cameras.length) {
                resultBox.style.display = "block";
                resultBox.innerText = "No camera found on this device.";
                return;
            }

            await html5QrCode.start(
                { facingMode: "environment" },
                getScannerConfig(),
                onScanSuccess,
                onScanFailure
            );

            scannerStarted = true;
        } catch (err) {
            console.error("Camera start failed:", err);

            try {
                const cameras = await Html5Qrcode.getCameras();

                if (cameras && cameras.length) {
                    await html5QrCode.start(
                        cameras[0].id,
                        getScannerConfig(),
                        onScanSuccess,
                        onScanFailure
                    );

                    scannerStarted = true;
                    return;
                }
            } catch (fallbackErr) {
                console.error("Fallback camera start failed:", fallbackErr);
            }

            resultBox.style.display = "block";
            resultBox.innerText = "Unable to start camera scanner.";
        }
    }

    try {
        const db = await openDB();
        await syncWithServer(db);

        console.log('Two-way sync active. Starting QR scanner...');
        await startQRScanner(db);

    } catch (err) {
        console.error('Check-in failed:', err);
        loadingOverlay.style.display = "none";
        loader.style.display = "none";
        resultBox.style.display = "block";
        resultBox.innerText = "Error: " + err.message;
    }
})();
</script>