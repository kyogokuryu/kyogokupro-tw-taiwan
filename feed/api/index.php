<?php
/**
 * Feed API Endpoints
 * GET  /feed/api/?action=videos&page=1    - Get videos list
 * GET  /feed/api/?action=video&id=1       - Get single video
 * POST /feed/api/?action=like             - Toggle like
 * POST /feed/api/?action=view             - Record view
 * POST /feed/api/?action=analytics        - Record analytics event
 * POST /feed/api/?action=upload           - Upload video file (admin)
 * POST /feed/api/?action=ai_generate      - AI generate content (admin)
 * GET  /feed/api/?action=products         - Get EC-CUBE products (admin)
 * GET  /feed/api/?action=seo_article&id=1 - Get SEO article for video
 */

// Suppress PHP warnings/notices from corrupting JSON output
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);
ini_set('display_errors', 0);
ob_start(); // Buffer any unexpected output

require_once __DIR__ . '/../includes/config.php';

// === Fallback defaults: ensure critical constants are ALWAYS defined ===
// This prevents video widget from disappearing if config.php is incomplete
if (!defined('SITE_URL'))    define('SITE_URL', 'https://tw.kyogokupro.com');
if (!defined('FEED_URL'))    define('FEED_URL', SITE_URL . '/feed');
if (!defined('FEED_PATH'))   define('FEED_PATH', __DIR__ . '/..');
if (!defined('ECCUBE_URL'))  define('ECCUBE_URL', SITE_URL);
if (!defined('UPLOAD_DIR'))  define('UPLOAD_DIR', FEED_PATH . '/uploads/videos/');
if (!defined('UPLOAD_URL'))  define('UPLOAD_URL', FEED_URL . '/uploads/videos/');
if (!defined('VIDEOS_PER_PAGE')) define('VIDEOS_PER_PAGE', 10);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Discard any buffered PHP warnings before sending JSON
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$db = Database::getInstance();
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'videos':
            $page = max(1, intval($_GET['page'] ?? 1));
            $videos = $db->getVideos($page);
            $total = $db->getVideoCount();
            $ip = getClientIp();
            
            foreach ($videos as &$video) {
                enrichVideoData($video, $db, $ip);
            }
            
            jsonResponse([
                'success' => true,
                'data' => $videos,
                'pagination' => [
                    'page' => $page,
                    'per_page' => VIDEOS_PER_PAGE,
                    'total' => $total,
                    'total_pages' => ceil($total / VIDEOS_PER_PAGE),
                    'has_more' => ($page * VIDEOS_PER_PAGE) < $total
                ]
            ]);
            break;

        case 'video':
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(['error' => 'Invalid video ID'], 400);
            }
            
            $video = $db->getVideo($id);
            if (!$video) {
                jsonResponse(['error' => 'Video not found'], 404);
            }
            
            enrichVideoData($video, $db, getClientIp());
            $video['json_ld'] = generateVideoJsonLd($video);
            
            jsonResponse(['success' => true, 'data' => $video]);
            break;

        case 'like':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $videoId = intval($input['video_id'] ?? 0);
            
            if ($videoId <= 0) {
                jsonResponse(['error' => 'Invalid video ID'], 400);
            }
            
            $ip = getClientIp();
            $liked = $db->toggleLike($videoId, $ip);
            $video = $db->getVideo($videoId);
            
            jsonResponse([
                'success' => true,
                'liked' => $liked,
                'like_count' => $video['like_count'],
                'formatted_likes' => formatNumber($video['like_count'])
            ]);
            break;

        case 'view':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $videoId = intval($input['video_id'] ?? 0);
            
            if ($videoId <= 0) {
                jsonResponse(['error' => 'Invalid video ID'], 400);
            }
            
            $db->incrementViewCount($videoId);
            $db->recordAnalytics($videoId, 'view');
            
            jsonResponse(['success' => true]);
            break;

        case 'analytics':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $videoId = intval($input['video_id'] ?? 0);
            $actionType = $input['action_type'] ?? '';
            
            if ($videoId <= 0 || !in_array($actionType, ['share', 'product_click'])) {
                jsonResponse(['error' => 'Invalid parameters'], 400);
            }
            
            $db->recordAnalytics($videoId, $actionType);
            jsonResponse(['success' => true]);
            break;

        // ===== Admin API Endpoints =====

        case 'admin_create':
            requireAdminAuth();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $videoId = $db->createVideo([
                'title' => $input['title'] ?? 'New Video',
                'description' => $input['description'] ?? '',
                'video_url' => $input['video_url'] ?? '',
                'video_file_path' => $input['video_file_path'] ?? null,
                'video_type' => $input['video_type'] ?? 'youtube',
                'thumbnail_url' => $input['thumbnail_url'] ?? null,
                'duration' => $input['duration'] ?? null,
                'sort_order' => $input['sort_order'] ?? 0,
                'is_published' => $input['is_published'] ?? 1,
                'tags' => $input['tags'] ?? null,
            ]);
            if (!empty($input['product_ids'])) {
                $db->setVideoProducts($videoId, $input['product_ids']);
            }
            jsonResponse(['success' => true, 'video_id' => $videoId]);
            break;

        case 'upload':
            requireAdminAuth();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            handleVideoUpload();
            break;

        case 'upload_thumbnail':
            requireAdminAuth();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            handleThumbnailUpload();
            break;

        case 'ai_generate':
            requireAdminAuth();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            handleAiGenerate($db);
            break;

        case 'products':
            requireAdminAuth();
            $products = $db->getEcCubeProducts();
            foreach ($products as &$product) {
                $product['image_url'] = getProductImageUrl($product['image_file'] ?? null);
                $product['formatted_price'] = $product['price'] ? 'NT$' . number_format($product['price']) : '';
            }
            jsonResponse(['success' => true, 'data' => $products]);
            break;

        case 'seo_article':
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(['error' => 'Invalid video ID'], 400);
            }
            $video = $db->getVideo($id);
            if (!$video || !$video['seo_article_published']) {
                jsonResponse(['error' => 'Article not found'], 404);
            }
            jsonResponse([
                'success' => true,
                'data' => [
                    'title' => $video['title'],
                    'article' => $video['ai_seo_article'],
                    'variant' => $video['active_variant'],
                ]
            ]);
            break;

        case 'set_variant':
            requireAdminAuth();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $videoId = intval($input['video_id'] ?? 0);
            $variant = $input['variant'] ?? 'A';
            if ($videoId <= 0 || !in_array($variant, ['A', 'B'])) {
                jsonResponse(['error' => 'Invalid parameters'], 400);
            }
            $db->setActiveVariant($videoId, $variant);
            jsonResponse(['success' => true]);
            break;

        case 'toggle_seo':
            requireAdminAuth();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $videoId = intval($input['video_id'] ?? 0);
            if ($videoId <= 0) {
                jsonResponse(['error' => 'Invalid video ID'], 400);
            }
            $db->toggleSeoArticle($videoId);
            jsonResponse(['success' => true]);
            break;

        case 'comments':
            $videoId = intval($_GET['video_id'] ?? 0);
            if ($videoId <= 0) {
                jsonResponse(['error' => 'Invalid video ID'], 400);
            }
            $comments = $db->getComments($videoId);
            jsonResponse(['success' => true, 'data' => $comments, 'count' => count($comments)]);
            break;

        case 'comment':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $videoId = intval($input['video_id'] ?? 0);
            $nickname = trim($input['nickname'] ?? '');
            $text = trim($input['comment_text'] ?? '');
            if ($videoId <= 0 || empty($text)) {
                jsonResponse(['error' => 'Invalid parameters'], 400);
            }
            if (mb_strlen($text) > 500) {
                jsonResponse(['error' => 'Comment too long'], 400);
            }
            $ip = getClientIp();
            $commentId = $db->addComment($videoId, $nickname, $text, $ip);
            $count = $db->getCommentCount($videoId);
            jsonResponse(['success' => true, 'comment_id' => $commentId, 'comment_count' => $count]);
            break;

        case 'bookmark':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['error' => 'Method not allowed'], 405);
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $videoId = intval($input['video_id'] ?? 0);
            if ($videoId <= 0) {
                jsonResponse(['error' => 'Invalid video ID'], 400);
            }
            $ip = getClientIp();
            $bookmarked = $db->toggleBookmark($videoId, $ip);
            $count = $db->getBookmarkCount($videoId);
            jsonResponse(['success' => true, 'bookmarked' => $bookmarked, 'bookmark_count' => $count]);
            break;

        case 'feed_videos':
            // Get random videos for infinite scroll feed
            // Optional: exclude_ids (comma-separated) to avoid duplicates
            $excludeIdsStr = $_GET['exclude_ids'] ?? '';
            $excludeIds = $excludeIdsStr ? array_map('intval', explode(',', $excludeIdsStr)) : [];
            $limit = min(intval($_GET['limit'] ?? 10), 20);
            $videos = $db->getRandomVideos($limit, $excludeIds);
            $ip = getClientIp();
            foreach ($videos as &$video) {
                enrichVideoData($video, $db, $ip);
            }
            header('Access-Control-Allow-Origin: *');
            jsonResponse([
                'success' => true,
                'data' => $videos,
            ]);
            break;

        case 'product_videos':
            // Get videos linked to a specific product
            $productId = intval($_GET['product_id'] ?? 0);
            if ($productId <= 0) {
                jsonResponse(['error' => 'Invalid product ID'], 400);
            }
            $videos = $db->getVideosByProductId($productId);
            $ip = getClientIp();
            foreach ($videos as &$video) {
                enrichVideoData($video, $db, $ip);
            }
            // Allow cross-origin for widget
            header('Access-Control-Allow-Origin: *');
            jsonResponse([
                'success' => true,
                'data' => $videos,
                'product_id' => $productId
            ]);
            break;

        default:
            jsonResponse(['error' => 'Unknown action'], 400);
    }
} catch (Exception $e) {
    error_log('Feed API Error: ' . $e->getMessage());
    jsonResponse(['error' => 'Internal server error'], 500);
}

