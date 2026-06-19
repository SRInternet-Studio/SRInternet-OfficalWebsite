<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$admin = sr_require_login();
$flash = sr_pull_flash();
$members = sr_fetch_all('team_members');
$csrfToken = sr_csrf_token();

$editingId = max(0, (int) ($_GET['edit'] ?? 0));
$isCreating = (($_GET['mode'] ?? '') === 'new');
$editingMember = null;

foreach ($members as $member) {
    if ((int) $member['id'] === $editingId) {
        $editingMember = $member;
        break;
    }
}

$adminPageTitle = '成员管理';
$adminPageKey = 'members';
$adminPageDescription = '默认显示成员列表，点击编辑后再维护头像、职位和简介。';

require __DIR__ . '/partials/header.php';
?>
<section class="panel-toolbar">
    <div class="toolbar-copy">
        <h3>成员列表</h3>
        <p>列表模式默认只展示摘要信息，减少页面干扰。</p>
    </div>
    <a class="btn-primary-solid" href="<?= sr_escape(sr_admin_url('members.php')) ?>?mode=new">新增成员</a>
</section>

<article class="form-card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>成员名称</th>
                    <th>职位</th>
                    <th>头像 URL</th>
                    <th>排序</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><?= sr_escape((string) $member['name']) ?></td>
                        <td><?= sr_escape((string) $member['position']) ?></td>
                        <td><code><?= sr_escape((string) $member['avatar_url']) ?></code></td>
                        <td><?= (int) $member['sort_order'] ?></td>
                        <td><a class="table-link" href="<?= sr_escape(sr_admin_url('members.php')) ?>?edit=<?= (int) $member['id'] ?>">编辑</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</article>

<?php if ($isCreating || $editingMember !== null): ?>
    <article class="form-card add-card">
        <h3><?= $editingMember !== null ? '编辑成员' : '新增成员' ?></h3>
        <form method="post" action="<?= sr_escape(sr_admin_url('action.php')) ?>" class="grid-form">
            <input type="hidden" name="csrf_token" value="<?= sr_escape($csrfToken) ?>">
            <input type="hidden" name="action" value="<?= $editingMember !== null ? 'update_member' : 'add_member' ?>">
            <?php if ($editingMember !== null): ?>
                <input type="hidden" name="id" value="<?= (int) $editingMember['id'] ?>">
            <?php endif; ?>

            <label><span>成员名称</span><input type="text" name="name" value="<?= sr_escape((string) ($editingMember['name'] ?? '')) ?>" maxlength="40" required></label>
            <label><span>头像 URL</span><input type="text" name="avatar_url" value="<?= sr_escape((string) ($editingMember['avatar_url'] ?? '')) ?>" maxlength="255" required></label>
            <label><span>职位</span><input type="text" name="position" value="<?= sr_escape((string) ($editingMember['position'] ?? '')) ?>" maxlength="40" required></label>
            <label><span>排序</span><input type="number" name="sort_order" value="<?= (int) ($editingMember['sort_order'] ?? 0) ?>" min="0"></label>
            <label class="full-width"><span>简介</span><textarea name="bio" rows="3" maxlength="160" required><?= sr_escape((string) ($editingMember['bio'] ?? '')) ?></textarea></label>

            <div class="action-row">
                <button type="submit" class="btn-primary-solid"><?= $editingMember !== null ? '保存成员' : '新增成员' ?></button>
                <a class="ghost-link" href="<?= sr_escape(sr_admin_url('members.php')) ?>">返回列表</a>
            </div>
        </form>

        <?php if ($editingMember !== null): ?>
            <form method="post" action="<?= sr_escape(sr_admin_url('action.php')) ?>" class="inline-danger-form" onsubmit="return confirm('确认删除该成员吗？');">
                <input type="hidden" name="csrf_token" value="<?= sr_escape($csrfToken) ?>">
                <input type="hidden" name="action" value="delete_member">
                <input type="hidden" name="id" value="<?= (int) $editingMember['id'] ?>">
                <button type="submit" class="btn-danger">删除成员</button>
            </form>
        <?php endif; ?>
    </article>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
