(() => {
  const login = document.querySelector('#login-form');
  const form = document.querySelector('#recovery-form');
  const heading = document.querySelector('.auth-title');
  const email = form.elements.email;
  const details = document.querySelector('#reset-details');
  const submit = form.querySelector('[type="submit"]');
  const status = document.querySelector('#recovery-status');

  function showRecovery(open) {
    login.hidden = open;
    form.hidden = !open;
    heading.textContent = open ? 'Reset your password' : 'Staff Sign In';
    form.reset();
    details.hidden = details.disabled = true;
    email.readOnly = false;
    email.value = login.querySelector('#email').value;
    submit.textContent = 'Email reset code';
    status.textContent = '';
    (open ? email : login.querySelector('#email')).focus();
  }
  document.querySelector('#forgot-password').onclick = () => showRecovery(true);
  document.querySelector('#back-to-login').onclick = () => showRecovery(false);
  form.onsubmit = async event => {
    event.preventDefault();
    submit.disabled = true;
    const back = document.querySelector('#back-to-login');
    back.disabled = true;
    try {
      await RD.session();
      if (details.hidden) {
        await RD.call('request-staff-reset', {email: email.value});
        status.textContent = 'If this email belongs to an active staff account, a code will arrive shortly. It expires in ten minutes.';
        email.readOnly = true;
        details.hidden = details.disabled = false;
        submit.textContent = 'Reset password';
        form.elements.code.focus();
      } else {
        await RD.call('reset-staff-password', {code: form.elements.code.value, password: form.elements.newPassword.value});
        login.querySelector('#email').value = email.value;
        login.querySelector('#password').value = '';
        showRecovery(false);
        await RDUI.notice('Your password has been updated. Sign in with your new password.');
      }
    } catch (error) {
      status.textContent = error.message;
    } finally {
      submit.disabled = back.disabled = false;
    }
  };
})();
