/* ============================================
   RepairDesk - Core JavaScript
   ============================================ */

document.addEventListener('DOMContentLoaded', () => {
  initMobileMenu();
  initNavScroll();
  initActiveNavLink();
  initSmoothScroll();
  initAccordions();
  initPricingToggle();
});

/* --- Mobile Menu Toggle --- */
function initMobileMenu() {
  const hamburger = document.querySelector('.navbar-hamburger');
  const mobileMenu = document.querySelector('.navbar-mobile');

  if (!hamburger || !mobileMenu) return;

  hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('active');
    mobileMenu.classList.toggle('open');
    document.body.style.overflow = mobileMenu.classList.contains('open') ? 'hidden' : '';
  });

  // Close menu when clicking a link
  mobileMenu.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      hamburger.classList.remove('active');
      mobileMenu.classList.remove('open');
      document.body.style.overflow = '';
    });
  });

  // Close menu on resize to desktop
  window.addEventListener('resize', () => {
    if (window.innerWidth > 860) {
      hamburger.classList.remove('active');
      mobileMenu.classList.remove('open');
      document.body.style.overflow = '';
    }
  });
}

/* --- Navbar Scroll Shadow --- */
function initNavScroll() {
  const navbar = document.querySelector('.navbar');
  if (!navbar) return;

  function updateNavbar() {
    if (window.scrollY > 10) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  }

  window.addEventListener('scroll', updateNavbar, { passive: true });
  updateNavbar();
}

/* --- Active Nav Link Highlighting --- */
function initActiveNavLink() {
  const currentPath = window.location.pathname;
  const navLinks = document.querySelectorAll('.navbar-links a, .navbar-mobile a:not(.btn)');

  navLinks.forEach(link => {
    const href = link.getAttribute('href');
    if (!href) return;

    // Normalize paths for comparison
    const linkPath = new URL(href, window.location.origin).pathname;

    // Check for exact match or if current page matches the link
    const currentFile = currentPath.split('/').pop() || 'index.html';
    const linkFile = linkPath.split('/').pop() || 'index.html';

    if (currentFile === linkFile) {
      link.classList.add('active');
    }
  });
}

/* --- Smooth Scroll for Anchor Links --- */
function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', (e) => {
      const targetId = anchor.getAttribute('href');
      if (targetId === '#') return;

      const target = document.querySelector(targetId);
      if (!target) return;

      e.preventDefault();

      const navbarHeight = document.querySelector('.navbar')?.offsetHeight || 72;
      const targetPosition = target.getBoundingClientRect().top + window.scrollY - navbarHeight - 16;

      window.scrollTo({
        top: targetPosition,
        behavior: 'smooth'
      });
    });
  });
}

/* --- Accordion --- */
function initAccordions() {
  const accordionTriggers = document.querySelectorAll('.accordion-trigger');

  accordionTriggers.forEach(trigger => {
    trigger.addEventListener('click', () => {
      const item = trigger.closest('.accordion-item');
      const isActive = item.classList.contains('active');

      // Close all items in the same accordion
      const accordion = item.closest('.accordion');
      if (accordion) {
        accordion.querySelectorAll('.accordion-item').forEach(i => {
          i.classList.remove('active');
        });
      }

      // Toggle current item
      if (!isActive) {
        item.classList.add('active');
      }
    });
  });
}

/* --- Pricing Toggle (Monthly / Annual) --- */
function initPricingToggle() {
  const toggleBtns = document.querySelectorAll('.toggle-btn');
  const monthlyPrices = document.querySelectorAll('.price-monthly');
  const annualPrices = document.querySelectorAll('.price-annual');

  if (toggleBtns.length === 0) return;

  toggleBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      toggleBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const isAnnual = btn.dataset.period === 'annual';

      monthlyPrices.forEach(el => {
        el.style.display = isAnnual ? 'none' : 'block';
      });

      annualPrices.forEach(el => {
        el.style.display = isAnnual ? 'block' : 'none';
      });
    });
  });
}

/* --- Simple Hash Router (SPA-like Navigation) --- */
class Router {
  constructor(routes = {}) {
    this.routes = routes;
    this.contentEl = null;
  }

  init(contentSelector) {
    this.contentEl = document.querySelector(contentSelector);
    if (!this.contentEl) return;

    window.addEventListener('hashchange', () => this.handleRoute());
    this.handleRoute();
  }

  handleRoute() {
    const hash = window.location.hash.slice(1) || '/';
    const route = this.routes[hash];

    if (route && this.contentEl) {
      if (typeof route === 'function') {
        route(this.contentEl);
      } else if (typeof route === 'string') {
        this.loadPage(route);
      }
    }
  }

  async loadPage(url) {
    if (!this.contentEl) return;

    try {
      const response = await fetch(url);
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const html = await response.text();

      // Extract body content if it's a full HTML page
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');
      const main = doc.querySelector('main') || doc.body;

      this.contentEl.innerHTML = main.innerHTML;
      window.scrollTo(0, 0);

      // Re-initialize interactive components
      initAccordions();
      initPricingToggle();
    } catch (err) {
      console.error('Router: Failed to load page', err);
      this.contentEl.innerHTML = '<div class="container section text-center"><h2>Page not found</h2><p>Sorry, we couldn\'t load that page.</p></div>';
    }
  }

  addRoute(path, handler) {
    this.routes[path] = handler;
  }
}

// Export router for optional use
window.RepairDeskRouter = Router;
