<?php
/**
 * Helper Functions
 */

// === Fallback defaults: ensure critical constants are defined ===
if (!defined('SITE_URL'))  define('SITE_URL', 'https://tw.kyogokupro.com');
if (!defined('FEED_URL'))  define('FEED_URL', SITE_URL . '/feed');
if (!defined('FEED_PATH')) define('FEED_PATH', __DIR__ . '/..');

/**
 * Extract YouTube video ID from URL
 */
function extractYoutubeId($url) {
    $patterns = [
        '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
        '/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

/**
 * Get YouTube thumbnail URL
 */
function getYoutubeThumbnail($url, $quality = 'hqdefault') {
    $videoId = extractYoutubeId($url);
    if ($videoId) {
        return "https://img.youtube.com/vi/{$videoId}/{$quality}.jpg";
    }
    return null;
}

/**
 * Get YouTube embed URL
 */
function getYoutubeEmbedUrl($url) {
    $videoId = extractYoutubeId($url);
    if ($videoId) {
        return "https://www.youtube.com/embed/{$videoId}";
    }
    return $url;
}

/**
 * Sanitize output
 */
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Format number for display (e.g., 1.2K, 3.4M)
 */
function formatNumber($num) {
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . 'M';
    }
    if ($num >= 1000) {
        return round($num / 1000, 1) . 'K';
    }
    return $num;
}

/**
 * Format date for display in Chinese
 */
function formatDate($dateStr) {
    $date = new DateTime($dateStr);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->days == 0) {
        if ($diff->h == 0) {
            return $diff->i . ' 分鐘前';
        }
        return $diff->h . ' 小時前';
    }
    if ($diff->days < 7) {
        return $diff->days . ' 天前';
    }
    if ($diff->days < 30) {
        return floor($diff->days / 7) . ' 週前';
    }
    if ($diff->days < 365) {
        return floor($diff->days / 30) . ' 個月前';
    }
    return $date->format('Y/m/d');
}

/**
 * Get client IP address
 */
function getClientIp() {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            // X-Forwarded-For may contain multiple IPs
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            return $ip;
        }
    }
    return '0.0.0.0';
}

/**
 * Get product image URL from EC-CUBE
 */
function getProductImageUrl($fileName) {
    if (empty($fileName)) {
        return FEED_URL . '/assets/img/no-image.svg';
    }
    return SITE_URL . '/html/upload/save_image/' . $fileName;
}

/**
 * Get product URL in EC-CUBE
 */
function getProductUrl($productId) {
    return SITE_URL . '/products/detail/' . $productId;
}

/**
 * Generate JSON-LD for a video
 */
function generateVideoJsonLd($video) {
    $thumbnailUrl = $video['thumbnail_url'] ?: getYoutubeThumbnail($video['video_url']);
    
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'VideoObject',
        'name' => $video['title'],
        'description' => $video['description'] ?: $video['title'],
        'thumbnailUrl' => $thumbnailUrl,
        'uploadDate' => date('c', strtotime($video['created_at'])),
        'contentUrl' => $video['video_url'],
        'embedUrl' => getYoutubeEmbedUrl($video['video_url']),
        'interactionStatistic' => [
            [
                '@type' => 'InteractionCounter',
                'interactionType' => 'https://schema.org/WatchAction',
                'userInteractionCount' => $video['view_count']
            ],
            [
                '@type' => 'InteractionCounter',
                'interactionType' => 'https://schema.org/LikeAction',
                'userInteractionCount' => $video['like_count']
            ]
        ],
        'inLanguage' => 'zh-TW',
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Kyogoku Professional',
            'url' => SITE_URL
        ]
    ];
    
    if (!empty($video['duration'])) {
        $jsonLd['duration'] = 'PT' . $video['duration'] . 'S';
    }
    
    return $jsonLd;
}

/**
 * CSRF token generation and validation
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * JSON response helper
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
