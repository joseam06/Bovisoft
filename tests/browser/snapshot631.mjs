import assert from 'node:assert/strict';
import {webcrypto} from 'node:crypto';
import {assembleSnapshot} from '../../resources/js/sync/snapshot.js';
globalThis.crypto??=webcrypto;
const bytes=new TextEncoder().encode(JSON.stringify({records:[{module:'animales',record_uuid:'ficticio',nombre:'Niña 🐄'}],references:{potreros:[]}}));
const sha256=[...new Uint8Array(await crypto.subtle.digest('SHA-256',bytes))].map(v=>v.toString(16).padStart(2,'0')).join('');
const split=Math.floor(bytes.length/2);const parts=[bytes.slice(0,split),bytes.slice(split)];
const pages=parts.map((b,i)=>({format:'catalog-chunks-v1',account_id:1,snapshot_uuid:'snapshot-ficticio',total_records:1,total_bytes:bytes.length,sha256,part:i+1,parts:2,chunk:Buffer.from(b).toString('base64'),complete:i===1,next_page:i===1?null:2,cursor:i===1?'cursor-ficticio':null}));
const state={parts:[]};let attempts=0;
await assert.rejects(assembleSnapshot(pages[0],async()=>{attempts++;throw new TypeError('Desconexión ficticia');},1,state));
assert.equal(state.parts.length,1);
const result=await assembleSnapshot(pages[0],async()=>{attempts++;return pages[1];},1,state);
assert.equal(result.records[0].nombre,'Niña 🐄');assert.equal(attempts,2);assert.equal(result.cursor,'cursor-ficticio');
for(const corruption of [{part:1},{account_id:2},{sha256:'0'.repeat(64)},{complete:false},{chunk:Buffer.from('corrupto').toString('base64')}]){
    await assert.rejects(assembleSnapshot(pages[0],async()=>({...pages[1],...corruption}),1),e=>e.code==='BOVI_SNAPSHOT_INVALID');
}
console.log('PASS: partes ordenadas, identidad, integridad, UTF-8 y recuperación sin republicar una descarga parcial.');
