/* Same-origin API; no browser-stored credentials or permissions. */
window.RD = {
  endpoint: new URL('../api/index.php', document.currentScript.src),
  csrf: '', user: null,
  escape(value) { return String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); },
  async call(action, body) {
    const url = new URL(this.endpoint); url.searchParams.set('action', action);
    const response = await fetch(url, {method: body === undefined ? 'GET' : 'POST', credentials:'same-origin', headers: {'Content-Type':'application/json','X-CSRF-Token':this.csrf}, ...(body === undefined ? {} : {body: JSON.stringify(body)})});
    let data; try { data = await response.json(); } catch { throw new Error('Backend unavailable. Serve this project through PHP and complete database setup.'); }
    if (!response.ok) throw new Error(data.error || 'Request failed.');
    return data;
  },
  async session() { const s = await this.call('session'); this.csrf=s.csrf; this.user=s.user; this.demo=s.demo; return s; }
};
window.addEventListener('unhandledrejection', e => { e.preventDefault(); alert(e.reason?.message || 'Request failed.'); });
