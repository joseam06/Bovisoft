/* Shared extension for the page and worker; existing Production vaults remain intact. */
(() => {
    const base = self.BoviOfflineStore;
    base.workspace = async account => {
        const db = await base.open();
        return new Promise((resolve, reject) => {
            const request = db.transaction('vaults').objectStore('vaults').get('workspace:' + String(account));
            request.onsuccess = () => resolve(request.result?.payload);
            request.onerror = () => reject(request.error);
        });
    };
    base.commitWorkspace = async (vault, epoch, revision) => {
        const db = await base.open();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(['state', 'vaults'], 'readwrite');
            let failure;
            const stateRequest = tx.objectStore('state').get('security');
            stateRequest.onsuccess = () => {
                const state = stateRequest.result || {};
                if (state.accountId !== vault.accountId || state.epoch !== epoch || state.pendingLogout) {
                    failure = 'La cuenta cambió. Tus cambios anteriores se conservan bloqueados.';
                    tx.abort(); return;
                }
                const previous = tx.objectStore('vaults').get('workspace:' + vault.accountId);
                previous.onsuccess = () => {
                    if ((previous.result?.payload.revision || null) !== revision) {
                        failure = 'La copia cambió en otra pestaña. Vuelve a abrirla antes de guardar.';
                        tx.abort(); return;
                    }
                    tx.objectStore('vaults').put({accountId:'workspace:' + vault.accountId, payload:vault});
                };
            };
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
            tx.onabort = () => reject(failure ? new Error(failure) : tx.error);
        });
    };
    // A separate namespace preserves earlier passphrase-protected copies untouched.
    base.automaticWorkspace = async account => {
        const db = await base.open();
        return new Promise((resolve, reject) => {
            const request = db.transaction('vaults').objectStore('vaults').get('automatic:' + String(account));
            request.onsuccess = () => resolve(request.result?.payload);
            request.onerror = () => reject(request.error);
        });
    };
    const leaseLive = (lease, actor, now=Date.now()) => lease && lease.accountId===actor.accountId && lease.epoch===actor.epoch
        && now>=lease.updatedAt && now<lease.expiresAt && lease.expiresAt-now<=15000;
    base.syncLease = async (action, actor, holder, fence=null, delay=0) => {
        const db=await base.open();
        return new Promise((resolve,reject)=>{
            const tx=db.transaction('state','readwrite'),state=tx.objectStore('state');
            let result=null;
            const security=state.get('security');
            security.onsuccess=()=>{
                const valid=security.result?.accountId===actor.accountId && security.result?.epoch===actor.epoch && !security.result?.pendingLogout;
                const request=state.get('sync-lease');
                request.onsuccess=()=>{
                    const old=request.result,now=Date.now(),owned=old?.holder===holder && old?.fence===fence;
                    if(action==='release'){
                        if(owned){state.put({...old,expiresAt:0},'sync-lease');result=true;}
                    }else if(valid && action==='claim' && !leaseLive(old,actor,now)){
                        if(old?.accountId===actor.accountId && old?.epoch===actor.epoch && now>=old.updatedAt && old.retryAt>now && old.retryAt-now<=300000)return;
                        result={...actor,holder,fence:(old?.fence||0)+1,updatedAt:now,expiresAt:now+15000,retryAt:0};state.put(result,'sync-lease');
                    }else if(valid && owned && leaseLive(old,actor,now)){
                        if(action==='renew')state.put({...old,updatedAt:now,expiresAt:now+15000},'sync-lease');
                        if(action==='defer'){state.put({...old,retryAt:now+Math.min(300000,Math.max(0,delay))},'sync-lease');result=true;}
                        if(action==='renew'||action==='check')result=true;
                    }
                };
            };
            tx.oncomplete=()=>resolve(result);tx.onerror=()=>reject(tx.error);tx.onabort=()=>reject(tx.error);
        });
    };
    base.commitAutomaticWorkspace = async (vault, epoch, revision, lease=null) => {
        const db = await base.open();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(['state', 'vaults'], 'readwrite');
            let failure;
            const request = tx.objectStore('state').get('security');
            request.onsuccess = () => {
                const state = request.result || {};
                if (state.accountId !== vault.accountId || state.epoch !== epoch || state.pendingLogout) {
                    failure = 'La cuenta cambió. Sus pendientes se conservan.';
                    tx.abort(); return;
                }
                const write = () => {
                const old = tx.objectStore('vaults').get('automatic:' + vault.accountId);
                old.onsuccess = () => {
                    if ((old.result?.payload.revision || null) !== revision) {
                        failure = 'Los datos cambiaron en otra pestaña. Se volverá a intentar.';
                        tx.abort(); return;
                    }
                    tx.objectStore('vaults').put({accountId:'automatic:' + vault.accountId, payload:vault});
                };
                };
                if(lease){
                    const held=tx.objectStore('state').get('sync-lease');
                    held.onsuccess=()=>{
                        if(!leaseLive(held.result,{accountId:vault.accountId,epoch}) || held.result.holder!==lease.holder || held.result.fence!==lease.fence){
                            failure='BOVI_SYNC_LEASE_LOST';tx.abort();return;
                        }
                        write();
                    };
                }else write();
            };
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
            tx.onabort = () => {
                const error = failure ? new Error(failure) : tx.error;
                if (failure === 'Los datos cambiaron en otra pestaña. Se volverá a intentar.') error.code = 'BOVI_REVISION_CHANGED';
                if (failure === 'BOVI_SYNC_LEASE_LOST') error.code = failure;
                reject(error);
            };
        });
    };
})();
