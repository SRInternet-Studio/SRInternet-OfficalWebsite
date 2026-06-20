<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

sr_require_installation();

$admin = sr_require_login();
$flash = sr_pull_flash();
$csrfToken = sr_csrf_token();

try {
    $navigationItems = sr_fetch_all('navigation_items');
    $heroButtons = sr_fetch_all('hero_buttons');
    $products = sr_fetch_all('products');
    $members = sr_fetch_all('team_members');
    $admins = sr_fetch_all('admins');
    $logs = array_slice(sr_fetch_all('operation_logs'), 0, 8);
    $recommendedProducts = array_values(array_filter(
        $products,
        static fn (array $product): bool => ((int) $product['is_recommended']) === 1
    ));
    $contactEmails = sr_parse_multiline_setting(sr_setting('contact_email'));
    $contactGithubRepositories = sr_parse_multiline_setting(sr_setting('contact_github_repository'));
    
    // Environment Data
    $serverOs = php_uname('s');
    if (php_uname('m')) {
        $serverOs .= ' ' . php_uname('m');
    }
    $phpVersion = PHP_VERSION;
    $serverSoftwareInfo = explode(' ', $_SERVER['SERVER_SOFTWARE'] ?? 'Web Server');
    $serverSoftware = $serverSoftwareInfo[0] ?: 'Web Server';
    
    $memoryLimit = ini_get('memory_limit') ?: 'N/A';
    if ($memoryLimit === '-1') {
        $memoryLimit = '无限制';
    }
    
    $maxUpload = ini_get('upload_max_filesize') ?: 'N/A';
    $timezone = date_default_timezone_get();
} catch (Throwable $exception) {
    sr_flash('error', '数据库连接失败：' . $exception->getMessage());
    sr_redirect(sr_install_url());
}

$adminPageTitle = '数据总览';
$adminPageKey = 'dashboard';
$adminPageDescription = '查看官网内容规模、最近操作与常用管理入口。';

