<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$admin = sr_require_login();
$flash = sr_pull_flash();
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

$adminPageTitle = '数据总览';
$adminPageKey = 'dashboard';
$adminPageDescription = '查看官网内容规模、最近操作与常用管理入口。';

require __DIR__ . '/partials/header.php';
?>
<section class="stats-grid">
    <article class="stat-card">
        <span class="stat-label">导航项</span>
        <strong><?= count($navigationItems) ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">Hero 按钮</span>
        <strong><?= count($heroButtons) ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">产品数量</span>
        <strong><?= count($products) ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">推荐产品</span>
        <strong><?= count($recommendedProducts) ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">成员数量</span>
        <strong><?= count($members) ?></strong>
    </article>
    <article class="stat-card">
        <span class="stat-label">管理员数量</span>
        <strong><?= count($admins) ?></strong>
    </article>
</section>

<section class="dashboard-grid">
    <article class="form-card">
        <div class="section-head">
            <h3>快捷入口</h3>
            <p>常用内容模块已拆分为独立页面。</p>
        </div>
        <div class="quick-links">
            <a class="quick-link-card" href="/SR-Admin/navigation.php">导航管理</a>
            <a class="quick-link-card" href="/SR-Admin/hero.php">Hero 管理</a>
            <a class="quick-link-card" href="/SR-Admin/products.php">产品管理</a>
            <a class="quick-link-card" href="/SR-Admin/community.php">社区管理</a>
            <a class="quick-link-card" href="/SR-Admin/members.php">成员管理</a>
            <a class="quick-link-card" href="/SR-Admin/contact.php">联系我们</a>
            <a class="quick-link-card" href="/SR-Admin/footer.php">页脚管理</a>
            <a class="quick-link-card" href="/SR-Admin/admins.php">管理员</a>
            <a class="quick-link-card" href="/SR-Admin/logs.php">操作日志</a>
        </div>
    </article>

    <article class="form-card">
        <div class="section-head">
            <h3>系统状态</h3>
            <p>数据库、上传目录与当前管理员信息。</p>
        </div>
        <div class="info-list">
            <div class="info-item"><span>数据库文件</span><code><?= sr_escape(sr_db_path()) ?></code></div>
            <div class="info-item"><span>图片目录</span><code><?= sr_escape(SR_PRODUCT_IMAGE_ABSOLUTE_DIR) ?></code></div>
            <div class="info-item"><span>当前管理员</span><strong><?= sr_escape((string) $admin['username']) ?></strong></div>
            <div class="info-item"><span>联系邮箱</span><strong><?= sr_escape($contactEmails[0] ?? '未设置') ?></strong></div>
        </div>
    </article>
</section>

<section class="dashboard-grid">
    <article class="form-card">
        <div class="section-head">
            <h3>内容摘要</h3>
            <p>快速了解当前前台关键配置。</p>
        </div>
        <div class="info-list">
            <div class="info-item"><span>Hero 标题</span><strong><?= sr_escape(sr_setting('hero_title')) ?></strong></div>
            <div class="info-item"><span>社区 B 站</span><code><?= sr_escape(sr_setting('community_bilibili_url')) ?></code></div>
            <div class="info-item"><span>GitHub 仓库</span><code><?= sr_escape($contactGithubRepositories[0] ?? '未设置') ?></code></div>
            <div class="info-item"><span>申请加入链接</span><code><?= sr_escape(sr_setting('contact_join_link')) ?></code></div>
        </div>
    </article>

    <article class="form-card">
        <div class="section-head">
            <h3>最近操作</h3>
            <p>显示最近 8 条后台操作日志。</p>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>时间</th>
                        <th>操作</th>
                        <th>操作人</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= sr_escape((string) $log['created_at']) ?></td>
                            <td><?= sr_escape((string) $log['action']) ?></td>
                            <td><?= sr_escape((string) $log['operator']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
