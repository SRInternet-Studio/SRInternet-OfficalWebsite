<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$admin = sr_require_login();
$flash = sr_pull_flash();
$communityBilibili = sr_setting('community_bilibili_url');
$communityGithub = sr_setting('community_github_url');
$communityQq = sr_setting('community_qq_url');
$csrfToken = sr_csrf_token();

$adminPageTitle = '社区管理';
$adminPageKey = 'community';
$adminPageDescription = '管理 B 站、GitHub 和 QQ 群入口。';

require __DIR__ . '/partials/header.php';
?>
<article class="form-card">
    <form method="post" action="<?= sr_escape(sr_admin_url('action.php')) ?>" class="grid-form">
        <input type="hidden" name="csrf_token" value="<?= sr_escape($csrfToken) ?>">
        <input type="hidden" name="action" value="save_community">

        <label><span>B 站链接</span><input type="text" name="bilibili_url" value="<?= sr_escape($communityBilibili) ?>" maxlength="255" required></label>
        <label><span>GitHub 链接</span><input type="text" name="github_url" value="<?= sr_escape($communityGithub) ?>" maxlength="255" required></label>
        <label><span>QQ群链接</span><input type="text" name="qq_url" value="<?= sr_escape($communityQq) ?>" maxlength="255" required></label>

        <div class="action-row">
            <button type="submit" class="btn-primary-solid">保存社区链接</button>
        </div>
    </form>
</article>
<?php require __DIR__ . '/partials/footer.php'; ?>
