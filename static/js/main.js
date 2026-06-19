// Theme
const THEME_STORAGE_KEY = 'sr-theme';
const THEME_META_COLORS = {
  dark: '#090b13',
  light: '#f7f9ff'
};
const root = document.documentElement;
const themeToggles = document.querySelectorAll('[data-theme-toggle]');
const themeColorMeta = document.querySelector('meta[name="theme-color"]');
const systemThemeQuery = window.matchMedia ? window.matchMedia('(prefers-color-scheme: light)') : null;

function getStoredTheme() {
  try {
    return localStorage.getItem(THEME_STORAGE_KEY);
  } catch (error) {
    return null;
  }
}

function getPreferredTheme() {
  const storedTheme = getStoredTheme();
  if (storedTheme === 'light' || storedTheme === 'dark') {
    return storedTheme;
  }

  return systemThemeQuery?.matches ? 'light' : 'dark';
}

function updateThemeToggleState(theme) {
  const nextTheme = theme === 'light' ? 'dark' : 'light';
  const text = nextTheme === 'light' ? '浅色' : '深色';

  themeToggles.forEach(button => {
    const textNode = button.querySelector('.theme-toggle__text');
    const sunIcon = button.querySelector('.icon-sun');
    const moonIcon = button.querySelector('.icon-moon');

    button.setAttribute('aria-label', `切换到${text}模式`);
    button.setAttribute('title', `切换到${text}模式`);
    button.setAttribute('aria-pressed', String(theme === 'light'));

    if (sunIcon && moonIcon) {
      if (theme === 'light') {
        sunIcon.style.display = 'none';
        moonIcon.style.display = 'block';
      } else {
        sunIcon.style.display = 'block';
        moonIcon.style.display = 'none';
      }
    } else {
      // Fallback for font-awesome if still used
      const iconNode = button.querySelector('.theme-toggle__icon');
      if (iconNode) {
        const iconClass = nextTheme === 'light' ? 'fa-sun' : 'fa-moon';
        iconNode.className = `fas ${iconClass} theme-toggle__icon`;
      }
    }

    if (textNode) textNode.textContent = text;
  });
}

function applyTheme(theme, options = {}) {
  const { persist = false } = options;

  root.setAttribute('data-theme', theme);
  updateThemeToggleState(theme);

  if (themeColorMeta) {
    themeColorMeta.setAttribute('content', THEME_META_COLORS[theme] || THEME_META_COLORS.dark);
  }

  if (persist) {
    try {
      localStorage.setItem(THEME_STORAGE_KEY, theme);
    } catch (error) {
      // Ignore storage errors and continue.
    }
  }
}

function initTheme() {
  applyTheme(root.getAttribute('data-theme') || getPreferredTheme());

  themeToggles.forEach(button => {
    button.addEventListener('click', (event) => {
      const currentTheme = root.getAttribute('data-theme') || getPreferredTheme();
      const nextTheme = currentTheme === 'light' ? 'dark' : 'light';
      
      // Support View Transitions API
      if (document.startViewTransition) {
        document.documentElement.classList.add('theme-transitioning');
        
        // Setup transition coordinates based on click event
        const x = event.clientX;
        const y = event.clientY;
        const endRadius = Math.hypot(
          Math.max(x, innerWidth - x),
          Math.max(y, innerHeight - y)
        );

        const transition = document.startViewTransition(() => {
          applyTheme(nextTheme, { persist: true });
        });

        transition.ready.then(() => {
          const clipPath = [
            `circle(0px at ${x}px ${y}px)`,
            `circle(${endRadius}px at ${x}px ${y}px)`
          ];
          document.documentElement.animate(
            {
              clipPath: clipPath
            },
            {
              duration: 400,
              easing: 'ease-out',
              pseudoElement: '::view-transition-new(root)'
            }
          );
        });

        transition.finished.finally(() => {
          document.documentElement.classList.remove('theme-transitioning');
        });
      } else {
        applyTheme(nextTheme, { persist: true });
      }
    });
  });

  if (systemThemeQuery) {
    const handleSystemThemeChange = event => {
      if (getStoredTheme()) return;
      applyTheme(event.matches ? 'light' : 'dark');
    };

    if (typeof systemThemeQuery.addEventListener === 'function') {
      systemThemeQuery.addEventListener('change', handleSystemThemeChange);
    } else if (typeof systemThemeQuery.addListener === 'function') {
      systemThemeQuery.addListener(handleSystemThemeChange);
    }
  }
}

