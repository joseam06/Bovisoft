import test from 'node:test';
import assert from 'node:assert/strict';
import {enrich,validateLocal,category,localDate,addDays} from '../../resources/js/sync/module-views.js';

const farm='11111111-1111-4111-8111-111111111111',animal='22222222-2222-4222-8222-222222222222',paddock='33333333-3333-4333-8333-333333333333';
function model(){return {fincas:{[farm]:{record_uuid:farm,activa:true}},animales:{[animal]:{record_uuid:animal,finca_uuid:farm,tipo:'vaca',estado:'activo',potrero_uuid:paddock}},potreros:{[paddock]:{record_uuid:paddock,finca_uuid:farm,estado:'activo',capacidad_animales:1}},produccion:{},salud:{}};}

test('local occupancy follows assignments and states; unavailable capacity cannot be assigned',()=>{
    const data=enrich(model());assert.equal(data.potreros[paddock].animales_actuales,1);assert.equal(data.potreros[paddock].porcentaje_ocupacion,100);
    assert.throws(()=>validateLocal('animales',null,{finca_uuid:farm,potrero_uuid:paddock},data),/capacidad/);
    data.animales[animal].potrero_uuid=null;enrich(data);assert.equal(data.potreros[paddock].animales_actuales,0);
    validateLocal('animales',null,{finca_uuid:farm,potrero_uuid:paddock},data);
});

test('health category, scheduled application and withdrawal indicators use the actual dates',()=>{
    const data=model();data.salud.health={tipo:'hormonas',fecha_aplicacion:localDate(),dias_carencia:3,proxima_aplicacion:addDays(localDate(),5)};
    enrich(data);assert.equal(data.salud.health.categoria,'reproductivo');assert.equal(data.salud.health.fin_carencia,addDays(localDate(),3));assert.equal(data.salud.health.en_carencia,true);assert.equal(data.salud.health.alerta_proxima,true);
    assert.equal(category('diagnostico'),'clinico');assert.equal(category('control'),'seguimiento');assert.equal(category('bioseguridad'),'preventivo');
});

test('historical production retains attribution and date; linked health cost cannot change locally',()=>{
    const data=model();data.animales[animal].estado='inactivo';
    const record={animal_uuid:animal,finca_uuid:farm,fecha:localDate(),litros:'10.00'};
    validateLocal('produccion',record,{...record,litros:'12.35'},data);
    assert.throws(()=>validateLocal('produccion',record,{...record,fecha:addDays(localDate(),-1)},data),/revisión online/);
    const health={animal_uuid:animal,finca_uuid:farm,fecha_aplicacion:localDate(),nombre_producto:'Ficticio',costo:'10.00',egreso_vinculado:true};
    assert.throws(()=>validateLocal('salud',health,{...health,costo:'20.00'},data),/egreso vinculado/);
    assert.equal(health.costo,'10.00');
});
