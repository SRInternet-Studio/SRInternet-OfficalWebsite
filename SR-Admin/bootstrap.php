<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

function sr_bootstrap(): void
{
    sr_ensure_directories();
    sr_ensure_storage_protection();
    sr_start_secure_session();
    if (sr_is_installed()) {
        sr_db();
    }
}

function sr_ensure_directories(): void
{
    if (!is_dir(SR_DATA_DIR)) {
        if (!@mkdir(SR_DATA_DIR, 0775, true) && !is_dir(SR_DATA_DIR)) {
            throw new RuntimeException('无法创建 SQLite 数据目录：' . SR_DATA_DIR);
        }
    }

    if (!is_dir(SR_PRODUCT_IMAGE_ABSOLUTE_DIR)) {
        if (!@mkdir(SR_PRODUCT_IMAGE_ABSOLUTE_DIR, 0775, true) && !is_dir(SR_PRODUCT_IMAGE_ABSOLUTE_DIR)) {
            throw new RuntimeException('无法创建产品图片目录：' . SR_PRODUCT_IMAGE_ABSOLUTE_DIR);
        }
    }
}

function sr_ensure_storage_protection(): void
{
    $htaccessPath = SR_DATA_DIR . DIRECTORY_SEPARATOR . '.htaccess';
    if (!is_file($htaccessPath)) {
        @file_put_contents($htaccessPath, "Deny from all\n");
    }
}

function sr_load_runtime_config(): array
{
    if (!is_file(SR_RUNTIME_CONFIG_PATH)) {
        return [];
    }

    $config = require SR_RUNTIME_CONFIG_PATH;
    return is_array($config) ? $config : [];
}

function sr_save_runtime_config(array $config): void
{
    sr_ensure_directories();

    $payload = "<?php\n\nreturn " . var_export($config, true) . ";\n";
    if (@file_put_contents(SR_RUNTIME_CONFIG_PATH, $payload, LOCK_EX) === false) {
        throw new RuntimeException('无法写入运行时配置文件。');
    }

    @chmod(SR_RUNTIME_CONFIG_PATH, 0664);
}

function sr_is_installed(): bool
{
    $config = sr_load_runtime_config();
    $dbFile = $config['db_file'] ?? '';

    if (is_string($dbFile) && $dbFile !== '' && is_file(sr_db_path_from_file_name($dbFile))) {
        return true;
    }

    return is_file(sr_db_path_from_file_name('site.sqlite'));
}

function sr_has_runtime_config(): bool
{
    $config = sr_load_runtime_config();
    $dbFile = $config['db_file'] ?? '';

    return is_string($dbFile) && $dbFile !== '';
}

function sr_db_path(): string
{
    $config = sr_load_runtime_config();
    $dbFile = $config['db_file'] ?? 'site.sqlite';

    return sr_db_path_from_file_name((string) $dbFile);
}

function sr_db_path_from_file_name(string $fileName): string
{
    return SR_DATA_DIR . DIRECTORY_SEPARATOR . basename($fileName);
}

function sr_generate_random_db_file_name(): string
{
    return 'site_' . bin2hex(random_bytes(16)) . '.sqlite';
}

function sr_start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');

    $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
    $forwardedSsl = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')));
    $requestScheme = strtolower(trim((string) ($_SERVER['REQUEST_SCHEME'] ?? '')));
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        || $forwardedProto === 'https'
        || $forwardedSsl === 'on'
        || $requestScheme === 'https';

    session_name(SR_SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function sr_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!sr_is_installed()) {
        throw new RuntimeException('系统尚未安装，请先完成安装。');
    }

    sr_prepare_sqlite_storage();

    $pdo = new PDO('sqlite:' . sr_db_path());
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');

    sr_initialize_database($pdo);

    return $pdo;
}

function sr_prepare_sqlite_storage(): void
{
    $databasePath = sr_db_path();

    if (!is_dir(SR_DATA_DIR)) {
        throw new RuntimeException('SQLite 数据目录不存在：' . SR_DATA_DIR);
    }

    if (!is_writable(SR_DATA_DIR)) {
        throw new RuntimeException('SQLite 数据目录不可写，请为目录授予写入权限：' . SR_DATA_DIR);
    }

    if (!is_file($databasePath)) {
        if (@file_put_contents($databasePath, '') === false) {
            throw new RuntimeException('无法创建 SQLite 数据库文件，请检查目录权限：' . $databasePath);
        }

        @chmod($databasePath, 0664);
    }

    if (!is_writable($databasePath)) {
        throw new RuntimeException('SQLite 数据库文件不可写，请检查权限：' . $databasePath);
    }
}

