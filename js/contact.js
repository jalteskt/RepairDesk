document.addEventListener('DOMContentLoaded',async()=>{
  const links=document.querySelectorAll('[data-shop-contact]');
  if(!links.length)return;
  const {email}=await RD.call('shop-contact');
  for(const link of links){
    if(email) link.href='mailto:'+encodeURIComponent(email).replace('%40','@')+'?subject='+encodeURIComponent(link.dataset.subject||'RepairDesk visitor inquiry');
    else link.onclick=event=>{event.preventDefault();RDUI.notice('The shop has not set a support address yet.');};
  }
});
