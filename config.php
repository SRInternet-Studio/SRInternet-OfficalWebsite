<?php

declare(strict_types=1);

/**
 * -------------------------------------------------------------------
 * 系统全局配置文件
 * -------------------------------------------------------------------
 * 此文件定义了系统运行所需的所有核心常量与配置项。
 */

// 站点根目录
define('SR_ROOT_DIR', __DIR__);

// 后台管理目录
define('SR_ADMIN_DIR', SR_ROOT_DIR . DIRECTORY_SEPARATOR . 'SR-Admin');

// 数据存储目录 (SQLite 数据库与临时文件存放在此)
define('SR_DATA_DIR', SR_ADMIN_DIR . DIRECTORY_SEPARATOR . 'data');

// 运行时配置路径 (存放安装时生成的动态配置)
define('SR_RUNTIME_CONFIG_PATH', SR_DATA_DIR . DIRECTORY_SEPARATOR . 'runtime.php');

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
