<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once SR_ADMIN_DIR . '/bootstrap.php';

try {
    if (sr_is_installed()) {
        $today = date('Y-m-d');
        $pdo = sr_db();
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO daily_visits (visit_date, visit_count) VALUES (:date, 0)");
        $stmt->execute([':date' => $today]);
        $stmt = $pdo->prepare("UPDATE daily_visits SET visit_count = visit_count + 1 WHERE visit_date = :date");
        $stmt->execute([':date' => $today]);
    }

    sr_json_response(200, [
        'success' => true,
        'installed' => sr_is_installed(),
        'installUrl' => sr_install_url(),
        'data' => sr_public_site_data(),
    ]);
} catch (Throwable $exception) {
    sr_json_response(500, [
        'success' => false,
        'message' => 'Failed to load site content.',
    ]);
}