// ===== Helper Functions =====

function enrichVideoData(&$video, $db, $ip) {
    // Determine video source
    if ($video['video_type'] === 'upload' && $video['video_file_path']) {
        $video['video_src'] = UPLOAD_URL . basename($video['video_file_path']);
        $video['is_uploaded'] = true;
    } else {
        $video['video_src'] = $video['video_url'];
        $video['is_uploaded'] = false;
    }
    
    // Video file URL for uploaded videos
    if ($video['video_type'] === 'upload' && !empty($video['video_file_path'])) {
        $video['video_file_url'] = UPLOAD_URL . basename($video['video_file_path']);
    } else {
        $video['video_file_url'] = '';
    }
    
    $video['thumbnail'] = $video['thumbnail_url'] ?: getYoutubeThumbnail($video['video_url']);
    $video['embed_url'] = getYoutubeEmbedUrl($video['video_url']);
    $video['youtube_id'] = extractYoutubeId($video['video_url']);
    $video['products'] = $db->getVideoProducts($video['id']);
    $video['has_liked'] = $db->hasLiked($video['id'], $ip);
    $video['has_bookmarked'] = $db->hasBookmarked($video['id'], $ip);
    $video['comment_count'] = $db->getCommentCount($video['id']);
    $video['bookmark_count'] = $db->getBookmarkCount($video['id']);
    $video['formatted_date'] = formatDate($video['created_at']);
    $video['formatted_views'] = formatNumber($video['view_count']);
    $video['formatted_likes'] = formatNumber($video['like_count']);
    
    // Display title/description based on active A/B variant
    $variant = $video['active_variant'] ?? 'A';
    if ($variant === 'B' && !empty($video['title_variant_b'])) {
        $video['display_title'] = $video['title_variant_b'];
        $video['display_description'] = $video['desc_variant_b'] ?? $video['description'];
    } elseif (!empty($video['title_variant_a'])) {
        $video['display_title'] = $video['title_variant_a'];
        $video['display_description'] = $video['desc_variant_a'] ?? $video['description'];
    } else {
        $video['display_title'] = $video['title'];
        $video['display_description'] = $video['description'];
    }
    
    foreach ($video['products'] as &$product) {
        $product['image_url'] = getProductImageUrl($product['image_file']);
        $product['product_url'] = getProductUrl($product['id']);
    }
}

