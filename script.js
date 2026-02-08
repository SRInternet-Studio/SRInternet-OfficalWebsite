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

// Join Us functionality with confirmation dialog
const joinUsUrl = 'https://sr-studio.feishu.cn/share/base/form/shrcnCXosqYjZzH6GJZhOScLjIh';
const dialog = document.getElementById('join-us-dialog');
const dialogConfirm = document.getElementById('dialog-confirm');
const dialogCancel = document.getElementById('dialog-cancel');
const joinUsLinks = document.querySelectorAll('#join-us-nav, #join-us-hero, #join-us-footer');

const openDialog = (e) => {
  e.preventDefault();
  if (dialog) {
    dialog.classList.add('is-open');
    dialogConfirm?.focus();
    document.body.style.overflow = 'hidden';
  }
};

const closeDialog = () => {
  if (dialog) {
    dialog.classList.remove('is-open');
    document.body.style.overflow = '';
  }
};

joinUsLinks.forEach(link => {
  link.addEventListener('click', openDialog);
});

if (dialogConfirm) {
  dialogConfirm.addEventListener('click', () => {
    window.open(joinUsUrl, '_blank', 'noopener,noreferrer');
    closeDialog();
  });
}

if (dialogCancel) {
  dialogCancel.addEventListener('click', closeDialog);
}

if (dialog) {
  dialog.addEventListener('click', (e) => {
    if (e.target === dialog) {
      closeDialog();
    }
  });
  
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && dialog.classList.contains('is-open')) {
      closeDialog();
    }
  });
}
