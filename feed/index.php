<?php
/**
 * Feed - TikTok-style Video Feed
 * tw.kyogokupro.com/feed/
 * EC-CUBEとは完全に独立した動画フィード
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$db = Database::getInstance();

// Get initial videos for SSR (SEO)
$videos = $db->getVideos(1);
$totalVideos = $db->getVideoCount();

// Generate JSON-LD for all videos
$jsonLdItems = [];
foreach ($videos as $video) {
    $jsonLdItems[] = generateVideoJsonLd($video);
}

// Page meta
$pageTitle = 'Kyogoku Professional 影片動態 | 台灣官方';
$pageDescription = 'Kyogoku Professional 台灣官方影片動態。觀看最新的美髮產品介紹、使用教學和造型技巧影片。';
$pageUrl = FEED_URL;
$ogImage = !empty($videos) ? (getYoutubeThumbnail($videos[0]['video_url'], 'maxresdefault') ?: '') : '';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0, user-scalable=no">
    <title><?php echo h($pageTitle); ?></title>
    <meta name="description" content="<?php echo h($pageDescription); ?>">
    <meta name="keywords" content="Kyogoku,京極,美髮,護髮,角蛋白,台灣,影片,教學">
    
    <!-- GEO Tags for Taiwan -->
    <meta name="geo.region" content="TW">
    <meta name="geo.placename" content="Taiwan">
    <meta name="content-language" content="zh-TW">
    <meta name="language" content="Chinese (Traditional)">
    
    <!-- hreflang -->
    <link rel="alternate" hreflang="zh-TW" href="<?php echo h($pageUrl); ?>">
    <link rel="alternate" hreflang="x-default" href="<?php echo h($pageUrl); ?>">
    
    <!-- Canonical -->
    <link rel="canonical" href="<?php echo h($pageUrl); ?>">
    
    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo h($pageTitle); ?>">
    <meta property="og:description" content="<?php echo h($pageDescription); ?>">
    <meta property="og:url" content="<?php echo h($pageUrl); ?>">
    <meta property="og:site_name" content="Kyogoku Professional Taiwan">
    <meta property="og:locale" content="zh_TW">
    <?php if ($ogImage): ?>
    <meta property="og:image" content="<?php echo h($ogImage); ?>">
    <?php endif; ?>
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo h($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo h($pageDescription); ?>">
    <?php if ($ogImage): ?>
    <meta name="twitter:image" content="<?php echo h($ogImage); ?>">
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/apple-touch-icon-152x152.png">
    
    <!-- Preconnect -->
    <link rel="preconnect" href="https://www.youtube.com">
    <link rel="preconnect" href="https://img.youtube.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://www.youtube.com">
    
    <!-- Styles -->
    <link rel="stylesheet" href="/feed/assets/css/feed.css?v=<?php echo time(); ?>">
    
    <!-- Theme Color -->
    <meta name="theme-color" content="#000000">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-capable" content="yes">
    
    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "CollectionPage",
        "name": "<?php echo h($pageTitle); ?>",
        "description": "<?php echo h($pageDescription); ?>",
        "url": "<?php echo h($pageUrl); ?>",
        "inLanguage": "zh-TW",
        "isPartOf": {
            "@type": "WebSite",
            "name": "Kyogoku Professional Taiwan",
            "url": "<?php echo h(SITE_URL); ?>"
        },
        "publisher": {
            "@type": "Organization",
            "name": "Kyogoku Professional",
            "url": "<?php echo h(SITE_URL); ?>"
        }
    }
    </script>
    <?php if (!empty($jsonLdItems)): ?>
    <script type="application/ld+json">
    <?php echo json_encode($jsonLdItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
    </script>
    <?php endif; ?>
</head>
<body>
    <!-- Header -->
    <header class="feed-header">
        <a href="/feed/" class="feed-logo">
            <span class="feed-logo-text">
                KYOGOKU
                <span class="feed-logo-sub">影片動態</span>
            </span>
        </a>
        <a href="/" class="feed-nav-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
            商品一覽
        </a>
    </header>

    <!-- Feed Container -->
    <div id="feedContainer" class="feed-container">
        <!-- Videos will be loaded here by JavaScript -->
        
        <!-- SSR fallback for SEO crawlers -->
        <noscript>
            <?php foreach ($videos as $video): ?>
            <article class="video-card" itemscope itemtype="https://schema.org/VideoObject">
                <meta itemprop="name" content="<?php echo h($video['title']); ?>">
                <meta itemprop="description" content="<?php echo h($video['description']); ?>">
                <meta itemprop="thumbnailUrl" content="<?php echo h(getYoutubeThumbnail($video['video_url'])); ?>">
                <meta itemprop="uploadDate" content="<?php echo date('c', strtotime($video['created_at'])); ?>">
                <meta itemprop="contentUrl" content="<?php echo h($video['video_url']); ?>">
                <div class="video-info">
                    <h2 class="video-title" itemprop="name"><?php echo h($video['title']); ?></h2>
                    <p class="video-description" itemprop="description"><?php echo h($video['description']); ?></p>
                    <a href="<?php echo h($video['video_url']); ?>" target="_blank" rel="noopener">觀看影片</a>
                </div>
            </article>
            <?php endforeach; ?>
        </noscript>
    </div>

    <!-- Scroll Indicator -->
    <div class="scroll-indicator">
        <svg viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
        <span class="scroll-indicator-text">向下滑動</span>
    </div>

    <!-- Share Modal -->
    <div id="shareModal" class="share-modal">
        <div class="share-modal-content">
            <div class="share-modal-title">分享影片</div>
            <div class="share-options">
                <button class="share-option" data-type="line">
                    <span class="share-option-icon line">
                        <svg viewBox="0 0 24 24"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>
                    </span>
                    <span class="share-option-label">LINE</span>
                </button>
                <button class="share-option" data-type="facebook">
                    <span class="share-option-icon facebook">
                        <svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </span>
                    <span class="share-option-label">Facebook</span>
                </button>
                <button class="share-option" data-type="twitter">
                    <span class="share-option-icon twitter">
                        <svg viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                    </span>
                    <span class="share-option-label">Twitter</span>
                </button>
                <button class="share-option" data-type="copy">
                    <span class="share-option-icon copy">
                        <svg viewBox="0 0 24 24"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
                    </span>
                    <span class="share-option-label">複製連結</span>
                </button>
            </div>
            <button class="share-modal-close">取消</button>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast" class="toast"></div>

    <!-- JavaScript -->
    <script src="/feed/assets/js/feed.js?v=<?php echo time(); ?>"></script>
</body>
</html>