function sr_initialize_database(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS settings (
            setting_key TEXT PRIMARY KEY,
            setting_value TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS navigation_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            link TEXT NOT NULL,
            open_in_new_tab INTEGER NOT NULL DEFAULT 0,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS hero_buttons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            label TEXT NOT NULL,
            link TEXT NOT NULL,
            color_class TEXT NOT NULL,
            icon_class TEXT NOT NULL DEFAULT "fas fa-arrow-right",
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            link TEXT NOT NULL,
            description TEXT NOT NULL,
            tags TEXT NOT NULL DEFAULT "",
            image_url TEXT NOT NULL DEFAULT "",
            is_recommended INTEGER NOT NULL DEFAULT 0,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS team_members (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            avatar_url TEXT NOT NULL,
            position TEXT NOT NULL,
            bio TEXT NOT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            email TEXT NOT NULL,
            qq TEXT NOT NULL DEFAULT "",
            last_login_at TEXT,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            account TEXT NOT NULL,
            ip_address TEXT NOT NULL,
            successful INTEGER NOT NULL DEFAULT 0,
            attempted_at TEXT NOT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS operation_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            action TEXT NOT NULL,
            operator TEXT NOT NULL,
            details TEXT NOT NULL DEFAULT "",
            created_at TEXT NOT NULL
        )'
    );

    sr_migrate_database($pdo);
    sr_seed_defaults($pdo);
}

function sr_migrate_database(PDO $pdo): void
{
    $columns = $pdo->query('PRAGMA table_info(hero_buttons)')->fetchAll() ?: [];
    $hasIconClass = false;

    foreach ($columns as $column) {
        if (((string) ($column['name'] ?? '')) === 'icon_class') {
            $hasIconClass = true;
            break;
        }
    }

    if (!$hasIconClass) {
        $pdo->exec('ALTER TABLE hero_buttons ADD COLUMN icon_class TEXT NOT NULL DEFAULT "fas fa-arrow-right"');
    }
}

