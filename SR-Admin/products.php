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
$adminPageDescription = '默认显示产品列表,编辑时会展示当前图片 URL 与更换图片表单。';

$availableImages = sr_get_available_product_images();

require __DIR__ . '/partials/header.php';
?>
<section class="panel-toolbar">
    <div class="toolbar-copy">
        <h3>产品列表</h3>
        <p>查看产品摘要、图片地址和推荐状态，点击编辑后再修改详细信息。</p>
    </div>
    <a class="btn-primary-solid" href="<?= sr_escape(sr_admin_url('products.php')) ?>?mode=new">新增产品</a>
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
                        <td><a class="table-link" href="<?= sr_escape(sr_admin_url('products.php')) ?>?edit=<?= (int) $product['id'] ?>">编辑</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</article>

<?php if ($isCreating || $editingProduct !== null): ?>
    <article class="form-card add-card">
        <h3><?= $editingProduct !== null ? '编辑产品' : '新增产品' ?></h3>
        <form method="post" action="<?= sr_escape(sr_admin_url('action.php')) ?>" enctype="multipart/form-data" class="grid-form">
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
                <div class="full-width" style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <span style="font-weight: 600; font-size: 0.9rem; color: var(--text-main);">当前图片</span>
                    <div class="preview-line">
                        <img src="/<?= sr_escape((string) $editingProduct['image_url']) ?>" alt="<?= sr_escape((string) $editingProduct['name']) ?>" class="product-preview">
                        <code style="font-size: 0.85rem;"><?= sr_escape((string) $editingProduct['image_url']) ?></code>
                    </div>
                </div>
            <?php endif; ?>

            <div class="full-width image-input-section">
                <input type="hidden" name="image_input_method" id="imageInputMethod" value="upload">
                
                <div class="image-tabs">
                    <button type="button" class="image-tab active" data-method="upload">
                        <i class="fas fa-upload"></i> 上传文件
                    </button>
                    <button type="button" class="image-tab" data-method="select">
                        <i class="fas fa-images"></i> 选择现有图片
                    </button>
                    <button type="button" class="image-tab" data-method="path">
                        <i class="fas fa-link"></i> 输入路径/URL
                    </button>
                </div>

                <div class="image-input-content">
                    <div class="image-panel active" data-panel="upload">
                        <label>
                            <span><?= $editingProduct !== null ? '上传新图片' : '上传产品图片' ?></span>
                            <input type="file" name="image_file" id="imageFileInput" accept=".jpg,.jpeg,.png,.webp" <?= $editingProduct === null ? 'required' : '' ?>>
                        </label>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.5rem 0 0 0;">支持 JPG、PNG、WEBP 格式，最大 3MB</p>
                    </div>

                    <div class="image-panel" data-panel="select">
                        <?php if (count($availableImages) > 0): ?>
                            <div style="margin-bottom: 0.75rem;">
                                <span style="font-weight: 600; font-size: 0.9rem;">从 /static/images/products/ 目录选择：</span>
                            </div>
                            <div class="image-grid" id="imageGrid">
                                <?php foreach ($availableImages as $img): ?>
                                    <div class="image-item" data-path="<?= sr_escape($img['path']) ?>">
                                        <div class="image-item-preview">
                                            <img src="/<?= sr_escape($img['path']) ?>" alt="<?= sr_escape($img['filename']) ?>" loading="lazy">
                                        </div>
                                        <div class="image-item-info">
                                            <div class="image-item-name" title="<?= sr_escape($img['filename']) ?>"><?= sr_escape($img['filename']) ?></div>
                                            <div class="image-item-size"><?= round($img['size'] / 1024, 1) ?> KB</div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="selected_image" id="selectedImage" value="">
                        <?php else: ?>
                            <p style="color: var(--text-muted); font-size: 0.9rem;">目录中暂无可用图片</p>
                        <?php endif; ?>
                    </div>

                    <div class="image-panel" data-panel="path">
                        <label>
                            <span>图片路径或 URL</span>
                            <input type="text" name="manual_image_path" id="manualImagePath" placeholder="例如：static/images/products/example.jpg 或 https://example.com/image.jpg">
                        </label>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.5rem 0 0 0;">
                            可以输入相对路径（如 static/images/products/xxx.jpg）或完整的图片 URL
                        </p>
                    </div>
                </div>
            </div>

            <label class="checkbox-line"><input type="checkbox" name="is_recommended" <?= ((int) ($editingProduct['is_recommended'] ?? 0)) === 1 ? 'checked' : '' ?>><span>设为推荐</span></label>

            <div class="action-row">
                <button type="submit" class="btn-primary-solid"><?= $editingProduct !== null ? '保存产品' : '新增产品' ?></button>
                <a class="ghost-link" href="<?= sr_escape(sr_admin_url('products.php')) ?>">返回列表</a>
            </div>
        </form>

        <?php if ($editingProduct !== null): ?>
            <form method="post" action="<?= sr_escape(sr_admin_url('action.php')) ?>" class="inline-danger-form" onsubmit="return confirm('确认删除该产品吗？这会尝试删除后台上传的图片。');">
                <input type="hidden" name="csrf_token" value="<?= sr_escape($csrfToken) ?>">
                <input type="hidden" name="action" value="delete_product">
                <input type="hidden" name="id" value="<?= (int) $editingProduct['id'] ?>">
                <button type="submit" class="btn-danger">删除产品</button>
            </form>
        <?php endif; ?>
    </article>

    <?php if ($isCreating || $editingProduct !== null): ?>
    <script>
    (function() {
        const tabs = document.querySelectorAll('.image-tab');
        const panels = document.querySelectorAll('.image-panel');
        const methodInput = document.getElementById('imageInputMethod');
        const fileInput = document.getElementById('imageFileInput');
        const imageItems = document.querySelectorAll('.image-item');
        const selectedImageInput = document.getElementById('selectedImage');
        const manualPathInput = document.getElementById('manualImagePath');

        // 标签切换
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const method = this.dataset.method;
                
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                panels.forEach(p => p.classList.remove('active'));
                const targetPanel = document.querySelector(`[data-panel="${method}"]`);
                if (targetPanel) {
                    targetPanel.classList.add('active');
                }
                
                methodInput.value = method;

                // 根据选择的方法调整 required 属性
                if (method === 'upload') {
                    <?php if ($editingProduct === null): ?>
                    fileInput.required = true;
                    <?php endif; ?>
                } else {
                    fileInput.required = false;
                }
            });
        });

        // 图片选择
        imageItems.forEach(item => {
            item.addEventListener('click', function() {
                imageItems.forEach(i => i.classList.remove('selected'));
                this.classList.add('selected');
                selectedImageInput.value = this.dataset.path;
            });
        });

        // 表单提交验证
        const form = document.querySelector('.grid-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const method = methodInput.value;
                
                if (method === 'upload') {
                    <?php if ($editingProduct === null): ?>
                    if (!fileInput.files || fileInput.files.length === 0) {
                        e.preventDefault();
                        alert('请上传图片文件');
                        return false;
                    }
                    <?php endif; ?>
                } else if (method === 'select') {
                    if (!selectedImageInput.value) {
                        e.preventDefault();
                        alert('请从列表中选择图片');
                        return false;
                    }
                } else if (method === 'path') {
                    if (!manualPathInput.value.trim()) {
                        e.preventDefault();
                        alert('请输入图片路径或URL');
                        return false;
                    }
                }
            });
        }
    })();
    </script>
    <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
