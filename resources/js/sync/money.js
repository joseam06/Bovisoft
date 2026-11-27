export function cents(value='0'){
    const text=String(value??'0');if(!/^-?\d+(?:\.\d{1,2})?$/.test(text))throw new Error('Importe decimal inválido. Usa como máximo dos decimales.');
    const negative=text.startsWith('-'),[whole,part='']=text.replace('-','').split('.');
    const n=BigInt(whole)*100n+BigInt(part.padEnd(2,'0'));return negative?-n:n;
}
export function decimal(n){return(n<0n?'-':'')+(n<0n?-n:n)/100n+'.'+String((n<0n?-n:n)%100n).padStart(2,'0');}
export function sum(rows,field='monto'){return rows.reduce((n,r)=>n+cents(r[field]??'0'),0n);}
export function format(value){const n=typeof value==='bigint'?value:cents(value),v=decimal(n),[whole,part]=v.split('.');return whole.replace(/\B(?=(\d{3})+(?!\d))/g,'.')+','+part;}
