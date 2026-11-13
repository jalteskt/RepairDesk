(() => {
  const key = 'repairdesk-theme';
  const system = matchMedia('(prefers-color-scheme: dark)');
  let preference;
  try { preference = localStorage.getItem(key); } catch {}
  let toggle;
  function render() {
    const theme = ['light', 'dark'].includes(preference) ? preference : system.matches ? 'dark' : 'light';
    document.documentElement.dataset.theme = theme;
    const favicon = document.querySelector('link[rel="icon"]');
    if (favicon) {
      const filename = theme === 'light' ? 'logo-white.svg' : 'logo.svg';
      favicon.href = new URL(filename, favicon.href).href;
    }
    if (toggle) {
      toggle.setAttribute('aria-pressed', String(theme === 'dark'));
      toggle.title = theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode';
    }
  }
  render();
  system.addEventListener('change', render);
  window.addEventListener('storage', event => {
    if (event.key === key || event.key === null) { preference = event.newValue; render(); }
  });
  document.addEventListener('DOMContentLoaded', () => {
    toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'theme-control';
    toggle.setAttribute('aria-label', 'Dark mode');
    toggle.innerHTML = `
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
           stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path class="moon" d="M20.8 13.1A9 9 0 0 1 10.9 3.2a9 9 0 1 0 9.9 9.9Z"/>
        <g class="sun">
          <circle cx="12" cy="12" r="4"/>
          <path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.4 1.4m11.2 11.2L19 19M5 19l1.4-1.4M17.6 6.4 19 5"/>
        </g>
      </svg>`;
    toggle.onclick = () => {
      preference = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
      try { localStorage.setItem(key, preference); } catch {}
      render();
    };
    document.body.append(toggle);
    render();
  });
})();
