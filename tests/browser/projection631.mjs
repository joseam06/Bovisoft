import assert from 'node:assert/strict';
import {enrich,localDate} from '../../resources/js/sync/module-views.js';
const NativeDate=Date;
for(const instant of ['2026-11-25T04:59:59Z','2026-11-25T05:00:00Z']){
    globalThis.Date=class extends NativeDate{constructor(...args){super(...(args.length?args:[instant]));}};
    const today=instant.includes('04:59')?'2026-11-24':'2026-11-25';assert.equal(localDate(),today);
    const model=enrich({animales:{a:{record_uuid:'a',fecha_nacimiento:'2024-11-01',estado:'activo',potrero_uuid:'p'},b:{estado:'vendido',potrero_uuid:'p'}},potreros:{p:{record_uuid:'p',capacidad_animales:2,fecha_ultima_rotacion:'2026-11-01',dias_descanso:24}},salud:{s:{tipo:'tratamiento',fecha_aplicacion:'2026-11-24',dias_carencia:1,proxima_aplicacion:'2026-11-25'}}});
    assert.equal(model.animales.a.edad,'2 años');assert.equal(model.potreros.p.animales_actuales,1);assert.equal(model.potreros.p.porcentaje_ocupacion,50);assert.equal(model.potreros.p.disponibilidad,1);
    assert.equal(model.potreros.p.requiere_rotacion,today==='2026-11-25');assert.equal(model.salud.s.en_carencia,true);assert.equal(model.salud.s.dias_carencia_restantes,today==='2026-11-24'?1:0);assert.equal(model.salud.s.alerta_proxima,true);
}
globalThis.Date=NativeDate;
console.log('PASS: fecha colombiana al cambiar el día, edades, ocupación, rotación y carencia.');
