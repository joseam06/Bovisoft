const storeUrl = '/offline-store.js';
export const storeReady = import(/* @vite-ignore */ storeUrl).then(() => window.BoviOfflineStore);
const channel = 'BroadcastChannel' in window ? new BroadcastChannel('bovisoft-offline-security-v1') : null;
let workerReady;

function notify(state) {
    window.dispatchEvent(new CustomEvent('bovi:security', { detail: state }));
    const body = document.body;
    if (body?.dataset.boviPrivate === 'true' && state.accountId !== body.dataset.boviAccount) {
        const panel = document.createElement('div');
        panel.style.cssText = 'position:fixed;inset:0;z-index:99999;background:#7f1d1d;color:white;padding:10vw;font:18px system-ui';
        const title = document.createElement('h1');
        title.textContent = 'La sesión cambió en este navegador';
        const text = document.createElement('p');
        text.textContent = 'El contenido anterior está bloqueado. Vuelve a abrir BoviSoft con la cuenta actual.';
        const link = document.createElement('a');
        link.href = '/dashboard';
        link.textContent = 'Abrir BoviSoft';
        link.style.color = 'white';
        panel.append(title, text, link);
        body.replaceChildren(panel);
    }
}
channel?.addEventListener('message', event => {
    if (event.data.type === 'SECURITY') notify(event.data.state);
    if (event.data.type === 'VAULT') window.dispatchEvent(new CustomEvent('bovi:vault', { detail: event.data }));
});
navigator.serviceWorker?.addEventListener('message', event => {
    if (event.data.type === 'BOVI_SECURITY') notify(event.data.state);
});

export function ensureWorker() {
    return workerReady ||= (async () => {
        if (!('serviceWorker' in navigator) || !window.isSecureContext) throw new Error('Este navegador necesita HTTPS o localhost para preparar la consulta offline.');
        const existing = await navigator.serviceWorker.getRegistration('/');
        if (!existing) await navigator.serviceWorker.register('/sw.js', { scope: '/', updateViaCache: 'none' });
        else if (navigator.onLine) {
            try {
                await existing.update();
                const replacement = existing.installing || existing.waiting;
                if (replacement && replacement.state !== 'activated') {
                    await new Promise((resolve, reject) => {
                        const timeout = setTimeout(() => reject(new Error('La actualización de la interfaz no terminó. Recarga y vuelve a intentar.')), 10000);
                        const inspectState = () => {
                            if (['activated','redundant'].includes(replacement.state)) {
                                clearTimeout(timeout); replacement.removeEventListener('statechange',inspectState);
                                replacement.state === 'activated' ? resolve() : reject(new Error('La actualización no pudo activarse.'));
                            }
                        };
                        replacement.addEventListener('statechange',inspectState); inspectState();
                    });
                }
            } catch {
                // A cached, complete shell remains usable when update checking has no network.
                if (!existing.active) throw new Error('No se pudo activar la interfaz offline.');
            }
        }
        const registration = await navigator.serviceWorker.ready;
        if (!navigator.serviceWorker.controller) {
            await new Promise((resolve, reject) => {
                const timeout = setTimeout(() => reject(new Error('No se pudo guardar la interfaz sin conexión. Se volverá a intentar automáticamente.')), 30000);
                navigator.serviceWorker.addEventListener('controllerchange', () => { clearTimeout(timeout); resolve(); }, { once: true });
                if (navigator.serviceWorker.controller) { clearTimeout(timeout); resolve(); }
            });
        }
        return registration;
    })().catch(error => { workerReady = null; throw error; });
}

export async function workerCall(message) {
    await ensureWorker();
    return new Promise((resolve, reject) => {
        const ports = new MessageChannel();
        const timer = setTimeout(() => reject(new Error('No terminó el guardado automático de la interfaz. Tus cambios se conservan.')), 120000);
        ports.port1.onmessage = event => {
            clearTimeout(timer);
            ports.port1.close();
            event.data.ok ? resolve(event.data.result) : reject(new Error(event.data.error));
        };
        navigator.serviceWorker.controller.postMessage(message, [ports.port2]);
    });
}

