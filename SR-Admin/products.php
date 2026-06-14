<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$admin = sr_require_login();
$flash = sr_pull_flash();
$products = sr_fetch_all('products');
$csrfToken = sr_csrf_token();

$editingId = max(0, (int) ($_GET['edit'] ?? 0));
$isCreating = (($_GET['mode'] ?? '') === 'new');
$editingProduct = null;

foreach ($products as $product) {
    if ((int) $product['id'] === $editingId) {
        $editingProduct = $product;
        break;
    }
}

$adminPageTitle = '产品管理';
$adminPageKey = 'products';
$adminPageDescription = '默认显示产品列表，编辑时会展示当前图片 URL 与更换图片表单。';

require __DIR__ . '/partials/header.php';
?>
<section class="panel-toolbar">
    <div class="toolbar-copy">
        <h3>产品列表</h3>
        <p>查看产品摘要、图片地址和推荐状态，点击编辑后再修改详细信息。</p>
    </div>
    <a class="btn-primary-solid" href="/SR-Admin/products.php?mode=new">新增产品</a>
</section>

<article class="form-card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>产品名</th>
                    <th>图片 URL</th>
                    <th>标签</th>
                    <th>推荐</th>
                    <th>排序</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= sr_escape((string) $product['name']) ?></td>
                        <td><code><?= sr_escape((string) $product['image_url']) ?></code></td>
                        <td><?= sr_escape((string) $product['tags']) ?></td>
                        <td><?= ((int) $product['is_recommended']) === 1 ? '是' : '否' ?></td>
                        <td><?= (int) $product['sort_order'] ?></td>
                        <td><a class="table-link" href="/SR-Admin/products.php?edit=<?= (int) $product['id'] ?>">编辑</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</article>

<?php if ($isCreating || $editingProduct !== null): ?>
    <article class="form-card add-card">
        <h3><?= $editingProduct !== null ? '编辑产品' : '新增产品' ?></h3>
        <form method="post" action="/SR-Admin/action.php" enctype="multipart/form-data" class="grid-form">
            <input type="hidden" name="csrf_token" value="<?= sr_escape($csrfToken) ?>">
            <input type="hidden" name="action" value="<?= $editingProduct !== null ? 'update_product' : 'add_product' ?>">
            <?php if ($editingProduct !== null): ?>
                <input type="hidden" name="id" value="<?= (int) $editingProduct['id'] ?>">
            <?php endif; ?>

            <label><span>产品名称</span><input type="text" name="name" value="<?= sr_escape((string) ($editingProduct['name'] ?? '')) ?>" maxlength="60" required></label>
            <label><span>产品链接</span><input type="text" name="link" value="<?= sr_escape((string) ($editingProduct['link'] ?? '')) ?>" maxlength="255" required></label>
            <label class="full-width"><span>产品描述</span><textarea name="description" rows="3" maxlength="220" required><?= sr_escape((string) ($editingProduct['description'] ?? '')) ?></textarea></label>
            <label><span>产品标签</span><input type="text" name="tags" value="<?= sr_escape((string) ($editingProduct['tags'] ?? '')) ?>" maxlength="120" placeholder="多个标签用逗号分隔"></label>
            <label><span>排序</span><input type="number" name="sort_order" value="<?= (int) ($editingProduct['sort_order'] ?? 0) ?>" min="0"></label>
            <?php if ($editingProduct !== null): ?>
                <label><span>当前图片 URL</span><input type="text" value="<?= sr_escape((string) $editingProduct['image_url']) ?>" readonly></label>
                <label><span>更换图片</span><input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp"></label>
            <?php else: ?>
                <label><span>产品图片</span><input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp" required></label>
            <?php endif; ?>
            <label class="checkbox-line"><input type="checkbox" name="is_recommended" <?= ((int) ($editingProduct['is_recommended'] ?? 0)) === 1 ? 'checked' : '' ?>><span>设为推荐</span></label>
            <?php if ($editingProduct !== null): ?>
                <label class="full-width"><span>图片预览</span>
                    <div class="preview-line">
                        <img src="/<?= sr_escape((string) $editingProduct['image_url']) ?>" alt="<?= sr_escape((string) $editingProduct['name']) ?>" class="product-preview">
                        <span><?= sr_escape((string) $editingProduct['image_url']) ?></span>
                    </div>
                </label>
            <?php endif; ?>

            <div class="action-row">
                <button type="submit" class="btn-primary-solid"><?= $editingProduct !== null ? '保存产品' : '新增产品' ?></button>
                <a class="ghost-link" href="/SR-Admin/products.php">返回列表</a>
            </div>
        </form>

        <?php if ($editingProduct !== null): ?>
            <form method="post" action="/SR-Admin/action.php" class="inline-danger-form" onsubmit="return confirm('确认删除该产品吗？这会尝试删除后台上传的图片。');">
                <input type="hidden" name="csrf_token" value="<?= sr_escape($csrfToken) ?>">
                <input type="hidden" name="action" value="delete_product">
                <input type="hidden" name="id" value="<?= (int) $editingProduct['id'] ?>">
                <button type="submit" class="btn-danger">删除产品</button>
            </form>
        <?php endif; ?>
    </article>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
