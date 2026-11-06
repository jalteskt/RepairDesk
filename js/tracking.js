let trackedJob=null;
function loadCode(code){document.getElementById('track-code').value=code;handleLookup();}
function handleLookup(e){e?.preventDefault();trackedJob=null;document.getElementById('tracker-display').style.display='none';document.getElementById('verify-code').textContent=document.getElementById('track-code').value.trim().toUpperCase();document.getElementById('track-form').style.display='none';document.getElementById('verify-step').style.display='block';document.getElementById('tracking-email').value='';hideHomeContent();}
async function verifyTracking(e){e.preventDefault();await RD.session();trackedJob=await RD.call('track',{code:document.getElementById('track-code').value,email:document.getElementById('tracking-email').value});document.getElementById('tracking-email').value='';renderTracking();}
function renderTracking(){
 const j=trackedJob;const put=(id,v)=>document.getElementById(id).textContent=v;
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
async function approveQuote(){if(confirm('Approve '+peso(trackedJob.billing.total)+'?'))await customerAction('approve');}
async function declineQuote(){if(confirm('Decline this quote?'))await customerAction('decline');}
async function requestCancellation(){const m=prompt('Reason for cancellation request:');if(m){await customerAction('cancellation',m);alert('Request saved for shop review.');}}
async function requestWarranty(){const m=prompt('Describe the warranty issue:');if(m){await customerAction('warranty',m);alert('Warranty request saved.');}}
function openContactModal(){document.getElementById('contact-modal').style.display='flex';}
function closeContactModal(){document.getElementById('contact-modal').style.display='none';}
async function submitMessage(e){e.preventDefault();await customerAction('message',e.target.querySelector('input').value+': '+e.target.querySelector('textarea').value);closeContactModal();alert('Message saved for shop review.');}
function hideHomeContent(){document.getElementById('home-content').style.display='none';}
function showHomeContent(){document.getElementById('home-content').style.display='';}
function resetToSearch(){trackedJob=null;document.getElementById('verify-step').style.display='none';document.getElementById('tracker-display').style.display='none';document.getElementById('track-form').style.display='';document.getElementById('tracking-email').value='';showHomeContent();}
document.addEventListener('DOMContentLoaded',async()=>{await RD.session();document.getElementById('code-hint').hidden=!RD.demo;document.querySelector('.otp-demo-box').hidden=!RD.demo;});
