<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$admin = sr_require_login();
$flash = sr_pull_flash();
$logs = sr_fetch_all('operation_logs');

$adminPageTitle = '操作日志';
$adminPageKey = 'logs';
$adminPageDescription = '记录后台操作、操作人、时间和详情。';

require __DIR__ . '/partials/header.php';
?>
<article class="form-card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>时间</th>
                    <th>操作</th>
                    <th>操作人</th>
                    <th>详情</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= sr_escape((string) $log['created_at']) ?></td>
                        <td><?= sr_escape((string) $log['action']) ?></td>
                        <td><?= sr_escape((string) $log['operator']) ?></td>
                        <td><?= sr_escape((string) $log['details']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</article>
<?php require __DIR__ . '/partials/footer.php'; ?>
