window.RDUI = {
  notice(message) { return this.show(message, 'notice'); },
  ask(message) { return this.show(message, 'confirm'); },
  input(message, value = '', type = 'text') { return this.show(message, 'input', value, type); },
  show(message, mode, value = '', type = 'text') {
    return new Promise(resolve => {
      const dialog = document.createElement('dialog');
      dialog.className = 'rd-dialog';
      const form = document.createElement('form');
      form.method = 'dialog';
      const title = document.createElement('h2');
      title.textContent = mode === 'notice' ? 'RepairDesk' : mode === 'input' ? 'Enter details' : 'Confirm action';
      dialog.setAttribute('aria-label',title.textContent);
      const label = document.createElement('label');
      label.textContent = message;
      const field = document.createElement('input');
      if (mode === 'input') {
        field.type = type; field.value = value; field.required = true;
        label.append(field);
      }
      const actions = document.createElement('div');
      actions.className = 'rd-actions';
      const submit = document.createElement('button');
      submit.className = 'btn btn-primary'; submit.value = 'ok';
      submit.textContent = mode === 'confirm' ? 'Confirm' : 'OK';
      if (mode !== 'notice') {
        const cancel = document.createElement('button');
        cancel.type = 'button'; cancel.textContent = 'Cancel'; cancel.className = 'btn btn-outline';
        cancel.onclick = () => dialog.close('cancel'); actions.append(cancel);
      }
      actions.append(submit); form.append(title,label,actions); dialog.append(form); document.body.append(dialog);
      dialog.addEventListener('close', () => {
        const ok = dialog.returnValue === 'ok'; dialog.remove();
        resolve(mode === 'input' ? (ok ? field.value : null) : ok);
      }, {once:true});
      dialog.showModal(); if (mode === 'input') field.focus();
    });
  }
};
