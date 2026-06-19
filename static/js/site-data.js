const SITE_CONTENT_ENDPOINT = '/api/site-content.php';
const INSTALL_PAGE_FALLBACK = '/install.php';

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function buildLinkAttributes(url, openInNewTab = true) {
  const target = openInNewTab ? ' target="_blank" rel="noopener noreferrer"' : '';
  return `href="${escapeHtml(url)}"${target}`;
}

function getArray(value) {
  return Array.isArray(value) ? value : [];
}

function setSectionLeadText(id, text) {
  const node = document.getElementById(id);
  if (node) {
    node.textContent = text;
  }
}

function renderGridPlaceholder(containerId, message) {
  const container = document.getElementById(containerId);
  if (!container) return;

  container.innerHTML = `
    <article class="content-placeholder">
      <p>${escapeHtml(message)}</p>
    </article>
  `;
}

function renderListPlaceholder(listId, message) {
  const list = document.getElementById(listId);
  if (!list) return;

  list.innerHTML = `
    <li class="content-placeholder content-placeholder--list">${escapeHtml(message)}</li>
  `;
}

function renderNavigation(data) {
  const navList = document.getElementById('nav-list');
  if (!navList) return;

  var navigationItems = getArray(data.navigation);
  if (navigationItems.length === 0) {
    navList.innerHTML = '<li><a href="#home" aria-current="page">首页</a></li>';
    return;
  }

  var currentHash = window.location.hash || '#home';

  navList.innerHTML = navigationItems.map(function (item) {
    var isSamePageLink = item.link && item.link.indexOf('#') === 0;
    var isActive = false;
    if (isSamePageLink) {
      if (item.link === currentHash) {
        isActive = true;
      }
    }
    var ariaCurrent = isActive ? ' aria-current="page"' : '';
    var attrs = buildLinkAttributes(item.link, Boolean(item.openInNewTab));
    return '<li><a ' + attrs + ariaCurrent + '>' + escapeHtml(item.name) + '</a></li>';
  }).join('');
}

function renderHero(data) {
  const titleNode = document.getElementById('hero-title');
  const subtitleNode = document.getElementById('hero-subtitle');
  const buttonsNode = document.getElementById('hero-buttons');
  const heroTitle = data.hero?.title || '欢迎来到官网';
  const heroSubtitle = data.hero?.subtitle || '当前还没有填写首页主视觉内容，后续可在后台继续补充。';

  if (titleNode) titleNode.textContent = heroTitle;
  if (subtitleNode) subtitleNode.textContent = heroSubtitle;

  if (buttonsNode) {
    const buttons = getArray(data.hero?.buttons);
    if (buttons.length === 0) {
      buttonsNode.innerHTML = `
        <span class="btn btn-ghost" aria-disabled="true">
          <i class="fas fa-pen" aria-hidden="true"></i>
          <span>待补充内容</span>
        </span>
      `;
      return;
    }

    buttonsNode.innerHTML = buttons.map(button => {
      const colorClass = ['btn-primary', 'btn-blue', 'btn-ghost'].includes(button.colorClass) ? button.colorClass : 'btn-primary';
      const isExternal = /^https?:\/\//i.test(button.link);
      const iconClass = typeof button.iconClass === 'string' && /\bfa-[a-z0-9-]+\b/i.test(button.iconClass)
        ? button.iconClass
        : 'fas fa-arrow-right';
      return `
        <a class="btn ${colorClass}" ${buildLinkAttributes(button.link, isExternal)}>
          <i class="${escapeHtml(iconClass)}" aria-hidden="true"></i>
          <span>${escapeHtml(button.label)}</span>
        </a>
      `;
    }).join('');
  }
}

function productCardClass(product) {
  return product.isRecommended ? 'card card-featured card-wide' : 'card';
}

function renderProducts(data) {
  const grid = document.getElementById('product-grid');
  if (!grid) return;

  const products = getArray(data.products);
  if (products.length === 0) {
    renderGridPlaceholder('product-grid', '暂时还没有产品展示，后续会在这里更新。');
    return;
  }

  grid.innerHTML = products.map(product => {
    const tagHtml = (product.tags || []).map(tag => `<li class="tag">${escapeHtml(tag)}</li>`).join('');
    const recommendedBadge = product.isRecommended ? '<div class="card-label">推荐</div>' : '';

    return `
      <a href="${escapeHtml(product.link)}" class="${productCardClass(product)}" target="_blank" rel="noopener noreferrer">
        <div class="card-bg">
          <img src="${escapeHtml(product.imageUrl)}" alt="${escapeHtml(product.name)} 产品图" loading="lazy" decoding="async">
        </div>
        <div class="card-overlay"></div>
        ${recommendedBadge}
        <div class="card-content">
          <div class="card-header">
            <h3>${escapeHtml(product.name)}</h3>
            <p class="card-desc">${escapeHtml(product.description)}</p>
          </div>
          <div class="card-hover-content">
            <div class="card-hover-content-inner">
              <ul class="tag-list">${tagHtml}</ul>
            </div>
          </div>
        </div>
      </a>
    `;
  }).join('');
}

