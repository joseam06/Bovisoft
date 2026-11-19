/* BoviSoft: cache only the anonymous shell and an explicit static-asset list. */
importScripts('/offline-store.js');
importScripts('/workspace-store.js');
const CONTROL = 'bovisoft-offline-control-v1';
const INDEX = '/offline/cache-index';
const PREFIX = 'bovisoft-offline-shell-';
const store = self.BoviOfflineStore;
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', event => event.waitUntil(self.clients.claim()));

async function broadcast(state) {
    const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    clients.forEach(client => client.postMessage({ type: 'BOVI_SECURITY', state }));
}
async function identity(response, before) {
    const account = response.headers.get('X-Bovisoft-Account');
    const binding = response.headers.get('X-Bovisoft-Binding');
    if (account === null || !binding) return;
    // Only the page's serialized logout reconciliation may release this boundary.
    const current = await store.security();
    if (current.pendingLogout || current.epoch !== before.epoch && current.binding !== binding) return;
    const state = await store.transition(account === 'guest' ? null : account, binding);
    if (state.changed) await broadcast(state);
}
async function index() {
    const response = await (await caches.open(CONTROL)).match(INDEX);
    return response ? response.json() : null;
}
async function check(version) {
    const manifest = await index();
    const entry = manifest?.versions?.[version] || (manifest?.version === version ? manifest : null);
    if (!entry) return false;
    const cache = await caches.open(entry.cacheName);
    return (await Promise.all(entry.urls.map(url => cache.match(url)))).every(Boolean);
}
async function prepare(manifest) {
    if (!Array.isArray(manifest.urls) || !/^[a-f0-9]{64}$/.test(manifest.version)) throw new Error('Manifiesto no válido.');
    if (await check(manifest.version)) {
        if (manifest.automaticMode) {
            const current = await index();
            await (await caches.open(CONTROL)).put(INDEX, new Response(JSON.stringify({...current, automaticMode: true}), {headers: {'Content-Type': 'application/json'}}));
        }
        return true;
    }
    const cacheName = PREFIX + manifest.version + '-' + crypto.randomUUID();
    const cache = await caches.open(cacheName);
    try {
        for (const path of manifest.urls) {
            const url = new URL(path, self.location.origin);
            if (url.origin !== self.location.origin || !['/offline-shell', '/offline-store.js', '/workspace-store.js', '/favicon.ico'].includes(url.pathname)
                && !/^\/(build|vendor|images)\//.test(url.pathname)) throw new Error('Recurso no permitido.');
            const response = await fetch(url, { credentials: 'omit', cache: 'reload', redirect: 'error' });
            if (!response.ok) throw new Error('No se pudo guardar toda la interfaz.');
            await cache.put(path, response);
        }
        if (!(await Promise.all(manifest.urls.map(url => cache.match(url)))).every(Boolean)) throw new Error('La interfaz quedó incompleta.');
        const previous = await index();
        const versions = { ...(previous?.versions || {}) };
        if (previous?.version && !versions[previous.version]) versions[previous.version] = { version: previous.version, cacheName: previous.cacheName, urls: previous.urls };
        versions[manifest.version] = { ...manifest, cacheName };
        await (await caches.open(CONTROL)).put(INDEX, new Response(JSON.stringify({ ...manifest, cacheName, versions }), { headers: { 'Content-Type': 'application/json' } }));
        return true;
    } catch (error) {
        await caches.delete(cacheName);
        throw error;
    }
}
self.addEventListener('message', event => {
    const reply = event.ports[0];
    event.waitUntil((async () => {
        try {
            const result = event.data.type === 'PREPARE_SHELL' ? await prepare(event.data.manifest)
                : event.data.type === 'CHECK_SHELL' ? await check(event.data.version) : false;
            reply?.postMessage({ ok: true, result });
        } catch (error) { reply?.postMessage({ ok: false, error: error.message }); }
    })());
});
const missing = () => new Response('<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>BoviSoft · Sin conexión</title><body style="font:18px system-ui;margin:0;background:#7f1d1d;color:white;padding:10vw"><h1>BoviSoft sin conexión</h1><p>La conexión se interrumpió antes de que se guardara la interfaz de este navegador. Al reconectar, BoviSoft la guardará automáticamente.</p></body></html>', { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;
    event.respondWith((async () => {
        const before = await store.security();
        if (url.pathname === '/logout' && request.method === 'POST') {
            const state = await store.transition(null, null, true);
            await broadcast(state);
        }
        // Ordinary online navigation keeps using the real Laravel screens.
        // Only unsynchronized UUID routes need the local shell before reaching the server.
        if (request.mode === 'navigate' && /^\/(fincas|animales)\/[a-f0-9-]{36}(\/|$)/i.test(url.pathname)) {
            const state = await store.security();
            const vault = state.accountId && !state.pendingLogout && await store.automaticWorkspace(state.accountId);
            const manifest = await index();
            if (vault?.complete && await check(vault.shellVersion)) {
                const entry = manifest.versions?.[vault.shellVersion] || manifest;
                return (await caches.open(entry.cacheName)).match('/offline-shell');
            }
        }
        if (request.method === 'GET' && request.mode !== 'navigate') {
            // These shared helpers retain their URLs across releases. Fetch the current
            // implementation online, keeping the complete cached copy for offline reloads.
            if (['/offline-store.js','/workspace-store.js'].includes(url.pathname)) {
                try { const fresh=await fetch(request,{cache:'no-store'});if(fresh.ok)return fresh; } catch { /* Use the last complete shell below. */ }
            }
            const manifest = await index();
            for (const entry of manifest ? [manifest, ...Object.values(manifest.versions || {})] : []) {
                if (entry.urls.includes(url.pathname)) {
                    const cached = await (await caches.open(entry.cacheName)).match(url.pathname);
                    if (cached) return cached;
                }
            }
        }
        try {
            const response = await fetch(request);
            await identity(response, before);
            return response;
        } catch (error) {
            if (request.mode === 'navigate' || url.pathname === '/logout') {
                const manifest = await index();
                if (manifest?.automaticMode && await check(manifest.version)) {
                    return (await caches.open(manifest.cacheName)).match('/offline-shell');
                }
                return missing();
            }
            throw error;
        }
    })());
});
