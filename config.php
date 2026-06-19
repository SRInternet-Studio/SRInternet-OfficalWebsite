<?php

declare(strict_types=1);

/**
 * -------------------------------------------------------------------
 * 系统全局配置文件
 * -------------------------------------------------------------------
 * 此文件定义了系统运行所需的所有核心常量与配置项。
 * 安装完成后，安装程序会把 SQLite 相对路径写回本文件。
 */

// 站点根目录
define('SR_ROOT_DIR', __DIR__);

function sr_is_valid_admin_dir(string $path): bool
{
    return is_dir($path)
        && is_file($path . DIRECTORY_SEPARATOR . 'bootstrap.php')
        && is_file($path . DIRECTORY_SEPARATOR . 'login.php')
        && is_file($path . DIRECTORY_SEPARATOR . 'index.php');
}

function sr_detect_admin_dir(): string
{
    $defaultPath = SR_ROOT_DIR . DIRECTORY_SEPARATOR . 'SR-Admin';
    if (sr_is_valid_admin_dir($defaultPath)) {
        return $defaultPath;
    }

    $directories = glob(SR_ROOT_DIR . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];
    foreach ($directories as $directory) {
        if (sr_is_valid_admin_dir($directory)) {
            return $directory;
        }
    }

    return $defaultPath;
}

// 后台管理目录，支持安装后直接重命名目录进行隐藏
define('SR_ADMIN_DIR', sr_detect_admin_dir());
define('SR_ADMIN_BASENAME', basename(SR_ADMIN_DIR));
define('SR_ADMIN_WEB_PATH', '/' . str_replace('%2F', '/', rawurlencode(SR_ADMIN_BASENAME)));

// 数据库存储目录与安装锁
define('SR_SQL_DIR', SR_ROOT_DIR . DIRECTORY_SEPARATOR . 'sql');
define('SR_SQL_PROTECTION_PATH', SR_SQL_DIR . DIRECTORY_SEPARATOR . '.htaccess');
define('SR_INSTALL_LOCK_PATH', SR_ROOT_DIR . DIRECTORY_SEPARATOR . 'installed.lock');
define('SR_CONFIG_FILE_PATH', __FILE__);

// 安装完成后会写入类似 sql/site_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.sqlite
define('SR_SQLITE_PATH', '');

// 产品图片上传相对目录 (用于前端访问)
define('SR_PRODUCT_IMAGE_RELATIVE_DIR', 'static/images/products');

// 产品图片上传绝对目录 (用于后端保存文件)
define('SR_PRODUCT_IMAGE_ABSOLUTE_DIR', SR_ROOT_DIR . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'products');

// 会话(Session)名称
define('SR_SESSION_NAME', 'sr_admin_session');

// 登录安全配置
define('SR_MAX_LOGIN_ATTEMPTS', 5);              // 同一账号最大尝试登录次数
define('SR_MAX_LOGIN_ATTEMPTS_PER_IP', 20);      // 同一 IP 最大尝试登录次数
define('SR_LOGIN_WINDOW_SECONDS', 900);          // 错误尝试计数窗口 (秒，默认 15 分钟)

// 上传文件大小限制 (默认 3MB)
define('SR_MAX_UPLOAD_BYTES', 3_145_728);

// 默认时区配置
date_default_timezone_set('Asia/Shanghai');
