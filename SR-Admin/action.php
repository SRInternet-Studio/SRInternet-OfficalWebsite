<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    sr_redirect('/SR-Admin/index.php');
}

$admin = sr_require_login();
$action = trim((string) ($_POST['action'] ?? ''));
$redirectMap = [
    'add_navigation' => '/SR-Admin/navigation.php',
    'update_navigation' => '/SR-Admin/navigation.php',
    'delete_navigation' => '/SR-Admin/navigation.php',
    'save_hero' => '/SR-Admin/hero.php',
    'add_hero_button' => '/SR-Admin/hero.php',
    'update_hero_button' => '/SR-Admin/hero.php',
    'delete_hero_button' => '/SR-Admin/hero.php',
    'add_product' => '/SR-Admin/products.php',
    'update_product' => '/SR-Admin/products.php',
    'delete_product' => '/SR-Admin/products.php',
    'save_community' => '/SR-Admin/community.php',
    'add_member' => '/SR-Admin/members.php',
    'update_member' => '/SR-Admin/members.php',
    'delete_member' => '/SR-Admin/members.php',
    'save_contact' => '/SR-Admin/contact.php',
    'save_footer' => '/SR-Admin/footer.php',
    'add_admin' => '/SR-Admin/admins.php',
    'update_admin' => '/SR-Admin/admins.php',
    'delete_admin' => '/SR-Admin/admins.php',
];
$redirectTarget = $redirectMap[$action] ?? '/SR-Admin/index.php';