function sr_seed_defaults(PDO $pdo): void
{
    $timestamp = sr_now();
    $defaultSettings = [
        'site_name' => 'SR思锐 团队',
        'hero_title' => '探索编程的无限可能',
        'hero_subtitle' => '一个以创新创造为导向的青少年编程学习与实践社区，和伙伴们一起用代码创造未来。',
        'community_bilibili_url' => 'https://space.bilibili.com/1969160969',
        'community_github_url' => 'https://github.com/SRInternet-Studio',
        'community_qq_url' => 'https://qm.qq.com/cgi-bin/qm/qr?k=0OC7vApC79hlsj1cx1SapeOKI_PaAaXY&jump_from=webapi&authKey=4c9uHeinCJS+DhdSe/CRUVCL6h22wqKtzrTxO82E1QSh4mwB9B5e3liZKOl1G8kN',
        'contact_email' => 'admin@sr-studio.cn',
        'contact_github_repository' => 'SR思锐 团队|https://github.com/SRInternet-Studio',
        'contact_join_link' => 'https://sr-studio.feishu.cn/share/base/form/shrcnCXosqYjZzH6GJZhOScLjIh',
        'contact_query_link' => 'https://sr-studio.feishu.cn/share/base/query/shrcnS4TCHe62IdPLJRsAvWqV3b',
        'contact_community_link' => '#community',
        'footer_quick_links' => "首页|#home\n产品中心|#products\n社区|#community\n关于我们|#about",
        'footer_community_links' => "Bilibili|https://space.bilibili.com/1969160969\nGitHub|https://github.com/SRInternet-Studio\nQQ群|https://qm.qq.com/cgi-bin/qm/qr?k=0OC7vApC79hlsj1cx1SapeOKI_PaAaXY&jump_from=webapi&authKey=4c9uHeinCJS+DhdSe/CRUVCL6h22wqKtzrTxO82E1QSh4mwB9B5e3liZKOl1G8kN",
        'footer_contact_links' => "商务邮箱|mailto:admin@sr-studio.cn\n加入我们|https://sr-studio.feishu.cn/share/base/form/shrcnCXosqYjZzH6GJZhOScLjIh\n查询申请|https://sr-studio.feishu.cn/share/base/query/shrcnS4TCHe62IdPLJRsAvWqV3b",
        'footer_legal_links' => "团队政策|https://sr-studio.feishu.cn/wiki/GwKxwB1Ili5bP1kNcUvcFjI5n5c\n隐私政策|https://sr-studio.feishu.cn/wiki/GwKxwB1Ili5bP1kNcUvcFjI5n5c?chat_type=single&from=message&lang=zh&message_type=text#share-QUZ0dqcDmoPDa7xnjACcVGyhnKe\n使用条款|https://sr-studio.feishu.cn/wiki/GwKxwB1Ili5bP1kNcUvcFjI5n5c?from=tab_home#share-C3wadnNGhometBx2U4EcWqBXnBh",
    ];

    if ((int) $pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn() === 0) {
        $statement = $pdo->prepare('INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (:setting_key, :setting_value, :updated_at)');
        foreach ($defaultSettings as $key => $value) {
            $statement->execute([
                ':setting_key' => $key,
                ':setting_value' => $value,
                ':updated_at' => $timestamp,
            ]);
        }
    }

    $ensureSettingStatement = $pdo->prepare('SELECT COUNT(*) FROM settings WHERE setting_key = :setting_key');
    $insertSettingStatement = $pdo->prepare('INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (:setting_key, :setting_value, :updated_at)');
    foreach ($defaultSettings as $key => $value) {
        $ensureSettingStatement->execute([':setting_key' => $key]);
        if ((int) $ensureSettingStatement->fetchColumn() > 0) {
            continue;
        }

        $insertSettingStatement->execute([
            ':setting_key' => $key,
            ':setting_value' => $value,
            ':updated_at' => $timestamp,
        ]);
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM navigation_items')->fetchColumn() === 0) {
        $items = [
            ['首页', '#home', 0, 10],
            ['账户', 'http://united.sr-studio.cn', 1, 20],
            ['产品中心', '#products', 0, 30],
            ['社区', '#community', 0, 40],
            ['关于我们', '#about', 0, 50],
            ['联系我们', '#contact', 0, 60],
            ['团队政策', 'https://sr-studio.feishu.cn/wiki/GwKxwB1Ili5bP1kNcUvcFjI5n5c', 1, 70],
        ];

        $statement = $pdo->prepare(
            'INSERT INTO navigation_items (name, link, open_in_new_tab, sort_order, created_at, updated_at)
             VALUES (:name, :link, :open_in_new_tab, :sort_order, :created_at, :updated_at)'
        );

        foreach ($items as [$name, $link, $openInNewTab, $sortOrder]) {
            $statement->execute([
                ':name' => $name,
                ':link' => $link,
                ':open_in_new_tab' => $openInNewTab,
                ':sort_order' => $sortOrder,
                ':created_at' => $timestamp,
                ':updated_at' => $timestamp,
            ]);
        }
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM hero_buttons')->fetchColumn() === 0) {
        $buttons = [
            ['探索产品', '#products', 'btn-primary', 'fas fa-rocket', 10],
            ['加入我们', 'https://sr-studio.feishu.cn/share/base/form/shrcnCXosqYjZzH6GJZhOScLjIh', 'btn-blue', 'fas fa-user-plus', 20],
            ['加入社区', '#community', 'btn-ghost', 'fas fa-users', 30],
        ];

        $statement = $pdo->prepare(
            'INSERT INTO hero_buttons (label, link, color_class, icon_class, sort_order, created_at, updated_at)
             VALUES (:label, :link, :color_class, :icon_class, :sort_order, :created_at, :updated_at)'
        );

        foreach ($buttons as [$label, $link, $colorClass, $iconClass, $sortOrder]) {
            $statement->execute([
                ':label' => $label,
                ':link' => $link,
                ':color_class' => $colorClass,
                ':icon_class' => $iconClass,
                ':sort_order' => $sortOrder,
                ':created_at' => $timestamp,
                ':updated_at' => $timestamp,
            ]);
        }
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() === 0) {
        $products = [
            ['简儿 QQ机器人', 'https://www.bilibili.com/video/BV1i5AEziE9k/', '基于 HypeR 和 OneBot v11 框架，自研插件生态的新一代AI QQ机器人。', 'AI,QQ 机器人,OneBot', 'static/images/products/jianer.jpg', 1, 10],
            ['壁纸生成器 5', 'https://wallpaper.sr-studio.cn', '从 ACG API 获取多风格图片，让 AI 帮你定制个性壁纸。', 'Python,图像处理,GUI', 'static/images/products/wallpaper.jpg', 0, 20],
            ['APICORE 规范', 'https://www.bilibili.com/video/BV1q6xuzTEjW/', '灵活、强大、可扩展的 API 配置与编排运行时规范。', 'API,规范,可扩展', 'static/images/products/sron.jpg', 0, 30],
            ['班级热搜排行榜', 'https://www.bilibili.com/video/BV1ZUXJYxEUt', '班级社交动态速览，让校园生活更有趣、更热闹。', '数据分析,Web,实时', 'static/images/products/classhot.jpg', 0, 40],
            ['Papa AI', 'https://www.bilibili.com/video/BV1zef8YME8Z', '你的全能 AI 伙伴，兼顾学习与娱乐体验。', 'AI,NLP,机器学习', 'static/images/products/papa.jpg', 0, 50],
            ['TimerIn 桌面时钟', 'https://www.bilibili.com/video/BV1vU411U7To', '美观实用的桌面时钟，提供多种提醒方式与主题。', '桌面,时间管理,GUI', 'static/images/products/timerin.jpg', 0, 60],
            ['希沃定制启动器', 'https://www.bilibili.com/video/BV1ky42187JM', '面向教育场景的定制启动器，优化课堂体验。', '教育技术,系统工具,定制', 'static/images/products/seewostart.jpg', 0, 70],
        ];

        $statement = $pdo->prepare(
            'INSERT INTO products (name, link, description, tags, image_url, is_recommended, sort_order, created_at, updated_at)
             VALUES (:name, :link, :description, :tags, :image_url, :is_recommended, :sort_order, :created_at, :updated_at)'
        );

        foreach ($products as [$name, $link, $description, $tags, $imageUrl, $isRecommended, $sortOrder]) {
            $statement->execute([
                ':name' => $name,
                ':link' => $link,
                ':description' => $description,
                ':tags' => $tags,
                ':image_url' => $imageUrl,
                ':is_recommended' => $isRecommended,
                ':sort_order' => $sortOrder,
                ':created_at' => $timestamp,
                ':updated_at' => $timestamp,
            ]);
        }
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM team_members')->fetchColumn() === 0) {
        $members = [
            ['SR思锐 开发者', 'https://avatars.githubusercontent.com/u/89620382?v=4', '创始成员兼CEO', '统筹、管理、策划并驱动项目迭代。', 10],
            ['Songyuhao', 'https://avatars.githubusercontent.com/u/65804940?v=4', '运维兼CTO', '基础设施维护与综合项目体验。', 20],
            ['LittleDream', 'http://q.qlogo.cn/headimg_dl?dst_uin=2089949602&spec=640', '游戏部', '用 PyGame 点燃创意实践。', 30],
            ['kizunerwe', 'http://q.qlogo.cn/headimg_dl?dst_uin=3341771757&spec=640', '高级运维工程师', '从端到端的运维支持与项目维护。', 40],
            ['桃子', 'http://q.qlogo.cn/headimg_dl?dst_uin=2822554898&spec=640', '简儿 AI', '简儿 QQ 机器人项目核心技术与负责人。', 50],
            ['Ariakage', 'https://avatars.githubusercontent.com/u/61618153?v=4', '创新技术核心', '从AI到项目，从运维到运营，从技术到创新。', 60],
            ['路人Ray', 'http://q.qlogo.cn/headimg_dl?dst_uin=3357559641&spec=640', '交互体验设计', '从UI到海报，都是他的拿手好戏。', 70],
        ];

        $statement = $pdo->prepare(
            'INSERT INTO team_members (name, avatar_url, position, bio, sort_order, created_at, updated_at)
             VALUES (:name, :avatar_url, :position, :bio, :sort_order, :created_at, :updated_at)'
        );

        foreach ($members as [$name, $avatarUrl, $position, $bio, $sortOrder]) {
            $statement->execute([
                ':name' => $name,
                ':avatar_url' => $avatarUrl,
                ':position' => $position,
                ':bio' => $bio,
                ':sort_order' => $sortOrder,
                ':created_at' => $timestamp,
                ':updated_at' => $timestamp,
            ]);
        }
    }

}

