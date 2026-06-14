const SITE_CONTENT_ENDPOINT = '/api/site-content.php';

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

function renderNavigation(data) {
  const navList = document.getElementById('nav-list');
  if (!navList || !Array.isArray(data.navigation)) return;

  navList.innerHTML = data.navigation.map((item, index) => {
    const ariaCurrent = index === 0 ? ' aria-current="page"' : '';
    const attrs = buildLinkAttributes(item.link, Boolean(item.openInNewTab));
    return `<li><a ${attrs}${ariaCurrent}>${escapeHtml(item.name)}</a></li>`;
  }).join('');
}

function renderHero(data) {
  const titleNode = document.getElementById('hero-title');
  const subtitleNode = document.getElementById('hero-subtitle');
  const buttonsNode = document.getElementById('hero-buttons');
  if (titleNode && data.hero?.title) titleNode.textContent = data.hero.title;
  if (subtitleNode && data.hero?.subtitle) subtitleNode.textContent = data.hero.subtitle;

  if (buttonsNode && Array.isArray(data.hero?.buttons) && data.hero.buttons.length > 0) {
    buttonsNode.innerHTML = data.hero.buttons.map(button => {
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
  if (!grid || !Array.isArray(data.products) || data.products.length === 0) return;

  grid.innerHTML = data.products.map(product => {
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
  if (!communityGrid || !data.community) return;

  communityGrid.innerHTML = `
    <a href="${escapeHtml(data.community.bilibiliUrl)}" target="_blank" rel="noopener noreferrer" class="community-card bilibili-card">
      <div class="card-bg-glow"></div>
      <div class="community-icon"><i class="fab fa-bilibili" aria-hidden="true"></i></div>
      <div class="community-content">
        <h3 class="community-fill" data-text="Bilibili">Bilibili</h3>
        <p class="community-fill" data-text="关注我们的账号，获取最新教程与产品演示视频。">关注我们的账号，获取最新教程与产品演示视频。</p>
      </div>
      <div class="community-action"><span class="community-fill" data-text="前往 B 站">前往 B 站</span><i class="fas fa-arrow-right" aria-hidden="true"></i></div>
    </a>
    <a href="${escapeHtml(data.community.githubUrl)}" target="_blank" rel="noopener noreferrer" class="community-card github-card">
      <div class="card-bg-glow"></div>
      <div class="community-icon"><i class="fab fa-github" aria-hidden="true"></i></div>
      <div class="community-content">
        <h3 class="community-fill" data-text="GitHub">GitHub</h3>
        <p class="community-fill" data-text="访问仓库，参与开源项目，共同学习进步。">访问仓库，参与开源项目，共同学习进步。</p>
      </div>
      <div class="community-action"><span class="community-fill" data-text="前往 GitHub">前往 GitHub</span><i class="fas fa-arrow-right" aria-hidden="true"></i></div>
    </a>
    <a href="${escapeHtml(data.community.qqGroupUrl)}" target="_blank" rel="noopener noreferrer" class="community-card qq-card">
      <div class="card-bg-glow"></div>
      <div class="community-icon"><i class="fab fa-qq" aria-hidden="true"></i></div>
      <div class="community-content">
        <h3 class="community-fill" data-text="QQ 交流群">QQ 交流群</h3>
        <p class="community-fill" data-text="加入 QQ 群，与志同道合的小伙伴实时交流。">加入 QQ 群，与志同道合的小伙伴实时交流。</p>
      </div>
      <div class="community-action"><span class="community-fill" data-text="加入 QQ 群">加入 QQ 群</span><i class="fas fa-arrow-right" aria-hidden="true"></i></div>
    </a>
  `;
}

function renderMembers(data) {
  const teamGrid = document.getElementById('team-grid');
  if (!teamGrid || !Array.isArray(data.members)) return;

  const membersHtml = data.members.map(member => `
    <article class="team-card">
      <img src="${escapeHtml(member.avatarUrl)}" alt="${escapeHtml(member.name)} 头像" loading="lazy" decoding="async">
      <div>
        <h3>${escapeHtml(member.name)}</h3>
        <p class="muted">${escapeHtml(member.position)}</p>
        <p class="small">${escapeHtml(member.bio)}</p>
      </div>
    </article>
  `).join('');

  const joinLink = data.contact?.joinLink || '#contact';
  teamGrid.innerHTML = membersHtml + `
    <a href="${escapeHtml(joinLink)}" target="_blank" rel="noopener noreferrer" class="team-card team-card--more" style="text-decoration: none;">
      <div>
        <i class="fas fa-plus" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 0.5rem;"></i>
        <h3>还有更多</h3>
        <p class="small">期待精彩的你也一起加入</p>
      </div>
    </a>
  `;
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

  if (businessList && emails.length > 0) {
    businessList.innerHTML = emails.map(email => `
      <li><a href="mailto:${escapeHtml(email)}"><i class="fas fa-envelope"></i>${escapeHtml(email)}</a></li>
    `).join('');
  }

  if (githubList && githubRepositories.length > 0) {
    githubList.innerHTML = githubRepositories.map(repository => {
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
    }).join('');
  }

  if (joinList) {
    const joinLink = escapeHtml(data.contact?.joinLink || '#');
    const queryLink = escapeHtml(data.contact?.queryLink || '#');
    const communityLink = escapeHtml(data.contact?.communityLink || '#');
    joinList.innerHTML = `
      <li><a href="${joinLink}" target="_blank" rel="noopener noreferrer"><i class="fas fa-user-plus"></i>填写团队成员申请表单</a></li>
      <li><a href="${queryLink}" target="_blank" rel="noopener noreferrer"><i class="fas fa-search"></i>查询你的申请结果</a></li>
      <li><a href="${communityLink}" target="_blank" rel="noopener noreferrer"><i class="fas fa-users"></i>用户社区</a></li>
    `;
  }
}

function renderFooter(data) {
  const footerQuick = document.getElementById('footer-quick-links');
  const footerCommunity = document.getElementById('footer-community-links');
  const footerContact = document.getElementById('footer-contact-links');
  const footerLegal = document.getElementById('footer-legal-links');

  if (footerQuick) {
    footerQuick.innerHTML = renderFooterLinkItems(data.footer?.quickLinks);
  }

  if (footerCommunity) {
    footerCommunity.innerHTML = renderFooterLinkItems(data.footer?.communityLinks);
  }

  if (footerContact) {
    footerContact.innerHTML = renderFooterLinkItems(data.footer?.contactLinks);
  }

  if (footerLegal) {
    footerLegal.innerHTML = renderFooterLinkItems(data.footer?.legalLinks);
  }
}

async function initSiteData() {
  if (window.location.protocol === 'file:') return;

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

    renderNavigation(payload.data);
    renderHero(payload.data);
    renderProducts(payload.data);
    renderCommunity(payload.data);
    renderMembers(payload.data);
    renderContact(payload.data);
    renderFooter(payload.data);
  } catch (error) {
    console.error('Failed to load site content:', error);
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initSiteData);
} else {
  initSiteData();
}
