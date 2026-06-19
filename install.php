<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once SR_ADMIN_DIR . '/bootstrap.php';

$errorMessage = '';
$successMessage = '';
$justInstalled = false;
$isLocked = sr_has_install_lock();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        if ($isLocked) {
            throw new RuntimeException('安装程序已锁定，禁止再次访问。');
        }

        sr_verify_csrf($_POST['csrf_token'] ?? null);

        $username = sr_normalize_text($_POST['username'] ?? '', 32, '管理员账号');
        $email = sr_normalize_email($_POST['email'] ?? '');
        $qq = sr_normalize_qq($_POST['qq'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['password_confirm'] ?? '');

        if (strlen($password) < 10) {
            throw new RuntimeException('管理员密码至少 10 位。');
        }

        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
            throw new RuntimeException('管理员密码必须同时包含大写字母、小写字母和数字。');
        }

        if (!hash_equals($password, $confirmPassword)) {
            throw new RuntimeException('两次输入的管理员密码不一致。');
        }

        $databasePath = sr_db_path_from_file_name(sr_generate_random_db_file_name());
        sr_write_sqlite_path_to_config($databasePath);
        sr_set_db_path_override($databasePath);

        try {
            $pdo = sr_open_sqlite($databasePath);
            sr_create_initial_admin_with_pdo($pdo, $username, $password, $email, $qq);
            sr_create_install_lock();
        } catch (Throwable $exception) {
            @unlink($databasePath);
            @unlink(SR_INSTALL_LOCK_PATH);
            sr_write_sqlite_path_to_config('');
            sr_set_db_path_override(null);
            throw $exception;
        }

        $successMessage = '安装完成，数据库已随机命名并保存到根目录的 sql 目录。';
        $justInstalled = true;
        $isLocked = true;
    } catch (Throwable $exception) {
        $errorMessage = $exception->getMessage();
    }
}

if ($isLocked && !$justInstalled) {
    http_response_code(403);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统安装</title>
    <link rel="stylesheet" href="<?= sr_escape(sr_admin_asset_url('styles.css')) ?>">
</head>
<body class="admin-login-page">
    <main class="login-shell">
        <section class="login-card">
            <p class="login-badge">System Install</p>
            <h1>官网系统安装</h1>

            <?php if ($justInstalled): ?>
                <p class="login-desc">安装成功，您现在可以进入前台或后台。为了隐藏管理后台，可以直接重命名后台文件夹。</p>
            <?php elseif ($isLocked): ?>
                <p class="login-desc">安装程序已锁定，禁止再次访问。</p>
            <?php else: ?>
                <p class="login-desc">安装时会引导您创建管理员账号，并将随机命名的 SQLite 数据库存入根目录的 sql 目录。</p>
            <?php endif; ?>

            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert--error"><?= sr_escape($errorMessage) ?></div>
            <?php endif; ?>

            <?php if ($successMessage !== ''): ?>
                <div class="alert alert--success"><?= sr_escape($successMessage) ?></div>
            <?php endif; ?>

            <?php if ($justInstalled): ?>
                <div class="stack-form">
                    <label>
                        <span>数据库位置</span>
                        <input type="text" value="<?= sr_escape(sr_db_relative_path()) ?>" readonly>
                    </label>
                    <label>
                        <span>安装锁</span>
                        <input type="text" value="installed.lock 已生成" readonly>
                    </label>
                </div>

                <div class="stack-form" style="margin-top: 1.25rem;">
                    <a class="ghost-link" href="/index.html" target="_blank" rel="noopener noreferrer">进入前台</a>
                    <a class="ghost-link" href="<?= sr_escape(sr_admin_url('login.php')) ?>">进入后台</a>
                </div>
            <?php elseif ($isLocked): ?>
                <div class="stack-form">
                    <label>
                        <span>访问状态</span>
                        <input type="text" value="安装页已锁定" readonly>
                    </label>
                </div>
            <?php else: ?>
                <form method="post" class="stack-form" style="margin-top: 1.25rem;">
                    <input type="hidden" name="csrf_token" value="<?= sr_escape(sr_csrf_token()) ?>">

                    <label>
                        <span>管理员账号</span>
                        <input type="text" name="username" maxlength="32" autocomplete="username" required>
                    </label>

                    <label>
                        <span>管理员邮箱</span>
                        <input type="email" name="email" maxlength="120" autocomplete="email" required>
                    </label>

                    <label>
                        <span>管理员 QQ</span>
                        <input type="text" name="qq" maxlength="12" inputmode="numeric">
                    </label>

                    <label>
                        <span>管理员密码</span>
                        <input type="password" name="password" minlength="10" autocomplete="new-password" required>
                    </label>

                    <label>
                        <span>确认密码</span>
                        <input type="password" name="password_confirm" minlength="10" autocomplete="new-password" required>
                    </label>

                    <button type="submit" class="btn-primary-solid">创建数据库并完成安装</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
