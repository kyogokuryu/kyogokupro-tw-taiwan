<?php
/**
 * Feed API Endpoints
 * GET  /feed/api/?action=videos&page=1    - Get videos list
 * GET  /feed/api/?action=video&id=1       - Get single video
 * POST /feed/api/?action=like             - Toggle like
 * POST /feed/api/?action=view             - Record view
 * POST /feed/api/?action=analytics        - Record analytics event
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

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
            
            // Enrich video data
            foreach ($videos as &$video) {
                $video['thumbnail'] = $video['thumbnail_url'] ?: getYoutubeThumbnail($video['video_url']);
                $video['embed_url'] = getYoutubeEmbedUrl($video['video_url']);
                $video['youtube_id'] = extractYoutubeId($video['video_url']);
                $video['products'] = $db->getVideoProducts($video['id']);
                $video['has_liked'] = $db->hasLiked($video['id'], $ip);
                $video['formatted_date'] = formatDate($video['created_at']);
                $video['formatted_views'] = formatNumber($video['view_count']);
                $video['formatted_likes'] = formatNumber($video['like_count']);
                
                // Enrich product data
                foreach ($video['products'] as &$product) {
                    $product['image_url'] = getProductImageUrl($product['image_file']);
                    $product['product_url'] = getProductUrl($product['id']);
                }
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
            
            $video['thumbnail'] = $video['thumbnail_url'] ?: getYoutubeThumbnail($video['video_url']);
            $video['embed_url'] = getYoutubeEmbedUrl($video['video_url']);
            $video['youtube_id'] = extractYoutubeId($video['video_url']);
            $video['products'] = $db->getVideoProducts($video['id']);
            $video['has_liked'] = $db->hasLiked($video['id'], getClientIp());
            $video['json_ld'] = generateVideoJsonLd($video);
            
            foreach ($video['products'] as &$product) {
                $product['image_url'] = getProductImageUrl($product['image_file']);
                $product['product_url'] = getProductUrl($product['id']);
            }
            
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

        default:
            jsonResponse(['error' => 'Unknown action', 'available' => ['videos', 'video', 'like', 'view', 'analytics']], 400);
    }
} catch (Exception $e) {
    error_log('Feed API Error: ' . $e->getMessage());
    jsonResponse(['error' => 'Internal server error'], 500);
}
