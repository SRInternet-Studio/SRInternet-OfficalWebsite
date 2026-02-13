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
  
  // API endpoint for fetching ads (set to null to use fallback)
  apiEndpoint: '/api/ads',
  
  // Expiry date for automatic hiding (February 27, 2026)
  expiryDate: new Date('2026-02-27T23:59:59+08:00'),
  
  // Fallback ad data (used when API is unavailable)
  fallbackAd: {
    id: 'sponsor-gift-coludai',
    type: 'iframe', // 'iframe', 'banner', 'card'
    priority: 'high', // 'high', 'medium', 'low'
    url: 'https://gift.coludai.cn',
    title: '赞助商 - Gift Coludai',
    content: '感谢赞助商的支持',
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
  try {
    const closedAds = JSON.parse(sessionStorage.getItem('closedAds') || '[]');
    if (Array.isArray(closedAds) && closedAds.includes(ad.id)) return false;
  } catch (e) {
    // Ignore storage access failures and JSON parse errors, continue showing the ad
  }
  
  return true;
}

/**
 * Render an advertisement based on its type
 * @param {Object} ad - Advertisement data
 */
function renderAd(ad) {
  if (!adContent || !adWrapper) return;
  
  // Store current ad ID for close button
  if (adSection) {
    adSection.dataset.currentAdId = ad.id;
  }
  
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
  
  // Check if imageUrl is provided
  if (!ad.imageUrl) {
    console.warn(`Banner ad (ID: ${ad.id}) missing imageUrl, skipping render`);
    return;
  }
  
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
  
  // Create card container
  const cardDiv = document.createElement('div');
  cardDiv.className = 'ad-card';
  
  // Add image if provided
  if (ad.imageUrl) {
    const img = document.createElement('img');
    img.src = ad.imageUrl;
    img.alt = ad.title || '赞助商';
    img.loading = 'lazy';
    cardDiv.appendChild(img);
  }
  
  // Create card content
  const contentDiv = document.createElement('div');
  contentDiv.className = 'ad-card-content';
  
  // Add title
  const title = document.createElement('h3');
  title.textContent = ad.title || '赞助商';
  contentDiv.appendChild(title);
  
  // Add description
  const description = document.createElement('p');
  description.textContent = ad.content || ad.description || '';
  contentDiv.appendChild(description);
  
  // Add link button
  const link = document.createElement('a');
  link.href = ad.url;
  link.className = 'btn btn-primary';
  link.target = '_blank';
  link.rel = 'noopener noreferrer';
  link.textContent = '了解更多 ';
  
  const icon = document.createElement('i');
  icon.className = 'fas fa-arrow-right';
  icon.setAttribute('aria-hidden', 'true');
  link.appendChild(icon);
  
  contentDiv.appendChild(link);
  cardDiv.appendChild(contentDiv);
  
  adContent.appendChild(cardDiv);
}

/**
 * Initialize the advertisement system
 */
async function initAdSystem() {
  if (!adSection) return;
  
  try {
    // Fetch ads from API
    const ads = await fetchAdsFromFeishu();
    
    // Filter ads that should be shown
    const visibleAds = ads.filter(ad => shouldShowAd(ad));
    
    // Sort by priority
    const sortedAds = sortAdsByPriority(visibleAds);
    
    // Display the first ad if available
    if (sortedAds.length > 0) {
      renderAd(sortedAds[0]);
      adSection.classList.add('is-visible');
    }
  } catch (error) {
    console.error('Failed to initialize ad system:', error);
  }
  
  // Handle close button
  if (adCloseBtn) {
    adCloseBtn.addEventListener('click', () => {
      const adId = adSection.dataset.currentAdId;
      const hideAdSection = () => {
        adSection.classList.remove('is-visible');
      };

      try {
        // Guard against unavailable or blocked sessionStorage
        if (typeof window === 'undefined' || !window.sessionStorage) {
          hideAdSection();
          return;
        }

        const storedValue = sessionStorage.getItem('closedAds');
        let closedAds;

        if (storedValue == null || storedValue === '') {
          closedAds = [];
        } else {
          try {
            closedAds = JSON.parse(storedValue);
          } catch (e) {
            // Malformed JSON, reset to empty list
            closedAds = [];
          }

          if (!Array.isArray(closedAds)) {
            closedAds = [];
          }
        }

        if (adId && !closedAds.includes(adId)) {
          closedAds.push(adId);
          sessionStorage.setItem('closedAds', JSON.stringify(closedAds));
        }
      } catch (e) {
        // Ignore storage errors and fall back to just hiding the ad
      } finally {
        // Always hide the ad section, even if storage fails
        hideAdSection();
      }
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
 * Fetch advertisements from backend API
 * @returns {Promise<Array>} Array of ad objects
 */
async function fetchAdsFromFeishu() {
  // If API endpoint is not configured, use fallback
  if (!adConfig.apiEndpoint) {
    console.log('API endpoint not configured, using fallback ad');
    return [adConfig.fallbackAd];
  }
  
  try {
    const response = await fetch(adConfig.apiEndpoint, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json'
      },
      // Add timeout
      signal: AbortSignal.timeout(5000)
    });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    
    if (!data.success || !Array.isArray(data.data)) {
      throw new Error('Invalid API response format');
    }
    
    // If no ads returned, use fallback
    if (data.data.length === 0) {
      console.log('No ads from API, using fallback');
      return [adConfig.fallbackAd];
    }
    
    // Process dates
    return data.data.map(ad => ({
      ...ad,
      startDate: ad.startDate ? new Date(ad.startDate) : null,
      endDate: ad.endDate ? new Date(ad.endDate) : null
    }));
  } catch (error) {
    console.error('Failed to fetch ads from API:', error);
    // Fallback to default ad on error
    return [adConfig.fallbackAd];
  }
}

/**
 * Sort ads by priority
 * @param {Array} ads - Array of ad objects
 * @returns {Array} Sorted ads
 */
function sortAdsByPriority(ads) {
  const priorityOrder = { high: 3, medium: 2, low: 1 };
  
  return ads.slice().sort((a, b) => {
    const aPriority = priorityOrder[a.priority] || 0;
    const bPriority = priorityOrder[b.priority] || 0;
    return bPriority - aPriority;
  });
}
