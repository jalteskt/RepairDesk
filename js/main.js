
document.addEventListener('DOMContentLoaded', () => {
  initMobileMenu();
  initNavScroll();
  initActiveNavLink();
  initSmoothScroll();
  initAccordions();
});

function initMobileMenu() {
  const hamburger = document.querySelector('.navbar-hamburger');
  const mobileMenu = document.querySelector('.navbar-mobile');

  if (!hamburger || !mobileMenu) return;

  hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('active');
    mobileMenu.classList.toggle('open');
    document.body.style.overflow = mobileMenu.classList.contains('open') ? 'hidden' : '';
  });

  mobileMenu.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      hamburger.classList.remove('active');
      mobileMenu.classList.remove('open');
      document.body.style.overflow = '';
    });
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 860) {
      hamburger.classList.remove('active');
      mobileMenu.classList.remove('open');
      document.body.style.overflow = '';
    }
  });
}

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

function initActiveNavLink() {
  const currentPath = window.location.pathname;
  const navLinks = document.querySelectorAll('.navbar-links a, .navbar-mobile a:not(.btn)');

  navLinks.forEach(link => {
    const href = link.getAttribute('href');
    if (!href) return;
    if (href.charAt(0) === '#') return; // in-page anchors (e.g. #need-a-repair) never take the active state

    const linkPath = new URL(href, window.location.origin).pathname;

    const currentFile = currentPath.split('/').pop() || 'index.html';
    const linkFile = linkPath.split('/').pop() || 'index.html';

    if (currentFile === linkFile) {
      link.classList.add('active');
    }
  });
}

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

function initAccordions() {
  const accordionTriggers = document.querySelectorAll('.accordion-trigger');

  accordionTriggers.forEach(trigger => {
    trigger.addEventListener('click', () => {
      const item = trigger.closest('.accordion-item');
      const isActive = item.classList.contains('active');

      const accordion = item.closest('.accordion');
      if (accordion) {
        accordion.querySelectorAll('.accordion-item').forEach(i => {
          i.classList.remove('active');
        });
      }

      if (!isActive) {
        item.classList.add('active');
      }
    });
  });
}
