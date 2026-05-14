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
                    store.put(row);
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

            if (!db.objectStoreNames.contains('tbl_general_client_form')) {
                console.warn(`Object store 'tbl_general_client_form' not found. Sync aborted.`);
                return;
            }

            // Get company ID from local IndexedDB if exists
            const users = db.objectStoreNames.contains('tbl_team_account') 
                ? await getAllFromStore(db, 'tbl_team_account') 
                : [];
            
            if (!users.length) {
                console.error("No company ID found. Make sure 'tbl_team_account' exists and has 'col_company_Id'.");
                return;
            }

            const companyId = users[0].col_company_Id;
            console.log(`Starting synchronization for company ID: ${companyId}`);

            const response = await fetch(`sync_care_plan.php?company_id=${companyId}`);
            console.log('Fetch response status:', response.status);

            if (!response.ok) throw new Error(`Fetch failed with status ${response.status}`);

            const serverData = await response.json();
            console.log('Server data received:', serverData);

            const tableName = 'tbl_general_client_form';
            if (serverData[tableName]) {
                await putRowsInStore(db, tableName, serverData[tableName]);
                console.log(`Table synced: ${tableName}`);
                logProgress(1, 1);
            }

            const elapsed = Date.now() - startTime;
            const remaining = MIN_SYNC_TIME - elapsed;
            setTimeout(() => console.log('Synchronization complete'), remaining > 0 ? remaining : 0);

        } catch (err) {
            console.error('Synchronization failed:', err);
        }
    }

    if (navigator.onLine) {
        runSync();
    } else {
        console.warn('No internet connection. Waiting to sync when online...');
    }

    window.addEventListener('online', () => {
        console.log('Internet connection restored. Running synchronization...');
        runSync();
    });
})();
