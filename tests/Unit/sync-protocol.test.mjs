import test from 'node:test';
import assert from 'node:assert/strict';
import {acknowledge, applyDelta, prepareOperation, nextOperation, missingParents, retryAfterMs, backoffMs, sameActor} from '../../resources/js/sync/protocol.js';

const farm='11111111-1111-4111-8111-111111111111', animal='22222222-2222-4222-8222-222222222222', operation='33333333-3333-4333-8333-333333333333';
const op=()=>({operation_uuid:operation,record_uuid:farm,module:'fincas',type:'create',data:{nombre:'Ficticia'},status:'pending',dependsOn:[]});
const state=()=>({base:{fincas:{},animales:{}},queue:[op()]});
const reply=()=>({protocol:1,account_id:1,operation_uuid:operation,record_uuid:farm,module:'fincas',status:201,record:{record_uuid:farm,id:1,version:1,deleted:false}});
const delta=()=>({account_id:1,cursor:'cursor',has_more:false,changes:[],valid:{fincas:[],animales:[]},references:{potreros:[],tipos:{},razas:{}}});

test('only a matching account, operation and version can consume a pending write',()=>{
    for(const invalid of [{account_id:2},{operation_uuid:animal},{record_uuid:animal},{status:200},{record:{...reply().record,version:2}},{protocol:0}]){
        const working=state(),before=structuredClone(working);
        assert.throws(()=>acknowledge(working,working.queue[0],{...reply(),...invalid},1),{code:'BOVI_PROTOCOL_INVALID'});
        assert.deepEqual(working,before);
    }
    const working=state();working.base.fincas[farm]={...reply().record,version:4};
    acknowledge(working,working.queue[0],reply(),1);
    assert.equal(working.queue.length,0);assert.equal(working.base.fincas[farm].version,4);
    const removed=state();removed.removals={fincas:{[farm]:true},animales:{}};
    acknowledge(removed,removed.queue[0],reply(),1);assert.equal(removed.base.fincas[farm],undefined);
    for(const type of ['update','delete']){
        const working=state();working.queue[0]={...op(),type,base_version:3};
        const result={...reply(),status:200,record:{...reply().record,version:4,deleted:type==='delete'}};
        acknowledge(working,working.queue[0],result,1);
        assert.equal(working.queue.length,0);
        if(type==='delete')assert.equal(working.removals.fincas[farm],true);
        else assert.equal(working.base.fincas[farm].version,4);
    }
    const conflict=state(),draft=structuredClone(conflict.queue[0].data);
    acknowledge(conflict,conflict.queue[0],{...reply(),status:409,error:'version_conflict',current:{...reply().record,version:4}},1);
    assert.equal(conflict.queue[0].status,'conflict');assert.deepEqual(conflict.queue[0].data,draft);
});

test('retries preserve exact wire data and wait for a confirmed parent',()=>{
    const working=state(),parent=working.queue[0],child={...op(),module:'animales',record_uuid:animal,operation_uuid:animal,dependsOn:[operation],data:{finca_uuid:farm}};
    working.queue.unshift(child);
    assert.equal(nextOperation(working),parent);assert.equal(missingParents(working,child),true);
    const first=prepareOperation(parent);parent.data.nombre='Later local draft';
    assert.deepEqual(prepareOperation(parent).wire,first.wire);
    acknowledge(working,first,reply(),1);
    assert.equal(nextOperation(working),child);assert.equal(missingParents(working,child),false);
});

test('validate the entire incremental download before changing data; retain drafts on remote deletion',()=>{
    for(const invalid of [{account_id:2},{valid:{}},{references:{}},{changes:[{module:'fincas',action:'upsert',record:reply().record}]}]){
        const working=state(),before=structuredClone(working);
        assert.throws(()=>applyDelta(working,{...delta(),...invalid},1),{code:'BOVI_PROTOCOL_INVALID'});assert.deepEqual(working,before);
    }
    const working=state();working.base.fincas[farm]=reply().record;
    const before=structuredClone(working.queue);
    applyDelta(working,{...delta(),changes:[{module:'fincas',action:'remove',record_uuid:farm}]},1);
    assert.deepEqual(working.queue,before);assert.equal(working.removals.fincas[farm],true);
    assert.equal(working.base.fincas[farm],undefined);assert.equal(working.cursor,'cursor');
});

test('backoff honors valid Retry-After and identity includes the session epoch',()=>{
    assert.equal(retryAfterMs('120'),120000);assert.equal(retryAfterMs('invalid'),0);
    assert.equal(retryAfterMs('Thu, 01 Jan 1970 00:00:05 GMT',0),5000);
    assert.equal(retryAfterMs('999999'),300000);assert.equal(backoffMs(2000,120000),120000);
    assert.equal(sameActor({accountId:'1',epoch:2},{accountId:'1',epoch:3}),false);
    assert.equal(sameActor({accountId:'2',epoch:2},{accountId:'1',epoch:2}),false);
});