function requireAdminAuth() {
    if (empty($_SESSION['feed_admin_logged_in'])) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
}

function handleVideoUpload() {
    if (!isset($_FILES['video'])) {
        jsonResponse(['error' => '未選擇影片檔案'], 400);
    }

    $file = $_FILES['video'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => '檔案超過伺服器限制',
            UPLOAD_ERR_FORM_SIZE => '檔案超過表單限制',
            UPLOAD_ERR_PARTIAL => '檔案上傳不完整',
            UPLOAD_ERR_NO_FILE => '未選擇檔案',
        ];
        jsonResponse(['error' => $errors[$file['error']] ?? '上傳錯誤'], 400);
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        jsonResponse(['error' => '檔案大小超過 100MB 限制'], 400);
    }

    // Validate MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, ALLOWED_VIDEO_TYPES)) {
        jsonResponse(['error' => '不支援的檔案格式。請上傳 MP4、WebM 或 MOV 格式'], 400);
    }

    // Validate extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_VIDEO_EXTENSIONS)) {
        jsonResponse(['error' => '不支援的副檔名'], 400);
    }

    // Create upload directory
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    // Generate unique filename
    $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $filepath = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        jsonResponse(['error' => '檔案儲存失敗'], 500);
    }

    jsonResponse([
        'success' => true,
        'data' => [
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => UPLOAD_URL . $filename,
            'size' => $file['size'],
            'original_name' => $file['name'],
        ]
    ]);
}