function sr_create_initial_admin(string $username, string $password, string $email, string $qq = ''): void
{
    $pdo = sr_db();
    if ((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
        throw new RuntimeException('管理员已存在，不能重复创建初始管理员。');
    }

    $timestamp = sr_now();
    $statement = $pdo->prepare(
        'INSERT INTO admins (username, password_hash, email, qq, last_login_at, created_at, updated_at)
         VALUES (:username, :password_hash, :email, :qq, :last_login_at, :created_at, :updated_at)'
    );
    $statement->execute([
        ':username' => $username,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':email' => $email,
        ':qq' => $qq,
        ':last_login_at' => null,
        ':created_at' => $timestamp,
        ':updated_at' => $timestamp,
    ]);
}

function sr_now(): string
{
    return date('Y-m-d H:i:s');
}

function sr_json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function sr_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function sr_redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}

function sr_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function sr_pull_flash(): ?array
{
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function sr_csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function sr_verify_csrf(?string $token): void
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (!is_string($sessionToken) || !is_string($token) || !hash_equals($sessionToken, $token)) {
        throw new RuntimeException('无效的表单令牌，请刷新页面后重试。');
    }
}

function sr_client_ip(): string
{
    return trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown')) ?: 'unknown';
}

function sr_require_installation(): void
{
    if (!sr_is_installed()) {
        sr_flash('error', '请先完成后台安装。');
        sr_redirect('/SR-Admin/install.php');
    }
}

function sr_current_admin(): ?array
{
    if (!sr_is_installed()) {
        return null;
    }

    if (empty($_SESSION['admin_id'])) {
        return null;
    }

    $statement = sr_db()->prepare('SELECT id, username, email, qq, last_login_at FROM admins WHERE id = :id LIMIT 1');
    $statement->execute([':id' => (int) $_SESSION['admin_id']]);
    $admin = $statement->fetch();

    return is_array($admin) ? $admin : null;
}

function sr_require_login(): array
{
    sr_require_installation();

    $admin = sr_current_admin();
    if ($admin === null) {
        sr_flash('error', '请先登录后台。');
        sr_redirect('/SR-Admin/login.php');
    }

    return $admin;
}

