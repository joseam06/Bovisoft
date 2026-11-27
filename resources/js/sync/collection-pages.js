// Shared native-table enhancement: all records remain available to filtering.
export function collectionPages(body, records, buildRow, size=50) {
    const table=body.closest('table');table.parentElement.parentElement.querySelector('[data-record-pagination]')?.remove();
    const nav=document.createElement('nav');nav.dataset.recordPagination='true';nav.setAttribute('aria-label','Paginación de animales');nav.className='flex flex-wrap items-center justify-between gap-3 mt-4';
    const status=document.createElement('span');status.setAttribute('role','status');
    const previous=document.createElement('button'),next=document.createElement('button');
    for(const button of [previous,next]){button.type='button';button.className='px-4 py-2 rounded-xl border border-red-700 text-red-800 font-semibold';button.style.minHeight='44px';}
    previous.textContent='Anterior';next.textContent='Siguiente';nav.append(previous,status,next);table.parentElement.after(nav);
    let filtered=records,page=0;
    function paint(){
        const start=page*size,fragment=document.createDocumentFragment();for(const row of filtered.slice(start,start+size))fragment.append(buildRow(row));body.replaceChildren(fragment);
        status.textContent=filtered.length?`${start+1}–${Math.min(start+size,filtered.length)} de ${filtered.length} animales`:'0 animales';
        previous.disabled=page===0;next.disabled=start+size>=filtered.length;
    }
    previous.addEventListener('click',()=>{if(page){page--;paint();}});next.addEventListener('click',()=>{if((page+1)*size<filtered.length){page++;paint();}});
    paint();return rows=>{filtered=rows;page=0;paint();};
}