try {
    sr_verify_csrf($_POST['csrf_token'] ?? null);

    $pdo = sr_db();
    $now = sr_now();
    $operator = (string) $admin['username'];

    switch ($action) {
        case 'add_navigation':
            $statement = $pdo->prepare(
                'INSERT INTO navigation_items (name, link, open_in_new_tab, sort_order, created_at, updated_at)
                 VALUES (:name, :link, :open_in_new_tab, :sort_order, :created_at, :updated_at)'
            );
            $name = sr_normalize_text($_POST['name'] ?? '', 30, '导航名称');
            $link = sr_normalize_link($_POST['link'] ?? '');
            $statement->execute([
                ':name' => $name,
                ':link' => $link,
                ':open_in_new_tab' => sr_checkbox_value('open_in_new_tab'),
                ':sort_order' => sr_normalize_sort_order($_POST['sort_order'] ?? 0),
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
            sr_log_operation('新增导航', $operator, $name);
            sr_flash('success', '导航项已新增。');
            break;

        case 'update_navigation':
            $statement = $pdo->prepare(
                'UPDATE navigation_items
                 SET name = :name, link = :link, open_in_new_tab = :open_in_new_tab, sort_order = :sort_order, updated_at = :updated_at
                 WHERE id = :id'
            );
            $name = sr_normalize_text($_POST['name'] ?? '', 30, '导航名称');
            $statement->execute([
                ':name' => $name,
                ':link' => sr_normalize_link($_POST['link'] ?? ''),
                ':open_in_new_tab' => sr_checkbox_value('open_in_new_tab'),
                ':sort_order' => sr_normalize_sort_order($_POST['sort_order'] ?? 0),
                ':updated_at' => $now,
                ':id' => (int) ($_POST['id'] ?? 0),
            ]);
            sr_log_operation('更新导航', $operator, $name);
            sr_flash('success', '导航项已更新。');
            break;

        case 'delete_navigation':
            $id = (int) ($_POST['id'] ?? 0);
            $statement = $pdo->prepare('DELETE FROM navigation_items WHERE id = :id');
            $statement->execute([':id' => $id]);
            sr_log_operation('删除导航', $operator, 'ID: ' . $id);
            sr_flash('success', '导航项已删除。');
            break;

        case 'save_hero':
            sr_save_setting('hero_title', sr_normalize_text($_POST['hero_title'] ?? '', 80, 'Hero 标题'));
            sr_save_setting('hero_subtitle', sr_normalize_text($_POST['hero_subtitle'] ?? '', 200, 'Hero 副标题'));
            sr_log_operation('保存 Hero', $operator, '更新首页主文案');
            sr_flash('success', 'Hero 文案已保存。');
            break;

        case 'add_hero_button':
            $label = sr_normalize_text($_POST['label'] ?? '', 20, '按钮文字');
            $statement = $pdo->prepare(
                'INSERT INTO hero_buttons (label, link, color_class, icon_class, sort_order, created_at, updated_at)
                 VALUES (:label, :link, :color_class, :icon_class, :sort_order, :created_at, :updated_at)'
            );
            $statement->execute([
                ':label' => $label,
                ':link' => sr_normalize_link($_POST['link'] ?? ''),
                ':color_class' => sr_normalize_button_color($_POST['color_class'] ?? ''),
                ':icon_class' => sr_normalize_button_icon($_POST['icon_class'] ?? ''),
                ':sort_order' => sr_normalize_sort_order($_POST['sort_order'] ?? 0),
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
            sr_log_operation('新增 Hero 按钮', $operator, $label);
            sr_flash('success', 'Hero 按钮已新增。');
            break;

        case 'update_hero_button':
            $label = sr_normalize_text($_POST['label'] ?? '', 20, '按钮文字');
            $statement = $pdo->prepare(
                'UPDATE hero_buttons
                 SET label = :label, link = :link, color_class = :color_class, icon_class = :icon_class, sort_order = :sort_order, updated_at = :updated_at
                 WHERE id = :id'
            );
            $statement->execute([
                ':label' => $label,
                ':link' => sr_normalize_link($_POST['link'] ?? ''),
                ':color_class' => sr_normalize_button_color($_POST['color_class'] ?? ''),
                ':icon_class' => sr_normalize_button_icon($_POST['icon_class'] ?? ''),
                ':sort_order' => sr_normalize_sort_order($_POST['sort_order'] ?? 0),
                ':updated_at' => $now,
                ':id' => (int) ($_POST['id'] ?? 0),
            ]);
            sr_log_operation('更新 Hero 按钮', $operator, $label);
            sr_flash('success', 'Hero 按钮已更新。');
            break;

        case 'delete_hero_button':
            $id = (int) ($_POST['id'] ?? 0);
            $statement = $pdo->prepare('DELETE FROM hero_buttons WHERE id = :id');
            $statement->execute([':id' => $id]);
            sr_log_operation('删除 Hero 按钮', $operator, 'ID: ' . $id);
            sr_flash('success', 'Hero 按钮已删除。');
            break;

        case 'add_product':
            if (!sr_uploaded_file_is_present('image_file')) {
                throw new RuntimeException('新增产品必须上传图片。');
            }

            $name = sr_normalize_text($_POST['name'] ?? '', 60, '产品名称');
            $statement = $pdo->prepare(
                'INSERT INTO products (name, link, description, tags, image_url, is_recommended, sort_order, created_at, updated_at)
                 VALUES (:name, :link, :description, :tags, :image_url, :is_recommended, :sort_order, :created_at, :updated_at)'
            );
            $statement->execute([
                ':name' => $name,
                ':link' => sr_normalize_link($_POST['link'] ?? ''),
                ':description' => sr_normalize_text($_POST['description'] ?? '', 220, '产品描述'),
                ':tags' => sr_normalize_tags($_POST['tags'] ?? ''),
                ':image_url' => sr_save_product_image($_FILES['image_file']),
                ':is_recommended' => sr_checkbox_value('is_recommended'),
                ':sort_order' => sr_normalize_sort_order($_POST['sort_order'] ?? 0),
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
            sr_log_operation('新增产品', $operator, $name);
            sr_flash('success', '产品已新增。');
            break;

        case 'update_product':
            $id = (int) ($_POST['id'] ?? 0);
            $currentProductStatement = $pdo->prepare('SELECT image_url FROM products WHERE id = :id LIMIT 1');
            $currentProductStatement->execute([':id' => $id]);
            $currentProduct = $currentProductStatement->fetch();
            if (!is_array($currentProduct)) {
                throw new RuntimeException('产品不存在。');
            }

            $imageUrl = (string) $currentProduct['image_url'];
            if (sr_uploaded_file_is_present('image_file')) {
                $newImageUrl = sr_save_product_image($_FILES['image_file']);
                sr_delete_managed_product_image($imageUrl);
                $imageUrl = $newImageUrl;
            }

            $name = sr_normalize_text($_POST['name'] ?? '', 60, '产品名称');
            $statement = $pdo->prepare(
                'UPDATE products
                 SET name = :name, link = :link, description = :description, tags = :tags, image_url = :image_url,
                     is_recommended = :is_recommended, sort_order = :sort_order, updated_at = :updated_at
                 WHERE id = :id'
            );
            $statement->execute([
                ':name' => $name,
                ':link' => sr_normalize_link($_POST['link'] ?? ''),
                ':description' => sr_normalize_text($_POST['description'] ?? '', 220, '产品描述'),
                ':tags' => sr_normalize_tags($_POST['tags'] ?? ''),
                ':image_url' => $imageUrl,
                ':is_recommended' => sr_checkbox_value('is_recommended'),
                ':sort_order' => sr_normalize_sort_order($_POST['sort_order'] ?? 0),
                ':updated_at' => $now,
                ':id' => $id,
            ]);
            sr_log_operation('更新产品', $operator, $name);
            sr_flash('success', '产品已更新。');
            break;

        case 'delete_product':
            $id = (int) ($_POST['id'] ?? 0);
            $productStatement = $pdo->prepare('SELECT name, image_url FROM products WHERE id = :id LIMIT 1');
            $productStatement->execute([':id' => $id]);
            $product = $productStatement->fetch();
            if (!is_array($product)) {
                throw new RuntimeException('产品不存在。');
            }

            $statement = $pdo->prepare('DELETE FROM products WHERE id = :id');
            $statement->execute([':id' => $id]);
            sr_delete_managed_product_image((string) $product['image_url']);
            sr_log_operation('删除产品', $operator, (string) $product['name']);
            sr_flash('success', '产品已删除。');
            break;

        case 'save_community':
            sr_save_setting('community_bilibili_url', sr_normalize_link($_POST['bilibili_url'] ?? '', 'B 站链接'));
            sr_save_setting('community_github_url', sr_normalize_link($_POST['github_url'] ?? '', 'GitHub 链接'));
            sr_save_setting('community_qq_url', sr_normalize_link($_POST['qq_url'] ?? '', 'QQ群链接'));
            sr_log_operation('保存社区信息', $operator, '更新社区入口');
            sr_flash('success', '社区链接已保存。');
            break;

        case 'add_member':
            $name = sr_normalize_text($_POST['name'] ?? '', 40, '成员名称');
            $statement = $pdo->prepare(
                'INSERT INTO team_members (name, avatar_url, position, bio, sort_order, created_at, updated_at)
                 VALUES (:name, :avatar_url, :position, :bio, :sort_order, :created_at, :updated_at)'
            );
            $statement->execute([
                ':name' => $name,
                ':avatar_url' => sr_normalize_link($_POST['avatar_url'] ?? '', '头像 URL'),
                ':position' => sr_normalize_text($_POST['position'] ?? '', 40, '职位'),
                ':bio' => sr_normalize_text($_POST['bio'] ?? '', 160, '简介'),
                ':sort_order' => sr_normalize_sort_order($_POST['sort_order'] ?? 0),
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
            sr_log_operation('新增成员', $operator, $name);
            sr_flash('success', '成员已新增。');
            break;

        case 'update_member':
            $name = sr_normalize_text($_POST['name'] ?? '', 40, '成员名称');
            $statement = $pdo->prepare(
                'UPDATE team_members
                 SET name = :name, avatar_url = :avatar_url, position = :position, bio = :bio, sort_order = :sort_order, updated_at = :updated_at
                 WHERE id = :id'
            );
            $statement->execute([
                ':name' => $name,
                ':avatar_url' => sr_normalize_link($_POST['avatar_url'] ?? '', '头像 URL'),
                ':position' => sr_normalize_text($_POST['position'] ?? '', 40, '职位'),
                ':bio' => sr_normalize_text($_POST['bio'] ?? '', 160, '简介'),
                ':sort_order' => sr_normalize_sort_order($_POST['sort_order'] ?? 0),
                ':updated_at' => $now,
                ':id' => (int) ($_POST['id'] ?? 0),
            ]);
            sr_log_operation('更新成员', $operator, $name);
            sr_flash('success', '成员已更新。');
            break;

        case 'delete_member':
            $id = (int) ($_POST['id'] ?? 0);
            $statement = $pdo->prepare('DELETE FROM team_members WHERE id = :id');
            $statement->execute([':id' => $id]);
            sr_log_operation('删除成员', $operator, 'ID: ' . $id);
            sr_flash('success', '成员已删除。');
            break;

        case 'save_contact':
            sr_save_setting('contact_email', sr_normalize_email_list($_POST['contact_email'] ?? '', '联系邮箱'));
            sr_save_setting('contact_github_repository', sr_normalize_link_list($_POST['contact_github_repository'] ?? '', 'GitHub 仓库'));
            sr_save_setting('contact_join_link', sr_normalize_link($_POST['contact_join_link'] ?? '', '申请加入链接'));
            sr_save_setting('contact_query_link', sr_normalize_link($_POST['contact_query_link'] ?? '', '查询申请链接'));
            sr_save_setting('contact_community_link', sr_normalize_link($_POST['contact_community_link'] ?? '', '用户社区链接'));
            sr_log_operation('保存联系信息', $operator, '更新联系我们区块');
            sr_flash('success', '联系信息已保存。');
            break;

        case 'save_footer':
            sr_save_setting('footer_quick_links', sr_normalize_named_link_list($_POST['footer_quick_links'] ?? '', '页脚导航'));
            sr_save_setting('footer_community_links', sr_normalize_named_link_list($_POST['footer_community_links'] ?? '', '页脚社区链接'));
            sr_save_setting('footer_contact_links', sr_normalize_named_link_list($_POST['footer_contact_links'] ?? '', '页脚联系链接'));
            sr_save_setting('footer_legal_links', sr_normalize_named_link_list($_POST['footer_legal_links'] ?? '', '页脚法律链接'));
            sr_log_operation('保存页脚信息', $operator, '更新页脚导航与链接');
            sr_flash('success', '页脚内容已保存。');
            break;

        case 'add_admin':
            $username = sr_normalize_text($_POST['username'] ?? '', 32, '账号');
            $password = (string) ($_POST['password'] ?? '');
            if (strlen($password) < 8) {
                throw new RuntimeException('管理员密码至少 8 位。');
            }

            $statement = $pdo->prepare(
                'INSERT INTO admins (username, password_hash, email, qq, last_login_at, created_at, updated_at)
                 VALUES (:username, :password_hash, :email, :qq, :last_login_at, :created_at, :updated_at)'
            );
            $statement->execute([
                ':username' => $username,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ':email' => sr_normalize_email($_POST['email'] ?? ''),
                ':qq' => sr_normalize_qq($_POST['qq'] ?? ''),
                ':last_login_at' => null,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
            sr_log_operation('新增管理员', $operator, $username);
            sr_flash('success', '管理员已新增。');
            break;

        case 'update_admin':
            $id = (int) ($_POST['id'] ?? 0);
            $username = sr_normalize_text($_POST['username'] ?? '', 32, '账号');
            $password = trim((string) ($_POST['password'] ?? ''));

            if ($password !== '' && strlen($password) < 8) {
                throw new RuntimeException('管理员密码至少 8 位。');
            }

            $statement = $pdo->prepare(
                'UPDATE admins SET username = :username, email = :email, qq = :qq, updated_at = :updated_at WHERE id = :id'
            );
            $statement->execute([
                ':username' => $username,
                ':email' => sr_normalize_email($_POST['email'] ?? ''),
                ':qq' => sr_normalize_qq($_POST['qq'] ?? ''),
                ':updated_at' => $now,
                ':id' => $id,
            ]);

            if ($password !== '') {
                $passwordStatement = $pdo->prepare(
                    'UPDATE admins SET password_hash = :password_hash, updated_at = :updated_at WHERE id = :id'
                );
                $passwordStatement->execute([
                    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    ':updated_at' => $now,
                    ':id' => $id,
                ]);
            }

            if ($id === (int) $admin['id']) {
                $_SESSION['admin_id'] = $id;
            }

            sr_log_operation('更新管理员', $operator, $username);
            sr_flash('success', '管理员信息已更新。');
            break;

        case 'delete_admin':
            $id = (int) ($_POST['id'] ?? 0);
            if ($id === (int) $admin['id']) {
                throw new RuntimeException('不能删除当前登录的管理员。');
            }

            $count = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
            if ($count <= 1) {
                throw new RuntimeException('系统至少需要保留一个管理员。');
            }

            $statement = $pdo->prepare('DELETE FROM admins WHERE id = :id');
            $statement->execute([':id' => $id]);
            sr_log_operation('删除管理员', $operator, 'ID: ' . $id);
            sr_flash('success', '管理员已删除。');
            break;

        default:
            throw new RuntimeException('未识别的操作。');
    }
} catch (Throwable $exception) {
    sr_flash('error', $exception->getMessage());
}

sr_redirect($redirectTarget);
