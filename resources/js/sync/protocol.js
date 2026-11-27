// Shared transport/state contract. Domain rules remain in Laravel services.
export const modules = Object.freeze({
    fincas: {parents: []},
    animales: {parents: [{module: 'fincas', field: 'finca_uuid'}, {module:'potreros',field:'potrero_uuid',optional:true}]},
    potreros: {parents: [{module:'fincas',field:'finca_uuid'}]},
    produccion: {parents: [{module:'fincas',field:'finca_uuid'},{module:'animales',field:'animal_uuid'}]},
    salud: {parents: [{module:'fincas',field:'finca_uuid'},{module:'animales',field:'animal_uuid'}]},
    ingresos:{parents:[{module:'fincas',field:'finca_uuid'},{module:'animales',field:'animal_uuid',optional:true}]},
    egresos:{parents:[{module:'fincas',field:'finca_uuid'},{module:'animales',field:'animal_uuid',optional:true},{module:'salud',field:'salud_uuid',optional:true}]},
    perfil:{parents:[]},
});
const uuid = value => typeof value === 'string' && /^[a-f0-9]{8}(-[a-f0-9]{4}){3}-[a-f0-9]{12}$/i.test(value);
const equalUuid = (a,b) => uuid(a) && uuid(b) && a.toLowerCase() === b.toLowerCase();
const object = value => value !== null && typeof value === 'object' && !Array.isArray(value);
export function syncError(code, message) {return Object.assign(new Error(message), {code});}
function requireValue(value) {
    if (!value) throw syncError('BOVI_PROTOCOL_INVALID', 'La respuesta del servidor no confirma esta operación. Tus pendientes se conservan.');
}
export function sameActor(actual, expected) {
    return !!actual && !!expected && actual.accountId === expected.accountId && actual.epoch === expected.epoch;
}
function record(value, module, id = null) {
    requireValue(object(value) && !!modules[module] && uuid(value.record_uuid)
        && (id === null || equalUuid(value.record_uuid,id)) && Number.isSafeInteger(value.id) && value.id > 0
        && Number.isSafeInteger(value.version) && value.version > 0 && typeof value.deleted === 'boolean');
    if (!value.deleted) for (const parent of modules[module].parents) if(!parent.optional||value[parent.field])requireValue(uuid(value[parent.field]));
}
export function validateReply(reply, operation, accountId) {
    requireValue(object(reply) && reply.protocol === 1 && String(reply.account_id) === String(accountId)
        && equalUuid(reply.operation_uuid,operation.operation_uuid) && reply.module === operation.module
        && equalUuid(reply.record_uuid,operation.record_uuid) && !!modules[operation.module]);
    if ([200,201].includes(reply.status)) {
        requireValue(reply.status === (operation.type === 'create' ? 201 : 200));
        record(reply.record,operation.module,operation.record_uuid);
        requireValue(reply.record.version === (operation.type === 'create' ? 1 : operation.base_version + 1)
            && reply.record.deleted === (operation.type === 'delete'));
        if (operation.type !== 'delete') for (const parent of modules[operation.module].parents)
            if(!parent.optional||operation.data[parent.field])requireValue(equalUuid(reply.record[parent.field],operation.data[parent.field]));
    } else {
        requireValue([404,409,422].includes(reply.status) && typeof reply.error === 'string');
        if (reply.current !== undefined) record(reply.current,operation.module,operation.record_uuid);
    }
    if(reply.related!==undefined){requireValue(Array.isArray(reply.related));for(const related of reply.related){requireValue(['animales','potreros','salud'].includes(related.module));record(related.record,related.module);}}
    return reply;
}
export function acknowledge(working, sent, reply, accountId) {
    // Validate even if another tab has already consumed the operation.
    validateReply(reply,sent.wire || sent,accountId);
    const operation=working.queue.find(op=>op.operation_uuid===sent.operation_uuid);
    if (!operation) return;
    if (![200,201].includes(reply.status)) {
        operation.status=reply.status===409?'conflict':'error';operation.result=reply;return;
    }
    const latest=working.base[operation.module][operation.record_uuid];
    if (!working.removals?.[operation.module]?.[operation.record_uuid] && (!latest || latest.version<=reply.record.version)) {
        if (reply.record.deleted) {
            delete working.base[operation.module][operation.record_uuid];
            working.removals ||= Object.fromEntries(Object.keys(modules).map(name=>[name,{}]));
            working.removals[operation.module][operation.record_uuid]=true;
        } else working.base[operation.module][operation.record_uuid]=reply.record;
    }
    working.queue=working.queue.filter(op=>op.operation_uuid!==operation.operation_uuid);
    for(const related of reply.related||[]){const value=related.record,base=working.base[related.module];if(!working.removals?.[related.module]?.[value.record_uuid]&&(!base[value.record_uuid]||base[value.record_uuid].version<=value.version))base[value.record_uuid]=value;}
}
export function nextOperation(working) {
    return working.queue.find(op=>modules[op.module] && ['pending','sending'].includes(op.status)
        && !op.dependsOn.some(id=>working.queue.some(parent=>parent.operation_uuid===id)));
}
export function missingParents(working, operation) {
    return modules[operation.module].parents.some(parent=>(!parent.optional||operation.data[parent.field])&&!working.base[parent.module][operation.data[parent.field]]);
}
export function prepareOperation(operation) {
    operation.started=true;operation.status='sending';
    operation.wire ||= structuredClone({operation_uuid:operation.operation_uuid,record_uuid:operation.record_uuid,
        module:operation.module,type:operation.type,...(operation.type==='create'?{}:{base_version:operation.base_version}),data:operation.data});
    return structuredClone(operation);
}
export function validateDelta(delta, accountId) {
    requireValue(object(delta) && String(delta.account_id)===String(accountId) && Array.isArray(delta.changes)
        && typeof delta.cursor==='string' && delta.cursor.length>0 && typeof delta.has_more==='boolean'
        && object(delta.valid) && object(delta.references) && Array.isArray(delta.references.potreros)
        && object(delta.references.tipos) && object(delta.references.razas));
    for(const module of Object.keys(modules)) requireValue(Array.isArray(delta.valid[module]) && delta.valid[module].every(uuid));
    for(const change of delta.changes) {
        requireValue(object(change) && !!modules[change.module]);
        if(change.action==='upsert') {
            record(change.record,change.module);
            requireValue(!change.record.deleted && delta.valid[change.module].includes(change.record.record_uuid));
            for(const parent of modules[change.module].parents) if(!parent.optional||change.record[parent.field])requireValue(delta.valid[parent.module].includes(change.record[parent.field]));
        } else requireValue(change.action==='remove' && uuid(change.record_uuid) && !delta.valid[change.module].includes(change.record_uuid));
    }
    return delta;
}
export function applyDelta(working, delta, accountId) {
    validateDelta(delta,accountId); // No mutation until the complete manifest is checked.
    working.removals ||= Object.fromEntries(Object.keys(modules).map(name=>[name,{}]));
    for(const change of delta.changes) {
        const id=change.record_uuid || change.record.record_uuid, base=working.base[change.module];
        if(change.action==='remove'){delete base[id];working.removals[change.module][id]=true;}
        else {
            delete working.removals[change.module][id];
            if(!base[id] || base[id].version<=change.record.version)base[id]=change.record;
        }
    }
    for(const module of Object.keys(modules)) {
        const allowed=new Set(delta.valid[module]);
        for(const id of Object.keys(working.base[module])) if(!allowed.has(id)){
            delete working.base[module][id];working.removals[module][id]=true;
        }
    }
    working.references=delta.references;working.cursor=delta.cursor;
}
export function retryAfterMs(header, now=Date.now()) {
    if(!header)return 0;
    const seconds=Number(header), delay=Number.isFinite(seconds)?seconds*1000:Date.parse(header)-now;
    return Number.isFinite(delay)?Math.min(300000,Math.max(0,delay)):0;
}
export function backoffMs(previous, serverDelay=0) {
    return Math.max(Math.min(30000,Math.max(1000,previous)*2),serverDelay);
}
