<?php require_once('header-log.php'); ?>

<div class="container-fluid" id="splash-screen" style="height:100vh; margin-top:-130px; display:flex; flex-direction:column; justify-content:center; align-items:center;">
    <div id="splash-logo" class="img-logo">
        <img id="stafflinks-logo" src="./images/logo.png" alt="StaffLinks Care Logo" style="width: 280px; height: 80px; opacity: 1; transition: opacity 1s ease;">
    </div>
    <div style="width: 80%;">
        <div style="margin:0% auto; display: flex; justify-content: center; align-items: center; text-align: left; 
            width: 60%; background-color: #ddd; border-radius: 10px; overflow: hidden; height: auto;">
            <div id="progress-bar" style="width: 15%; height: auto; background-color: #A72218; text-align: left; line-height: 15px; color: white; font-weight: bold; font-size:12px; border-radius: 10px; padding:2px 2px 2px 5px; transition: width 0.5s ease-in-out;">
                0%
            </div>
        </div>
    </div>
    <div id="progress-text" style="margin-top: 10px; font-weight: bold;">Initializing...</div>
</div>

<script>
    // Fade-in logo
    document.addEventListener("DOMContentLoaded", () => {
        const logo = document.getElementById("stafflinks-logo");
        setTimeout(() => {
            logo.style.opacity = 1;
        }, 300); // small delay before fade-in
    });

    (async function() {
        const startTime = Date.now();
        const MIN_SYNC_TIME = 5000;
        const dbName = "stafflinks";
        const DB_VERSION = 2;

        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');

        function updateProgress(completed, total) {
            const percent = Math.floor((completed / total) * 100);
            progressBar.style.width = percent + '%'; // animated due to CSS transition
            progressBar.innerText = percent + '%';
            progressText.innerText = `Getting everything ready`;
        }

        function openDB(name, version) {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open(name, version);
                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject("Failed to open IndexedDB");
            });
        }

        function getAllFromStore(db, storeName) {
            return new Promise((resolve, reject) => {
                const tx = db.transaction(storeName, "readonly");
                const store = tx.objectStore(storeName);
                const getAllRequest = store.getAll();
                getAllRequest.onsuccess = () => resolve(getAllRequest.result);
                getAllRequest.onerror = () => reject(`Failed to get data from ${storeName}`);
            });
        }

        function putRowsInStore(db, storeName, rows) {
            return new Promise((resolve) => {
                const tx = db.transaction(storeName, "readwrite");
                const store = tx.objectStore(storeName);
                store.clear().onsuccess = function() {
                    for (const row of rows) {
                        try {
                            store.put(row);
                        } catch (err) {
                            console.error(`Failed to put row in ${storeName}:`, err, row);
                        }
                    }
                };
                tx.oncomplete = () => resolve();
                tx.onerror = () => resolve();
            });
        }

        try {
            const db = await openDB(dbName, DB_VERSION);

            if (!db.objectStoreNames.contains('tbl_team_account')) {
                alert("User account data not found in IndexedDB!");
                window.location.href = "./login";
                return;
            }

            const users = await getAllFromStore(db, 'tbl_team_account');
            if (!users || users.length === 0) {
                alert("No user found in local IndexedDB!");
                window.location.href = "./login";
                return;
            }

            const companyId = users[0].col_company_Id;

            progressText.innerText = "Getting everything ready";

            const response = await fetch(`synchronizer_backend.php?company_id=${companyId}`);
            const serverData = await response.json();

            const keyPaths = {
                'tbl_cancelled_call': 'id',
                'tbl_clients_medication_records': 'id',
                'tbl_client_status_records': 'id',
                'tbl_clients_task_records': 'id',
                'tbl_daily_shift_records': 'id',
                'tbl_finished_meds': 'id',
                'tbl_finished_tasks': 'id',
                'tbl_general_client_form': 'id',
                'tbl_manage_runs': 'id',
                'tbl_client_medical': 'id',
                'tbl_future_planning': 'id',
                'tbl_general_team_form': 'id',
                'tbl_schedule_calls': 'id'
            };

            const tableNames = Object.keys(serverData);

            // Create object stores if not exist
            const version = db.version + 1;
            db.close();
            const upgradeDB = await new Promise((resolve, reject) => {
                const req = indexedDB.open(dbName, version);
                req.onupgradeneeded = (e) => {
                    const upgradeDb = e.target.result;
                    for (const tableName of tableNames) {
                        if (!upgradeDb.objectStoreNames.contains(tableName)) {
                            const keyPath = keyPaths[tableName] || 'id';
                            upgradeDb.createObjectStore(tableName, {
                                keyPath
                            });
                        }
                    }
                };
                req.onsuccess = () => resolve(req.result);
                req.onerror = () => reject("Failed to upgrade IndexedDB");
            });

            // Insert tables with animated progress
            let completed = 0;
            for (const tableName of tableNames) {
                await putRowsInStore(upgradeDB, tableName, serverData[tableName]);
                completed++;
                updateProgress(completed, tableNames.length);
            }

            const elapsed = Date.now() - startTime;
            const remaining = MIN_SYNC_TIME - elapsed;
            setTimeout(() => window.location.href = "./login", remaining > 0 ? remaining : 0);

        } catch (err) {
            console.error(err);
            alert("❌ Synchronization failed. Please try again.");
            window.location.href = "./login";
        }

    })();
</script>

<?php require_once('footer-log.php'); ?>