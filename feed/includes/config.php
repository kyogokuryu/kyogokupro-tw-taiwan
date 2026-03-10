<?php
/**
 * Feed Configuration
 * tw.kyogokupro.com/feed/
 * EC-CUBEとは完全に独立した動画フィード設定
 */

// Error reporting (本番では OFF にする)
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);

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

// Upload Configuration
define('UPLOAD_DIR', FEED_PATH . '/uploads/videos/');
define('UPLOAD_URL', FEED_URL . '/uploads/videos/');
define('MAX_UPLOAD_SIZE', 100 * 1024 * 1024); // 100MB
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/quicktime']);
define('ALLOWED_VIDEO_EXTENSIONS', ['mp4', 'webm', 'mov']);

// OpenAI API Configuration
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');
define('OPENAI_MODEL', 'gpt-4.1-mini');
define('OPENAI_BASE_URL', 'https://api.openai.com/v1');

// Session
session_start();

// Timezone
date_default_timezone_set('Asia/Taipei');
