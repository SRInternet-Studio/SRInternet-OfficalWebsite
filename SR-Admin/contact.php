<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$admin = sr_require_login();
$flash = sr_pull_flash();
$contactEmail = sr_setting('contact_email');
$contactGithubRepository = sr_setting('contact_github_repository');
$contactJoinLink = sr_setting('contact_join_link');
$contactQueryLink = sr_setting('contact_query_link');
$contactCommunityLink = sr_setting('contact_community_link');
$csrfToken = sr_csrf_token();

$adminPageTitle = '联系我们';
$adminPageKey = 'contact';
$adminPageDescription = '支持为联系邮箱和 GitHub 仓库录入多条数据，每行一条。';

require __DIR__ . '/partials/header.php';
?>
<article class="form-card">
    <form method="post" action="<?= sr_escape(sr_admin_url('action.php')) ?>" class="grid-form">
        <input type="hidden" name="csrf_token" value="<?= sr_escape($csrfToken) ?>">
        <input type="hidden" name="action" value="save_contact">

        <label class="full-width"><span>联系邮箱</span><textarea name="contact_email" rows="4" placeholder="每行一个邮箱" required><?= sr_escape($contactEmail) ?></textarea></label>
        <label class="full-width">
            <span>GitHub 仓库</span>
            <textarea name="contact_github_repository" rows="4" placeholder="每行一个仓库链接。支持自定义名称，格式为：名称|链接" required><?= sr_escape($contactGithubRepository) ?></textarea>
            <p class="small muted">例如：<code>项目主页|https://github.com/SRInternet-Studio/xxx</code></p>
        </label>
        <label><span>申请加入链接</span><input type="text" name="contact_join_link" value="<?= sr_escape($contactJoinLink) ?>" maxlength="255" required></label>
        <label><span>查询申请链接</span><input type="text" name="contact_query_link" value="<?= sr_escape($contactQueryLink) ?>" maxlength="255" required></label>
        <label><span>用户社区链接</span><input type="text" name="contact_community_link" value="<?= sr_escape($contactCommunityLink) ?>" maxlength="255" required></label>

        <div class="action-row">
            <button type="submit" class="btn-primary-solid">保存联系信息</button>
        </div>
    </form>
</article>
<?php require __DIR__ . '/partials/footer.php'; ?>
