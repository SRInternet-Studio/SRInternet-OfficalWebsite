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

let lastFocusedElement = null;

// Get all focusable elements within the dialog
const getFocusableElements = () => {
  if (!dialog) return [];
  return dialog.querySelectorAll(
    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
  );
};

const trapFocus = (e) => {
  const focusableElements = getFocusableElements();
  if (focusableElements.length === 0) return;
  
  const firstElement = focusableElements[0];
  const lastElement = focusableElements[focusableElements.length - 1];
  
  if (e.key === 'Tab') {
    if (e.shiftKey && document.activeElement === firstElement) {
      e.preventDefault();
      lastElement.focus();
    } else if (!e.shiftKey && document.activeElement === lastElement) {
      e.preventDefault();
      firstElement.focus();
    }
  }
};

const handleEscape = (e) => {
  if (e.key === 'Escape' && dialog?.classList.contains('is-open')) {
    closeDialog();
  }
};

const openDialog = (e) => {
  e.preventDefault();
  if (dialog) {
    lastFocusedElement = document.activeElement;
    dialog.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    dialogConfirm?.focus();
    
    // Add event listeners only when dialog is open
    document.addEventListener('keydown', trapFocus);
    document.addEventListener('keydown', handleEscape);
  }
};

const closeDialog = () => {
  if (dialog) {
    dialog.classList.remove('is-open');
    document.body.style.overflow = '';
    
    // Remove event listeners when dialog is closed
    document.removeEventListener('keydown', trapFocus);
    document.removeEventListener('keydown', handleEscape);
    
    // Return focus to the element that opened the dialog
    if (lastFocusedElement) {
      lastFocusedElement.focus();
      lastFocusedElement = null;
    }
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
}
