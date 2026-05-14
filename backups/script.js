if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('./sw.js')
        .then(() => console.log('Service Worker Registered'))
        .catch(err => console.error('SW registration failed:', err));
}

const dynamicValues = {
    id: [1, 2, 3],
    clientId: ['a', 'b'],
    Clientshift_Date: ['2024-01-01']
};

const PRIORITY_PATTERNS = [/care-plan/, /dashboard/];

function normalizeURL(url) {
    try {
        const u = new URL(url, location.href);
        if (u.origin !== location.origin) return null;
        return u.href.split('#')[0];
    } catch { return null; }
}

function stripDynamicParts(str) {
    return str.replace(/\$\{[^}]+\}/g, '').replace(/encodeURIComponent\([^)]*\)/g, '');
}

function extractURLsFromJS(code) {
    const urls = new Set();
    [...code.matchAll(/`([^`]*\?.*?)`/gs)].forEach(m => {
        const u = normalizeURL(stripDynamicParts(m[1]));
        if (u) urls.add(u);
    });
    [...code.matchAll(/(['"])([^'"]+\?.*?)\1\s*\+/g)].forEach(m => {
        const u = normalizeURL(m[2]);
        if (u) urls.add(u);
    });
    [
        /location(?:\.href|\.assign|\.replace)?\s*=\s*['"`]([^'"`]+)['"`]/g,
        /fetch\s*\(\s*['"`]([^'"`]+)['"`]/g,
        /axios\.(?:get|post)\s*\(\s*['"`]([^'"`]+)['"`]/g,
        /xhr\.open\s*\(\s*['"`]\w+['"`]\s*,\s*['"`]([^'"`]+)['"`]/g
    ].forEach(rx => {
        [...code.matchAll(rx)].forEach(m => {
            const u = normalizeURL(m[1]);
            if (u) urls.add(u);
        });
    });
    [...code.matchAll(/['"`](\/[a-zA-Z0-9\-_]+)['"`]/g)].forEach(m => {
        const u = normalizeURL(m[1]);
        if (u) urls.add(u);
    });
    return urls;
}

function expandDynamicTemplates(code) {
    const urls = new Set();
    [...code.matchAll(/`([^`]*\${(\w+)}[^`]*)`/g)].forEach(m => {
        const base = m[1], varName = m[2];
        if (dynamicValues[varName]) {
            dynamicValues[varName].forEach(val => {
                const u = normalizeURL(base.replace(/\$\{\w+\}/g, val));
                if (u) urls.add(u);
            });
        } else {
            const u = normalizeURL(base.replace(/\$\{\w+\}/g, ''));
            if (u) urls.add(u);
        }
    });
    return urls;
}

function collectAllLinks() {
    const urls = new Set();
    document.querySelectorAll('a[href], form[action], [data-href], script[src]').forEach(el => {
        const raw = el.getAttribute('href') || el.getAttribute('action') || el.dataset.href || el.src;
        const u = normalizeURL(raw);
        if (u) urls.add(u);
    });
    document.querySelectorAll('script').forEach(script => {
        const code = script.textContent || '';
        extractURLsFromJS(code).forEach(u => urls.add(u));
        expandDynamicTemplates(code).forEach(u => urls.add(u));
    });
    document.querySelectorAll('[href],[action],[data-href]').forEach(el => {
        const attr = el.getAttribute('href') || el.getAttribute('action') || el.dataset.href;
        if (attr && !attr.includes('.php') && !attr.includes('.html')) {
            const u = normalizeURL(attr);
            if (u) urls.add(u);
        }
    });
    return [...urls];
}

async function getIndexedDBStaffLinks() {
    return new Promise(resolve => {
        const req = indexedDB.open('stafflinks');
        req.onerror = () => resolve([]);
        req.onsuccess = () => {
            const db = req.result;
            if (!db.objectStoreNames.contains('staff')) return resolve([]);
            const tx = db.transaction('staff', 'readonly');
            const store = tx.objectStore('staff');
            const getAll = store.getAll();
            getAll.onsuccess = () => {
                resolve(
                    getAll.result
                        .map(r => r.profile_url || r.link)
                        .filter(Boolean)
                        .map(normalizeURL)
                        .filter(Boolean)
                );
            };
        };
    });
}

let debounce;
async function sendLinksToSW() {
    clearTimeout(debounce);
    debounce = setTimeout(async () => {
        if (!navigator.serviceWorker.controller) return;

        const domLinks = collectAllLinks();
        const dbLinks = await getIndexedDBStaffLinks();
        const all = [...new Set([...domLinks, ...dbLinks])];

        if (all.length) {
            navigator.serviceWorker.controller.postMessage({ action: 'cacheLinks', links: all });
        }

        all.filter(url => PRIORITY_PATTERNS.some(rx => rx.test(url)))
            .forEach(url => {
                if ('caches' in window) caches.open('stafflinks-offline-v4').then(c => c.add(url).catch(() => {}));
            });
    }, 300);
}

window.addEventListener('load', sendLinksToSW);
new MutationObserver(sendLinksToSW).observe(document.body, { childList: true, subtree: true });
