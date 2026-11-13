(() => {
  const footer = document.querySelector('[data-site-footer]');
  if (!footer) return;
  const root = new URL('../', document.currentScript.src);
  async function loadFooter() {
    const response = await fetch(new URL('components/footer.html', root));
    if (!response.ok) return;
    const template = document.createElement('template');
    template.innerHTML = await response.text();
    const row = template.content.querySelector('.footer-inner');
    if (!row) return;
    for (const link of row.querySelectorAll('a[href]')) {
      link.href = new URL(link.getAttribute('href'), root).href;
    }
    const logo = row.querySelector('.site-wordmark');
    logo.src = new URL(logo.getAttribute('src'), root).href;
    footer.replaceChildren(row);
  }
  loadFooter().catch(() => {
    // Keep the static home link if the shared footer cannot be loaded.
  });
})();
