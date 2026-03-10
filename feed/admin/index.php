<?php
/**
 * Feed Admin Panel
 * tw.kyogokupro.com/feed/admin/
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Simple authentication
$ADMIN_USER = 'kyogoku';
$ADMIN_PASS = 'Kyogoku2026Feed!';

// Check login
if (!isset($_SESSION['feed_admin_logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        if ($_POST['username'] === $ADMIN_USER && $_POST['password'] === $ADMIN_PASS) {
            $_SESSION['feed_admin_logged_in'] = true;
            header('Location: /feed/admin/');
            exit;
        } else {
            $loginError = '帳號或密碼錯誤';
        }
    }
    
    if (!isset($_SESSION['feed_admin_logged_in'])) {
        showLoginForm($loginError ?? null);
        exit;
    }
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['feed_admin_logged_in']);
    header('Location: /feed/admin/');
    exit;
}

$db = Database::getInstance();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';
    
    switch ($action) {
        case 'create':
            $videoId = $db->createVideo([
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'video_url' => $_POST['video_url'] ?? '',
                'video_type' => $_POST['video_type'] ?? 'youtube',
                'thumbnail_url' => $_POST['thumbnail_url'] ?? null,
                'duration' => !empty($_POST['duration']) ? intval($_POST['duration']) : null,
                'sort_order' => intval($_POST['sort_order'] ?? 0),
                'is_published' => isset($_POST['is_published']) ? 1 : 0,
                'tags' => $_POST['tags'] ?? null,
            ]);
            
            if (!empty($_POST['product_ids'])) {
                $db->setVideoProducts($videoId, $_POST['product_ids']);
            }
            
            $message = '影片已新增成功';
            $messageType = 'success';
            break;
            
        case 'update':
            $id = intval($_POST['video_id']);
            $db->updateVideo($id, [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'video_url' => $_POST['video_url'] ?? '',
                'video_type' => $_POST['video_type'] ?? 'youtube',
                'thumbnail_url' => $_POST['thumbnail_url'] ?? null,
                'duration' => !empty($_POST['duration']) ? intval($_POST['duration']) : null,
                'sort_order' => intval($_POST['sort_order'] ?? 0),
                'is_published' => isset($_POST['is_published']) ? 1 : 0,
                'tags' => $_POST['tags'] ?? null,
            ]);
            
            $productIds = isset($_POST['product_ids']) ? $_POST['product_ids'] : [];
            $db->setVideoProducts($id, $productIds);
            
            $message = '影片已更新成功';
            $messageType = 'success';
            break;
            
        case 'delete':
            $id = intval($_POST['video_id']);
            $db->deleteVideo($id);
            $message = '影片已刪除';
            $messageType = 'success';
            break;
    }
}

// Get data
$videos = $db->getAllVideos();
$products = $db->getEcCubeProducts();
$analytics = $db->getAnalyticsSummary(30);

// Edit mode
$editVideo = null;
$editProducts = [];
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $editVideo = $db->getVideo($editId);
    if ($editVideo) {
        $editProductsRaw = $db->getVideoProducts($editId);
        foreach ($editProductsRaw as $ep) {
            $editProducts[] = $ep['id'];
        }
    }
}

showAdminPage($videos, $products, $analytics, $editVideo, $editProducts, $message, $messageType);

// ===== View Functions =====

function showLoginForm($error = null) {
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed 管理 - 登入</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; background: #f5f5f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-box { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .login-title { font-size: 24px; font-weight: 700; margin-bottom: 8px; text-align: center; }
        .login-sub { font-size: 14px; color: #666; text-align: center; margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 14px; font-weight: 500; margin-bottom: 6px; color: #333; }
        .form-input { width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; transition: border-color 0.2s; }
        .form-input:focus { outline: none; border-color: #c8a876; }
        .btn-login { width: 100%; padding: 12px; background: #c8a876; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-login:hover { background: #b89766; }
        .error { background: #fee; color: #c00; padding: 10px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-title">Feed 管理</div>
        <div class="login-sub">tw.kyogokupro.com/feed/</div>
        <?php if ($error): ?>
        <div class="error"><?php echo h($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">帳號</label>
                <input type="text" name="username" class="form-input" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">密碼</label>
                <input type="password" name="password" class="form-input" required>
            </div>
            <button type="submit" name="login" class="btn-login">登入</button>
        </form>
    </div>
</body>
</html>
<?php
}

function showAdminPage($videos, $products, $analytics, $editVideo, $editProducts, $message, $messageType) {
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed 管理面板</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Noto Sans TC', sans-serif; background: #f0f2f5; color: #333; }
        .admin-header { background: #1a1a1a; color: #fff; padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
        .admin-title { font-size: 18px; font-weight: 700; }
        .admin-title span { color: #c8a876; }
        .admin-nav { display: flex; gap: 16px; align-items: center; }
        .admin-nav a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; transition: color 0.2s; }
        .admin-nav a:hover { color: #fff; }
        .admin-content { max-width: 1200px; margin: 0 auto; padding: 24px; }
        
        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-label { font-size: 13px; color: #666; margin-bottom: 4px; }
        .stat-value { font-size: 28px; font-weight: 700; color: #1a1a1a; }
        .stat-sub { font-size: 12px; color: #999; margin-top: 4px; }
        
        /* Cards */
        .card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 24px; overflow: hidden; }
        .card-header { padding: 16px 20px; border-bottom: 1px solid #eee; display: flex; align-items: center; justify-content: space-between; }
        .card-title { font-size: 16px; font-weight: 600; }
        .card-body { padding: 20px; }
        
        /* Form */
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        .form-row.full { grid-template-columns: 1fr; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #555; }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; transition: border-color 0.2s; font-family: inherit; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: #c8a876; }
        .form-textarea { min-height: 80px; resize: vertical; }
        .form-hint { font-size: 12px; color: #999; margin-top: 4px; }
        .form-check { display: flex; align-items: center; gap: 8px; }
        .form-check input { width: 18px; height: 18px; }
        
        /* Buttons */
        .btn { padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: #c8a876; color: #fff; }
        .btn-primary:hover { background: #b89766; }
        .btn-danger { background: #ff4444; color: #fff; }
        .btn-danger:hover { background: #cc0000; }
        .btn-secondary { background: #eee; color: #333; }
        .btn-secondary:hover { background: #ddd; }
        .btn-sm { padding: 6px 12px; font-size: 13px; }
        
        /* Table */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px 16px; font-size: 13px; font-weight: 600; color: #666; border-bottom: 2px solid #eee; white-space: nowrap; }
        td { padding: 12px 16px; border-bottom: 1px solid #f0f0f0; font-size: 14px; vertical-align: middle; }
        tr:hover td { background: #fafafa; }
        .thumb-sm { width: 60px; height: 40px; object-fit: cover; border-radius: 6px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-green { background: #e6f9e6; color: #0a7; }
        .badge-gray { background: #f0f0f0; color: #666; }
        .actions { display: flex; gap: 8px; }
        
        /* Message */
        .message { padding: 12px 20px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .message-success { background: #e6f9e6; color: #0a7; border: 1px solid #b3e6b3; }
        .message-error { background: #fee; color: #c00; border: 1px solid #fcc; }
        
        /* Multi-select */
        .multi-select { border: 1px solid #ddd; border-radius: 8px; max-height: 200px; overflow-y: auto; padding: 8px; }
        .multi-select label { display: flex; align-items: center; gap: 8px; padding: 6px 8px; border-radius: 6px; cursor: pointer; font-size: 13px; }
        .multi-select label:hover { background: #f5f5f5; }
        .multi-select input { width: 16px; height: 16px; }
        
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .admin-content { padding: 16px; }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-title"><span>Feed</span> 管理面板</div>
        <nav class="admin-nav">
            <a href="/feed/" target="_blank">前往 Feed</a>
            <a href="/feed/admin/?action=logout">登出</a>
        </nav>
    </header>

    <div class="admin-content">
        <?php if ($message): ?>
        <div class="message message-<?php echo $messageType; ?>"><?php echo h($message); ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">影片總數</div>
                <div class="stat-value"><?php echo count($videos); ?></div>
            </div>
            <?php
            $totalViews = 0; $totalLikes = 0;
            foreach ($analytics as $a) {
                if ($a['action_type'] === 'view') $totalViews = $a['count'];
                if ($a['action_type'] === 'like') $totalLikes = $a['count'];
            }
            ?>
            <div class="stat-card">
                <div class="stat-label">30天觀看次數</div>
                <div class="stat-value"><?php echo number_format($totalViews); ?></div>
                <div class="stat-sub">不重複用戶: <?php 
                    foreach ($analytics as $a) { if ($a['action_type'] === 'view') echo number_format($a['unique_users']); }
                ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">30天按讚數</div>
                <div class="stat-value"><?php echo number_format($totalLikes); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">30天商品點擊</div>
                <div class="stat-value"><?php 
                    $clicks = 0;
                    foreach ($analytics as $a) { if ($a['action_type'] === 'product_click') $clicks = $a['count']; }
                    echo number_format($clicks);
                ?></div>
            </div>
        </div>

        <!-- Add/Edit Form -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><?php echo $editVideo ? '編輯影片' : '新增影片'; ?></div>
                <?php if ($editVideo): ?>
                <a href="/feed/admin/" class="btn btn-secondary btn-sm">取消編輯</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="form_action" value="<?php echo $editVideo ? 'update' : 'create'; ?>">
                    <?php if ($editVideo): ?>
                    <input type="hidden" name="video_id" value="<?php echo $editVideo['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">影片標題 *</label>
                            <input type="text" name="title" class="form-input" required value="<?php echo h($editVideo['title'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">影片網址 *</label>
                            <input type="url" name="video_url" class="form-input" required placeholder="https://www.youtube.com/watch?v=..." value="<?php echo h($editVideo['video_url'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row full">
                        <div class="form-group">
                            <label class="form-label">說明</label>
                            <textarea name="description" class="form-textarea"><?php echo h($editVideo['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">影片類型</label>
                            <select name="video_type" class="form-select">
                                <option value="youtube" <?php echo ($editVideo['video_type'] ?? '') === 'youtube' ? 'selected' : ''; ?>>YouTube</option>
                                <option value="direct" <?php echo ($editVideo['video_type'] ?? '') === 'direct' ? 'selected' : ''; ?>>直接連結</option>
                                <option value="embed" <?php echo ($editVideo['video_type'] ?? '') === 'embed' ? 'selected' : ''; ?>>嵌入</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">縮圖網址</label>
                            <input type="url" name="thumbnail_url" class="form-input" placeholder="留空自動取得 YouTube 縮圖" value="<?php echo h($editVideo['thumbnail_url'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">影片長度（秒）</label>
                            <input type="number" name="duration" class="form-input" min="0" value="<?php echo h($editVideo['duration'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">排序（數字越小越前面）</label>
                            <input type="number" name="sort_order" class="form-input" value="<?php echo h($editVideo['sort_order'] ?? '0'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">標籤</label>
                            <input type="text" name="tags" class="form-input" placeholder="以逗號分隔，例如：護髮,角蛋白,教學" value="<?php echo h($editVideo['tags'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">狀態</label>
                            <div class="form-check" style="margin-top: 8px;">
                                <input type="checkbox" name="is_published" id="is_published" <?php echo (!$editVideo || $editVideo['is_published']) ? 'checked' : ''; ?>>
                                <label for="is_published">公開發布</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">關聯商品</label>
                        <div class="multi-select">
                            <?php foreach ($products as $p): ?>
                            <label>
                                <input type="checkbox" name="product_ids[]" value="<?php echo $p['id']; ?>" 
                                    <?php echo in_array($p['id'], $editProducts) ? 'checked' : ''; ?>>
                                <?php echo h($p['name']); ?> (ID: <?php echo $p['id']; ?>)
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-hint">選擇要在影片下方顯示的商品</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editVideo ? '更新影片' : '新增影片'; ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Video List -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">影片列表</div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>縮圖</th>
                            <th>標題</th>
                            <th>狀態</th>
                            <th>觀看</th>
                            <th>按讚</th>
                            <th>商品</th>
                            <th>排序</th>
                            <th>建立日期</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($videos as $v): ?>
                        <tr>
                            <td><?php echo $v['id']; ?></td>
                            <td>
                                <?php $thumb = getYoutubeThumbnail($v['video_url'], 'default'); ?>
                                <?php if ($thumb): ?>
                                <img src="<?php echo h($thumb); ?>" class="thumb-sm" alt="">
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo h($v['title']); ?></strong></td>
                            <td>
                                <?php if ($v['is_published']): ?>
                                <span class="badge badge-green">公開</span>
                                <?php else: ?>
                                <span class="badge badge-gray">草稿</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo number_format($v['view_count']); ?></td>
                            <td><?php echo number_format($v['like_count']); ?></td>
                            <td><?php echo $v['product_count']; ?></td>
                            <td><?php echo $v['sort_order']; ?></td>
                            <td><?php echo date('Y/m/d', strtotime($v['created_at'])); ?></td>
                            <td>
                                <div class="actions">
                                    <a href="/feed/admin/?edit=<?php echo $v['id']; ?>" class="btn btn-secondary btn-sm">編輯</a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('確定要刪除此影片嗎？');">
                                        <input type="hidden" name="form_action" value="delete">
                                        <input type="hidden" name="video_id" value="<?php echo $v['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">刪除</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
<?php
}
