<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$isInstalled = sr_is_installed();
$errorMessage = '';
$successMessage = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        if ($isInstalled) {
            throw new RuntimeException('安装程序已锁定。如需重装，请手动清理运行时配置后再执行。');
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

        $dbFileName = sr_generate_random_db_file_name();
        sr_save_runtime_config([
            'db_file' => $dbFileName,
            'installed_at' => sr_now(),
        ]);

        try {
            sr_db();
            sr_create_initial_admin($username, $password, $email, $qq);
        } catch (Throwable $exception) {
            @unlink(sr_db_path_from_file_name($dbFileName));
            @unlink(SR_RUNTIME_CONFIG_PATH);
            throw $exception;
        }

        $successMessage = '安装完成，已创建随机数据库文件并写入首个管理员。';
        $isInstalled = true;
    } catch (Throwable $exception) {
        $errorMessage = $exception->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SR-Admin 安装</title>
    <link rel="stylesheet" href="/SR-Admin/styles.css">
</head>
<body class="admin-login-page">
    <main class="login-shell">
        <section class="login-card">
            <p class="login-badge">SR-Admin Install</p>
            <h1>官网后台安装向导</h1>
            <p class="login-desc">安装时会生成随机 SQLite 文件名，并创建你指定的首个管理员账号。</p>

            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert--error"><?= sr_escape($errorMessage) ?></div>
            <?php endif; ?>

            <?php if ($successMessage !== ''): ?>
                <div class="alert alert--success"><?= sr_escape($successMessage) ?></div>
            <?php endif; ?>

            <?php if ($isInstalled): ?>
                <div class="stack-form">
                    <label>
                        <span>安装状态</span>
                        <input type="text" value="已完成安装，安装程序已锁定" readonly>
                    </label>
                    <label>
                        <span>数据库策略</span>
                        <input type="text" value="<?= sr_has_runtime_config() ? '已使用运行时配置管理数据库文件' : '检测到历史数据库文件，兼容模式已启用' ?>" readonly>
                    </label>
                </div>

                <div class="stack-form" style="margin-top: 1.25rem;">
                    <a class="ghost-link" href="/SR-Admin/login.php">前往后台登录</a>
                    <a class="ghost-link" href="/index.html" target="_blank" rel="noopener noreferrer">查看官网首页</a>
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