function sr_is_login_blocked(string $account, string $ipAddress): bool
{
    $accountStatement = sr_db()->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE attempted_at >= :boundary
           AND successful = 0
           AND account = :account
           AND ip_address = :ip_address'
    );
    $accountStatement->execute([
        ':boundary' => date('Y-m-d H:i:s', time() - SR_LOGIN_WINDOW_SECONDS),
        ':account' => $account,
        ':ip_address' => $ipAddress,
    ]);

    if ((int) $accountStatement->fetchColumn() >= SR_MAX_LOGIN_ATTEMPTS) {
        return true;
    }

    $ipStatement = sr_db()->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE attempted_at >= :boundary
           AND successful = 0
           AND ip_address = :ip_address'
    );
    $ipStatement->execute([
        ':boundary' => date('Y-m-d H:i:s', time() - SR_LOGIN_WINDOW_SECONDS),
        ':ip_address' => $ipAddress,
    ]);

    return (int) $ipStatement->fetchColumn() >= SR_MAX_LOGIN_ATTEMPTS_PER_IP;
}

function sr_record_login_attempt(string $account, string $ipAddress, bool $successful): void
{
    $statement = sr_db()->prepare(
        'INSERT INTO login_attempts (account, ip_address, successful, attempted_at)
         VALUES (:account, :ip_address, :successful, :attempted_at)'
    );
    $statement->execute([
        ':account' => $account,
        ':ip_address' => $ipAddress,
        ':successful' => $successful ? 1 : 0,
        ':attempted_at' => sr_now(),
    ]);
}

function sr_attempt_login(string $account, string $password): bool
{
    $normalizedAccount = trim($account);
    $ipAddress = sr_client_ip();

    if ($normalizedAccount === '' || $password === '') {
        return false;
    }

    if (sr_is_login_blocked($normalizedAccount, $ipAddress)) {
        throw new RuntimeException('登录尝试过于频繁，请 15 分钟后再试。');
    }

    $statement = sr_db()->prepare('SELECT * FROM admins WHERE username = :username LIMIT 1');
    $statement->execute([':username' => $normalizedAccount]);
    $admin = $statement->fetch();

    $isValid = is_array($admin) && password_verify($password, (string) $admin['password_hash']);
    sr_record_login_attempt($normalizedAccount, $ipAddress, $isValid);

    if (!$isValid || !is_array($admin)) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $admin['id'];

    $statement = sr_db()->prepare('UPDATE admins SET last_login_at = :last_login_at, updated_at = :updated_at WHERE id = :id');
    $statement->execute([
        ':last_login_at' => sr_now(),
        ':updated_at' => sr_now(),
        ':id' => (int) $admin['id'],
    ]);

    if (password_needs_rehash((string) $admin['password_hash'], PASSWORD_DEFAULT)) {
        $rehash = sr_db()->prepare('UPDATE admins SET password_hash = :password_hash, updated_at = :updated_at WHERE id = :id');
        $rehash->execute([
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':updated_at' => sr_now(),
            ':id' => (int) $admin['id'],
        ]);
    }

    sr_log_operation('管理员登录', (string) $admin['username'], '登录成功');

    return true;
}

