<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$admin = sr_require_login();
$flash = sr_pull_flash();
$footerQuickLinks = sr_setting('footer_quick_links');
$footerCommunityLinks = sr_setting('footer_community_links');
$footerContactLinks = sr_setting('footer_contact_links');
$footerLegalLinks = sr_setting('footer_legal_links');
$csrfToken = sr_csrf_token();

$adminPageTitle = '页脚管理';
$adminPageKey = 'footer';
$adminPageDescription = '独立维护页脚导航和四组页脚链接。每行使用“名称|链接”格式。';

require __DIR__ . '/partials/header.php';
?>
<article class="form-card">
    <form method="post" action="/SR-Admin/action.php" class="grid-form">
        <input type="hidden" name="csrf_token" value="<?= sr_escape($csrfToken) ?>">
        <input type="hidden" name="action" value="save_footer">

        <label class="full-width">
            <span>页脚导航</span>
            <textarea name="footer_quick_links" rows="5" placeholder="每行一个，格式：名称|链接" required><?= sr_escape($footerQuickLinks) ?></textarea>
            <p class="small muted">示例：<code>首页|#home</code> 或 <code>产品中心|#products</code></p>
        </label>

        <label class="full-width">
            <span>社区入口</span>
            <textarea name="footer_community_links" rows="5" placeholder="每行一个，格式：名称|链接" required><?= sr_escape($footerCommunityLinks) ?></textarea>
            <p class="small muted">示例：<code>GitHub|https://github.com/SRInternet-Studio</code></p>
        </label>

        <label class="full-width">
            <span>联系与合作</span>
            <textarea name="footer_contact_links" rows="5" placeholder="每行一个，格式：名称|链接" required><?= sr_escape($footerContactLinks) ?></textarea>
            <p class="small muted">支持 <code>mailto:</code>、站内锚点、相对路径和外部链接。</p>
        </label>

        <label class="full-width">
            <span>法律信息</span>
            <textarea name="footer_legal_links" rows="5" placeholder="每行一个，格式：名称|链接" required><?= sr_escape($footerLegalLinks) ?></textarea>
            <p class="small muted">示例：<code>团队政策|https://example.com/policy</code></p>
        </label>

        <div class="action-row">
            <button type="submit" class="btn-primary-solid">保存页脚内容</button>
        </div>
    </form>
</article>
<?php require __DIR__ . '/partials/footer.php'; ?>