function renderCommunity(data) {
  const communityGrid = document.getElementById('community-grid');
  if (!communityGrid) return;

  const community = data.community || {};
  const cards = [];

  if (community.bilibiliUrl) {
    cards.push(`
      <a href="${escapeHtml(community.bilibiliUrl)}" target="_blank" rel="noopener noreferrer" class="community-card bilibili-card">
        <div class="card-bg-glow"></div>
        <div class="community-icon"><i class="fab fa-bilibili" aria-hidden="true"></i></div>
        <div class="community-content">
          <h3 class="community-fill" data-text="Bilibili">Bilibili</h3>
          <p class="community-fill" data-text="关注我们的账号，获取最新教程与产品演示视频。">关注我们的账号，获取最新教程与产品演示视频。</p>
        </div>
        <div class="community-action"><span class="community-fill" data-text="前往 B 站">前往 B 站</span><i class="fas fa-arrow-right" aria-hidden="true"></i></div>
      </a>
    `);
  }

  if (community.githubUrl) {
    cards.push(`
      <a href="${escapeHtml(community.githubUrl)}" target="_blank" rel="noopener noreferrer" class="community-card github-card">
        <div class="card-bg-glow"></div>
        <div class="community-icon"><i class="fab fa-github" aria-hidden="true"></i></div>
        <div class="community-content">
          <h3 class="community-fill" data-text="GitHub">GitHub</h3>
          <p class="community-fill" data-text="访问仓库，参与开源项目，共同学习进步。">访问仓库，参与开源项目，共同学习进步。</p>
        </div>
        <div class="community-action"><span class="community-fill" data-text="前往 GitHub">前往 GitHub</span><i class="fas fa-arrow-right" aria-hidden="true"></i></div>
      </a>
    `);
  }

  if (community.qqGroupUrl) {
    cards.push(`
      <a href="${escapeHtml(community.qqGroupUrl)}" target="_blank" rel="noopener noreferrer" class="community-card qq-card">
        <div class="card-bg-glow"></div>
        <div class="community-icon"><i class="fab fa-qq" aria-hidden="true"></i></div>
        <div class="community-content">
          <h3 class="community-fill" data-text="QQ 交流群">QQ 交流群</h3>
          <p class="community-fill" data-text="加入 QQ 群，与志同道合的小伙伴实时交流。">加入 QQ 群，与志同道合的小伙伴实时交流。</p>
        </div>
        <div class="community-action"><span class="community-fill" data-text="加入 QQ 群">加入 QQ 群</span><i class="fas fa-arrow-right" aria-hidden="true"></i></div>
      </a>
    `);
  }

  if (cards.length === 0) {
    renderGridPlaceholder('community-grid', '社区入口暂未配置，敬请期待。');
    return;
  }

  communityGrid.innerHTML = cards.join('');
}

function renderMembers(data) {
  const teamGrid = document.getElementById('team-grid');
  if (!teamGrid) return;

  const members = getArray(data.members);

  const membersHtml = members.map(member => `
    <article class="team-card">
      <img src="${escapeHtml(member.avatarUrl)}" alt="${escapeHtml(member.name)} 头像" loading="lazy" decoding="async">
      <div>
        <h3>${escapeHtml(member.name)}</h3>
        <p class="muted">${escapeHtml(member.position)}</p>
        <p class="small">${escapeHtml(member.bio)}</p>
      </div>
    </article>
  `).join('');

  const joinLink = data.contact?.joinLink || '';
  const joinCard = joinLink ? `
    <a href="${escapeHtml(joinLink)}" target="_blank" rel="noopener noreferrer" class="team-card team-card--more" style="text-decoration: none;">
      <div>
        <i class="fas fa-plus" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.5rem;"></i>
        <h3>还有更多</h3>
        <p class="small">期待精彩的你也一起加入</p>
      </div>
    </a>
  ` : '';

  if (!membersHtml && !joinCard) {
    renderGridPlaceholder('team-grid', '团队成员信息暂未公开，后续会在这里展示。');
    return;
  }

  teamGrid.innerHTML = membersHtml + joinCard;
}

