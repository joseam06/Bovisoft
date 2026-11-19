const enc = new TextEncoder();
const dec = new TextDecoder();
const aad = meta => enc.encode(`bovisoft:automatic:1:${meta.accountId}:${meta.generation}`);

export async function seal(data, key, meta) {
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const ciphertext = await crypto.subtle.encrypt({name:'AES-GCM', iv, additionalData:aad(meta)}, key, enc.encode(JSON.stringify(data)));
    return {...meta, revision:crypto.randomUUID(), iv, ciphertext, complete:true};
}

export async function createAutomaticVault(data, meta) {
    // No account password or additional PIN is saved. This browser retains an opaque key.
    const key = await crypto.subtle.generateKey({name:'AES-GCM',length:256}, false, ['encrypt','decrypt']);
    return {key, envelope:await seal(data,key,{...meta,automaticKey:key})};
}

export async function readVault(vault, key = vault.automaticKey) {
    return JSON.parse(dec.decode(await crypto.subtle.decrypt({name:'AES-GCM',iv:vault.iv,additionalData:aad(vault)},key,vault.ciphertext)));
}
