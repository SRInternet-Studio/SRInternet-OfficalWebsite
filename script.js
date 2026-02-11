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

// Advertisement System
const adSection = document.getElementById('advertisement');
const adContent = document.getElementById('ad-content');
const adWrapper = adSection?.querySelector('.ad-wrapper');
const adCloseBtn = adSection?.querySelector('.ad-close');

// Advertisement configuration
const adConfig = {
  // Enable/disable the ad system
  enabled: true,
  
  // Expiry date for automatic hiding (February 27, 2026)
  expiryDate: new Date('2026-02-27T23:59:59+08:00'),
  
  // Current ad data (will be replaced by Feishu API in the future)
  currentAd: {
    id: 'sponsor-gift-coludai',
    type: 'iframe', // 'iframe', 'banner', 'card'
    priority: 'high', // 'high', 'medium', 'low'
    url: 'https://gift.coludai.cn',
    title: '赞助商 - Gift Coludai',
    description: '感谢赞助商的支持',
    startDate: new Date('2026-01-01T00:00:00+08:00'),
    endDate: new Date('2026-02-27T23:59:59+08:00')
  }
};

/**
 * Check if an advertisement should be displayed
 * @param {Object} ad - Advertisement data
 * @returns {boolean} - Whether the ad should be shown
 */
function shouldShowAd(ad) {
  const now = new Date();
  
  // Check if ad system is enabled
  if (!adConfig.enabled) return false;
  
  // Check global expiry date
  if (now > adConfig.expiryDate) return false;
  
  // Check ad-specific date range
  if (ad.startDate && now < ad.startDate) return false;
  if (ad.endDate && now > ad.endDate) return false;
  
  // Check if user has closed this ad (stored in sessionStorage)
  const closedAds = JSON.parse(sessionStorage.getItem('closedAds') || '[]');
  if (closedAds.includes(ad.id)) return false;
  
  return true;
}

/**
 * Render an advertisement based on its type
 * @param {Object} ad - Advertisement data
 */
function renderAd(ad) {
  if (!adContent || !adWrapper) return;
  
  // Clear previous content
  adContent.innerHTML = '';
  adContent.className = 'ad-content';
  
  // Set priority
  adWrapper.setAttribute('data-priority', ad.priority);
  
  // Render based on type
  switch (ad.type) {
    case 'iframe':
      renderIframeAd(ad);
      break;
    case 'banner':
      renderBannerAd(ad);
      break;
    case 'card':
      renderCardAd(ad);
      break;
    default:
      console.warn('Unknown ad type:', ad.type);
  }
}

/**
 * Render an iframe-based advertisement
 * @param {Object} ad - Advertisement data
 */
function renderIframeAd(ad) {
  adContent.classList.add('ad-type-iframe');
  
  const iframe = document.createElement('iframe');
  iframe.src = ad.url;
  iframe.title = ad.title || '赞助商内容';
  iframe.setAttribute('loading', 'lazy');
  iframe.setAttribute('sandbox', 'allow-scripts allow-forms allow-popups');
  
  adContent.appendChild(iframe);
}

/**
 * Render a banner-based advertisement
 * @param {Object} ad - Advertisement data
 */
function renderBannerAd(ad) {
  adContent.classList.add('ad-type-banner');
  
  const link = document.createElement('a');
  link.href = ad.url;
  link.target = '_blank';
  link.rel = 'noopener noreferrer';
  
  const img = document.createElement('img');
  img.src = ad.imageUrl;
  img.alt = ad.title || '赞助商广告';
  img.loading = 'lazy';
  
  link.appendChild(img);
  adContent.appendChild(link);
}

/**
 * Render a card-based advertisement
 * @param {Object} ad - Advertisement data
 */
function renderCardAd(ad) {
  adContent.classList.add('ad-type-card');
  
  const cardHTML = `
    <div class="ad-card">
      ${ad.imageUrl ? `<img src="${ad.imageUrl}" alt="${ad.title || '赞助商'}" loading="lazy">` : ''}
      <div class="ad-card-content">
        <h3>${ad.title || '赞助商'}</h3>
        <p>${ad.description || ''}</p>
        <a href="${ad.url}" class="btn btn-primary" target="_blank" rel="noopener noreferrer">
          了解更多 <i class="fas fa-arrow-right" aria-hidden="true"></i>
        </a>
      </div>
    </div>
  `;
  
  adContent.innerHTML = cardHTML;
}

/**
 * Initialize the advertisement system
 */
function initAdSystem() {
  if (!adSection) return;
  
  const ad = adConfig.currentAd;
  
  // Check if ad should be shown
  if (shouldShowAd(ad)) {
    renderAd(ad);
    adSection.classList.add('is-visible');
  }
  
  // Handle close button
  if (adCloseBtn) {
    adCloseBtn.addEventListener('click', () => {
      // Store closed ad ID in sessionStorage
      const closedAds = JSON.parse(sessionStorage.getItem('closedAds') || '[]');
      if (!closedAds.includes(ad.id)) {
        closedAds.push(ad.id);
        sessionStorage.setItem('closedAds', JSON.stringify(closedAds));
      }
      
      // Hide the ad section
      adSection.classList.remove('is-visible');
    });
  }
}

// Initialize ad system when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initAdSystem);
} else {
  initAdSystem();
}

/**
 * Future API integration function (placeholder)
 * This function will fetch ad data from Feishu API
 */
async function fetchAdsFromFeishu() {
  // TODO: Implement Feishu API integration
  // const response = await fetch('FEISHU_API_ENDPOINT');
  // const data = await response.json();
  // return processFeishuData(data);
  
  // For now, return current static config
  return [adConfig.currentAd];
}

/**
 * Process Feishu API data to ad format
 * @param {Object} data - Raw Feishu data
 * @returns {Array} - Processed ad objects
 */
function processFeishuData(data) {
  // TODO: Transform Feishu multi-dimensional table data to ad format
  // Example structure:
  // {
  //   id: string,
  //   type: 'iframe' | 'banner' | 'card',
  //   priority: 'high' | 'medium' | 'low',
  //   url: string,
  //   title: string,
  //   description: string,
  //   imageUrl: string,
  //   startDate: Date,
  //   endDate: Date
  // }
  return [];
}
