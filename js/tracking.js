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
 RD.showAppliancePhoto(byId('tracked-photo'), j);
 byId('track-section').hidden=true;
 const cannotRepair = j.status === 'Cannot Be Repaired';
 const exceptionStep = byId('step-0');
 exceptionStep.hidden = !cannotRepair;
 exceptionStep.classList.toggle('active', cannotRepair);
 exceptionStep.setAttribute('aria-current', cannotRepair ? 'step' : 'false');
 document.querySelector('[onclick="requestCancellation()"]').hidden=!j.canCancel;
 byId('inquiry-subject').value='Question about '+j.id;
const put=(id,v)=>document.getElementById(id).textContent=v;
 put('display-code','CODE: '+j.code);put('display-status-badge',j.status);put('display-appliance',j.brand+' '+j.model);put('display-fault',j.reportedIssue);put('display-customer',j.customer.name+' ('+j.customer.email+')');put('display-date','Intake: '+j.dateStarted);put('payment-badge',j.payment);
 const flow=['Received','Diagnosing','Awaiting Quote Approval','Repairing','Ready','Collected'];const step=j.status==='Waiting for Parts'?3:flow.indexOf(j.status);
 for (let i = 0; i < flow.length; i++) {
   const el = byId('step-' + (i + 1));
   el.hidden = cannotRepair;
   el.classList.toggle('completed', i < step);
   el.classList.toggle('active', i === step);
   el.setAttribute('aria-current', i === step ? 'step' : 'false');
   el.querySelector('.step-circle').textContent = i < step ? '✓' : String(i + 1);
 }
 const quote=document.getElementById('quote-approval-box');quote.style.display=j.status==='Awaiting Quote Approval'?'':'none';quote.querySelector('p').textContent='Review the itemized quote below before approving repair.';quote.querySelector('button').textContent='Approve Repair Quote ('+peso(j.billing.total)+')';
 const notes=document.getElementById('notes-list');notes.replaceChildren();for(const n of j.notes){const row=document.createElement('div');row.className='note-entry';const time=document.createElement('div');time.className='note-time';time.textContent=n.time+' • '+n.author;const body=document.createElement('div');body.className='note-text';body.textContent=n.text;row.append(time,body);notes.append(row);}
 document.querySelectorAll('.price-table tr').forEach((row,i)=>row.lastElementChild.textContent=peso([j.billing.diagnosticFee,j.billing.parts,j.billing.labor,j.billing.total][i]));
 const partsCell = document.querySelector('.price-table tr:nth-child(2) td');
 partsCell.replaceChildren('Replacement Parts');
 for (const item of j.billing.partItems || []) {
   const line = document.createElement('div');
   line.textContent = item.name + ' — ' + peso(Number(item.fee));
   partsCell.append(line);
 }
 renderWarranty(j);
 document.getElementById('verify-step').style.display='none';document.getElementById('tracker-display').style.display='block';hideHomeContent();
}
function renderWarranty(job, today = new Date()) {
  const card = byId('warranty-card');
  card.hidden = job.status !== 'Collected';
  if (card.hidden) return;

  const details = card.querySelector('p');
  const claim = card.querySelector('button');
  const dayMs = 86400000;
  const expiry = Date.parse(job.warrantyUntil + 'T00:00:00Z');
  claim.hidden = true;
  if (!Number.isFinite(expiry)) {
    details.textContent = 'The warranty end date is unavailable. Contact the shop for details.';
    return;
  }

  // The claims API treats the stored expiry as an inclusive UTC calendar date.
  const todayUtc = Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), today.getUTCDate());
  const daysLeft = (expiry - todayUtc) / dayMs;
  const endDate = new Intl.DateTimeFormat('en-PH', {
    year: 'numeric', month: 'long', day: 'numeric', timeZone: 'UTC'
  }).format(expiry);
  if (daysLeft < 0) {
    details.textContent = 'Your 90-day repair warranty ended on ' + endDate + '.';
    return;
  }

  claim.hidden = false;
  details.textContent = daysLeft === 0
    ? 'Your 90-day repair warranty ends today (' + endDate + ').'
    : 'Your 90-day repair warranty runs through ' + endDate + '. ' +
      daysLeft + (daysLeft === 1 ? ' day remaining.' : ' days remaining.');
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
function readTicketCode(raw) {
 const text = raw.trim();
 let code = text;
 if (/^https?:\/\//i.test(text)) {
   try {
     const url = new URL(text);
     code = new URLSearchParams(url.hash.slice(1)).get('ticket') || '';
   } catch {
     return '';
   }
 }
 code = code.toUpperCase();
 if (/^[A-Z2-9]{6}$/.test(code)) return code + 'XX';
 return /^[A-Z2-9]{8}$/.test(code) ? code : '';
}

async function scanTicket() {
 const dialog = byId('ticket-scanner');
 if (dialog.open) return;
 const video = byId('ticket-camera');
 const status = byId('scanner-status');
 const fileInput = byId('ticket-image');
 const chooseImage = byId('scan-image');
 let active = true;
 let scanner;
 let readingImage = false;

 function close() {
   if (!active) return;
   active = false;
   scanner?.destroy();
   dialog.close();
   video.hidden = true;
   fileInput.value = '';
 }
 async function accept(raw) {
   if (!active) return;
   const code = readTicketCode(raw);
   if (!code) {
     status.textContent = 'This QR code does not contain a RepairDesk ticket.';
     return;
   }
   close();
   byId('track-code').value = code;
   await handleLookup();
 }

 byId('close-scanner').onclick = close;
 dialog.oncancel = event => { event.preventDefault(); close(); };
 chooseImage.disabled = true;
 status.textContent = 'Opening scanner…';
 dialog.showModal();

 let QrScanner;
 try {
   ({default: QrScanner} = await import('./vendor/qr-scanner/qr-scanner.min.js'));
 } catch {
   if (active) status.textContent = 'Scanner files could not load. Close this window and enter your ticket code.';
   return;
 }
 if (!active) return;
 chooseImage.disabled = false;
 chooseImage.onclick = () => fileInput.click();
 fileInput.onchange = async () => {
   const file = fileInput.files[0];
   if (!file || !active || readingImage) return;
   readingImage = true;
   chooseImage.disabled = true;
   let result;
   try {
     result = await QrScanner.scanImage(file, {returnDetailedScanResult: true});
   } catch {
     if (active) status.textContent = 'No readable QR code found. Choose a clearer image.';
   } finally {
     if (active) {
       fileInput.value = '';
       chooseImage.disabled = false;
     }
     readingImage = false;
   }
   if (result) await accept(result.data);
 };

 if (!navigator.mediaDevices?.getUserMedia) {
   status.textContent = 'Camera access requires HTTPS or localhost. Choose a QR image instead.';
   return;
 }
 video.hidden = false;
 scanner = new QrScanner(video, result => {
   if (!readingImage) accept(result.data);
 }, {returnDetailedScanResult: true, preferredCamera: 'environment'});
 status.textContent = 'Allow camera access, then point it at the ticket.';
 try {
   await scanner.start();
 } catch {
   if (active) {
     video.hidden = true;
     status.textContent = 'Camera unavailable. Allow camera access or choose a QR image.';
   }
 }
}
document.addEventListener('DOMContentLoaded',()=>{
 const ticket = new URLSearchParams(location.hash.slice(1)).get('ticket');
 if (ticket) byId('track-code').value = readTicketCode(ticket);
 byId('scan-ticket').onclick=scanTicket;
 byId('resend-otp').onclick=async()=>{await handleLookup();await RDUI.notice('Check your inbox. Allow one minute between requests.');};
});
