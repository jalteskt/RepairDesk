let trackedJob=null;
const byId = id => document.getElementById(id);
async function handleLookup(e) {
  e?.preventDefault();
  const code = byId('track-code').value.trim().toUpperCase();
  if (!/^[A-Z2-9]{8}$/.test(code)) return RDUI.notice('Enter an eight-character ticket code.');
  await RD.session();
  await RD.call('request-otp',{code});
  trackedJob=null;
  byId('verify-code').textContent=code;
  byId('track-form').hidden=true; byId('scan-ticket').hidden=true;
  byId('verify-step').style.display='block'; byId('tracking-otp').value='';
  byId('tracking-otp').focus(); hideHomeContent();
}
async function verifyTracking(e) {
  e.preventDefault();
  trackedJob=await RD.call('track',{otp:byId('tracking-otp').value});
  byId('tracking-otp').value=''; renderTracking();
}
function renderTracking(){
 const j=trackedJob;
 byId('track-section').hidden=true;
 byId('step-0').setAttribute('aria-current',j.status==='Cannot Be Repaired'?'step':'false');
 byId('step-0').classList.toggle('active',j.status==='Cannot Be Repaired');
 document.querySelector('[onclick="requestCancellation()"]').hidden=!j.canCancel;
 byId('inquiry-subject').value='Question about '+j.id;
const put=(id,v)=>document.getElementById(id).textContent=v;
 put('display-code','CODE: '+j.code);put('display-status-badge',j.status);put('display-appliance',j.brand+' '+j.model);put('display-fault',j.reportedIssue);put('display-customer',j.customer.name+' ('+j.customer.email+')');put('display-date','Intake: '+j.dateStarted);put('payment-badge',j.payment);
 const flow=['Received','Diagnosing','Awaiting Quote Approval','Repairing','Ready','Collected'];const step=j.status==='Waiting for Parts'?3:flow.indexOf(j.status);
 document.getElementById('stepper-progress-bar').style.width=Math.max(0,step)*20+'%';
 for(let i=0;i<6;i++){const el=document.getElementById('step-'+(i+1));el.className='step-item'+(i<step?' completed':i===step?' active':'');el.querySelector('.step-circle').textContent=i<step?'✓':String(i+1);}
 const quote=document.getElementById('quote-approval-box');quote.style.display=j.status==='Awaiting Quote Approval'?'':'none';quote.querySelector('p').textContent='Review the itemized quote below before approving repair.';quote.querySelector('button').textContent='Approve Repair Quote ('+peso(j.billing.total)+')';
 const notes=document.getElementById('notes-list');notes.replaceChildren();for(const n of j.notes){const row=document.createElement('div');row.className='note-entry';const time=document.createElement('div');time.className='note-time';time.textContent=n.time+' • '+n.author;const body=document.createElement('div');body.className='note-text';body.textContent=n.text;row.append(time,body);notes.append(row);}
 document.querySelectorAll('.price-table tr').forEach((row,i)=>row.lastElementChild.textContent=peso([j.billing.diagnosticFee,j.billing.parts,j.billing.labor,j.billing.total][i]));
 const warranty=document.getElementById('warranty-card');warranty.style.display=j.status==='Collected'?'':'none';warranty.querySelector('p').textContent='Warranty valid through '+(j.warrantyUntil||'not set')+'.';
 document.getElementById('verify-step').style.display='none';document.getElementById('tracker-display').style.display='block';hideHomeContent();
}
function peso(n){return new Intl.NumberFormat('en-PH',{style:'currency',currency:'PHP'}).format(n);}
async function customerAction(kind,message){if(!trackedJob)throw Error('Verify tracking details first.');trackedJob=await RD.call('customer-action',{kind,message,version:trackedJob.version});renderTracking();}
async function approveQuote(){if(await RDUI.ask('Approve '+peso(trackedJob.billing.total)+'?'))await customerAction('approve');}
async function declineQuote(){if(await RDUI.ask('Decline this quote?'))await customerAction('decline');}
async function requestCancellation(){const m=await RDUI.input('Reason for cancellation request:');if(m){await customerAction('cancellation',m);RDUI.notice('Request saved for shop review.');}}
async function requestWarranty(){const m=await RDUI.input('Describe the warranty issue:');if(m){await customerAction('warranty',m);RDUI.notice('Warranty request saved.');}}
function openContactModal(){document.getElementById('contact-modal').style.display='flex';}
function closeContactModal(){document.getElementById('contact-modal').style.display='none';}
async function submitMessage(e){e.preventDefault();await customerAction('message',e.target.querySelector('input').value+': '+e.target.querySelector('textarea').value);closeContactModal();RDUI.notice('Message saved for shop review.');}
function hideHomeContent(){document.getElementById('home-content').style.display='none';}
function showHomeContent(){document.getElementById('home-content').style.display='';}
function resetToSearch() {
 trackedJob=null; byId('track-section').hidden=false; byId('verify-step').style.display='none';
 byId('tracker-display').style.display='none'; byId('track-form').hidden=false; byId('scan-ticket').hidden=false;
 byId('tracking-otp').value=''; showHomeContent(); byId('track-code').focus();
}
async function scanTicket() {
 if (typeof Html5QrcodeScanner==='undefined') return RDUI.notice('The QR reader is unavailable. Enter your ticket code instead.');
 const dialog=document.createElement('dialog'); dialog.className='rd-dialog'; dialog.setAttribute('aria-label','Scan ticket QR code');
 const reader=document.createElement('div'); reader.id='ticket-camera';
 const cancel=document.createElement('button'); cancel.className='btn btn-outline'; cancel.textContent='Close';
 dialog.append(reader,cancel); document.body.append(dialog); dialog.showModal();
 const scanner=new Html5QrcodeScanner(reader.id,{fps:10,qrbox:200},false);
 let done=false;
 async function finish(code) {
   if(done)return; done=true;
   try { await scanner.clear(); } finally {dialog.close();dialog.remove();}
   if(code){byId('track-code').value=code;await handleLookup();}
 }
 cancel.onclick=()=>finish(); dialog.oncancel=e=>{e.preventDefault();finish();};
 scanner.render(raw=>{const code=raw.trim().toUpperCase();if(/^[A-Z2-9]{8}$/.test(code))finish(code);});
}
document.addEventListener('DOMContentLoaded',()=>{
 byId('scan-ticket').onclick=scanTicket;
 byId('resend-otp').onclick=async()=>{await handleLookup();await RDUI.notice('Check your inbox. Allow one minute between requests.');};
});
