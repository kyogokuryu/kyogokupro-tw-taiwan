<?php
/**
 * Feed Admin Panel - 影片管理 + AI自動最佳化
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

$totalViews = 0;
$totalLikes = 0;
foreach ($videos as $v) {
    $totalViews += $v['view_count'];
    $totalLikes += $v['like_count'];
}

showAdminPage($videos, $products, $totalViews, $totalLikes, $message, $messageType);

// ===== View Functions =====

function showLoginForm($error = null) {
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>影片管理 - 登入</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0a; color: #e0e0e0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-box { background: #1a1a1a; border: 1px solid #333; border-radius: 12px; padding: 40px; width: 100%; max-width: 400px; }
        .login-box h1 { font-size: 24px; margin-bottom: 8px; color: #fff; }
        .login-box p { color: #888; margin-bottom: 24px; font-size: 14px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 14px; color: #aaa; }
        .form-group input { width: 100%; padding: 10px 14px; background: #0a0a0a; border: 1px solid #333; border-radius: 8px; color: #fff; font-size: 14px; outline: none; }
        .form-group input:focus { border-color: #e53e3e; }
        .btn-login { width: 100%; padding: 12px; background: #e53e3e; color: #fff; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 8px; }
        .btn-login:hover { background: #c53030; }
        .error { color: #e53e3e; font-size: 14px; margin-top: 12px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>影片管理系統</h1>
        <p>KYOGOKU Professional 台灣</p>
        <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>帳號</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label>密碼</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" name="login" class="btn-login">登入</button>
        </form>
    </div>
</body>
</html>
<?php exit;
}

function showAdminPage($videos, $products, $totalViews, $totalLikes, $message, $messageType) {
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>影片管理 + AI自動最佳化</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0a; color: #e0e0e0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        
        .header { background: #111; border-bottom: 1px solid #222; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        .header h1 { font-size: 18px; color: #fff; }
        .header h1 span { color: #e53e3e; }
        .header-actions { display: flex; gap: 12px; align-items: center; }
        .btn { padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: #e53e3e; color: #fff; }
        .btn-primary:hover { background: #c53030; }
        .btn-outline { background: transparent; color: #aaa; border: 1px solid #333; }
        .btn-outline:hover { color: #fff; border-color: #555; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; padding: 24px; }
        .stat-card { background: #1a1a1a; border: 1px solid #222; border-radius: 12px; padding: 20px; }
        .stat-card .label { font-size: 13px; color: #888; margin-bottom: 4px; }
        .stat-card .value { font-size: 28px; font-weight: 700; color: #fff; }
        .stat-card .value small { font-size: 14px; color: #888; font-weight: 400; }
        
        .content { padding: 0 24px 24px; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .section-header h2 { font-size: 18px; color: #fff; }
        
        .video-table { width: 100%; border-collapse: collapse; }
        .video-table th { text-align: left; padding: 12px 16px; background: #111; color: #888; font-size: 12px; font-weight: 500; text-transform: uppercase; border-bottom: 1px solid #222; }
        .video-table td { padding: 12px 16px; border-bottom: 1px solid #1a1a1a; vertical-align: middle; }
        .video-table tr:hover { background: #111; }
        
        .video-info { display: flex; align-items: center; gap: 12px; }
        .video-thumb { width: 80px; height: 45px; border-radius: 6px; object-fit: cover; background: #222; }
        .video-title { font-size: 14px; color: #fff; font-weight: 500; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .video-meta { font-size: 12px; color: #666; margin-top: 2px; }
        
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .badge-green { background: rgba(72,187,120,0.15); color: #48bb78; }
        .badge-gray { background: rgba(160,174,192,0.15); color: #a0aec0; }
        .badge-purple { background: rgba(159,122,234,0.15); color: #9f7aea; }
        .badge-red { background: rgba(245,101,101,0.15); color: #f56565; }
        .ai-status { display: flex; gap: 4px; flex-wrap: wrap; }
        .actions-cell { display: flex; gap: 6px; }
        
        /* Modal */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal { background: #1a1a1a; border: 1px solid #333; border-radius: 16px; width: 100%; max-width: 640px; max-height: 90vh; overflow-y: auto; position: relative; }
        .modal-header { padding: 20px 24px; border-bottom: 1px solid #222; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { font-size: 20px; color: #fff; font-weight: 700; }
        .modal-close { background: none; border: none; color: #888; font-size: 24px; cursor: pointer; padding: 4px 8px; }
        .modal-close:hover { color: #fff; }
        .modal-body { padding: 24px; }
        .modal-footer { padding: 16px 24px; border-top: 1px solid #222; display: flex; justify-content: flex-end; gap: 12px; }
        
        .form-section { margin-bottom: 24px; }
        .form-section-title { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
        .form-section-num { width: 24px; height: 24px; background: #e53e3e; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0; }
        .form-section-label { font-size: 15px; font-weight: 600; color: #fff; }
        
        .upload-area { border: 2px dashed #333; border-radius: 12px; padding: 40px 20px; text-align: center; cursor: pointer; transition: all 0.3s; }
        .upload-area:hover { border-color: #e53e3e; background: rgba(229,62,62,0.05); }
        .upload-area.dragover { border-color: #e53e3e; background: rgba(229,62,62,0.1); }
        .upload-area.has-file { border-color: #48bb78; background: rgba(72,187,120,0.05); }
        .upload-icon { font-size: 40px; color: #555; margin-bottom: 8px; }
        .upload-text { font-size: 14px; color: #888; }
        .upload-hint { font-size: 12px; color: #555; margin-top: 4px; }
        .upload-progress { margin-top: 12px; display: none; }
        .progress-bar { height: 4px; background: #222; border-radius: 2px; overflow: hidden; }
        .progress-fill { height: 100%; background: #e53e3e; border-radius: 2px; transition: width 0.3s; width: 0%; }
        .upload-filename { font-size: 13px; color: #48bb78; margin-top: 8px; }
        .upload-thumb-preview { margin-top: 12px; text-align: center; }
        .upload-thumb-preview img { max-width: 160px; border-radius: 8px; border: 1px solid #333; }
        .upload-thumb-preview .thumb-label { font-size: 11px; color: #888; margin-top: 4px; }
        
        .url-divider { text-align: center; color: #555; font-size: 13px; margin: 16px 0; position: relative; }
        .url-divider::before, .url-divider::after { content: ''; position: absolute; top: 50%; width: calc(50% - 20px); height: 1px; background: #333; }
        .url-divider::before { left: 0; }
        .url-divider::after { right: 0; }
        
        input[type="text"], input[type="url"], input[type="number"], textarea, select {
            width: 100%; padding: 10px 14px; background: #0a0a0a; border: 1px solid #333; border-radius: 8px; color: #fff; font-size: 14px; outline: none; font-family: inherit;
        }
        input:focus, textarea:focus, select:focus { border-color: #e53e3e; }
        textarea { resize: vertical; min-height: 80px; }
        
        .product-search { margin-bottom: 12px; position: relative; }
        .product-search input { padding-left: 36px; }
        .product-search::before { content: '\1F50D'; position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 14px; color: #555; pointer-events: none; }
        
        .product-list { max-height: 300px; overflow-y: auto; border: 1px solid #222; border-radius: 8px; background: #111; }
        .product-item { display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-bottom: 1px solid #1a1a1a; cursor: pointer; transition: background 0.2s; }
        .product-item:hover { background: #1a1a1a; }
        .product-item:last-child { border-bottom: none; }
        .product-item.hidden { display: none; }
        .product-item input[type="checkbox"] { accent-color: #e53e3e; width: 16px; height: 16px; flex-shrink: 0; }
        .product-img { width: 40px; height: 40px; border-radius: 6px; object-fit: cover; background: #222; flex-shrink: 0; }
        .product-name { font-size: 13px; color: #ddd; flex: 1; }
        .product-price { font-size: 12px; color: #888; flex-shrink: 0; }
        .product-no-results { padding: 20px; text-align: center; color: #555; font-size: 13px; display: none; }
        
        .toggle-row { display: flex; align-items: center; gap: 10px; }
        .toggle { position: relative; width: 44px; height: 24px; }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; inset: 0; background: #333; border-radius: 12px; cursor: pointer; transition: 0.3s; }
        .toggle-slider::before { content: ''; position: absolute; width: 18px; height: 18px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: 0.3s; }
        .toggle input:checked + .toggle-slider { background: #48bb78; }
        .toggle input:checked + .toggle-slider::before { transform: translateX(20px); }
        
        .ai-info { background: rgba(128,90,213,0.1); border: 1px solid rgba(128,90,213,0.3); border-radius: 8px; padding: 14px 16px; margin-top: 16px; }
        .ai-info-title { display: flex; align-items: center; gap: 6px; font-size: 14px; font-weight: 600; color: #9f7aea; margin-bottom: 4px; }
        .ai-info-text { font-size: 12px; color: #a0aec0; line-height: 1.5; }
        
        .completion-box { background: rgba(72,187,120,0.1); border: 1px solid rgba(72,187,120,0.3); border-radius: 12px; padding: 20px; text-align: center; }
        .completion-icon { font-size: 48px; color: #48bb78; margin-bottom: 8px; }
        .completion-title { font-size: 18px; font-weight: 700; color: #48bb78; margin-bottom: 4px; }
        .completion-subtitle { font-size: 13px; color: #a0aec0; margin-bottom: 16px; }
        .completion-items { text-align: left; }
        .completion-item { display: flex; align-items: center; gap: 8px; padding: 6px 0; font-size: 14px; color: #48bb78; }
        
        .spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        .generating-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.85); display: flex; flex-direction: column; align-items: center; justify-content: center; border-radius: 16px; z-index: 10; }
        .generating-text { color: #fff; margin-top: 16px; font-size: 14px; }
        .generating-sub { color: #888; margin-top: 4px; font-size: 12px; }
        
        .toast { position: fixed; top: 20px; right: 20px; padding: 12px 20px; border-radius: 8px; font-size: 14px; z-index: 2000; animation: slideIn 0.3s ease; }
        .toast-success { background: #48bb78; color: #fff; }
        .toast-error { background: #e53e3e; color: #fff; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        
        .message { padding: 12px 20px; border-radius: 8px; margin: 24px 24px 0; font-size: 14px; }
        .message-success { background: rgba(72,187,120,0.15); color: #48bb78; border: 1px solid rgba(72,187,120,0.3); }
        .message-error { background: rgba(245,101,101,0.15); color: #f56565; border: 1px solid rgba(245,101,101,0.3); }
        
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #111; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #555; }
        
        @media (max-width: 768px) {
            .stats { grid-template-columns: repeat(2, 1fr); }
            .header { flex-wrap: wrap; gap: 8px; }
            .video-title { max-width: 150px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>影片管理 + <span>AI自動最佳化</span></h1>
        <div class="header-actions">
            <a href="/feed/" target="_blank" class="btn btn-outline btn-sm">查看前台</a>
            <button class="btn btn-primary" onclick="openAddModal()">+ 新增影片</button>
            <a href="/feed/admin/?action=logout" class="btn btn-outline btn-sm">登出</a>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="message message-<?= $messageType ?>"><?= h($message) ?></div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat-card">
            <div class="label">影片總數</div>
            <div class="value"><?= count($videos) ?> <small>部</small></div>
        </div>
        <div class="stat-card">
            <div class="label">總觀看次數</div>
            <div class="value"><?= number_format($totalViews) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">總按讚數</div>
            <div class="value"><?= number_format($totalLikes) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">AI已生成</div>
            <div class="value"><?= count(array_filter($videos, function($v) { return !empty($v['title_variant_a']); })) ?> <small>/ <?= count($videos) ?></small></div>
        </div>
    </div>

    <div class="content">
        <div class="section-header">
            <h2>影片列表</h2>
        </div>
        <table class="video-table">
            <thead>
                <tr>
                    <th>影片</th>
                    <th>類型</th>
                    <th>觀看</th>
                    <th>按讚</th>
                    <th>AI狀態</th>
                    <th>公開</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($videos as $video): ?>
                <tr>
                    <td>
                        <div class="video-info">
                            <?php
                            $thumb = $video['thumbnail_url'] ?: getYoutubeThumbnail($video['video_url']);
                            if ($video['video_type'] === 'upload' && empty($thumb)) $thumb = '';
                            ?>
                            <img src="<?= h($thumb ?: '/feed/assets/img/no-image.svg') ?>" class="video-thumb" alt="">
                            <div>
                                <div class="video-title"><?= h($video['title']) ?></div>
                                <div class="video-meta">
                                    <?= formatDate($video['created_at']) ?>
                                    <?php if ($video['video_type'] === 'upload' && !empty($video['video_file_path'])): ?>
                                    &middot; <?= h(basename($video['video_file_path'])) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if ($video['video_type'] === 'upload'): ?>
                            <span class="badge badge-purple">上傳</span>
                        <?php else: ?>
                            <span class="badge badge-gray"><?= h($video['video_type'] ?: 'youtube') ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= formatNumber($video['view_count']) ?></td>
                    <td><?= formatNumber($video['like_count']) ?></td>
                    <td>
                        <div class="ai-status">
                            <?php if (!empty($video['title_variant_a'])): ?>
                                <span class="badge badge-green">A/B</span>
                            <?php endif; ?>
                            <?php if (!empty($video['ai_seo_article'])): ?>
                                <span class="badge badge-green">SEO</span>
                            <?php endif; ?>
                            <?php if (empty($video['title_variant_a']) && empty($video['ai_seo_article'])): ?>
                                <span class="badge badge-gray">未生成</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php if ($video['is_published']): ?>
                            <span class="badge badge-green">公開</span>
                        <?php else: ?>
                            <span class="badge badge-gray">非公開</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="actions-cell">
                            <button class="btn btn-outline btn-sm" onclick="openAiModal(<?= $video['id'] ?>)">AI</button>
                            <button class="btn btn-outline btn-sm" onclick="openDetailModal(<?= $video['id'] ?>)">詳細</button>
                            <form method="POST" style="display:inline" onsubmit="return confirm('確定要刪除此影片嗎？')">
                                <input type="hidden" name="form_action" value="delete">
                                <input type="hidden" name="video_id" value="<?= $video['id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm" style="color:#e53e3e;border-color:#e53e3e;">刪除</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($videos)): ?>
                <tr><td colspan="7" style="text-align:center;padding:40px;color:#555;">尚無影片，點擊「+ 新增影片」開始</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Video Modal -->
    <div class="modal-overlay" id="addModal">
        <div class="modal">
            <div id="generatingOverlay" class="generating-overlay" style="display:none;">
                <div class="spinner" style="width:40px;height:40px;border-width:3px;"></div>
                <div class="generating-text">AI 正在自動生成中...</div>
                <div class="generating-sub">標題 A/B、說明文 A/B、SEO文章</div>
            </div>
            <div id="completionView" style="display:none;">
                <div class="modal-header">
                    <h2>新增影片</h2>
                    <button class="modal-close" onclick="closeAddModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="completion-box">
                        <div class="completion-icon">&#10003;</div>
                        <div class="completion-title">完成！</div>
                        <div class="completion-subtitle">影片的新增和AI生成已全部完成</div>
                        <div class="completion-items" id="completionItems"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="closeAddModal(); location.reload();">關閉</button>
                </div>
            </div>
            <div id="formView">
                <div class="modal-header">
                    <h2>新增影片</h2>
                    <button class="modal-close" onclick="closeAddModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-section">
                        <div class="form-section-title">
                            <span class="form-section-num">1</span>
                            <span class="form-section-label">上傳影片</span>
                        </div>
                        <div class="upload-area" id="uploadArea" onclick="document.getElementById('videoFile').click()">
                            <div class="upload-icon">&#8679;</div>
                            <div class="upload-text">點擊或拖曳影片檔案至此</div>
                            <div class="upload-hint">MP4, WebM, MOV（最大100MB）</div>
                            <div class="upload-progress" id="uploadProgress">
                                <div class="progress-bar"><div class="progress-fill" id="progressFill"></div></div>
                            </div>
                            <div class="upload-filename" id="uploadFilename"></div>
                            <div class="upload-thumb-preview" id="thumbPreview" style="display:none;">
                                <img id="thumbPreviewImg" src="" alt="縮圖預覽">
                                <div class="thumb-label">自動擷取縮圖</div>
                            </div>
                        </div>
                        <input type="file" id="videoFile" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov" style="display:none" onchange="handleFileSelect(this)">
                        
                        <div class="url-divider">或</div>
                        <input type="url" id="videoUrl" placeholder="影片URL（YouTube或直接連結）">
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">
                            <span class="form-section-num">2</span>
                            <span class="form-section-label">關聯商品（選填）</span>
                        </div>
                        <div class="product-search">
                            <input type="text" id="productSearch" placeholder="搜尋商品名稱..." oninput="filterProducts(this.value)">
                        </div>
                        <div class="product-list" id="productList">
                            <?php foreach ($products as $product): ?>
                            <label class="product-item" data-name="<?= h(strtolower($product['name'])) ?>">
                                <input type="checkbox" name="product_ids[]" value="<?= $product['id'] ?>">
                                <img src="<?= h(getProductImageUrl($product['image_file'] ?? null)) ?>" class="product-img" alt="">
                                <span class="product-name"><?= h($product['name']) ?></span>
                                <span class="product-price"><?= $product['price'] ? 'NT$' . number_format($product['price']) : '' ?></span>
                            </label>
                            <?php endforeach; ?>
                            <div class="product-no-results" id="productNoResults">找不到符合的商品</div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">
                            <span class="form-section-num">3</span>
                            <span class="form-section-label">公開設定</span>
                        </div>
                        <div class="toggle-row">
                            <label class="toggle">
                                <input type="checkbox" id="isPublished" checked>
                                <span class="toggle-slider"></span>
                            </label>
                            <span>公開</span>
                        </div>
                    </div>

                    <div class="ai-info">
                        <div class="ai-info-title">&#10024; 新增後AI將自動生成</div>
                        <div class="ai-info-text">標題（A/B 2種版本）、說明文（A/B 2種版本）、SEO文章將自動生成。無需手動輸入。</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline" onclick="closeAddModal()">取消</button>
                    <button class="btn btn-primary" id="submitBtn" onclick="submitVideo()">新增 + AI生成 &#10024;</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal-overlay" id="detailModal">
        <div class="modal">
            <div class="modal-header">
                <h2 id="detailTitle">影片詳細</h2>
                <button class="modal-close" onclick="closeDetailModal()">&times;</button>
            </div>
            <div class="modal-body" id="detailBody"></div>
        </div>
    </div>

    <!-- Hidden canvas for thumbnail generation -->
    <canvas id="thumbCanvas" style="display:none;"></canvas>

    <script>
    var videosData = <?= json_encode($videos, JSON_UNESCAPED_UNICODE) ?>;
    var apiBase = '/feed/api/';
    var uploadedFileData = null;
    var generatedThumbnail = null;

    // Product search filter
    function filterProducts(query) {
        var items = document.querySelectorAll('#productList .product-item');
        var q = query.toLowerCase().trim();
        var visibleCount = 0;
        for (var i = 0; i < items.length; i++) {
            var name = items[i].getAttribute('data-name') || '';
            if (!q || name.indexOf(q) !== -1) {
                items[i].classList.remove('hidden');
                visibleCount++;
            } else {
                items[i].classList.add('hidden');
            }
        }
        document.getElementById('productNoResults').style.display = (visibleCount === 0 && q) ? 'block' : 'none';
    }

    // Modal
    function openAddModal() {
        document.getElementById('addModal').classList.add('active');
        document.getElementById('formView').style.display = '';
        document.getElementById('completionView').style.display = 'none';
        document.getElementById('generatingOverlay').style.display = 'none';
        resetForm();
    }
    function closeAddModal() { document.getElementById('addModal').classList.remove('active'); }
    function closeDetailModal() { document.getElementById('detailModal').classList.remove('active'); }

    function openDetailModal(videoId) {
        var v = null;
        for (var i = 0; i < videosData.length; i++) {
            if (videosData[i].id == videoId) { v = videosData[i]; break; }
        }
        if (!v) return;
        document.getElementById('detailTitle').textContent = v.title || '影片詳細';
        var html = '';
        if (v.title_variant_a || v.title_variant_b) {
            html += '<h3 style="color:#fff;margin-bottom:12px;">A/B 測試版本</h3>';
            html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">';
            var variants = ['A','B'];
            for (var vi = 0; vi < variants.length; vi++) {
                var ab = variants[vi];
                var isActive = v.active_variant === ab;
                var title = ab === 'A' ? v.title_variant_a : v.title_variant_b;
                var desc = ab === 'A' ? v.desc_variant_a : v.desc_variant_b;
                var views = ab === 'A' ? (v.variant_a_views||0) : (v.variant_b_views||0);
                var clicks = ab === 'A' ? (v.variant_a_clicks||0) : (v.variant_b_clicks||0);
                html += '<div style="background:#111;border:1px solid '+(isActive?'#48bb78':'#333')+';border-radius:8px;padding:14px;">';
                html += '<div style="font-size:12px;color:'+(isActive?'#48bb78':'#888')+';margin-bottom:6px;">版本 '+ab+(isActive?' (使用中)':'')+'</div>';
                html += '<div style="font-size:14px;color:#fff;margin-bottom:6px;">'+esc(title||'-')+'</div>';
                html += '<div style="font-size:12px;color:#aaa;">'+esc(desc||'-')+'</div>';
                html += '<div style="font-size:11px;color:#666;margin-top:8px;">觀看: '+views+' | 點擊: '+clicks+'</div>';
                if (!isActive) html += '<button class="btn btn-sm btn-outline" style="margin-top:8px;" onclick="setVariant('+v.id+',\''+ab+'\')">切換至'+ab+'</button>';
                html += '</div>';
            }
            html += '</div>';
        }
        if (v.ai_seo_article) {
            html += '<h3 style="color:#fff;margin-bottom:8px;">SEO文章 ';
            html += v.seo_article_published ? '<span class="badge badge-green">已發布</span>' : '<span class="badge badge-gray">未發布</span>';
            html += '</h3>';
            html += '<div style="background:#111;border:1px solid #222;border-radius:8px;padding:14px;margin-bottom:12px;max-height:300px;overflow-y:auto;">';
            html += '<div style="font-size:13px;color:#ccc;white-space:pre-wrap;line-height:1.6;">'+esc(v.ai_seo_article)+'</div>';
            html += '</div>';
            html += '<button class="btn btn-sm '+(v.seo_article_published?'btn-outline':'btn-primary')+'" onclick="toggleSeo('+v.id+')">'+(v.seo_article_published?'取消發布':'發布SEO文章')+'</button>';
        }
        if (!v.title_variant_a && !v.ai_seo_article) {
            html += '<div style="text-align:center;padding:30px;color:#666;">尚未生成AI內容<br><button class="btn btn-primary" style="margin-top:12px;" onclick="closeDetailModal();openAiModal('+v.id+')">立即生成</button></div>';
        }
        document.getElementById('detailBody').innerHTML = html;
        document.getElementById('detailModal').classList.add('active');
    }

    function openAiModal(videoId) {
        if (!confirm('確定要為此影片生成AI內容嗎？（標題A/B、說明文A/B、SEO文章）')) return;
        showToast('AI生成開始中...', 'success');
        fetch(apiBase + '?action=ai_generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ video_id: videoId })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) { showToast('AI生成完成！', 'success'); setTimeout(function() { location.reload(); }, 1000); }
            else { showToast('AI生成失敗: ' + (data.error || '未知錯誤'), 'error'); }
        })
        .catch(function(err) { showToast('AI生成錯誤: ' + err.message, 'error'); });
    }

    // Upload
    var uploadArea = document.getElementById('uploadArea');
    uploadArea.addEventListener('dragover', function(e) { e.preventDefault(); uploadArea.classList.add('dragover'); });
    uploadArea.addEventListener('dragleave', function() { uploadArea.classList.remove('dragover'); });
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault(); uploadArea.classList.remove('dragover');
        if (e.dataTransfer.files.length) { document.getElementById('videoFile').files = e.dataTransfer.files; handleFileSelect(document.getElementById('videoFile')); }
    });

    function generateThumbnail(file, callback) {
        var video = document.createElement('video');
        video.preload = 'metadata';
        video.muted = true;
        video.playsInline = true;
        
        video.onloadeddata = function() {
            // Seek to 1 second or 10% of duration
            var seekTime = Math.min(1, video.duration * 0.1);
            video.currentTime = seekTime;
        };
        
        video.onseeked = function() {
            var canvas = document.getElementById('thumbCanvas');
            var ctx = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            canvas.toBlob(function(blob) {
                if (blob) {
                    callback(blob, canvas.toDataURL('image/jpeg', 0.85));
                } else {
                    callback(null, null);
                }
                URL.revokeObjectURL(video.src);
            }, 'image/jpeg', 0.85);
        };
        
        video.onerror = function() {
            callback(null, null);
            URL.revokeObjectURL(video.src);
        };
        
        video.src = URL.createObjectURL(file);
    }

    function handleFileSelect(input) {
        var file = input.files[0];
        if (!file) return;
        if (file.size > 100*1024*1024) { showToast('檔案大小超過100MB限制', 'error'); return; }
        var allowed = ['video/mp4','video/webm','video/quicktime'];
        if (allowed.indexOf(file.type) === -1) { showToast('不支援的檔案格式', 'error'); return; }
        
        // Generate thumbnail from video
        generateThumbnail(file, function(thumbBlob, thumbDataUrl) {
            if (thumbDataUrl) {
                generatedThumbnail = thumbBlob;
                document.getElementById('thumbPreview').style.display = 'block';
                document.getElementById('thumbPreviewImg').src = thumbDataUrl;
            }
        });
        
        var formData = new FormData();
        formData.append('video', file);
        document.getElementById('uploadProgress').style.display = 'block';
        document.getElementById('uploadFilename').textContent = '';
        document.getElementById('progressFill').style.width = '0%';
        uploadArea.classList.remove('has-file');
        uploadArea.style.borderColor = '#e53e3e';
        
        var xhr = new XMLHttpRequest();
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                var pct = (e.loaded/e.total*100);
                document.getElementById('progressFill').style.width = pct+'%';
            }
        });
        xhr.addEventListener('load', function() {
            try {
                var resp = JSON.parse(xhr.responseText);
                if (resp.success) {
                    uploadedFileData = resp.data;
                    document.getElementById('uploadFilename').innerHTML = '&#10003; ' + esc(resp.data.original_name);
                    uploadArea.classList.add('has-file');
                    uploadArea.style.borderColor = '#48bb78';
                    document.getElementById('uploadProgress').style.display = 'none';
                    showToast('影片上傳成功', 'success');
                    
                    // Upload thumbnail if generated
                    if (generatedThumbnail) {
                        uploadThumbnail(generatedThumbnail, resp.data.filename);
                    }
                } else {
                    showToast('上傳失敗: ' + resp.error, 'error');
                    uploadArea.classList.remove('has-file');
                    uploadArea.style.borderColor = '#e53e3e';
                }
            } catch(e) {
                showToast('上傳回應解析錯誤', 'error');
                uploadArea.classList.remove('has-file');
                uploadArea.style.borderColor = '#e53e3e';
            }
        });
        xhr.addEventListener('error', function() {
            showToast('上傳網路錯誤', 'error');
            uploadArea.classList.remove('has-file');
            uploadArea.style.borderColor = '#e53e3e';
        });
        xhr.open('POST', apiBase + '?action=upload');
        xhr.send(formData);
    }

    function uploadThumbnail(blob, videoFilename) {
        var formData = new FormData();
        var thumbName = videoFilename.replace(/\.[^.]+$/, '') + '_thumb.jpg';
        formData.append('thumbnail', blob, thumbName);
        
        var xhr = new XMLHttpRequest();
        xhr.addEventListener('load', function() {
            try {
                var resp = JSON.parse(xhr.responseText);
                if (resp.success && uploadedFileData) {
                    uploadedFileData.thumbnail_url = resp.data.url;
                }
            } catch(e) {}
        });
        xhr.open('POST', apiBase + '?action=upload_thumbnail');
        xhr.send(formData);
    }

    // Submit
    function submitVideo() {
        var videoUrl = document.getElementById('videoUrl').value.trim();
        if (!uploadedFileData && !videoUrl) { showToast('請上傳影片檔案或輸入影片URL', 'error'); return; }
        
        var videoType, finalUrl, filePath = '', thumbnailUrl = '';
        if (uploadedFileData) {
            videoType = 'upload';
            finalUrl = uploadedFileData.url;
            filePath = uploadedFileData.filepath;
            thumbnailUrl = uploadedFileData.thumbnail_url || '';
        } else {
            videoType = (videoUrl.indexOf('youtube') !== -1 || videoUrl.indexOf('youtu.be') !== -1) ? 'youtube' : 'direct';
            finalUrl = videoUrl;
        }
        
        var productIds = [];
        var checkboxes = document.querySelectorAll('#productList input[type="checkbox"]:checked');
        for (var i = 0; i < checkboxes.length; i++) {
            productIds.push(parseInt(checkboxes[i].value));
        }
        var isPublished = document.getElementById('isPublished').checked ? 1 : 0;
        
        document.getElementById('submitBtn').disabled = true;
        document.getElementById('submitBtn').innerHTML = '<span class="spinner"></span> 處理中...';
        
        // Create via API
        fetch(apiBase + '?action=admin_create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                title: uploadedFileData ? uploadedFileData.original_name : '新影片',
                description: '',
                video_url: finalUrl,
                video_file_path: filePath,
                video_type: videoType,
                thumbnail_url: thumbnailUrl,
                is_published: isPublished,
                product_ids: productIds
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('formView').style.display = 'none';
                document.getElementById('generatingOverlay').style.display = 'flex';
                return fetch(apiBase + '?action=ai_generate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ video_id: data.video_id })
                }).then(function(r) { return r.json(); });
            } else { throw new Error(data.error || '建立失敗'); }
        })
        .then(function(aiData) {
            document.getElementById('generatingOverlay').style.display = 'none';
            document.getElementById('completionView').style.display = '';
            var items = '';
            if (aiData && aiData.success) {
                items += '<div class="completion-item"><span>&#10003;</span> 標題 A/B 2種版本生成完成</div>';
                items += '<div class="completion-item"><span>&#10003;</span> 說明文 A/B 2種版本生成完成</div>';
                items += '<div class="completion-item"><span>&#10003;</span> SEO文章生成完成</div>';
            } else {
                items += '<div class="completion-item" style="color:#e53e3e;"><span>&#10007;</span> AI生成失敗（影片已新增，可稍後重試）</div>';
            }
            document.getElementById('completionItems').innerHTML = items;
        })
        .catch(function(err) {
            document.getElementById('generatingOverlay').style.display = 'none';
            document.getElementById('formView').style.display = '';
            document.getElementById('submitBtn').disabled = false;
            document.getElementById('submitBtn').innerHTML = '新增 + AI生成 &#10024;';
            showToast('錯誤: ' + err.message, 'error');
        });
    }

    // API
    function setVariant(videoId, variant) {
        fetch(apiBase + '?action=set_variant', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({video_id:videoId,variant:variant}) })
        .then(function(r) { return r.json(); }).then(function(d) { if(d.success){showToast('已切換至版本 '+variant,'success');setTimeout(function(){location.reload();},800);} });
    }
    function toggleSeo(videoId) {
        fetch(apiBase + '?action=toggle_seo', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({video_id:videoId}) })
        .then(function(r) { return r.json(); }).then(function(d) { if(d.success){showToast('SEO文章狀態已更新','success');setTimeout(function(){location.reload();},800);} });
    }

    // Utils
    function resetForm() {
        uploadedFileData = null;
        generatedThumbnail = null;
        document.getElementById('videoUrl').value = '';
        document.getElementById('videoFile').value = '';
        document.getElementById('isPublished').checked = true;
        document.getElementById('productSearch').value = '';
        uploadArea.classList.remove('has-file');
        uploadArea.style.borderColor = '';
        document.getElementById('uploadProgress').style.display = 'none';
        document.getElementById('uploadFilename').textContent = '';
        document.getElementById('progressFill').style.width = '0%';
        document.getElementById('thumbPreview').style.display = 'none';
        document.getElementById('thumbPreviewImg').src = '';
        var checkboxes = document.querySelectorAll('#productList input[type="checkbox"]');
        for (var i = 0; i < checkboxes.length; i++) { checkboxes[i].checked = false; }
        filterProducts('');
        document.getElementById('submitBtn').disabled = false;
        document.getElementById('submitBtn').innerHTML = '新增 + AI生成 &#10024;';
    }
    function esc(s) { if(!s) return ''; var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
    function showToast(msg, type) {
        var t = document.createElement('div'); t.className = 'toast toast-'+type; t.textContent = msg;
        document.body.appendChild(t); setTimeout(function() { t.remove(); }, 3000);
    }
    </script>
</body>
</html>
<?php
}
