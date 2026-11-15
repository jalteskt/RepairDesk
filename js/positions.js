async function showPositions() {
  const list=document.getElementById('positions');
  const adminForm=document.getElementById('position-form');
  const positions=await RD.call('positions'); list.replaceChildren();
  if(!positions.length)list.textContent='There are no open positions at the moment.';
  for(const position of positions){
    const item=document.createElement('article'); item.className='card';
    const title=document.createElement(adminForm ? 'h3' : 'h2'); title.textContent=position.title;
    const description=document.createElement('p'); description.textContent=adminForm ? position.description : position.description.split('\n')[0];
    item.append(title,description);
    if (!adminForm) {
      item.append(roleLink(position));
    }
    if(adminForm){const close=document.createElement('button'); close.type='button';close.className='btn btn-outline';close.textContent='Close position';close.onclick=async()=>{if(await RDUI.ask('Close '+position.title+'?')){await RD.call('manage-position',{closeId:position.id});await showPositions();}};item.append(close);}
    list.append(item);
  }
}

function roleLink(role) {
  const link = document.createElement('a');
  link.href = 'roles.html?position=' + encodeURIComponent(role.id);
  link.className = 'btn btn-outline role-action';
  link.textContent = 'Learn more';
  link.setAttribute('aria-label', 'Learn more about ' + role.title);
  return link;
}

async function showRoleDetails(id, list) {
  const role = (await RD.call('positions')).find(position => String(position.id) === id);
  list.replaceChildren();
  if (!role) {
    list.textContent = 'This position is no longer listed.';
    return;
  }
  document.getElementById('roles-heading').textContent = role.title;
  document.title = role.title + ' | RepairDesk';
  const description = document.createElement('p');
  description.textContent = role.description;
  list.append(description);

  let email;
  try {
    ({email} = await RD.call('shop-contact'));
  } catch {
    const retry = document.createElement('button');
    retry.type = 'button';
    retry.className = 'btn btn-outline';
    retry.textContent = 'Retry loading application email';
    retry.onclick = () => location.reload();
    list.append(retry);
    return;
  }
  if (!email) {
    const message = document.createElement('p');
    message.textContent = 'An application email address has not been set.';
    list.append(message);
    return;
  }
  const apply = document.createElement('a');
  apply.className = 'btn btn-primary';
  apply.textContent = 'Apply';
  apply.href = 'mailto:' + encodeURIComponent(email).replace('%40', '@') +
    '?subject=' + encodeURIComponent('Application for ' + role.title);
  list.append(apply);
}

if(document.getElementById('position-form')){
  document.addEventListener('repairdesk:ready',()=>{
    document.getElementById('position-form').onsubmit=async e=>{
      e.preventDefault();const form=e.currentTarget;const data=new FormData(form);
      await RD.call('manage-position',{title:data.get('title'),description:data.get('description')});form.reset();await showPositions();
    };showPositions();
  });
} else {
  async function loadOpenRoles() {
    const list = document.getElementById('positions');
    list.textContent = 'Loading open roles…';
    list.setAttribute('aria-busy', 'true');
    try {
      if (selectedRole === null) await showPositions();
      else await showRoleDetails(selectedRole, list);
    } catch {
      const message = document.createElement('p');
      message.textContent = 'We couldn’t load the open roles.';
      const retry = document.createElement('button');
      retry.type = 'button';
      retry.className = 'btn btn-outline';
      retry.textContent = 'Try again';
      retry.onclick = loadOpenRoles;
      list.replaceChildren(message, retry);
    } finally {
      list.removeAttribute('aria-busy');
    }
  }
  const selectedRole = new URLSearchParams(location.search).get('position');
  if (selectedRole !== null) {
    const back = document.getElementById('roles-back');
    back.href = 'roles.html';
    back.textContent = '← All open roles';
    document.getElementById('roles-heading').textContent = 'Position details';
  }
  loadOpenRoles();
}
