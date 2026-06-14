<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$admin = sr_require_login();
$flash = sr_pull_flash();
$heroButtons = sr_fetch_all('hero_buttons');
$heroTitle = sr_setting('hero_title');
$heroSubtitle = sr_setting('hero_subtitle');
$csrfToken = sr_csrf_token();
$iconOptions = sr_hero_icon_options();

$editingId = max(0, (int) ($_GET['edit'] ?? 0));
$isCreating = (($_GET['mode'] ?? '') === 'new');
$editingButton = null;

foreach ($heroButtons as $button) {
    if ((int) $button['id'] === $editingId) {
        $editingButton = $button;
        break;
    }
}

$adminPageTitle = 'Hero 管理';
$adminPageKey = 'hero';
$adminPageDescription = '支持图标代码输入和常用图标快速选择。';

require __DIR__ . '/partials/header.php';
?>
<article class="form-card">
    <form method="post" action="/SR-Admin/action.php" class="stack-form">
        <input type="hidden" name="csrf_token" value="<?= sr_escape($csrfToken) ?>">
        <input type="hidden" name="action" value="save_hero">

        <label><span>Hero 标题</span><input type="text" name="hero_title" value="<?= sr_escape($heroTitle) ?>" maxlength="80" required></label>
        <label><span>Hero 副标题</span><textarea name="hero_subtitle" rows="3" maxlength="200" required><?= sr_escape($heroSubtitle) ?></textarea></label>

        <div class="action-row">
            <button type="submit" class="btn-primary-solid">保存 Hero 文案</button>
        </div>
    </form>
</article>

<section class="panel-toolbar">
    <div class="toolbar-copy">
        <h3>Hero 按钮列表</h3>
        <p>默认显示按钮摘要，点击编辑后再修改按钮内容、链接、配色和图标。</p>
    </div>
    <a class="btn-primary-solid" href="/SR-Admin/hero.php?mode=new">新增按钮</a>
</section>

<article class="form-card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>按钮文字</th>
                    <th>图标</th>
                    <th>链接</th>
                    <th>配色</th>
                    <th>排序</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($heroButtons as $button): ?>
                    <tr>
                        <td><?= sr_escape((string) $button['label']) ?></td>
                        <td><code><?= sr_escape((string) ($button['icon_class'] ?? 'fas fa-arrow-right')) ?></code></td>
                        <td><code><?= sr_escape((string) $button['link']) ?></code></td>
                        <td><?= sr_escape((string) $button['color_class']) ?></td>
                        <td><?= (int) $button['sort_order'] ?></td>
                        <td><a class="table-link" href="/SR-Admin/hero.php?edit=<?= (int) $button['id'] ?>">编辑</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</article>

<?php if ($isCreating || $editingButton !== null): ?>
    <article class="form-card add-card">
        <h3><?= $editingButton !== null ? '编辑 Hero 按钮' : '新增 Hero 按钮' ?></h3>
        <form method="post" action="/SR-Admin/action.php" class="grid-form">
            <input type="hidden" name="csrf_token" value="<?= sr_escape($csrfToken) ?>">
            <input type="hidden" name="action" value="<?= $editingButton !== null ? 'update_hero_button' : 'add_hero_button' ?>">
            <?php if ($editingButton !== null): ?>
                <input type="hidden" name="id" value="<?= (int) $editingButton['id'] ?>">
            <?php endif; ?>

            <label><span>按钮文字</span><input type="text" name="label" value="<?= sr_escape((string) ($editingButton['label'] ?? '')) ?>" maxlength="20" required></label>
            <label><span>按钮链接</span><input type="text" name="link" value="<?= sr_escape((string) ($editingButton['link'] ?? '')) ?>" maxlength="255" required></label>
            <label><span>按钮配色</span>
                <select name="color_class">
                    <?php $currentColor = (string) ($editingButton['color_class'] ?? 'btn-primary'); ?>
                    <option value="btn-primary" <?= $currentColor === 'btn-primary' ? 'selected' : '' ?>>主色</option>
                    <option value="btn-blue" <?= $currentColor === 'btn-blue' ? 'selected' : '' ?>>蓝色</option>
                    <option value="btn-ghost" <?= $currentColor === 'btn-ghost' ? 'selected' : '' ?>>透明</option>
                </select>
            </label>
            <label><span>排序</span><input type="number" name="sort_order" value="<?= (int) ($editingButton['sort_order'] ?? 0) ?>" min="0"></label>
            <label><span>图标选择</span>
                <?php $currentIcon = (string) ($editingButton['icon_class'] ?? 'fas fa-arrow-right'); ?>
                <select name="icon_picker" onchange="this.form.icon_class.value=this.value;">
                    <?php foreach ($iconOptions as $iconClass => $iconLabel): ?>
                        <option value="<?= sr_escape($iconClass) ?>" <?= $currentIcon === $iconClass ? 'selected' : '' ?>><?= sr_escape($iconLabel . ' / ' . $iconClass) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span>图标代码</span><input type="text" name="icon_class" value="<?= sr_escape($currentIcon) ?>" maxlength="80" required></label>
            <label class="full-width"><span>图标预览</span><div class="icon-preview-box"><i class="<?= sr_escape($currentIcon) ?>" aria-hidden="true"></i><code><?= sr_escape($currentIcon) ?></code></div></label>

            <div class="action-row">
                <button type="submit" class="btn-primary-solid"><?= $editingButton !== null ? '保存按钮' : '新增按钮' ?></button>
                <a class="ghost-link" href="/SR-Admin/hero.php">返回列表</a>
            </div>
        </form>

        <?php if ($editingButton !== null): ?>
            <form method="post" action="/SR-Admin/action.php" class="inline-danger-form" onsubmit="return confirm('确认删除该按钮吗？');">
                <input type="hidden" name="csrf_token" value="<?= sr_escape($csrfToken) ?>">
                <input type="hidden" name="action" value="delete_hero_button">
                <input type="hidden" name="id" value="<?= (int) $editingButton['id'] ?>">
                <button type="submit" class="btn-danger">删除按钮</button>
            </form>
        <?php endif; ?>
    </article>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
