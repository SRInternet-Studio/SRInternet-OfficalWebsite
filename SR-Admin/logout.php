<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

sr_logout();
sr_start_secure_session();
sr_flash('success', '已安全退出后台。');
sr_redirect(sr_admin_url('login.php'));
