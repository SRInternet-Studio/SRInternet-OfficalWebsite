<?php

declare(strict_types=1);

$adminPageTitle = $adminPageTitle ?? 'SR-Admin';
$adminPageKey = $adminPageKey ?? 'dashboard';
$adminPageDescription = $adminPageDescription ?? '';
$admin = $admin ?? sr_require_login();
$flash = $flash ?? null;
$menuItems = sr_admin_menu_items();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sr_escape($adminPageTitle) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= sr_escape(sr_admin_asset_url('styles.css')) ?>">
</head>
<body class="admin-page">
    <div class="admin-shell">
        <div id="sidebar-overlay" class="sidebar-overlay"></div>
        <aside class="admin-sidebar" id="admin-sidebar">
            <div class="sidebar-head">
                <p class="login-badge">SR-Admin</p>
                <h1>官网后台</h1>
                <p class="sidebar-text">内容统一管理中心</p>
            </div>

            <nav class="sidebar-nav" aria-label="后台导航">
                <?php foreach ($menuItems as $key => $item): ?>
                    <a class="sidebar-link <?= $key === $adminPageKey ? 'is-active' : '' ?>" href="<?= sr_escape((string) $item['href']) ?>">
                        <?= sr_escape((string) $item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar-footer">
                <p class="sidebar-user">当前管理员：<?= sr_escape((string) $admin['username']) ?></p>
                <p class="sidebar-text">上次登录：<?= sr_escape((string) ($admin['last_login_at'] ?? '首次登录')) ?></p>
                <div class="sidebar-actions">
                    <a class="ghost-link" href="/index.html" target="_blank" rel="noopener noreferrer">查看官网</a>
                    <a class="ghost-link danger-link" href="<?= sr_escape(sr_admin_url('logout.php')) ?>">退出登录</a>
                </div>
            </div>
        </aside>

        <main class="admin-content">
            <header class="page-header">
                <div class="header-left">
                    <button id="mobile-menu-btn" class="mobile-menu-btn" aria-label="打开菜单">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h2><?= sr_escape($adminPageTitle) ?></h2>
                        <?php if ($adminPageDescription !== ''): ?>
                            <p class="muted-line"><?= sr_escape($adminPageDescription) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <?php if ($flash !== null): ?>
                <div class="alert alert--<?= sr_escape((string) $flash['type']) ?>"><?= sr_escape((string) $flash['message']) ?></div>
            <?php endif; ?>
