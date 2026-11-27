import {syncError} from './protocol.js';

// Web Locks remains an optimization. IndexedDB arbitrates every sender, including
// browsers without Web Locks. The lease never controls access to offline data.
export async function leadership(store, actor, holder) {
    const token=await store.syncLease('claim',actor,holder);
    if(!token)return null;
    const controller=new AbortController();
    let failure, renewing=false;
    function lost(code='BOVI_SYNC_LEASE_LOST') {
        failure ||= syncError(code,code==='BOVI_REQUEST_TIMEOUT'?'El envío tardó demasiado. Se reintentará sin duplicarlo.':'Otra pestaña continuará la sincronización. Los pendientes se conservan.');
        controller.abort(failure);
        return failure;
    }
    const heartbeat=setInterval(async()=>{
        if(renewing||failure)return;
        renewing=true;
        try{if(!await store.syncLease('renew',actor,holder,token.fence))lost();}
        catch{lost();}finally{renewing=false;}
    },5000);
    async function check(){
        if(failure)throw failure;
        if(!await store.syncLease('check',actor,holder,token.fence))throw lost();
    }
    return {token,check,
        defer(delay){return store.syncLease('defer',actor,holder,token.fence,delay);},
        async request(callback){
            await check();
            const timeout=setTimeout(()=>lost('BOVI_REQUEST_TIMEOUT'),30000);
            try{const result=await callback(controller.signal);await check();return result;}
            catch(error){throw failure||error;}finally{clearTimeout(timeout);}
        },
        async close(){clearInterval(heartbeat);controller.abort();await store.syncLease('release',actor,holder,token.fence);},
    };
}
