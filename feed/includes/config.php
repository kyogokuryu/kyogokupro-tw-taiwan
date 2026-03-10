<?php
/**
 * Feed Configuration
 * tw.kyogokupro.com/feed/
 * EC-CUBEとは完全に独立した動画フィード設定
 */
// Error reporting (本番では OFF にする)
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);

// Load .env if available (for feed standalone)
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

// Database Configuration
define('DB_HOST', getenv('FEED_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('FEED_DB_NAME') ?: 'xs679489_taiwan');
define('DB_USER', getenv('FEED_DB_USER') ?: 'xs679489_taiwan');
define('DB_PASS', getenv('FEED_DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
define('SITE_URL', 'https://tw.kyogokupro.com');
define('FEED_URL', SITE_URL . '/feed');
define('FEED_PATH', __DIR__ . '/..');
define('ECCUBE_URL', SITE_URL);

// Admin Configuration
define('ADMIN_USER', 'kyogoku_admin');
define('ADMIN_PASS_HASH', ''); // Will be set during first login

// Pagination
define('VIDEOS_PER_PAGE', 10);

// Taiwan GEO settings
define('GEO_REGION', 'TW');
define('GEO_LANGUAGE', 'zh-TW');
define('GEO_CURRENCY', 'TWD');

// Session
session_start();

// Timezone
date_default_timezone_set('Asia/Taipei');
