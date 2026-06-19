<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$admin = sr_require_login();
$flash = sr_pull_flash();
$navigationItems = sr_fetch_all('navigation_items');
$csrfToken = sr_csrf_token();

$editingId = max(0, (int) ($_GET['edit'] ?? 0));
$isCreating = (($_GET['mode'] ?? '') === 'new');
$editingItem = null;

foreach ($navigationItems as $item) {
    if ((int) $item['id'] === $editingId) {
        $editingItem = $item;
        break;
    }
}

$adminPageTitle = '导航管理';
$adminPageKey = 'navigation';
$adminPageDescription = '默认显示导航列表，点击编辑或新增后再进入表单。';

require __DIR__ . '/partials/header.php';
?>
<section class="panel-toolbar">
    <div class="toolbar-copy">
        <h3>导航列表</h3>
        <p>管理导航名称、链接、排序和是否新标签页打开。</p>
    </div>
    <a class="btn-primary-solid" href="<?= sr_escape(sr_admin_url('navigation.php')) ?>?mode=new">新增导航项</a>
</section>

<article class="form-card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>名称</th>
                    <th>链接</th>
                    <th>排序</th>
                    <th>新标签页</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($navigationItems as $item): ?>
                    <tr>
                        <td><?= sr_escape((string) $item['name']) ?></td>
                        <td><code><?= sr_escape((string) $item['link']) ?></code></td>
                        <td><?= (int) $item['sort_order'] ?></td>
                        <td><?= ((int) $item['open_in_new_tab']) === 1 ? '是' : '否' ?></td>
                        <td><a class="table-link" href="<?= sr_escape(sr_admin_url('navigation.php')) ?>?edit=<?= (int) $item['id'] ?>">编辑</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</article>

<?php if ($isCreating || $editingItem !== null): ?>
    <article class="form-card add-card">
        <h3><?= $editingItem !== null ? '编辑导航项' : '新增导航项' ?></h3>
        <form method="post" action="<?= sr_escape(sr_admin_url('action.php')) ?>" class="grid-form">
            <input type="hidden" name="csrf_token" value="<?= sr_escape($csrfToken) ?>">
            <input type="hidden" name="action" value="<?= $editingItem !== null ? 'update_navigation' : 'add_navigation' ?>">
            <?php if ($editingItem !== null): ?>
                <input type="hidden" name="id" value="<?= (int) $editingItem['id'] ?>">
            <?php endif; ?>

            <label><span>导航名称</span><input type="text" name="name" value="<?= sr_escape((string) ($editingItem['name'] ?? '')) ?>" maxlength="30" required></label>
            <label><span>导航链接</span><input type="text" name="link" value="<?= sr_escape((string) ($editingItem['link'] ?? '')) ?>" maxlength="255" required></label>
            <label><span>排序</span><input type="number" name="sort_order" value="<?= (int) ($editingItem['sort_order'] ?? 0) ?>" min="0"></label>
            <label class="checkbox-line"><input type="checkbox" name="open_in_new_tab" <?= ((int) ($editingItem['open_in_new_tab'] ?? 0)) === 1 ? 'checked' : '' ?>><span>新标签页打开</span></label>

            <div class="action-row">
                <button type="submit" class="btn-primary-solid"><?= $editingItem !== null ? '保存导航' : '新增导航' ?></button>
                <a class="ghost-link" href="<?= sr_escape(sr_admin_url('navigation.php')) ?>">返回列表</a>
            </div>
        </form>

        <?php if ($editingItem !== null): ?>
            <form method="post" action="<?= sr_escape(sr_admin_url('action.php')) ?>" class="inline-danger-form" onsubmit="return confirm('确认删除该导航项吗？');">
                <input type="hidden" name="csrf_token" value="<?= sr_escape($csrfToken) ?>">
                <input type="hidden" name="action" value="delete_navigation">
                <input type="hidden" name="id" value="<?= (int) $editingItem['id'] ?>">
                <button type="submit" class="btn-danger">删除导航</button>
            </form>
        <?php endif; ?>
    </article>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
