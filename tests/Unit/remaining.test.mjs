import test from 'node:test';
import assert from 'node:assert/strict';
import {cents,decimal,sum,format} from '../../resources/js/sync/money.js';
import {acknowledge,modules} from '../../resources/js/sync/protocol.js';
const farm='11111111-1111-4111-8111-111111111111',animal='22222222-2222-4222-8222-222222222222',income='33333333-3333-4333-8333-333333333333',operation='44444444-4444-4444-8444-444444444444';
test('money retains cents at column maximum, sum, negatives and invalid precision',()=>{
 assert.equal(sum([{monto:'0.10'},{monto:'0.20'}]),30n);assert.equal(decimal(30n),'0.30');assert.equal(cents('999999999999.99'),99999999999999n);assert.equal(format(-12055n),'-120,55');assert.throws(()=>cents('1.234'));assert.throws(()=>cents('1e3'));
});
test('sale ACK applies its related animal exactly once; malformed effects never consume the queue',()=>{
 const op={operation_uuid:operation,record_uuid:income,module:'ingresos',type:'create',data:{finca_uuid:farm,animal_uuid:animal},status:'sending',dependsOn:[]};
 const record={record_uuid:income,id:1,version:1,deleted:false,finca_uuid:farm,animal_uuid:animal};
 const effect={record_uuid:animal,id:2,version:3,deleted:false,finca_uuid:farm,estado:'vendido'};
 const reply={protocol:1,account_id:1,operation_uuid:operation,record_uuid:income,module:'ingresos',status:201,record,related:[{module:'animales',record:effect}]};
 const state=()=>({base:Object.fromEntries(Object.keys(modules).map(m=>[m,{}])),queue:[structuredClone(op)]});
 const invalid=state(),before=structuredClone(invalid);assert.throws(()=>acknowledge(invalid,op,{...reply,related:[{module:'perfil',record:effect}]},1));assert.deepEqual(invalid,before);
 const data=state();acknowledge(data,op,reply,1);assert.equal(data.queue.length,0);assert.equal(data.base.animales[animal].estado,'vendido');data.base.animales[animal].version=4;acknowledge(data,op,reply,1);assert.equal(data.base.animales[animal].version,4);
});
