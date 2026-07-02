<?php
/**
 * Aylıq ödəniş xatırlatmaları.
 *
 * Windows Task Scheduler / cron nümunəsi (hər gün saat 09:00):
 *   php c:\xampp\htdocs\src\src\All\cron\send_payment_reminders.php
 *
 * Və ya brauzerdən (yalnız lokal/test):
 *   /src/All/cron/send_payment_reminders.php?key=CHANGE_ME
 */
declare(strict_types=1);

$isCli = (PHP_SAPI === 'cli');
if ($isCli) {
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['REQUEST_METHOD'] = 'GET';
}
if (!$isCli) {
    $expectedKey = getenv('PAYMENT_CRON_KEY') ?: 'tis_payment_cron_2026';
    $providedKey = $_GET['key'] ?? '';
    if (!hash_equals($expectedKey, (string) $providedKey)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
    header('Content-Type: application/json; charset=utf-8');
}

require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/../../vendor/autoload.php';
require_once dirname(__DIR__) . '/qeydiyyatar/odenis_helpers.php';

$result = process_payment_reminders($conn);
$conn->close();

$output = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

if ($isCli) {
    echo $output . PHP_EOL;
} else {
    echo $output;
}