async function broadcastTransition(logout) {
    // Clear keys in other tabs immediately, then persist the boundary before continuing.
    channel?.postMessage({ type: 'SECURITY', state: { accountId: null, epoch: -1 } });
    notify({ accountId: null, epoch: -1 });
    const store = await storeReady;
    const state = await store.transition(null, null, logout);
    channel?.postMessage({ type: 'SECURITY', state });
    notify(state);
    return state;
}

export async function lockLocal() {
    const store = await storeReady;
    const state = await store.transition(null, null, 'local');
    channel?.postMessage({ type: 'SECURITY', state });
    notify(state);
}

export function announceVault(accountId, generation) {
    channel?.postMessage({ type: 'VAULT', accountId, generation });
}

async function fetchIdentity(signal) {
    if (!navigator.onLine) throw new Error('Conéctate para verificar la cuenta. Tus cambios se conservan.');
    const store = await storeReady;
    const before = await store.security();
    let response = await fetch('/offline/session', { cache: 'no-store', credentials: 'same-origin', signal, headers: { Accept: 'application/json' } });
    if (!response.ok) throw new Error('No se pudo verificar la sesión. Vuelve a iniciar sesión con conexión.');
    let session = await response.json();
    let confirmedLogout = false;
    if ((await store.security()).pendingLogout) {
        if (session.account_id !== null) {
            const closed = await fetch('/logout', { method: 'POST', credentials: 'same-origin', signal,
                headers: { 'X-CSRF-TOKEN': session.csrf_token, Accept: 'application/json' } });
            if (!closed.ok) throw new Error('Falta completar el cierre de sesión en el servidor. Vuelve a conectarte e intenta de nuevo.');
        }
        response = await fetch('/offline/session', { cache: 'no-store', credentials: 'same-origin', signal, headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('No se pudo confirmar el cierre de sesión. Vuelve a intentar con Internet.');
        session = await response.json();
        if (session.account_id !== null) throw new Error('Falta completar el cierre de sesión en el servidor.');
        confirmedLogout = true;
    }
    const current = await store.security();
    if (!current.pendingLogout && current.epoch !== before.epoch && current.binding !== response.headers.get('X-Bovisoft-Binding')) {
        throw new Error('La sesión cambió mientras se verificaba. Vuelve a abrir la cuenta actual.');
    }
    const state = await store.transition(session.account_id, response.headers.get('X-Bovisoft-Binding'), confirmedLogout ? 'confirmed' : false);
    if (state.changed) { channel?.postMessage({ type: 'SECURITY', state }); notify(state); }
    return session;
}

export function identity(signal) {
    return navigator.locks ? navigator.locks.request('bovisoft-offline-session', signal ? { signal } : {}, () => fetchIdentity(signal)) : fetchIdentity(signal);
}

export async function logout(csrf) {
    const lockedUrl = '/dashboard';
    await broadcastTransition(true);
    if (navigator.onLine && csrf) {
        try {
            const response = await fetch('/logout', { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } });
            if (response.ok) { await identity(); location.assign('/'); return; }
        } catch { /* The persistent local lock remains in force. */ }
    }
    location.assign(lockedUrl);
}

document.addEventListener('submit', event => {
    const form = event.target;
    if (new URL(form.action, location.href).pathname !== '/logout') return;
    event.preventDefault();
    const csrf = new FormData(form).get('_token');
    logout(csrf).catch(() => location.assign('/dashboard'));
}, true);

window.addEventListener('pagehide', () => {
    window.dispatchEvent(new Event('bovi:pagehide'));
    if (document.body?.dataset.boviPrivate === 'true') notify({ accountId: null });
});
window.addEventListener('pageshow', event => {
    if (event.persisted && document.body?.dataset.boviPrivate === 'true') location.reload();
});

async function inspect() {
    try {
        const state = await (await storeReady).security();
        if (!navigator.onLine || state.accountId !== null || state.pendingLogout) notify(state);
        if (navigator.onLine) await identity();
    } catch { /* Offline access is verified by the offline reader, not an expired server session. */ }
}
document.addEventListener('visibilitychange', () => { if (!document.hidden) inspect(); });
window.addEventListener('online', inspect);
window.addEventListener('focus', inspect);
ensureWorker().catch(() => {});
inspect();
