const navToggle = document.querySelector('[data-nav-toggle]');
const nav = document.getElementById('primary-nav');
const header = document.querySelector('[data-header]');
const copyrightContent = document.getElementById('copyright');

const closeNav = () => {
  if (!nav) return;
  nav.classList.remove('is-open');
  if (navToggle) {
    navToggle.setAttribute('aria-expanded', 'false');
  }
};

if (navToggle && nav) {
  navToggle.addEventListener('click', () => {
    const expanded = navToggle.getAttribute('aria-expanded') === 'true';
    navToggle.setAttribute('aria-expanded', String(!expanded));
    nav.classList.toggle('is-open');
  });

  nav.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', closeNav);
  });

  document.addEventListener('click', event => {
    if (!nav.contains(event.target) && !navToggle.contains(event.target)) {
      closeNav();
    }
  });
}

const handleScroll = () => {
  if (!header) return;
  header.classList.toggle('is-scrolled', window.scrollY > 12);
};

document.addEventListener('scroll', handleScroll, { passive: true });
handleScroll();

const currentYear = new Date().getFullYear();
if (copyrightContent) {
    copyrightContent.innerText = `© ${currentYear} SR思锐 团队. 保留所有权利.`;
}