require __DIR__ . '/partials/header.php';
?>
<section class="stats-grid">
    <!-- 产品资源 -->
    <article class="stat-card" style="position: relative; overflow: hidden; padding: 1.5rem; background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);">
        <div style="position: absolute; right: -10px; bottom: -20px; font-size: 5.5rem; color: #90CAF9; opacity: 0.4; transform: rotate(-15deg); pointer-events: none; line-height: 1;">
            <i class="fas fa-box-open"></i>
        </div>
        <div style="position: relative; z-index: 1; display: flex; flex-direction: column; height: 100%;">
            <div style="color: #1976D2; font-size: 0.9rem; font-weight: 700; letter-spacing: 0.05em; margin-bottom: 0.25rem;">产品资源</div>
            <strong style="font-size: 2.5rem; font-weight: 800; color: #0D47A1; line-height: 1; display: block; font-family: ui-sans-serif, system-ui, sans-serif; margin-bottom: 1rem;"><?= count($products) ?></strong>
            <div style="font-size: 0.85rem; color: #1565C0; font-weight: 600; margin-top: auto;">包含 <?= count($recommendedProducts) ?> 个推荐</div>
        </div>
    </article>
    
    <!-- 团队规模 -->
    <article class="stat-card" style="position: relative; overflow: hidden; padding: 1.5rem; background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);">
        <div style="position: absolute; right: -10px; bottom: -20px; font-size: 5.5rem; color: #A5D6A7; opacity: 0.4; transform: rotate(-15deg); pointer-events: none; line-height: 1;">
            <i class="fas fa-users"></i>
        </div>
        <div style="position: relative; z-index: 1; display: flex; flex-direction: column; height: 100%;">
            <div style="color: #388E3C; font-size: 0.9rem; font-weight: 700; letter-spacing: 0.05em; margin-bottom: 0.25rem;">团队规模</div>
            <strong style="font-size: 2.5rem; font-weight: 800; color: #1B5E20; line-height: 1; display: block; font-family: ui-sans-serif, system-ui, sans-serif; margin-bottom: 1rem;"><?= count($members) ?></strong>
            <div style="font-size: 0.85rem; color: #2E7D32; font-weight: 600; margin-top: auto;">包含 <?= count($admins) ?> 个管理员</div>
        </div>
    </article>

    <!-- 前端入口 -->
    <article class="stat-card" style="position: relative; overflow: hidden; padding: 1.5rem; background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%);">
        <div style="position: absolute; right: -10px; bottom: -20px; font-size: 5.5rem; color: #FFCC80; opacity: 0.4; transform: rotate(-15deg); pointer-events: none; line-height: 1;">
            <i class="fas fa-compass"></i>
        </div>
        <div style="position: relative; z-index: 1; display: flex; flex-direction: column; height: 100%;">
            <div style="color: #F57C00; font-size: 0.9rem; font-weight: 700; letter-spacing: 0.05em; margin-bottom: 0.25rem;">前端入口</div>
            <strong style="font-size: 2.5rem; font-weight: 800; color: #E65100; line-height: 1; display: block; font-family: ui-sans-serif, system-ui, sans-serif; margin-bottom: 1rem;"><?= count($navigationItems) ?></strong>
            <div style="font-size: 0.85rem; color: #EF6C00; font-weight: 600; margin-top: auto;">搭配 <?= count($heroButtons) ?> 个首屏按钮</div>
        </div>
    </article>

    <!-- 运行状态 -->
    <article class="stat-card" style="position: relative; overflow: hidden; padding: 1.5rem; background: linear-gradient(135deg, #F3E5F5 0%, #E1BEE7 100%);">
        <div style="position: absolute; right: -10px; bottom: -20px; font-size: 5.5rem; color: #CE93D8; opacity: 0.4; transform: rotate(-15deg); pointer-events: none; line-height: 1;">
            <i class="fas fa-server"></i>
        </div>
        <div style="position: relative; z-index: 1; display: flex; flex-direction: column; height: 100%;">
            <div style="color: #8E24AA; font-size: 0.9rem; font-weight: 700; letter-spacing: 0.05em; margin-bottom: 0.25rem;">运行状态</div>
            <strong style="font-size: 2rem; font-weight: 800; color: #4A148C; line-height: 1; display: block; font-family: ui-sans-serif, system-ui, sans-serif; margin-bottom: 1rem; padding-top: 0.5rem;">正常</strong>
            <div style="font-size: 0.85rem; color: #7B1FA2; font-weight: 600; margin-top: auto; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 90%;" title="<?= sr_escape(sr_db_relative_path()) ?>"><?= sr_escape(sr_db_relative_path()) ?></div>
        </div>
    </article>
</section>

<style>
/* Neo-Bento Design System (Out of Comfort Zone) */
.neo-bento-wrapper {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
    margin-top: 2rem;
}
@media (max-width: 1024px) {
    .neo-bento-wrapper {
        grid-template-columns: 1fr;
    }
}
.neo-bento-main, .neo-bento-side {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.neo-bento-split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}
@media (max-width: 768px) {
    .neo-bento-split {
        grid-template-columns: 1fr;
    }
}

/* Panel Base */
.neo-bento-panel {
    background: var(--bg-panel);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 1.75rem;
    display: flex;
    flex-direction: column;
    position: relative;
}

/* Header */
.neo-bento-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.75rem;
}
.neo-bento-title {
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
.neo-bento-title i {
    font-size: 0.9rem;
    color: var(--text-main);
}

/* Quick Links */
.neo-links {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.neo-link-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.85rem 1rem;
    background: var(--bg-base);
    border-radius: 10px;
    border: 1px solid var(--border-color);
    color: var(--text-main);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
}
.neo-link-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.neo-link-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: var(--text-main);
    color: var(--bg-panel);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
}
.neo-link-arrow {
    color: var(--text-muted);
    font-size: 0.8rem;
}

