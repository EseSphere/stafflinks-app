self.onmessage = async e => {
    if (e.data?.type !== 'PROCESS_QUEUE') return;

    const openDB = () => new Promise((resolve, reject) => {
        const req = indexedDB.open('stafflinks', 2);
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject();
    });

    const db = await openDB();
    const tx = db.transaction('sync_queue', 'readwrite');
    const store = tx.objectStore('sync_queue');
    const all = await store.getAll();

    for (const item of all) {
        try {
            const res = await fetch('insert_shift.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(item.payload)
            });
            const result = await res.json();
            if (result.success) store.delete(item.queue_id);
        } catch {
            // remain queued
        }
    }
};
