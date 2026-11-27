/* Shared by the page and the classic Service Worker. No account data in Cache API. */
(() => {
    const NAME = 'bovisoft-offline-read-v1';
    let opening;
    function open() {
        return opening ||= new Promise((resolve, reject) => {
            const request = indexedDB.open(NAME, 1);
            request.onupgradeneeded = () => {
                request.result.createObjectStore('state');
                request.result.createObjectStore('vaults', { keyPath: 'accountId' });
            };
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => { opening = null; reject(request.error); };
        });
    }
    const initial = () => ({ accountId: null, binding: null, epoch: 0, pendingLogout: false });
    async function read(store, key) {
        const db = await open();
        return new Promise((resolve, reject) => {
            const request = db.transaction(store).objectStore(store).get(key);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }
    async function security() { return await read('state', 'security') || initial(); }
    async function transition(accountId, binding, logout = false) {
        const db = await open();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('state', 'readwrite');
            const store = tx.objectStore('state');
            let result;
            const request = store.get('security');
            request.onsuccess = () => {
                const old = request.result || initial();
                accountId = accountId == null ? null : String(accountId);
                result = { ...old, changed: false };
                if (logout === true) {
                    result = { accountId: null, binding: null, epoch: old.epoch + 1, pendingLogout: true, changed: true };
                } else if (logout === 'local') {
                    result = { ...old, epoch: old.epoch + 1, changed: true };
                } else if (!old.pendingLogout || logout === 'confirmed') {
                    const changed = old.accountId !== accountId || old.binding !== binding;
                    result = { accountId, binding, epoch: old.epoch + (changed ? 1 : 0), pendingLogout: false, changed };
                }
                store.put(result, 'security');
            };
            tx.oncomplete = () => resolve(result);
            tx.onerror = () => reject(tx.error);
            tx.onabort = () => reject(tx.error || new Error('No se pudo guardar el bloqueo.'));
        });
    }
    async function commit(vault, epoch) {
        const db = await open();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(['state', 'vaults'], 'readwrite');
            const request = tx.objectStore('state').get('security');
            let denied = false;
            request.onsuccess = () => {
                const state = request.result || initial();
                if (state.accountId !== vault.accountId || state.epoch !== epoch || state.pendingLogout) {
                    denied = true;
                    tx.abort();
                } else {
                    tx.objectStore('vaults').put(vault);
                }
            };
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
            tx.onabort = () => reject(denied ? new Error('La cuenta cambió durante la descarga. Vuelve a iniciar sesión.') : tx.error);
        });
    }
    self.BoviOfflineStore = { open, security, transition, commit, vault: account => read('vaults', String(account)) };
})();
