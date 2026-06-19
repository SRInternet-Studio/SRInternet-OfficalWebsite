<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

sr_require_login();

$daysInMonthFor = static function (int $year, int $month): int {
    return (int) (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t');
};

$view = $_GET['view'] ?? 'week'; // week, month, year
$dateParam = trim($_GET['date'] ?? '');

$pdo = sr_db();
$labels = [];
$data = [];

$now = time();

if ($view === 'week') {
    if (preg_match('/^(\d{4})-W(\d{1,2})$/', $dateParam, $matches)) {
        $year = (int)$matches[1];
        $week = (int)$matches[2];
        $dto = new DateTime();
        $dto->setISODate($year, $week);
        $startOfWeek = $dto->getTimestamp();
    } else {
        $startOfWeek = strtotime("Monday this week", $now);
    }
    
    for ($i = 0; $i < 7; $i++) {
        $currentDate = date('Y-m-d', $startOfWeek + ($i * 86400));
        $labels[] = date('m-d', $startOfWeek + ($i * 86400));
        
        $statement = $pdo->prepare('SELECT visit_count FROM daily_visits WHERE visit_date = :date');
        $statement->execute([':date' => $currentDate]);
        $data[] = (int) $statement->fetchColumn();
    }
} elseif ($view === 'month') {
    if (preg_match('/^(\d{4})-(\d{1,2})$/', $dateParam, $matches)) {
        $targetYear = (int)$matches[1];
        $targetMonth = (int)$matches[2];
    } else {
        $targetYear = (int)date('Y', $now);
        $targetMonth = (int)date('n', $now);
    }
    
    $daysInMonth = $daysInMonthFor($targetYear, $targetMonth);
    
    for ($i = 1; $i <= $daysInMonth; $i++) {
        $currentDate = sprintf('%04d-%02d-%02d', $targetYear, $targetMonth, $i);
        $labels[] = (string)$i;
        
        $statement = $pdo->prepare('SELECT visit_count FROM daily_visits WHERE visit_date = :date');
        $statement->execute([':date' => $currentDate]);
        $data[] = (int) $statement->fetchColumn();
    }
} elseif ($view === 'year') {
    if (preg_match('/^(\d{4})$/', $dateParam, $matches)) {
        $targetYear = (int)$matches[1];
    } else {
        $targetYear = (int)date('Y', $now);
    }
    
    for ($i = 1; $i <= 12; $i++) {
        $labels[] = $i . '月';
        
        $startOfMonth = sprintf('%04d-%02d-01', $targetYear, $i);
        $endOfMonth = sprintf('%04d-%02d-%02d', $targetYear, $i, $daysInMonthFor($targetYear, $i));
        
        $statement = $pdo->prepare('SELECT SUM(visit_count) FROM daily_visits WHERE visit_date >= :start AND visit_date <= :end');
        $statement->execute([':start' => $startOfMonth, ':end' => $endOfMonth]);
        $data[] = (int) $statement->fetchColumn();
    }
}

sr_json_response(200, [
    'labels' => $labels,
    'data' => $data,
    'title' => $view === 'week' ? "周访问数据" : ($view === 'month' ? "月访问数据" : "年访问数据")
]);