function sr_logout(): void
{
    $admin = sr_current_admin();
    if ($admin !== null) {
        sr_log_operation('管理员退出', (string) $admin['username'], '退出后台');
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

function sr_log_operation(string $action, string $operator, string $details = ''): void
{
    $statement = sr_db()->prepare(
        'INSERT INTO operation_logs (action, operator, details, created_at)
         VALUES (:action, :operator, :details, :created_at)'
    );
    $statement->execute([
        ':action' => $action,
        ':operator' => $operator,
        ':details' => $details,
        ':created_at' => sr_now(),
    ]);
}

function sr_setting(string $key, string $default = ''): string
{
    $statement = sr_db()->prepare('SELECT setting_value FROM settings WHERE setting_key = :setting_key LIMIT 1');
    $statement->execute([':setting_key' => $key]);
    $value = $statement->fetchColumn();

    return is_string($value) ? $value : $default;
}

function sr_save_setting(string $key, string $value): void
{
    $statement = sr_db()->prepare(
        'INSERT INTO settings (setting_key, setting_value, updated_at)
         VALUES (:setting_key, :setting_value, :updated_at)
         ON CONFLICT(setting_key) DO UPDATE SET
             setting_value = excluded.setting_value,
             updated_at = excluded.updated_at'
    );
    $statement->execute([
        ':setting_key' => $key,
        ':setting_value' => $value,
        ':updated_at' => sr_now(),
    ]);
}

function sr_fetch_all(string $tableName): array
{
    $allowedTables = ['navigation_items', 'hero_buttons', 'products', 'team_members', 'admins', 'operation_logs'];
    if (!in_array($tableName, $allowedTables, true)) {
        throw new InvalidArgumentException('Unsupported table requested.');
    }

    $orderBy = match ($tableName) {
        'operation_logs' => 'ORDER BY created_at DESC, id DESC',
        'admins' => 'ORDER BY id ASC',
        default => 'ORDER BY sort_order ASC, id ASC',
    };

    return sr_db()->query('SELECT * FROM ' . $tableName . ' ' . $orderBy)->fetchAll() ?: [];
}

function sr_admin_menu_items(): array
{
    return [
        'dashboard' => ['label' => '数据总览', 'href' => '/SR-Admin/index.php'],
        'navigation' => ['label' => '导航管理', 'href' => '/SR-Admin/navigation.php'],
        'hero' => ['label' => 'Hero 管理', 'href' => '/SR-Admin/hero.php'],
        'products' => ['label' => '产品管理', 'href' => '/SR-Admin/products.php'],
        'community' => ['label' => '社区管理', 'href' => '/SR-Admin/community.php'],
        'members' => ['label' => '成员管理', 'href' => '/SR-Admin/members.php'],
        'contact' => ['label' => '联系我们', 'href' => '/SR-Admin/contact.php'],
        'footer' => ['label' => '页脚管理', 'href' => '/SR-Admin/footer.php'],
        'admins' => ['label' => '管理员', 'href' => '/SR-Admin/admins.php'],
        'logs' => ['label' => '操作日志', 'href' => '/SR-Admin/logs.php'],
    ];
}

function sr_normalize_text(?string $value, int $maxLength, string $fieldName): string
{
    $normalized = trim((string) $value);
    if ($normalized === '') {
        throw new RuntimeException($fieldName . '不能为空。');
    }
    if (mb_strlen($normalized) > $maxLength) {
        throw new RuntimeException($fieldName . '长度不能超过 ' . $maxLength . ' 个字符。');
    }

    return $normalized;
}

function sr_normalize_optional_text(?string $value, int $maxLength): string
{
    $normalized = trim((string) $value);
    if (mb_strlen($normalized) > $maxLength) {
        throw new RuntimeException('输入内容过长。');
    }

    return $normalized;
}

function sr_normalize_email(?string $value): string
{
    $email = trim((string) $value);
    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        throw new RuntimeException('邮箱格式不正确。');
    }

    return $email;
}

function sr_parse_multiline_setting(string $value): array
{
    $lines = preg_split('/\r\n|\r|\n/', trim($value)) ?: [];
    $lines = array_values(array_filter(array_map(
        static fn (string $item): string => trim($item),
        $lines
    )));

    return array_values(array_unique($lines));
}

function sr_parse_named_link_item(string $value): array
{
    $parts = explode('|', trim($value), 2);
    if (count($parts) !== 2) {
        throw new RuntimeException('页脚链接格式必须为“名称|链接”。');
    }

    $label = trim($parts[0]);
    $url = trim($parts[1]);
    if ($label === '' || $url === '') {
        throw new RuntimeException('页脚链接格式必须为“名称|链接”，且两侧都不能为空。');
    }

    return [
        'label' => $label,
        'url' => $url,
    ];
}

function sr_parse_named_link_setting(string $value): array
{
    $items = sr_parse_multiline_setting($value);

    return array_map(static function (string $item): array {
        return sr_parse_named_link_item($item);
    }, $items);
}

function sr_normalize_email_list(?string $value, string $fieldName = '联系邮箱'): string
{
    $items = sr_parse_multiline_setting((string) $value);
    if ($items === []) {
        throw new RuntimeException($fieldName . '至少填写一项。');
    }

    if (count($items) > 10) {
        throw new RuntimeException($fieldName . '最多支持 10 项。');
    }

    foreach ($items as $email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException($fieldName . '中存在格式不正确的邮箱。');
        }
    }

    return implode("\n", $items);
}

function sr_normalize_qq(?string $value): string
{
    $qq = trim((string) $value);
    if ($qq === '') {
        return '';
    }

    if (!preg_match('/^\d{5,12}$/', $qq)) {
        throw new RuntimeException('QQ 号码格式不正确。');
    }

    return $qq;
}

function sr_normalize_sort_order(mixed $value): int
{
    return max(0, (int) $value);
}

function sr_normalize_link(?string $value, string $fieldName = '链接'): string
{
    $link = trim((string) $value);
    if ($link === '') {
        throw new RuntimeException($fieldName . '不能为空。');
    }

    if (str_starts_with($link, '#') || str_starts_with($link, '/')) {
        return $link;
    }

    if (preg_match('/^(https?:\/\/|mailto:)/i', $link) === 1) {
        if (str_starts_with(strtolower($link), 'mailto:')) {
            $email = substr($link, 7);
            if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw new RuntimeException($fieldName . '不是有效的邮箱链接。');
            }
            return $link;
        }

        if (filter_var($link, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException($fieldName . '格式不正确。');
        }

        return $link;
    }

    throw new RuntimeException($fieldName . '仅支持 http、https、mailto、锚点或站内相对路径。');
}

function sr_normalize_link_list(?string $value, string $fieldName = '链接'): string
{
    $items = sr_parse_multiline_setting((string) $value);
    if ($items === []) {
        throw new RuntimeException($fieldName . '至少填写一项。');
    }

    if (count($items) > 10) {
        throw new RuntimeException($fieldName . '最多支持 10 项。');
    }

    $normalizedItems = [];
    foreach ($items as $item) {
        $parts = explode('|', $item, 2);
        $link = trim(end($parts));
        $name = count($parts) === 2 ? trim($parts[0]) : '';
        
        $normalizedLink = sr_normalize_link($link, $fieldName);
        $normalizedItems[] = $name === '' ? $normalizedLink : $name . '|' . $normalizedLink;
    }

    return implode("\n", array_values(array_unique($normalizedItems)));
}

