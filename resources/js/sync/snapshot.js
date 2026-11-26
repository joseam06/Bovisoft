const invalid = message => Object.assign(new Error(message), {code: 'BOVI_SNAPSHOT_INVALID'});
// Transport assembly only. No partial catalog is published to the workspace.
export async function assembleSnapshot(first, fetchPage, accountId, state = {parts:[]}) {
    if(first.format!=='catalog-chunks-v1')return first;
    if(!Number.isSafeInteger(first.parts)||first.parts<1||!Number.isSafeInteger(first.total_records)||first.total_records<0||!Number.isSafeInteger(first.total_bytes)||first.total_bytes<1||!/^[a-f0-9]{64}$/.test(first.sha256))throw invalid('Manifiesto de descarga no válido.');
    let page=first, last;
    for(let index=1;index<=first.parts;index++){
        if(state.parts[index-1]){last=state.parts[index-1].page;continue;}
        if(index!==1)page=await fetchPage(`/snapshots/${first.snapshot_uuid}?page=${index}`);
        if(String(page.account_id)!==String(accountId)||page.snapshot_uuid!==first.snapshot_uuid||page.sha256!==first.sha256||page.total_bytes!==first.total_bytes||page.total_records!==first.total_records||page.parts!==first.parts||page.part!==index||page.complete!==(index===first.parts)||page.next_page!==(index===first.parts?null:index+1))throw invalid('Las páginas de la descarga no coinciden.');
        if(typeof page.chunk!=='string'||page.chunk.length>174764||! /^[A-Za-z0-9+/]*={0,2}$/.test(page.chunk))throw invalid('Bloque de descarga no válido.');
        const bytes=Uint8Array.from(atob(page.chunk),c=>c.charCodeAt(0));
        if(bytes.length>131072)throw invalid('Bloque demasiado grande.');
        state.parts.push({bytes,page:{...page,chunk:undefined}});last=page;
    }
    const bytes=new Uint8Array(first.total_bytes);let offset=0;
    for(const part of state.parts){if(offset+part.bytes.length>bytes.length)throw invalid('Tamaño de descarga no válido.');bytes.set(part.bytes,offset);offset+=part.bytes.length;}
    const digest=[...new Uint8Array(await crypto.subtle.digest('SHA-256',bytes))].map(v=>v.toString(16).padStart(2,'0')).join('');
    if(offset!==first.total_bytes||digest!==first.sha256||!last.cursor)throw invalid('La descarga no superó la verificación.');
    let catalog;
    try { catalog=JSON.parse(new TextDecoder('utf-8',{fatal:true}).decode(bytes)); }
    catch { throw invalid('El catálogo descargado no es válido.'); }
    if(!Array.isArray(catalog.records)||catalog.records.length!==first.total_records||!catalog.references)throw invalid('El catálogo está incompleto.');
    return {...last,records:catalog.records,references:catalog.references,complete:true,next_page:null};
}
