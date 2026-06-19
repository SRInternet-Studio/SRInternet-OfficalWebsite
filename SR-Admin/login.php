<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (!sr_is_installed()) {
    sr_redirect(sr_install_url());
}

if (sr_current_admin() !== null) {
    sr_redirect(sr_admin_url('index.php'));
}

$flash = sr_pull_flash();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        sr_verify_csrf($_POST['csrf_token'] ?? null);

        $account = sr_normalize_text($_POST['account'] ?? '', 32, '账号');
        $password = (string) ($_POST['password'] ?? '');

        if (!sr_attempt_login($account, $password)) {
            sr_flash('error', '账号或密码错误。');
            sr_redirect(sr_admin_url('login.php'));
        }

        sr_flash('success', '登录成功，欢迎回来。');
        sr_redirect(sr_admin_url('index.php'));
    } catch (Throwable $exception) {
        sr_flash('error', $exception->getMessage());
        sr_redirect(sr_admin_url('login.php'));
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SR-Admin 登录</title>
    <link rel="stylesheet" href="<?= sr_escape(sr_admin_asset_url('styles.css')) ?>">
</head>
<body class="admin-login-page">
    <main class="login-shell">
        <section class="login-card">
            <p class="login-badge">SR-Admin</p>
            <h1>官网后台登录</h1>
            <p class="login-desc">请输入安装时创建的管理员账号和密码。</p>

            <?php if ($flash !== null): ?>
                <div class="alert alert--<?= sr_escape((string) $flash['type']) ?>"><?= sr_escape((string) $flash['message']) ?></div>
            <?php endif; ?>

            <form method="post" class="stack-form">
                <input type="hidden" name="csrf_token" value="<?= sr_escape(sr_csrf_token()) ?>">

                <label>
                    <span>管理员账号</span>
                    <input type="text" name="account" maxlength="32" autocomplete="username" required>
                </label>

                <label>
                    <span>登录密码</span>
                    <input type="password" name="password" autocomplete="current-password" required>
                </label>

                <button type="submit" class="btn-primary-solid">安全登录</button>
            </form>
        </section>
    </main>
</body>
</html>
