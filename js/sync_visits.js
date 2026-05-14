(function() {
    const MIN_SYNC_TIME = 5000;
    const dbName = "stafflinks";

    function logProgress(completed, total) {
        const percent = Math.floor((completed / total) * 100);
        console.log(`Sync progress: ${percent}% (${completed}/${total} tables)`);
    }

    function openDB(name) {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(name);
            request.onsuccess = () => resolve(request.result);
            request.onerror = (e) => reject(`Failed to open IndexedDB: ${e.target.error}`);
        });
    }

    function getAllFromStore(db, storeName) {
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, "readonly");
            const store = tx.objectStore(storeName);
            const getAllRequest = store.getAll();
            getAllRequest.onsuccess = () => resolve(getAllRequest.result);
            getAllRequest.onerror = (e) => reject(`Failed to get data from ${storeName}: ${e.target.error}`);
        });
    }

    function putRowsInStore(db, storeName, rows) {
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, "readwrite");
            const store = tx.objectStore(storeName);

            try {
                for (const row of rows) {
                    if (row.id === undefined || row.id === null) {
                        console.warn(`Skipping row in ${storeName} due to invalid id:`, row);
                        continue;
                    }

                    if (storeName === "tbl_schedule_calls") {
                        const rowStatus = (row.call_status || "").toLowerCase();

                        // Skip insert if row's call_status is Completed or In-Progress
                        if (rowStatus === "completed" || rowStatus === "in-progress") {
                            console.log(`Skipping row ID ${row.id} in ${storeName} (call_status = ${row.call_status})`);
                            continue;
                        }

                        const existingRequest = store.get(row.id);
                        existingRequest.onsuccess = (event) => {
                            const existing = event.target.result;
                            const existingStatus = (existing?.call_status || "").toLowerCase();

                            if (existing && (existingStatus === "completed" || existingStatus === "in-progress")) {
                                console.log(`Skipping update for ID ${row.id} in ${storeName} (existing call_status = ${existing.call_status})`);
                            } else {
                                store.put(row);
                            }
                        };
                        existingRequest.onerror = (e) => {
                            console.warn(`Failed to check existing record in ${storeName}: ${e.target.error}`);
                        };
                    } else {
                        // Normal insert/update for other tables
                        store.put(row);
                    }
                }
            } catch (err) {
                return reject(`Error inserting row in ${storeName}: ${err}`);
            }

            tx.oncomplete = () => resolve();
            tx.onerror = (e) => reject(`Transaction error on ${storeName}: ${e.target.error}`);
        });
    }

    async function runSync() {
        try {
            const startTime = Date.now();
            const db = await openDB(dbName);
            console.log('IndexedDB opened:', db);

            const requiredStores = ['tbl_team_account', 'tbl_cancelled_call', 'tbl_client_status_records', 'tbl_schedule_calls'];
            for (const store of requiredStores) {
                if (!db.objectStoreNames.contains(store)) {
                    console.warn(`Warning: Object store '${store}' not found. Skipping.`);
                }
            }

            const users = await getAllFromStore(db, 'tbl_team_account');
            console.log('Users from IndexedDB:', users);

            if (!users || users.length === 0) throw new Error("No users found in 'tbl_team_account'");

            const companyId = users[0].col_company_Id;
            if (!companyId) throw new Error("Company ID not found for user");

            console.log(`Starting synchronization for company ID: ${companyId}`);

            const response = await fetch(`sync_visits.php?company_id=${companyId}`);
            console.log('Fetch response status:', response.status);

            if (!response.ok) throw new Error(`Fetch failed with status ${response.status}`);

            const serverData = await response.json();
            console.log('Server data received:', serverData);

            const tableNames = ['tbl_cancelled_call', 'tbl_client_status_records', 'tbl_schedule_calls'];

            let completed = 0;
            for (const tableName of tableNames) {
                if (serverData[tableName] && db.objectStoreNames.contains(tableName)) {
                    await putRowsInStore(db, tableName, serverData[tableName]);
                    console.log(`Table synced: ${tableName}`);
                } else {
                    console.warn(`Skipping table '${tableName}' (no data or object store missing)`);
                }
                completed++;
                logProgress(completed, tableNames.length);
            }

            const elapsed = Date.now() - startTime;
            const remaining = MIN_SYNC_TIME - elapsed;
            setTimeout(() => console.log('Synchronization complete'), remaining > 0 ? remaining : 0);

        } catch (err) {
            console.error('Synchronization failed:', err);
        }
    }

    // Run immediately if online
    if (navigator.onLine) {
        runSync();
    } else {
        console.warn('No internet connection. Waiting to sync when online...');
    }

    // Retry automatically when the user goes online
    window.addEventListener('online', () => {
        console.log('Internet connection restored. Running synchronization...');
        runSync();
    });
})();
