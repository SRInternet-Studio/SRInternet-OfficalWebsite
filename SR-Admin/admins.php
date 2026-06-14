<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$admin = sr_require_login();
$flash = sr_pull_flash();
$admins = sr_fetch_all('admins');
$csrfToken = sr_csrf_token();

$editingId = max(0, (int) ($_GET['edit'] ?? 0));
$isCreating = (($_GET['mode'] ?? '') === 'new');
$editingAdmin = null;

foreach ($admins as $managedAdmin) {
    if ((int) $managedAdmin['id'] === $editingId) {
        $editingAdmin = $managedAdmin;
        break;
    }
}

$adminPageTitle = '管理员';
$adminPageKey = 'admins';
$adminPageDescription = '默认先显示管理员列表，编辑时再展示密码与删除操作。';

require __DIR__ . '/partials/header.php';
?>
<section class="panel-toolbar">
    <div class="toolbar-copy">
        <h3>管理员列表</h3>
        <p>列表默认只展示账号摘要，避免页面堆满操作按钮。</p>
    </div>
    <a class="btn-primary-solid" href="/SR-Admin/admins.php?mode=new">新增管理员</a>
</section>

<article class="form-card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>账号</th>
                    <th>邮箱</th>
                    <th>QQ</th>
                    <th>上次登录</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $managedAdmin): ?>
                    <tr>
                        <td><?= sr_escape((string) $managedAdmin['username']) ?></td>
                        <td><?= sr_escape((string) $managedAdmin['email']) ?></td>
                        <td><?= sr_escape((string) $managedAdmin['qq']) ?></td>
                        <td><?= sr_escape((string) ($managedAdmin['last_login_at'] ?? '从未登录')) ?></td>
                        <td><a class="table-link" href="/SR-Admin/admins.php?edit=<?= (int) $managedAdmin['id'] ?>">编辑</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</article>

<?php if ($isCreating || $editingAdmin !== null): ?>
    <article class="form-card add-card">
        <h3><?= $editingAdmin !== null ? '编辑管理员' : '新增管理员' ?></h3>
        <form method="post" action="/SR-Admin/action.php" class="grid-form">
            <input type="hidden" name="csrf_token" value="<?= sr_escape($csrfToken) ?>">
            <input type="hidden" name="action" value="<?= $editingAdmin !== null ? 'update_admin' : 'add_admin' ?>">
            <?php if ($editingAdmin !== null): ?>
                <input type="hidden" name="id" value="<?= (int) $editingAdmin['id'] ?>">
            <?php endif; ?>

            <label><span>账号</span><input type="text" name="username" value="<?= sr_escape((string) ($editingAdmin['username'] ?? '')) ?>" maxlength="32" required></label>
            <label><span>邮箱</span><input type="email" name="email" value="<?= sr_escape((string) ($editingAdmin['email'] ?? '')) ?>" maxlength="120" required></label>
            <label><span>QQ</span><input type="text" name="qq" value="<?= sr_escape((string) ($editingAdmin['qq'] ?? '')) ?>" maxlength="12"></label>
            <label><span><?= $editingAdmin !== null ? '新密码' : '初始密码' ?></span><input type="password" name="password" minlength="8" maxlength="64" <?= $editingAdmin === null ? 'required' : '' ?> placeholder="<?= $editingAdmin !== null ? '留空则不修改' : '' ?>"></label>

            <div class="action-row">
                <button type="submit" class="btn-primary-solid"><?= $editingAdmin !== null ? '保存管理员' : '新增管理员' ?></button>
                <a class="ghost-link" href="/SR-Admin/admins.php">返回列表</a>
            </div>
        </form>

        <?php if ($editingAdmin !== null && count($admins) > 1): ?>
            <form method="post" action="/SR-Admin/action.php" class="inline-danger-form" onsubmit="return confirm('确认删除该管理员吗？');">
                <input type="hidden" name="csrf_token" value="<?= sr_escape($csrfToken) ?>">
                <input type="hidden" name="action" value="delete_admin">
                <input type="hidden" name="id" value="<?= (int) $editingAdmin['id'] ?>">
                <button type="submit" class="btn-danger">删除管理员</button>
            </form>
        <?php endif; ?>
    </article>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
