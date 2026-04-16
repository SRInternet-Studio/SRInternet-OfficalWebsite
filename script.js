const navToggle = document.querySelector('[data-nav-toggle]');
const nav = document.getElementById('primary-nav');
const header = document.querySelector('[data-header]');
const hero = document.getElementById('home');
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
    const target = event.target;
    if (!(target instanceof Node)) return;
    if (!nav.contains(target) && !navToggle.contains(target)) {
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

if (hero && window.matchMedia('(prefers-reduced-motion: no-preference)').matches) {
  hero.addEventListener('pointermove', event => {
    const rect = hero.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;
    hero.style.setProperty('--hero-pointer-x', `${x}px`);
    hero.style.setProperty('--hero-pointer-y', `${y}px`);
  });

  hero.addEventListener('pointerleave', () => {
    hero.style.setProperty('--hero-pointer-x', '50%');
    hero.style.setProperty('--hero-pointer-y', '35%');
  });
}

const currentYear = new Date().getFullYear();
if (copyrightContent) {
  copyrightContent.innerText = `© ${currentYear} SR思锐 团队 保留所有权利.`;
}

// Advertisement System
const adSection = document.getElementById('advertisement');
const adContent = document.getElementById('ad-content');
const adWrapper = adSection?.querySelector('.ad-wrapper');
const adCloseBtn = adSection?.querySelector('.ad-close');

const adConfig = {
  enabled: true,
  apiEndpoint: '/api/ads',
  expiryDate: new Date('2026-02-27T23:59:59+08:00'),
  fallbackAd: {
    id: 'sponsor-gift-coludai',
    type: 'iframe',
    priority: 'high',
    url: 'https://gift.coludai.cn',
    title: '赞助商 - Gift Coludai',
    content: '感谢赞助商的支持',
    startDate: new Date('2026-01-01T00:00:00+08:00'),
    endDate: new Date('2026-02-27T23:59:59+08:00')
  }
};

function shouldShowAd(ad) {
  const now = new Date();

  if (!adConfig.enabled) return false;
  if (now > adConfig.expiryDate) return false;
  if (ad.startDate && now < ad.startDate) return false;
  if (ad.endDate && now > ad.endDate) return false;

  try {
    const closedAds = JSON.parse(sessionStorage.getItem('closedAds') || '[]');
    if (Array.isArray(closedAds) && closedAds.includes(ad.id)) return false;
  } catch (error) {
    // Ignore storage failures and continue.
  }

  return true;
}

function renderAd(ad) {
  if (!adContent || !adWrapper) return;

  if (adSection) {
    adSection.dataset.currentAdId = ad.id;
  }

  adContent.innerHTML = '';
  adContent.className = 'ad-content';
  adWrapper.setAttribute('data-priority', ad.priority);

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

function renderIframeAd(ad) {
  if (!adContent) return;

  adContent.classList.add('ad-type-iframe');

  const iframe = document.createElement('iframe');
  iframe.src = ad.url;
  iframe.title = ad.title || '赞助商内容';
  iframe.setAttribute('loading', 'lazy');
  iframe.setAttribute('sandbox', 'allow-scripts allow-forms allow-popups');

  adContent.appendChild(iframe);
}

function renderBannerAd(ad) {
  if (!adContent) return;

  adContent.classList.add('ad-type-banner');

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

function renderCardAd(ad) {
  if (!adContent) return;

  adContent.classList.add('ad-type-card');

  const cardDiv = document.createElement('div');
  cardDiv.className = 'ad-card';

  if (ad.imageUrl) {
    const img = document.createElement('img');
    img.src = ad.imageUrl;
    img.alt = ad.title || '赞助商';
    img.loading = 'lazy';
    cardDiv.appendChild(img);
  }

  const contentDiv = document.createElement('div');
  contentDiv.className = 'ad-card-content';

  const title = document.createElement('h3');
  title.textContent = ad.title || '赞助商';
  contentDiv.appendChild(title);

  const description = document.createElement('p');
  description.textContent = ad.content || ad.description || '';
  contentDiv.appendChild(description);

  const link = document.createElement('a');
  link.href = ad.url;
  link.className = 'btn btn-primary';
  link.target = '_blank';
  link.rel = 'noopener noreferrer';
  link.textContent = '了解更多';

  const icon = document.createElement('i');
  icon.className = 'fas fa-arrow-right';
  icon.setAttribute('aria-hidden', 'true');
  link.appendChild(icon);

  contentDiv.appendChild(link);
  cardDiv.appendChild(contentDiv);
  adContent.appendChild(cardDiv);
}

async function fetchAdsFromBackend() {
  if (!adConfig.apiEndpoint) {
    return [adConfig.fallbackAd];
  }

  try {
    const response = await fetch(adConfig.apiEndpoint, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json'
      },
      signal: AbortSignal.timeout(5000)
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    if (!data.success || !Array.isArray(data.data)) {
      throw new Error('Invalid API response format');
    }

    if (data.data.length === 0) {
      return [adConfig.fallbackAd];
    }

    return data.data.map(ad => ({
      ...ad,
      startDate: ad.startDate ? new Date(ad.startDate) : null,
      endDate: ad.endDate ? new Date(ad.endDate) : null
    }));
  } catch (error) {
    console.error('Failed to fetch ads from API:', error);
    return [adConfig.fallbackAd];
  }
}

function sortAdsByPriority(ads) {
  const priorityOrder = { high: 3, medium: 2, low: 1 };

  return ads.slice().sort((a, b) => {
    const aPriority = priorityOrder[a.priority] || 0;
    const bPriority = priorityOrder[b.priority] || 0;
    return bPriority - aPriority;
  });
}

async function initAdSystem() {
  if (!adSection) return;

  try {
    const ads = await fetchAdsFromBackend();
    const visibleAds = ads.filter(ad => shouldShowAd(ad));
    const sortedAds = sortAdsByPriority(visibleAds);

    if (sortedAds.length > 0) {
      renderAd(sortedAds[0]);
      adSection.classList.add('is-visible');
    }
  } catch (error) {
    console.error('Failed to initialize ad system:', error);
  }

  if (adCloseBtn) {
    adCloseBtn.addEventListener('click', () => {
      const adId = adSection?.dataset.currentAdId;

      const hideAdSection = () => {
        adSection?.classList.remove('is-visible');
      };

      try {
        if (typeof window === 'undefined' || !window.sessionStorage) {
          hideAdSection();
          return;
        }

        const storedValue = sessionStorage.getItem('closedAds');
        let closedAds = [];

        if (storedValue) {
          try {
            const parsed = JSON.parse(storedValue);
            if (Array.isArray(parsed)) {
              closedAds = parsed;
            }
          } catch (error) {
            closedAds = [];
          }
        }

        if (adId && !closedAds.includes(adId)) {
          closedAds.push(adId);
          sessionStorage.setItem('closedAds', JSON.stringify(closedAds));
        }
      } catch (error) {
        // Ignore storage errors and just hide the ad.
      } finally {
        hideAdSection();
      }
    });
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initAdSystem);
} else {
  initAdSystem();
}
