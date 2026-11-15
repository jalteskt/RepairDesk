/* Server-backed cache for existing dashboard renderers. */
let snapshot = {jobs:[],technicians:[],settings:[]};
function getJobs(){ return structuredClone(snapshot.jobs); }
function getTechnicians(){ return structuredClone(snapshot.technicians); }
function getStatusBadge(status) {
  let badgeClass = "badge-gray";
  if (status === "Diagnosing") badgeClass = "badge-blue";
  else if (status === "Awaiting Quote Approval") badgeClass = "badge-amber";
  else if (status === "Repairing") badgeClass = "badge-teal";
  else if (status === "Waiting for Parts") badgeClass = "badge-yellow";
  else if (status === "Ready") badgeClass = "badge-green";
  else if (status === "Collected") badgeClass = "badge-emerald";
  else if (status === "Cannot Be Repaired" || status === "Declined") badgeClass = "badge-red";
  else if (status === "Cancelled") badgeClass = "badge-gray";

  return `<span class="badge ${badgeClass}">${status}</span>`;
}

function getPaymentBadge(status) {
  if (status === "Paid") return `<span class="badge badge-green">Paid</span>`;
  if (status === "Deposit") return `<span class="badge badge-blue">Deposit</span>`;
  return `<span class="badge badge-amber">Unpaid</span>`;
}

function formatPHP(amount) {
  return "₱" + Number(amount).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}


function initSidebarToggle() {
  const toggleBtn = document.getElementById("sidebar-toggle-btn");
  const sidebar = document.querySelector(".sidebar");
  if (!sidebar) return;

  let overlay = document.querySelector(".sidebar-overlay");
  if (!overlay) {
    overlay = document.createElement("div");
    overlay.className = "sidebar-overlay";
    document.body.appendChild(overlay);
  }

  if (toggleBtn) {
    toggleBtn.addEventListener("click", () => {
      sidebar.classList.toggle("open");
      overlay.classList.toggle("show");
    });
  }

  overlay.addEventListener("click", () => {
    sidebar.classList.remove("open");
    overlay.classList.remove("show");
  });
}


window.RepairDeskStore = {getJobs,getTechnicians,getStatusBadge,getPaymentBadge,formatPHP};
async function refreshData(){ snapshot=await RD.call('bootstrap'); RD.user=snapshot.user; }
async function changeJob(operation, fields={}) {
  const result=await RD.call('update-job',{id:currentJob.dbId,version:currentJob.version,operation,...fields});
  await refreshData(); currentJob=result; loadJobDetails(); return result;
}
document.addEventListener('DOMContentLoaded',async()=>{
  await RD.session();
  if(!RD.user){ location.replace('../pages/login.html'); return; }
  if(RD.user.role!=='admin' && /(?:settings|reports|customers|technicians)\.html$/.test(location.pathname)) {location.replace('jobs.html');return;}
  await refreshData(); initSidebarToggle(); initStaffNotifications();
  document.getElementById('staff-role').textContent = RD.user.role === 'admin' ? 'Shop administrator' : 'Technician';
  for(const id of ['sidebar-user-name','header-user-greeting']) {const e=document.getElementById(id);if(e)e.textContent=RD.user.name;}
  document.getElementById('staff-signout').onclick = async () => {
    if (!await RDUI.ask('Sign out of your RepairDesk account?')) return;
    await RD.call('logout', {});
    location.replace('../pages/login.html');
  };
  for(const id of ['input-tech','filter-technician']) {
    const select=document.getElementById(id); if(!select)continue;
    select.replaceChildren();
    if(id==='filter-technician')select.add(new Option('All technicians','all'));
    snapshot.technicians.filter(t=>t.status==='Active').forEach(t=>select.add(new Option(t.name,t.name)));
  }
  if(RD.user.role==='technician'){
    document.querySelectorAll('.sidebar-nav a').forEach(link=>{
      if(!['index.html','jobs.html'].includes(link.getAttribute('href')))link.closest('li')?.remove();
    });
    document.querySelectorAll('.nav-section, a[href*="action=new"], [onclick="openNewJobModal()"], a[href="technicians.html"], [onclick="viewCustomerRequests()"]').forEach(el=>el.hidden=true);
  }
  const badge=document.querySelector('.sidebar-nav a[href="jobs.html"] .badge');if(badge)badge.textContent=snapshot.jobs.length;
  const avatar=document.getElementById('sidebar-avatar');if(avatar)avatar.textContent=RD.user.name.split(/\s+/).map(word=>word[0]).slice(0,2).join('');
  document.dispatchEvent(new Event('repairdesk:ready'));
});

function initStaffNotifications() {
  const host=document.querySelector('.topbar-right');
  if(!host)return;
  const box=document.createElement('div'); box.className='staff-notifications';
  box.innerHTML='<button type="button" aria-label="Notifications" aria-expanded="false" aria-controls="staff-alerts"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><span class="alert-count" hidden></span></button><section id="staff-alerts" aria-label="Notifications" hidden><strong>Notifications</strong><p role="status"></p><ul></ul></section>';
  host.prepend(box);
  const bell=box.querySelector('button'),panel=box.querySelector('section'),badge=box.querySelector('.alert-count'),status=box.querySelector('p'),list=box.querySelector('ul');
  let busy=false;
  async function refresh(){
    if(busy||document.hidden)return; busy=true;
    try {
      const data=await RD.call('staff-notifications');
      badge.hidden=data.unread===0; badge.textContent=data.unread>99?'99+':String(data.unread);
      bell.setAttribute('aria-label','Notifications, '+data.unread+' unread');
      status.textContent=data.items.length?'Latest 50 notifications; unread first.':'No notifications yet.';
      list.replaceChildren();
      for(const item of data.items){
        const li=document.createElement('li'),link=document.createElement('a'),title=document.createElement('span'),time=document.createElement('small');
        link.href='job-detail.html?id='+encodeURIComponent('JOB-'+item.job_id);
        link.className=item.read_at?'':'is-unread';
        title.textContent=(item.read_at?'':'Unread: ')+item.message+' — '+item.tracking_code;
        time.textContent=new Date(item.created_at.replace(' ','T')+'Z').toLocaleString();
        link.append(title,time);li.append(link);list.append(li);
        link.addEventListener('click',async event=>{
          if(event.ctrlKey||event.metaKey||event.shiftKey||event.altKey)return;
          event.preventDefault();
          try{await RD.call('staff-notifications',{id:Number(item.id)});location.assign(link.href);}
          catch(error){status.textContent=error.message;}
        });
      }
    }catch(error){status.textContent='Notifications unavailable. '+error.message;}
    finally{busy=false;}
  }
  function close(){panel.hidden=true;bell.setAttribute('aria-expanded','false');}
  bell.addEventListener('click',()=>{panel.hidden=!panel.hidden;bell.setAttribute('aria-expanded',String(!panel.hidden));if(!panel.hidden)refresh();});
  document.addEventListener('click',event=>{if(!box.contains(event.target))close();});
  box.addEventListener('keydown',event=>{if(event.key==='Escape'){close();bell.focus();}});
  document.addEventListener('visibilitychange',()=>{if(!document.hidden)refresh();});
  refresh();const timer=setInterval(refresh,20000);
  window.addEventListener('pagehide',()=>clearInterval(timer),{once:true});
}