// Nav
function initNav() {
  const navToggle = document.querySelector('[data-nav-toggle]');
  const nav = document.getElementById('primary-nav');
  const header = document.querySelector('[data-header]');
  
  let lastScrollY = window.scrollY;

  function handleHeaderScroll() {
    if (!header) return;
    
    const currentScrollY = window.scrollY;
    const isScrollingDown = currentScrollY > lastScrollY;
    
    // Background style logic
    if (currentScrollY > 20) {
      header.classList.add('is-scrolled');
    } else {
      header.classList.remove('is-scrolled');
    }
    
    lastScrollY = currentScrollY;
  }

  function handleScrollSpy() {
    const scrollPosition = window.scrollY + window.innerHeight / 3;

    // 每次重新获取导航链接和对应区域，以适应动态渲染的导航内容
    const currentNavLinks = document.querySelectorAll('.nav-links a[href^="#"]');
    const currentSections = Array.from(currentNavLinks).map(function (link) {
      const target = document.querySelector(link.getAttribute('href'));
      return { link: link, target: target };
    }).filter(function (item) { return item.target; });

    let currentActiveIndex = -1;
    currentSections.forEach((item, index) => {
      const top = item.target.offsetTop;
      const bottom = top + item.target.offsetHeight;
      if (scrollPosition >= top && scrollPosition < bottom) {
        currentActiveIndex = index;
      }
    });

    if ((window.innerHeight + Math.round(window.scrollY)) >= document.body.offsetHeight - 50) {
      currentActiveIndex = currentSections.length - 1;
    }

    if (currentActiveIndex !== -1) {
      document.querySelectorAll('.nav-links a').forEach(link => {
        if (link.getAttribute('href').startsWith('#')) {
          link.removeAttribute('aria-current');
        }
      });
      currentSections[currentActiveIndex].link.setAttribute('aria-current', 'page');
    } else if (window.scrollY < 100 && currentSections.length > 0) {
      document.querySelectorAll('.nav-links a').forEach(link => {
        if (link.getAttribute('href').startsWith('#')) {
          link.removeAttribute('aria-current');
        }
      });
      currentSections[0].link.setAttribute('aria-current', 'page');
    }
  }

  window.addEventListener('scroll', () => {
    handleHeaderScroll();
    handleScrollSpy();
  }, { passive: true });
  
  handleScrollSpy();

  const setNavOpenState = isOpen => {
    if (!nav) return;
    nav.classList.toggle('is-open', isOpen);
    if (navToggle) {
      navToggle.setAttribute('aria-expanded', String(isOpen));
    }
  };

  const closeNav = () => {
    setNavOpenState(false);
  };

  if (navToggle && nav) {
    navToggle.addEventListener('click', () => {
      const expanded = navToggle.getAttribute('aria-expanded') === 'true';
      setNavOpenState(!expanded);
    });

    nav.addEventListener('click', event => {
      const target = event.target;
      if (!(target instanceof Element)) return;
      if (target.closest('a')) {
        closeNav();
      }
    });

    document.addEventListener('click', event => {
      const target = event.target;
      if (!(target instanceof Node)) return;
      if (window.innerWidth < 840 && !nav.contains(target) && !navToggle.contains(target)) {
        closeNav();
      }
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth >= 840) {
        nav.classList.remove('is-open');
        navToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }
}

// Layout
function initHero() {
  const hero = document.getElementById('home');
  
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
}

function initFooter() {
  const copyrightContent = document.getElementById('copyright');
  const currentYear = new Date().getFullYear();
  if (copyrightContent) {
    copyrightContent.innerText = `© ${currentYear} SR思锐 团队 保留所有权利.`;
  }
}

// Ripple Effect for Team Cards
document.addEventListener('click', (event) => {
  const target = event.target;
  if (!(target instanceof Element)) return;

  const card = target.closest('.team-card:not(.team-card--more)');
  if (!(card instanceof HTMLElement)) return;

  const rect = card.getBoundingClientRect();
  const x = event.clientX - rect.left;
  const y = event.clientY - rect.top;

  const ripple = document.createElement('span');
  ripple.classList.add('ripple');

  const maxDim = Math.max(card.clientWidth, card.clientHeight);
  ripple.style.width = ripple.style.height = `${maxDim}px`;
  ripple.style.left = `${x - maxDim / 2}px`;
  ripple.style.top = `${y - maxDim / 2}px`;

  card.appendChild(ripple);

  setTimeout(() => {
    ripple.remove();
  }, 600);
});

// Advertisement System
function initAdSystem() {
  const adSection = document.getElementById('advertisement');
  const adContent = document.getElementById('ad-content');
  const adWrapper = adSection?.querySelector('.ad-wrapper');
  const adCloseBtn = adSection?.querySelector('.ad-close');

  if (!adSection) return;

  const adConfig = {
    enabled: true,
    apiEndpoint: '/api/ads.php',
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

    if (window.location.protocol === 'file:') {
      console.info('Advertisement API is skipped under file:// protocol; using fallback ad.');
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

  async function initialize() {
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

  initialize();
}

function initApp() {
  initTheme();
  initNav();
  initHero();
  initFooter();
  initAdSystem();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initApp);
} else {
  initApp();
}