function renderFooterLinkItems(items) {
  if (!Array.isArray(items) || items.length === 0) return '';

  return items.map(item => {
    const label = item?.label ?? '';
    const url = item?.url ?? '#';
    const isExternal = /^(https?:\/\/|mailto:)/i.test(url);
    return `<li><a ${buildLinkAttributes(url, isExternal)}>${escapeHtml(label)}</a></li>`;
  }).join('');
}

function renderContact(data) {
  const businessList = document.getElementById('contact-business-list');
  const githubList = document.getElementById('contact-github-list');
  const joinList = document.getElementById('contact-join-list');

  const emails = Array.isArray(data.contact?.emails)
    ? data.contact.emails
    : (data.contact?.email ? [data.contact.email] : []);
  const githubRepositories = Array.isArray(data.contact?.githubRepositories)
    ? data.contact.githubRepositories
    : (data.contact?.githubRepository ? [data.contact.githubRepository] : []);

  if (businessList) {
    businessList.innerHTML = emails.length > 0 ? emails.map(email => `
      <li><a href="mailto:${escapeHtml(email)}"><i class="fas fa-envelope"></i>${escapeHtml(email)}</a></li>
    `).join('') : '<li class="content-placeholder content-placeholder--list">商务联系方式暂未填写。</li>';
  }

  if (githubList) {
    githubList.innerHTML = githubRepositories.length > 0 ? githubRepositories.map(repository => {
      let repoUrl = repository;
      let repoName = repository;

      if (typeof repository === 'object' && repository !== null) {
        repoUrl = repository.url;
        repoName = repository.name || repository.url;
      }

      if (repoName === repoUrl) {
        try {
          const urlObj = new URL(repoUrl);
          if (urlObj.hostname.includes('github.com')) {
            repoName = urlObj.pathname.replace(/^\/|\/$/g, '');
          }
        } catch (e) {}
      }
      
      return `<li><a href="${escapeHtml(repoUrl)}" target="_blank" rel="noopener noreferrer"><i class="fab fa-github"></i>${escapeHtml(repoName)}</a></li>`;
    }).join('') : '<li class="content-placeholder content-placeholder--list">GitHub 仓库信息暂未填写。</li>';
  }

  if (joinList) {
    const joinItems = [];

    if (data.contact?.joinLink) {
      joinItems.push(`<li><a href="${escapeHtml(data.contact.joinLink)}" target="_blank" rel="noopener noreferrer"><i class="fas fa-user-plus"></i>填写团队成员申请表单</a></li>`);
    }

    if (data.contact?.queryLink) {
      joinItems.push(`<li><a href="${escapeHtml(data.contact.queryLink)}" target="_blank" rel="noopener noreferrer"><i class="fas fa-search"></i>查询你的申请结果</a></li>`);
    }

    if (data.contact?.communityLink) {
      const isExternal = /^(https?:\/\/|mailto:)/i.test(data.contact.communityLink);
      joinItems.push(`<li><a ${buildLinkAttributes(data.contact.communityLink, isExternal)}><i class="fas fa-users"></i>用户社区</a></li>`);
    }

    joinList.innerHTML = joinItems.length > 0
      ? joinItems.join('')
      : '<li class="content-placeholder content-placeholder--list">加入方式暂未开放。</li>';
  }
}

function renderFooter(data) {
  const footerQuick = document.getElementById('footer-quick-links');
  const footerCommunity = document.getElementById('footer-community-links');
  const footerContact = document.getElementById('footer-contact-links');
  const footerLegal = document.getElementById('footer-legal-links');

  if (footerQuick) {
    footerQuick.innerHTML = renderFooterLinkItems(data.footer?.quickLinks) || '<li class="content-placeholder content-placeholder--list">暂无快捷链接</li>';
  }

  if (footerCommunity) {
    footerCommunity.innerHTML = renderFooterLinkItems(data.footer?.communityLinks) || '<li class="content-placeholder content-placeholder--list">暂无社区入口</li>';
  }

  if (footerContact) {
    footerContact.innerHTML = renderFooterLinkItems(data.footer?.contactLinks) || '<li class="content-placeholder content-placeholder--list">暂无联系信息</li>';
  }

  if (footerLegal) {
    footerLegal.innerHTML = renderFooterLinkItems(data.footer?.legalLinks) || '<li class="content-placeholder content-placeholder--list">暂无法律信息</li>';
  }

  const visitStats = document.getElementById('visit-stats');
  if (visitStats && data.visitStats) {
    visitStats.innerHTML = `总访问数: ${data.visitStats.totalVisits} | 今日访问: ${data.visitStats.todayVisits}`;
  }
}

