async function showPositions() {
  const list=document.getElementById('positions');
  const adminForm=document.getElementById('position-form');
  const positions=await RD.call('positions'); list.replaceChildren();
  if(!positions.length)list.textContent='There are no open positions at the moment.';
  for(const position of positions){
    const item=document.createElement('article'); item.className='card';
    const title=document.createElement('h3'); title.textContent=position.title;
    const description=document.createElement('p'); description.textContent=position.description;
    item.append(title,description);
    if(adminForm){const close=document.createElement('button'); close.type='button';close.className='btn btn-outline';close.textContent='Close position';close.onclick=async()=>{if(await RDUI.ask('Close '+position.title+'?')){await RD.call('manage-position',{closeId:position.id});await showPositions();}};item.append(close);}
    list.append(item);
  }
}
if(document.getElementById('position-form')){
  document.addEventListener('repairdesk:ready',()=>{
    document.getElementById('position-form').onsubmit=async e=>{
      e.preventDefault();const form=e.currentTarget;const data=new FormData(form);
      await RD.call('manage-position',{title:data.get('title'),description:data.get('description')});form.reset();await showPositions();
    };showPositions();
  });
}else showPositions();