function sr_normalize_named_link_list(?string $value, string $fieldName = '页脚链接'): string
{
    $items = sr_parse_multiline_setting((string) $value);
    if ($items === []) {
        throw new RuntimeException($fieldName . '至少填写一项。');
    }

    if (count($items) > 12) {
        throw new RuntimeException($fieldName . '最多支持 12 项。');
    }

    $normalizedItems = [];
    foreach ($items as $item) {
        $parsedItem = sr_parse_named_link_item($item);
        $normalizedItems[] = sr_normalize_text($parsedItem['label'], 30, $fieldName . '名称') . '|' . sr_normalize_link($parsedItem['url'], $fieldName . '链接');
    }

    return implode("\n", array_values(array_unique($normalizedItems)));
}

function sr_normalize_button_color(?string $value): string
{
    $color = trim((string) $value);
    $allowed = ['btn-primary', 'btn-blue', 'btn-ghost'];
    if (!in_array($color, $allowed, true)) {
        throw new RuntimeException('按钮配色不在允许范围内。');
    }

    return $color;
}

function sr_hero_icon_options(): array
{
    return [
        'fas fa-arrow-right' => '箭头',
        'fas fa-rocket' => '火箭',
        'fas fa-user-plus' => '加入',
        'fas fa-users' => '社区',
        'fas fa-box-open' => '产品',
        'fas fa-play' => '播放',
        'fas fa-star' => '星标',
        'fas fa-download' => '下载',
        'fas fa-code' => '代码',
        'fab fa-github' => 'GitHub',
    ];
}

function sr_normalize_button_icon(?string $value): string
{
    $iconClass = trim((string) $value);
    if ($iconClass === '') {
        return 'fas fa-arrow-right';
    }

    if (!preg_match('/^(fa[srbdl]?|fa-solid|fa-regular|fa-brands)\s+fa-[a-z0-9-]+(?:\s+fa-[a-z0-9-]+)*$/i', $iconClass)) {
        throw new RuntimeException('按钮图标代码格式不正确。');
    }

    return $iconClass;
}

function sr_normalize_tags(?string $value): string
{
    $raw = trim((string) $value);
    if ($raw === '') {
        return '';
    }

    $parts = preg_split('/[\r\n,，]+/u', $raw) ?: [];
    $parts = array_values(array_unique(array_filter(array_map(
        static fn (string $item): string => trim($item),
        $parts
    ))));

    if (count($parts) > 8) {
        throw new RuntimeException('产品标签最多支持 8 个。');
    }

    foreach ($parts as $part) {
        if (mb_strlen($part) > 20) {
            throw new RuntimeException('单个产品标签长度不能超过 20 个字符。');
        }
    }

    return implode(',', $parts);
}

function sr_checkbox_value(string $key): int
{
    return isset($_POST[$key]) ? 1 : 0;
}

function sr_uploaded_file_is_present(string $fieldName): bool
{
    return isset($_FILES[$fieldName]) && is_array($_FILES[$fieldName]) && (int) ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

function sr_save_product_image(array $file): string
{
    $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode !== UPLOAD_ERR_OK) {
        throw new RuntimeException('图片上传失败，请确认文件大小和网络状态。');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('未检测到有效上传文件。');
    }

    $fileSize = (int) ($file['size'] ?? 0);
    if ($fileSize <= 0 || $fileSize > SR_MAX_UPLOAD_BYTES) {
        throw new RuntimeException('图片大小不能超过 3MB。');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = (string) $finfo->file($tmpName);
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowedTypes[$mimeType])) {
        throw new RuntimeException('仅允许上传 JPG、PNG、WEBP 图片。');
    }

    $imageInfo = @getimagesize($tmpName);
    if ($imageInfo === false) {
        throw new RuntimeException('上传文件不是有效图片。');
    }

    $width = (int) ($imageInfo[0] ?? 0);
    $height = (int) ($imageInfo[1] ?? 0);
    if ($width < 60 || $height < 60 || $width > 6000 || $height > 6000) {
        throw new RuntimeException('图片尺寸不符合要求。');
    }

    $fileName = 'product_' . bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];
    $targetPath = SR_PRODUCT_IMAGE_ABSOLUTE_DIR . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException('图片保存失败，请检查目录权限。');
    }

    return SR_PRODUCT_IMAGE_RELATIVE_DIR . '/' . $fileName;
}

function sr_delete_managed_product_image(string $imageUrl): void
{
    $normalized = str_replace('\\', '/', trim($imageUrl));
    if ($normalized === '' || !str_starts_with($normalized, SR_PRODUCT_IMAGE_RELATIVE_DIR . '/product_')) {
        return;
    }

    $filePath = SR_ROOT_DIR . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
    $realBase = realpath(SR_PRODUCT_IMAGE_ABSOLUTE_DIR);
    $realFile = realpath($filePath);

    if ($realBase === false || $realFile === false) {
        return;
    }

    if (str_starts_with($realFile, $realBase . DIRECTORY_SEPARATOR) && is_file($realFile)) {
        @unlink($realFile);
    }
}

