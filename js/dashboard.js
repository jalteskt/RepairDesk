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
  await refreshData(); initSidebarToggle();
  document.getElementById('staff-role').textContent = RD.user.role === 'admin' ? 'Shop administrator' : 'Technician';
  for(const id of ['header-user-name','sidebar-user-name','header-user-greeting']) {const e=document.getElementById(id);if(e)e.textContent=RD.user.name;}
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