/* Logs Timeline */
.neo-timeline {
    position: relative;
    padding-left: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.neo-timeline::before {
    content: '';
    position: absolute;
    left: 3px;
    top: 0.5rem;
    bottom: 0;
    width: 2px;
    background: var(--border-color);
}
.neo-timeline-item {
    position: relative;
}
.neo-timeline-dot {
    position: absolute;
    left: -1.25rem;
    top: 0.35rem;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--text-main);
    transform: translateX(-50%);
    box-shadow: 0 0 0 4px var(--bg-panel);
}
.neo-timeline-content {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}
.neo-timeline-action {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--text-main);
}
.neo-timeline-meta {
    font-size: 0.8rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Environment List */
.neo-env {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.neo-env-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 1rem;
    border-bottom: 1px dashed var(--border-color);
}
.neo-env-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.neo-env-label {
    font-size: 0.85rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
.neo-env-value {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--text-main);
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    max-width: 60%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Config Summary */
.neo-config-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}
.neo-config-item {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}
.neo-config-label {
    font-size: 0.75rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 700;
}
.neo-config-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-main);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Status Badge */
.neo-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.3rem 0.75rem;
    background: var(--text-main);
    color: var(--bg-panel);
    border-radius: 99px;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.neo-status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
}
</style>

<div class="neo-bento-wrapper">
    <!-- 左侧主栏 -->
    <div class="neo-bento-main">
        <!-- 站点访问量 -->
        <article class="neo-bento-panel">
            <div id="chart-loading" style="position: absolute; inset: 0; display: flex; justify-content: center; align-items: center; background: rgba(255,255,255,0.7); z-index: 10; backdrop-filter: blur(2px); border-radius: 16px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--text-main);"></i>
            </div>
            <div class="neo-bento-header" style="margin-bottom: 2rem;">
                <div class="neo-bento-title"><i class="fas fa-chart-line"></i> SITE TRAFFIC</div>
                <div style="display: flex; gap: 0.5rem; align-items: center; background: var(--bg-base); padding: 0.35rem; border-radius: 8px; border: 1px solid var(--border-color); font-weight: normal; font-size: 0.85rem;">
                    <select id="chart-view" style="padding: 0.4rem 0.8rem; border: none; border-radius: 6px; background: var(--bg-panel); font-weight: 600; cursor: pointer; outline: none; box-shadow: none; color: var(--text-main);">
                        <option value="week">按周</option>
                        <option value="month">按月</option>
                        <option value="year">按年</option>
                    </select>
                    <div style="width: 1px; height: 1.5rem; background: var(--border-color);"></div>
                    <input type="week" id="chart-date-week" class="chart-date-input" style="padding: 0.4rem 0.8rem; border: none; background: transparent; cursor: pointer; outline: none; font-family: inherit; box-shadow: none; color: var(--text-main);">
                    <input type="month" id="chart-date-month" class="chart-date-input" style="padding: 0.4rem 0.8rem; border: none; background: transparent; cursor: pointer; outline: none; font-family: inherit; display: none; box-shadow: none; color: var(--text-main);">
                    <select id="chart-date-year" class="chart-date-input" style="padding: 0.4rem 0.8rem; border: none; border-radius: 4px; background: transparent; cursor: pointer; outline: none; font-family: inherit; display: none; box-shadow: none; color: var(--text-main);"></select>
                </div>
            </div>
            <div style="width: 100%; height: 320px; position: relative;">
                <canvas id="visitsChart"></canvas>
            </div>
        </article>

        <!-- 底部并排：日志与环境 -->
        <div class="neo-bento-split">
            <!-- 最近操作 -->
            <article class="neo-bento-panel">
                <div class="neo-bento-header">
                    <div class="neo-bento-title"><i class="fas fa-list-ul"></i> RECENT LOGS</div>
                    <a href="<?= sr_escape(sr_admin_url('logs.php')) ?>" style="font-size: 0.75rem; font-weight: 800; color: var(--text-main); text-decoration: none; letter-spacing: 0.05em;">VIEW ALL</a>
                </div>
                <?php if (empty($logs)): ?>
                    <div style="margin-top: 1rem; background: var(--bg-base); border-radius: 10px; padding: 2rem 1rem; text-align: center; border: 1px solid var(--border-color);">
                        <p style="color: var(--text-muted); font-weight: 600; margin: 0; font-size: 0.85rem;">No recent activity</p>
                    </div>
                <?php else: ?>
                    <div class="neo-timeline">
                        <?php foreach (array_slice($logs, 0, 5) as $log): ?>
                            <div class="neo-timeline-item">
                                <div class="neo-timeline-dot"></div>
                                <div class="neo-timeline-content">
                                    <div class="neo-timeline-action"><?= sr_escape((string) $log['action']) ?></div>
                                    <div class="neo-timeline-meta">
                                        <strong style="color: var(--text-main);"><?= sr_escape((string) $log['operator']) ?></strong>
                                        <span>&middot;</span>
                                        <span><?= sr_escape(date('m-d H:i', strtotime((string) $log['created_at']))) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>

            <!-- 运行环境 -->
            <article class="neo-bento-panel">
                <div class="neo-bento-header">
                    <div class="neo-bento-title"><i class="fas fa-server"></i> ENVIRONMENT</div>
                    <div class="neo-status">
                        <div class="neo-status-dot"></div> ACTIVE
                    </div>
                </div>
                <div class="neo-env">
                    <div class="neo-env-item">
                        <div class="neo-env-label"><i class="fas fa-desktop"></i> OS System</div>
                        <div class="neo-env-value" title="<?= sr_escape($serverOs) ?>"><?= sr_escape($serverOs) ?></div>
                    </div>
                    <div class="neo-env-item">
                        <div class="neo-env-label"><i class="fas fa-network-wired"></i> Web Server</div>
                        <div class="neo-env-value" title="<?= sr_escape($serverSoftware) ?>"><?= sr_escape($serverSoftware) ?></div>
                    </div>
                    <div class="neo-env-item">
                        <div class="neo-env-label"><i class="fas fa-code"></i> PHP Ver</div>
                        <div class="neo-env-value"><?= sr_escape($phpVersion) ?></div>
                    </div>
                    <div class="neo-env-item">
                        <div class="neo-env-label"><i class="fas fa-microchip"></i> Mem Limit</div>
                        <div class="neo-env-value"><?= sr_escape($memoryLimit) ?></div>
                    </div>
                    <div class="neo-env-item">
                        <div class="neo-env-label"><i class="fas fa-cloud-upload-alt"></i> Max Upload</div>
                        <div class="neo-env-value"><?= sr_escape($maxUpload) ?></div>
                    </div>
                </div>
            </article>
        </div>
    </div>

    <!-- 右侧侧边栏 -->
    <div class="neo-bento-side">
        <!-- 快捷入口 -->
        <article class="neo-bento-panel">
            <div class="neo-bento-header">
                <div class="neo-bento-title"><i class="fas fa-bolt"></i> QUICK ACTIONS</div>
            </div>
            <div class="neo-links">
                <a class="neo-link-item" href="<?= sr_escape(sr_admin_url('products.php')) ?>">
                    <div class="neo-link-left">
                        <div class="neo-link-icon"><i class="fas fa-box-open"></i></div>
                        <span>产品管理</span>
                    </div>
                    <i class="fas fa-arrow-right neo-link-arrow"></i>
                </a>
                <a class="neo-link-item" href="<?= sr_escape(sr_admin_url('navigation.php')) ?>">
                    <div class="neo-link-left">
                        <div class="neo-link-icon"><i class="fas fa-compass"></i></div>
                        <span>导航管理</span>
                    </div>
                    <i class="fas fa-arrow-right neo-link-arrow"></i>
                </a>
                <a class="neo-link-item" href="<?= sr_escape(sr_admin_url('community.php')) ?>">
                    <div class="neo-link-left">
                        <div class="neo-link-icon"><i class="fas fa-comments"></i></div>
                        <span>社区管理</span>
                    </div>
                    <i class="fas fa-arrow-right neo-link-arrow"></i>
                </a>
                <a class="neo-link-item" href="<?= sr_escape(sr_admin_url('members.php')) ?>">
                    <div class="neo-link-left">
                        <div class="neo-link-icon"><i class="fas fa-users"></i></div>
                        <span>成员管理</span>
                    </div>
                    <i class="fas fa-arrow-right neo-link-arrow"></i>
                </a>
                <a class="neo-link-item" href="<?= sr_escape(sr_admin_url('contact.php')) ?>">
                    <div class="neo-link-left">
                        <div class="neo-link-icon"><i class="fas fa-envelope"></i></div>
                        <span>联系我们</span>
                    </div>
                    <i class="fas fa-arrow-right neo-link-arrow"></i>
                </a>
                <a class="neo-link-item" href="<?= sr_escape(sr_admin_url('logs.php')) ?>">
                    <div class="neo-link-left">
                        <div class="neo-link-icon"><i class="fas fa-history"></i></div>
                        <span>操作日志</span>
                    </div>
                    <i class="fas fa-arrow-right neo-link-arrow"></i>
                </a>
            </div>
        </article>

        <!-- 前台配置 -->
        <article class="neo-bento-panel" style="flex: 1;">
            <div class="neo-bento-header">
                <div class="neo-bento-title"><i class="fas fa-sliders-h"></i> CONFIGURATION</div>
            </div>
            <div class="neo-config-grid">
                <div class="neo-config-item">
                    <div class="neo-config-label">Hero Title</div>
                    <div class="neo-config-value" title="<?= sr_escape(sr_setting('hero_title')) ?>"><?= sr_escape(sr_setting('hero_title')) ?></div>
                </div>
                <div class="neo-config-item">
                    <div class="neo-config-label">Bilibili URL</div>
                    <div class="neo-config-value" title="<?= sr_escape(sr_setting('community_bilibili_url')) ?>"><?= sr_escape(sr_setting('community_bilibili_url')) ?: '未设置' ?></div>
                </div>
                <div class="neo-config-item">
                    <div class="neo-config-label">Contact Email</div>
                    <div class="neo-config-value" title="<?= sr_escape($contactEmails[0] ?? '未设置') ?>"><?= sr_escape($contactEmails[0] ?? '未设置') ?></div>
                </div>
            </div>
        </article>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    let currentView = 'week';
    let chartInstance = null;

    const viewSelect = document.getElementById('chart-view');
    const weekInput = document.getElementById('chart-date-week');
    const monthInput = document.getElementById('chart-date-month');
    const yearInput = document.getElementById('chart-date-year');
    const chartLoading = document.getElementById('chart-loading');
    
    const updateLoadingBg = () => {
        const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        chartLoading.style.background = isDarkMode ? 'rgba(30, 41, 59, 0.7)' : 'rgba(255, 255, 255, 0.7)';
    };
    updateLoadingBg();
    
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        updateLoadingBg();
        if (chartInstance) {
            loadData();
        }
    });

    const inputs = {
        week: weekInput,
        month: monthInput,
        year: yearInput
    };
    const ctx = document.getElementById('visitsChart').getContext('2d');

    const now = new Date();
    const currentYear = now.getFullYear();
    const currentMonth = String(now.getMonth() + 1).padStart(2, '0');
    
    const getWeekNumber = (d) => {
        d = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
        d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay()||7));
        var yearStart = new Date(Date.UTC(d.getUTCFullYear(),0,1));
        return Math.ceil((((d - yearStart) / 86400000) + 1)/7);
    };
    const currentWeek = String(getWeekNumber(now)).padStart(2, '0');

    for (let year = currentYear; year >= currentYear - 4; year -= 1) {
        const option = document.createElement('option');
        option.value = String(year);
        option.textContent = `${year}年`;
        yearInput.appendChild(option);
    }

    weekInput.value = `${currentYear}-W${currentWeek}`;
    monthInput.value = `${currentYear}-${currentMonth}`;
    yearInput.value = String(currentYear);

    const loadData = async () => {
        const dateParam = inputs[currentView].value;
        if (!dateParam) return;
        
        chartLoading.style.display = 'flex';
        
        try {
            const response = await fetch(`visits-api.php?view=${currentView}&date=${encodeURIComponent(dateParam)}&csrf_token=<?= sr_escape($csrfToken) ?>`);
            const responseText = await response.text();
            let data;

            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                throw new Error(`Chart API returned invalid JSON (${response.status}): ${responseText.slice(0, 300)}`);
            }
            
            if (chartInstance) {
                chartInstance.destroy();
            }

            const isDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const primaryColor = isDark ? '#38bdf8' : '#3b82f6';
            const tooltipBg = isDark ? 'rgba(15, 23, 42, 0.95)' : 'rgba(255, 255, 255, 0.95)';
            const tooltipTitleColor = isDark ? '#f8fafc' : '#0f172a';
            const tooltipBodyColor = isDark ? '#f8fafc' : '#0f172a';
            const gridColor = isDark ? 'rgba(51, 65, 85, 0.5)' : 'rgba(226, 232, 240, 0.5)';
            const tickColor = isDark ? '#94a3b8' : '#64748b';

            const gradient = ctx.createLinearGradient(0, 0, 0, 260);
            gradient.addColorStop(0, isDark ? 'rgba(56, 189, 248, 0.5)' : 'rgba(59, 130, 246, 0.5)');
            gradient.addColorStop(1, isDark ? 'rgba(56, 189, 248, 0.05)' : 'rgba(59, 130, 246, 0.05)');

            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: ' 访问人次',
                        data: data.data,
                        borderColor: primaryColor,
                        backgroundColor: gradient,
                        borderWidth: 3,
                        pointBackgroundColor: isDark ? '#0f172a' : '#ffffff',
                        pointBorderColor: primaryColor,
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: primaryColor,
                        pointHoverBorderColor: isDark ? '#0f172a' : '#ffffff',
                        pointHoverBorderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: tooltipBg,
                            titleColor: tooltipTitleColor,
                            bodyColor: tooltipBodyColor,
                            titleFont: { size: 13, family: "system-ui, sans-serif" },
                            bodyFont: { size: 14, weight: 'bold', family: "system-ui, sans-serif" },
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: false,
                            borderColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)',
                            borderWidth: 1,
                            boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)'
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                font: { size: 12, family: "system-ui, sans-serif" },
                                color: tickColor
                            }
                        },
                        y: {
                            beginAtZero: true,
                            border: {
                                display: false
                            },
                            grid: {
                                color: gridColor,
                                drawTicks: false
                            },
                            ticks: {
                                precision: 0,
                                font: { size: 12, family: "system-ui, sans-serif" },
                                color: tickColor,
                                padding: 12,
                                maxTicksLimit: 6
                            }
                        }
                    }
                }
            });
        } catch (e) {
            console.error('Failed to load chart data', e);
        } finally {
            setTimeout(() => {
                chartLoading.style.display = 'none';
            }, 300);
        }
    };

    viewSelect.addEventListener('change', (e) => {
        currentView = e.target.value;
        Object.values(inputs).forEach(input => input.style.display = 'none');
        inputs[currentView].style.display = 'block';
        loadData();
    });

    Object.values(inputs).forEach(input => {
        input.addEventListener('change', loadData);
    });

    loadData();
});
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>