function sr_public_site_data(): array
{
    if (!sr_is_installed()) {
        return [
            'siteName' => 'SR思锐 团队',
            'navigation' => [],
            'hero' => [
                'title' => '',
                'subtitle' => '',
                'buttons' => [],
            ],
            'products' => [],
            'community' => [
                'bilibiliUrl' => '',
                'githubUrl' => '',
                'qqGroupUrl' => '',
            ],
            'members' => [],
            'contact' => [
                'email' => '',
                'emails' => [],
                'githubRepository' => '',
                'githubRepositories' => [],
                'joinLink' => '',
                'queryLink' => '',
            ],
            'footer' => [
                'quickLinks' => [],
                'communityLinks' => [],
                'contactLinks' => [],
                'legalLinks' => [],
            ],
        ];
    }

    $pdo = sr_db();
    $navigation = $pdo->query('SELECT name, link, open_in_new_tab, sort_order FROM navigation_items ORDER BY sort_order ASC, id ASC')->fetchAll() ?: [];
    $heroButtons = $pdo->query('SELECT label, link, color_class, icon_class, sort_order FROM hero_buttons ORDER BY sort_order ASC, id ASC')->fetchAll() ?: [];
    $products = $pdo->query('SELECT id, name, link, description, tags, image_url, is_recommended, sort_order FROM products ORDER BY sort_order ASC, id ASC')->fetchAll() ?: [];
    $members = $pdo->query('SELECT name, avatar_url, position, bio, sort_order FROM team_members ORDER BY sort_order ASC, id ASC')->fetchAll() ?: [];

    return [
        'siteName' => sr_setting('site_name', 'SR思锐 团队'),
        'navigation' => array_map(static function (array $item): array {
            return [
                'name' => (string) $item['name'],
                'link' => (string) $item['link'],
                'openInNewTab' => ((int) $item['open_in_new_tab']) === 1,
            ];
        }, $navigation),
        'hero' => [
            'title' => sr_setting('hero_title'),
            'subtitle' => sr_setting('hero_subtitle'),
            'buttons' => array_map(static function (array $button): array {
                return [
                    'label' => (string) $button['label'],
                    'link' => (string) $button['link'],
                    'colorClass' => (string) $button['color_class'],
                    'iconClass' => (string) ($button['icon_class'] ?? 'fas fa-arrow-right'),
                ];
            }, $heroButtons),
        ],
        'products' => array_map(static function (array $product): array {
            $tags = trim((string) $product['tags']);
            return [
                'name' => (string) $product['name'],
                'link' => (string) $product['link'],
                'description' => (string) $product['description'],
                'tags' => $tags === '' ? [] : array_values(array_filter(array_map('trim', explode(',', $tags)))),
                'imageUrl' => (string) $product['image_url'],
                'isRecommended' => ((int) $product['is_recommended']) === 1,
            ];
        }, $products),
        'community' => [
            'bilibiliUrl' => sr_setting('community_bilibili_url'),
            'githubUrl' => sr_setting('community_github_url'),
            'qqGroupUrl' => sr_setting('community_qq_url'),
        ],
        'members' => array_map(static function (array $member): array {
            return [
                'name' => (string) $member['name'],
                'avatarUrl' => (string) $member['avatar_url'],
                'position' => (string) $member['position'],
                'bio' => (string) $member['bio'],
            ];
        }, $members),
        'contact' => (static function (): array {
            $emails = sr_parse_multiline_setting(sr_setting('contact_email'));
            $githubRepositories = sr_parse_multiline_setting(sr_setting('contact_github_repository'));

            $parsedGithubRepos = array_map(static function (string $item): array {
                $parts = explode('|', $item, 2);
                if (count($parts) === 2) {
                    return ['name' => trim($parts[0]), 'url' => trim($parts[1])];
                }
                return ['name' => '', 'url' => trim($parts[0])];
            }, $githubRepositories);

            return [
                'emails' => $emails,
                'githubRepositories' => $parsedGithubRepos,
                'email' => $emails[0] ?? '',
                'githubRepository' => $githubRepositories[0] ?? '',
                'joinLink' => sr_setting('contact_join_link'),
                'queryLink' => sr_setting('contact_query_link'),
                'communityLink' => sr_setting('contact_community_link'),
            ];
        })(),
        'footer' => [
            'quickLinks' => sr_parse_named_link_setting(sr_setting('footer_quick_links')),
            'communityLinks' => sr_parse_named_link_setting(sr_setting('footer_community_links')),
            'contactLinks' => sr_parse_named_link_setting(sr_setting('footer_contact_links')),
            'legalLinks' => sr_parse_named_link_setting(sr_setting('footer_legal_links')),
        ],
    ];
}

if (!defined('SR_SKIP_AUTO_BOOTSTRAP') || SR_SKIP_AUTO_BOOTSTRAP !== true) {
    sr_bootstrap();
}