function renderUninstalledState(installUrl) {
  const targetInstallUrl = typeof installUrl === 'string' && installUrl.trim() !== ''
    ? installUrl
    : INSTALL_PAGE_FALLBACK;

  renderNavigation({
    navigation: [
      { name: '首页', link: '#home', openInNewTab: false },
      { name: '安装系统', link: targetInstallUrl, openInNewTab: false }
    ]
  });

  renderHero({
    hero: {
      title: '官网尚未完成安装',
      subtitle: '请先完成系统安装，安装后再通过后台填写首页内容。',
      buttons: [
        {
          label: '进入安装',
          link: targetInstallUrl,
          colorClass: 'btn-primary',
          iconClass: 'fas fa-wrench'
        }
      ]
    }
  });

  setSectionLeadText('products-lead', '安装完成后，产品内容会显示在这里。');
  setSectionLeadText('community-lead', '安装完成后，可在这里展示社区入口。');
  setSectionLeadText('about-lead', '安装完成后，可在这里展示团队成员与介绍。');
  setSectionLeadText('contact-lead', '安装完成后，可在这里配置联系方式与加入方式。');

  renderGridPlaceholder('product-grid', '暂无产品内容，请先完成安装并在后台添加。');
  renderGridPlaceholder('community-grid', '暂无社区入口，请先完成安装。');
  renderGridPlaceholder('team-grid', '暂无团队成员，请先完成安装后配置。');
  renderListPlaceholder('contact-business-list', '暂无联系方式，请先完成安装。');
  renderListPlaceholder('contact-github-list', '暂无仓库信息，请先完成安装。');
  renderListPlaceholder('contact-join-list', '暂无加入方式，请先完成安装。');
  renderFooter({
    footer: {
      quickLinks: [
        { label: '首页', url: '#home' },
        { label: '安装系统', url: targetInstallUrl }
      ],
      communityLinks: [],
      contactLinks: [],
      legalLinks: []
    }
  });
}

function renderLoadErrorState() {
  renderNavigation({ navigation: [] });
  renderHero({
    hero: {
      title: '首页内容暂时无法加载',
      subtitle: '请检查站点接口或稍后刷新页面重试。',
      buttons: []
    }
  });
  renderGridPlaceholder('product-grid', '内容加载失败。');
  renderGridPlaceholder('community-grid', '内容加载失败。');
  renderGridPlaceholder('team-grid', '内容加载失败。');
  renderListPlaceholder('contact-business-list', '内容加载失败。');
  renderListPlaceholder('contact-github-list', '内容加载失败。');
  renderListPlaceholder('contact-join-list', '内容加载失败。');
}

function renderStaticPreviewState() {
  renderHero({
    hero: {
      title: '当前为静态预览模式',
      subtitle: '首页动态内容与安装检测需要通过支持 PHP 的 Web 环境访问。',
      buttons: []
    }
  });
  renderGridPlaceholder('product-grid', '静态预览下不会加载动态内容。');
  renderGridPlaceholder('community-grid', '静态预览下不会加载动态内容。');
  renderGridPlaceholder('team-grid', '静态预览下不会加载动态内容。');
  renderListPlaceholder('contact-business-list', '静态预览下不会加载动态内容。');
  renderListPlaceholder('contact-github-list', '静态预览下不会加载动态内容。');
  renderListPlaceholder('contact-join-list', '静态预览下不会加载动态内容。');
}

function hideSkeletonScreen() {
  const skeleton = document.getElementById('skeleton-screen');
  if (skeleton) {
    skeleton.classList.add('is-hidden');
    setTimeout(() => {
      skeleton.style.display = 'none';
    }, 500); // Wait for transition to finish
  }
}

async function initSiteData() {
  if (window.location.protocol === 'file:') {
    renderStaticPreviewState();
    hideSkeletonScreen();
    return;
  }

  try {
    const response = await fetch(SITE_CONTENT_ENDPOINT, {
      method: 'GET',
      headers: { 'Content-Type': 'application/json' }
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const payload = await response.json();
    if (!payload.success || !payload.data) {
      throw new Error('Invalid site content payload');
    }

    if (!payload.installed) {
      renderUninstalledState(payload.installUrl);
      hideSkeletonScreen();
      return;
    }

    renderNavigation(payload.data);
    renderHero(payload.data);
    renderProducts(payload.data);
    renderCommunity(payload.data);
    renderMembers(payload.data);
    renderContact(payload.data);
    renderFooter(payload.data);
  } catch (error) {
    console.error('Failed to load site content:', error);
    renderLoadErrorState();
  } finally {
    hideSkeletonScreen();
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initSiteData);
} else {
  initSiteData();
}