function handleThumbnailUpload() {
    if (!isset($_FILES['thumbnail'])) {
        jsonResponse(['error' => 'No thumbnail file'], 400);
    }

    $file = $_FILES['thumbnail'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['error' => 'Upload error'], 400);
    }

    // Validate it's an image
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'])) {
        jsonResponse(['error' => 'Invalid image format'], 400);
    }

    // Save to thumbnails directory
    $thumbDir = FEED_PATH . '/uploads/thumbnails/';
    if (!is_dir($thumbDir)) {
        mkdir($thumbDir, 0755, true);
    }

    $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.jpg';
    $filepath = $thumbDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        jsonResponse(['error' => 'Failed to save thumbnail'], 500);
    }

    $url = FEED_URL . '/uploads/thumbnails/' . $filename;
    jsonResponse([
        'success' => true,
        'data' => [
            'filename' => $filename,
            'url' => $url,
        ]
    ]);
}

function handleAiGenerate($db) {
    require_once __DIR__ . '/../includes/ai.php';

    $input = json_decode(file_get_contents('php://input'), true);
    $videoId = intval($input['video_id'] ?? 0);

    if ($videoId <= 0) {
        jsonResponse(['error' => 'Invalid video ID'], 400);
    }

    $video = $db->getVideo($videoId);
    if (!$video) {
        jsonResponse(['error' => 'Video not found'], 404);
    }

    // Get linked product names
    $linkedProductNames = [];
    $productIds = $db->getVideoProductIds($videoId);
    if (!empty($productIds)) {
        $products = $db->getEcCubeProducts();
        foreach ($products as $p) {
            if (in_array($p['id'], $productIds)) {
                $linkedProductNames[] = $p['name'];
            }
        }
    }

    $ai = new AiGenerator();
    $result = $ai->generateAll(
        $video['title'] ?: ($input['title'] ?? ''),
        $video['description'] ?: ($input['description'] ?? ''),
        $linkedProductNames
    );

    if (empty($result['title_variant_a']) && empty($result['seo_article'])) {
        jsonResponse(['error' => 'AI生成失敗。請確認API金鑰設定。'], 500);
    }

    // Update database
    $result['seo_article_published'] = 1;
    $db->updateVideoAiContent($videoId, $result);

    jsonResponse([
        'success' => true,
        'data' => $result,
    ]);
}
