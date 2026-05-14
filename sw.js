const CACHE_VERSION = 2;
const CACHE_NAME = `stafflinks-offline-v${CACHE_VERSION}`;
const OFFLINE_FALLBACK = 'index.php';
const MAX_CONCURRENT_FETCHES = 3;

const DB_NAME = 'stafflinks';
const DB_VERSION = 1;
const RETRY_STORE = 'retry-queue';

const cachedURLs = new Set();
const queue = [];
let activeFetches = 0;

function openDB() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, DB_VERSION);

        req.onupgradeneeded = e => {
            const db = e.target.result;
            if (!db.objectStoreNames.contains(RETRY_STORE)) {
                db.createObjectStore(RETRY_STORE, { keyPath: 'id', autoIncrement: true });
            }
        };

        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

async function enqueueRetry(request) {
    const db = await openDB();
    const tx = db.transaction(RETRY_STORE, 'readwrite');
    tx.objectStore(RETRY_STORE).add({
        url: request.url,
        method: request.method,
        headers: [...request.headers],
        body: request.method !== 'GET' ? await request.clone().text() : null,
        time: Date.now()
    });
}

async function replayRetries() {
    const db = await openDB();
    const tx = db.transaction(RETRY_STORE, 'readwrite');
    const store = tx.objectStore(RETRY_STORE);
    const all = store.getAll();

    all.onsuccess = async () => {
        for (const item of all.result) {
            try {
                await fetch(item.url, {
                    method: item.method,
                    headers: item.headers,
                    body: item.body
                });
                store.delete(item.id);
            } catch {}
        }
    };
}

self.addEventListener('install', e => {
    e.waitUntil(
        caches.open(CACHE_NAME)
            .then(c => c.add(OFFLINE_FALLBACK))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', e => {
    e.waitUntil(
        Promise.all([
            self.clients.claim(),
            caches.keys().then(keys =>
                Promise.all(
                    keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
                )
            )
        ])
    );
});

self.addEventListener('message', e => {
    if (e.data?.action === 'cacheLinks' && Array.isArray(e.data.links)) {
        e.data.links.forEach(enqueue);
        processQueue();
    }
});

function enqueue(url) {
    if (!cachedURLs.has(url)) {
        cachedURLs.add(url);
        queue.push(url);
    }
}

function processQueue() {
    while (activeFetches < MAX_CONCURRENT_FETCHES && queue.length) {
        const url = queue.shift();
        activeFetches++;
        cachePage(url).finally(() => {
            activeFetches--;
            processQueue();
        });
    }
}

async function cachePage(url) {
    try {
        const cache = await caches.open(CACHE_NAME);
        const response = await fetch(url);
        if (!response.ok) return;

        await cache.put(url, response.clone());
        const text = await response.text();

        const extract = r =>
            [...text.matchAll(r)].map(m => new URL(m[1], url).href);

        const links = [
            ...extract(/href\s*=\s*["'](.*?)["']/gi),
            ...extract(/<form[^>]*action\s*=\s*["'](.*?)["']/gi),
            ...extract(/window\.location(?:\.href)?\s*=\s*['"`](.*?)['"`]/gi),
            ...extract(/\.php\?[^"' >]+/gi)
        ];

        links
            .filter(l => l.startsWith(location.origin) && l.includes('.php'))
            .forEach(enqueue);

    } catch {}
}

self.addEventListener('fetch', event => {
    const req = event.request;

    event.respondWith(
        caches.match(req).then(cached => {
            return cached || fetch(req).then(res => {
                if (req.method === 'GET' && res.ok) {
                    caches.open(CACHE_NAME).then(c => c.put(req, res.clone()));
                }
                return res;
            }).catch(async () => {
                if (req.method !== 'GET') {
                    await enqueueRetry(req);
                    if ('sync' in self.registration) {
                        self.registration.sync.register('retry-sync');
                    }
                }
                if (req.headers.get('accept')?.includes('text/html')) {
                    return caches.match(OFFLINE_FALLBACK);
                }
            });
        })
    );
});

self.addEventListener('sync', e => {
    if (e.tag === 'retry-sync') {
        e.waitUntil(replayRetries());
    }
});
