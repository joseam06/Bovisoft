// Presentation only: shared navigation and table affordances, including cached Blade views.
const mobile = window.matchMedia('(max-width: 1023px), (hover: none) and (pointer: coarse)');
const sidebar = document.getElementById('sidebar');
const content = document.getElementById('app-content');
const toggle = document.getElementById('nav-toggle');
const backdrop = document.getElementById('nav-backdrop');
let opened = false, previousOverflow = '';

function menu(show, restoreFocus = true) {
    if (!sidebar || !toggle) return;
    show = mobile.matches && show;
    if (show && !opened) previousOverflow = document.body.style.overflow;
    if (show) document.body.style.overflow = 'hidden';
    else if (opened) document.body.style.overflow = previousOverflow;
    opened = show;
    document.body.classList.toggle('bovi-nav-open', show);
    toggle.setAttribute('aria-expanded', String(show));
    sidebar.inert = mobile.matches && !show;
    sidebar.setAttribute('aria-hidden', String(mobile.matches && !show));
    if (content) content.inert = show || !!document.querySelector('#modal-camara:not(.hidden)');
    if (backdrop) backdrop.hidden = !show;
    if (show) requestAnimationFrame(() => document.getElementById('nav-close')?.focus({preventScroll:true}));
    else if (restoreFocus) toggle.focus({preventScroll:true});
}
toggle?.addEventListener('click', () => menu(!opened));
document.getElementById('nav-close')?.addEventListener('click', () => menu(false));
backdrop?.addEventListener('click', () => menu(false));
document.addEventListener('click', event => {
    // Capture before offline routing replaces the main and stops propagation.
    if (opened && event.target.closest('#sidebar a')) menu(false, false);
}, true);
document.addEventListener('keydown', event => {
    if (!opened) return;
    if (event.key === 'Escape') {event.preventDefault();menu(false);}
    if (event.key === 'Tab') {
        const items=[...sidebar.querySelectorAll('a[href],button:not([disabled])')].filter(node=>node.getClientRects().length);
        const first=items[0],last=items.at(-1);
        if (event.shiftKey && document.activeElement===first) {event.preventDefault();last?.focus();}
        else if(!event.shiftKey && document.activeElement===last){event.preventDefault();first?.focus();}
    }
});
mobile.addEventListener('change', () => menu(false, false));
menu(false, false);

const publicToggle = document.getElementById('public-nav-toggle');
const publicNav = document.getElementById('public-nav');
function publicMenu(show) {
    if(!publicNav)return;
    publicNav.dataset.open=String(show);
    publicToggle?.setAttribute('aria-expanded',String(show));
}
publicToggle?.addEventListener('click',()=>publicMenu(publicNav.dataset.open!=='true'));
publicNav?.addEventListener('click',event=>{if(event.target.closest('a'))publicMenu(false);});
document.addEventListener('keydown',event=>{if(event.key==='Escape')publicMenu(false);});

// Let tables scroll inside their cards instead of widening the entire page.
function tables() {
    document.querySelectorAll('i.fa-solid,i.fas,i.fa,i.far,i.fa-regular,i.fab,i.fa-brands').forEach(icon=>{
        if(!icon.hasAttribute('aria-label')&&!icon.hasAttribute('role'))icon.setAttribute('aria-hidden','true');
    });
    document.querySelectorAll('.app-main table').forEach(table=>{
        let region=table.closest('.overflow-x-auto,.bovi-table-scroll');
        if(!region){region=document.createElement('div');table.before(region);region.append(table);}
        if(!region.classList.contains('bovi-table-scroll')){
            region.classList.add('bovi-table-scroll');region.tabIndex=0;
            region.setAttribute('role','region');
            region.setAttribute('aria-label',table.querySelector('caption')?.textContent||'Tabla: desliza para ver todas las columnas');
            table.classList.add(table.querySelectorAll('thead tr:first-child th').length>=5?'bovi-table-wide':'bovi-table-compact');
        }
    });
    document.querySelectorAll('.app-main a,.app-main button').forEach(node=>{
        if(node.getAttribute('aria-label')||node.title||node.textContent.trim())return;
        const labels=[['.fa-edit,.fa-pen,.fa-pen-to-square','Editar'],['.fa-eye','Ver detalle'],['.fa-trash,.fa-trash-can','Eliminar']];
        for(const [selector,label] of labels)if(node.querySelector(selector)){node.setAttribute('aria-label',label);break;}
    });
}
tables();
const main = document.querySelector('.app-main');
if(main){let queued=false;new MutationObserver(()=>{if(!queued){queued=true;queueMicrotask(()=>{queued=false;tables();});}}).observe(main,{childList:true,subtree:true});}

// A backdrop-filter card establishes a containing block for position:fixed.
// Portal the existing camera dialog, retaining its existing media/close handlers.
const camera=document.getElementById('modal-camara');
if(camera){
    document.body.append(camera);
    let visible=false,returnFocus;
    new MutationObserver(()=>{
        const show=!camera.classList.contains('hidden');
        if(show===visible)return;
        visible=show;
        if(show){returnFocus=document.activeElement;content.inert=true;camera.querySelector('button')?.focus({preventScroll:true});}
        else{content.inert=opened;returnFocus?.focus({preventScroll:true});}
    }).observe(camera,{attributes:true,attributeFilter:['class']});
    camera.addEventListener('keydown',event=>{
        if(event.key==='Escape'){event.preventDefault();window.cerrarCamara?.();}
        if(event.key==='Tab'){
            const buttons=[...camera.querySelectorAll('button')].filter(node=>node.getClientRects().length&&!node.disabled);
            if(event.shiftKey&&document.activeElement===buttons[0]){event.preventDefault();buttons.at(-1)?.focus();}
            else if(!event.shiftKey&&document.activeElement===buttons.at(-1)){event.preventDefault();buttons[0]?.focus();}
        }
    });
}
