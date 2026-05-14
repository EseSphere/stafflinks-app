const CACHE_VERSION = 'v4';
const CACHE_NAME = `stafflinks-offline-${CACHE_VERSION}`;
const OFFLINE_FALLBACK = 'index.php';
const MAX_CONCURRENT_FETCHES = 4;

const cachedURLs = new Set();
const queue = [];
const priorityQueue = [];
let active = 0;

const POST_DB = 'stafflinks-posts';
const POST_STORE = 'queue';
const HIGH_PRIORITY_PATTERNS = [/care-plan/, /dashboard/];

function openPostDB() {
    return new Promise(res => {
        const r = indexedDB.open(POST_DB, 1);
        r.onupgradeneeded = e =>
            e.target.result.createObjectStore(POST_STORE, { keyPath: 'id', autoIncrement: true });
        r.onsuccess = () => res(r.result);
    });
}

self.addEventListener('install', e => {
    e.waitUntil(caches.open(CACHE_NAME).then(c => c.add(OFFLINE_FALLBACK)));
    self.skipWaiting();
});

self.addEventListener('activate', e => {
    e.waitUntil(caches.keys().then(keys =>
        Promise.all(keys.filter(k => k.startsWith('stafflinks-offline-') && k !== CACHE_NAME)
            .map(k => caches.delete(k))
        )
    ));
    self.clients.claim();
});

self.addEventListener('message', e => {
    if (e.data?.action === 'cacheLinks') {
        e.data.links.forEach(url => enqueue(url));
        processQueue();
    }
});

function enqueue(url) {
    if (!url) return;
    url = normalizeURL(url);
    if (!url || cachedURLs.has(url)) return;
    cachedURLs.add(url);
    if (HIGH_PRIORITY_PATTERNS.some(rx => rx.test(url))) priorityQueue.push(url);
    else queue.push(url);
}

function processQueue() {
    while (active < MAX_CONCURRENT_FETCHES && (priorityQueue.length || queue.length)) {
        const url = priorityQueue.length ? priorityQueue.shift() : queue.shift();
        active++;
        cacheAndCrawl(url).finally(() => {
            active--;
            processQueue();
        });
    }
}

function normalizeURL(url) {
    try { return new URL(url, self.location.href).href.split('#')[0]; } catch { return null; }
}

async function cacheAndCrawl(url) {
    try {
        const cache = await caches.open(CACHE_NAME);
        if (await cache.match(url)) return;
        const res = await fetch(url, { credentials: 'same-origin' });
        if (!res.ok) return;
        await cache.put(url, res.clone());
        if (!res.headers.get('content-type')?.includes('text/html')) return;
        const text = await res.text();

        [...text.matchAll(/<(a|form|script)[^>]*(?:href|action|src)\s*=\s*["']([^"']+)["']/gi)]
            .map(m => normalizeURL(m[2], url))
            .filter(Boolean)
            .forEach(enqueue);

        [...text.matchAll(/<script[^>]*>([\s\S]*?)<\/script>/gi)]
            .map(m => extractJSLinks(m[1], url))
            .forEach(set => set.forEach(enqueue));

        [...text.matchAll(/href\s*=\s*["'](\/[a-zA-Z0-9\-]+)["']/gi)]
            .map(m => normalizeURL(m[1], url))
            .filter(Boolean)
            .forEach(enqueue);

    } catch {}
}

function extractJSLinks(code, baseUrl) {
    const urls = new Set();
    [...code.matchAll(/`([^`]*\?.*?)`/gs)].forEach(m => urls.add(normalizeURL(m[1], baseUrl)));
    [...code.matchAll(/(['"])([^'"]+\?.*?)\1\s*\+/g)].forEach(m => urls.add(normalizeURL(m[2], baseUrl)));
    [...code.matchAll(/['"`](\/[a-zA-Z0-9\-_]+)['"`]/g)].forEach(m => urls.add(normalizeURL(m[1], baseUrl)));
    return urls;
}

self.addEventListener('sync', e => { if (e.tag === 'stafflinks-sync') e.waitUntil(processPostQueue()); });

async function processPostQueue() {
    const db = await openPostDB();
    const tx = db.transaction(POST_STORE, 'readwrite');
    const store = tx.objectStore(POST_STORE);
    const all = store.getAll();
    return new Promise(res => {
        all.onsuccess = async () => {
            for (const item of all.result) {
                try { await fetch(item.url, { method: 'POST', headers: item.headers, body: item.body }); store.delete(item.id); } 
                catch { return res(); }
            }
            res();
        };
    });
}

self.addEventListener('fetch', e => {
    const req = e.request;
    if (req.method === 'POST') {
        e.respondWith(fetch(req.clone()).catch(async () => {
            const db = await openPostDB();
            const store = db.transaction(POST_STORE, 'readwrite').objectStore(POST_STORE);
            store.add({ url: req.url, headers: [...req.headers], body: await req.clone().text() });
            if ('sync' in self.registration) self.registration.sync.register('stafflinks-sync');
            return new Response(JSON.stringify({ queued: true, offline: true }), { headers: { 'Content-Type': 'application/json' } });
        }));
        return;
    }
    e.respondWith(caches.match(req).then(cached =>
        cached || fetch(req).then(res => { if (res.ok) caches.open(CACHE_NAME).then(c => c.put(req, res.clone())); return res; })
        .catch(() => req.headers.get('accept')?.includes('text/html') ? caches.match(OFFLINE_FALLBACK) : undefined)
    ));
});
