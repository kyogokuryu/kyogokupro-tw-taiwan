<?php
/**
 * Video Sitemap Generator
 * tw.kyogokupro.com/feed/sitemap.xml
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

header('Content-Type: application/xml; charset=utf-8');

$db = Database::getInstance();
$videos = $db->getVideos(1, 1000); // Get all published videos

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">
    <url>
        <loc><?php echo h(FEED_URL); ?>/</loc>
        <lastmod><?php echo date('Y-m-d'); ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
<?php foreach ($videos as $video): ?>
        <video:video>
            <video:thumbnail_loc><?php echo h($video['thumbnail_url'] ?: getYoutubeThumbnail($video['video_url'], 'hqdefault')); ?></video:thumbnail_loc>
            <video:title><?php echo h($video['title']); ?></video:title>
            <video:description><?php echo h($video['description'] ?: $video['title']); ?></video:description>
            <video:content_loc><?php echo h($video['video_url']); ?></video:content_loc>
            <video:player_loc><?php echo h(getYoutubeEmbedUrl($video['video_url'])); ?></video:player_loc>
<?php if ($video['duration']): ?>
            <video:duration><?php echo intval($video['duration']); ?></video:duration>
<?php endif; ?>
            <video:publication_date><?php echo date('c', strtotime($video['created_at'])); ?></video:publication_date>
            <video:family_friendly>yes</video:family_friendly>
            <video:live>no</video:live>
<?php if ($video['tags']): ?>
<?php foreach (explode(',', $video['tags']) as $tag): ?>
            <video:tag><?php echo h(trim($tag)); ?></video:tag>
<?php endforeach; ?>
<?php endif; ?>
            <video:view_count><?php echo intval($video['view_count']); ?></video:view_count>
        </video:video>
<?php endforeach; ?>
    </url>
</urlset>
