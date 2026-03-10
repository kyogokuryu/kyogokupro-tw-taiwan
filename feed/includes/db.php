<?php
/**
 * Database Connection Class
 */

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            die(json_encode(['error' => 'Database connection failed']));
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo() {
        return $this->pdo;
    }

    /**
     * Get published videos with pagination
     */
    public function getVideos($page = 1, $limit = VIDEOS_PER_PAGE) {
        $offset = ($page - 1) * $limit;
        $stmt = $this->pdo->prepare('
            SELECT v.*, 
                   GROUP_CONCAT(DISTINCT vp.product_id) as product_ids
            FROM feed_videos v
            LEFT JOIN feed_video_products vp ON v.id = vp.video_id
            WHERE v.is_published = 1
            GROUP BY v.id
            ORDER BY v.sort_order ASC, v.created_at DESC
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get random published videos, excluding specified IDs
     */
    public function getRandomVideos($limit = 10, $excludeIds = []) {
        $where = 'WHERE v.is_published = 1';
        $params = [];
        if (!empty($excludeIds)) {
            $placeholders = [];
            foreach ($excludeIds as $i => $id) {
                $key = ':ex' . $i;
                $placeholders[] = $key;
                $params[$key] = intval($id);
            }
            $where .= ' AND v.id NOT IN (' . implode(',', $placeholders) . ')';
        }
        $stmt = $this->pdo->prepare('
            SELECT v.*, 
                   GROUP_CONCAT(DISTINCT vp.product_id) as product_ids
            FROM feed_videos v
            LEFT JOIN feed_video_products vp ON v.id = vp.video_id
            ' . $where . '
            GROUP BY v.id
            ORDER BY RAND()
            LIMIT :limit
        ');
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get total published video count
     */
    public function getVideoCount() {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM feed_videos WHERE is_published = 1');
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get single video by ID
     */
    public function getVideo($id) {
        $stmt = $this->pdo->prepare('SELECT * FROM feed_videos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Get products linked to a video
     */
    public function getVideoProducts($videoId) {
        $stmt = $this->pdo->prepare('
            SELECT p.id, p.name, p.description_list, 
                   (SELECT pi.file_name FROM dtb_product_image pi WHERE pi.product_id = p.id ORDER BY pi.sort_no ASC LIMIT 1) as image_file,
                   (SELECT pc.price02 FROM dtb_product_class pc WHERE pc.product_id = p.id LIMIT 1) as price
            FROM feed_video_products vp
            JOIN dtb_product p ON vp.product_id = p.id
            WHERE vp.video_id = :video_id
            GROUP BY p.id
            ORDER BY vp.sort_order ASC
        ');
        $stmt->execute([':video_id' => $videoId]);
        return $stmt->fetchAll();
    }

    /**
     * Get videos linked to a specific product
     */
    public function getVideosByProductId($productId) {
        $stmt = $this->pdo->prepare('
            SELECT v.* FROM feed_videos v
            JOIN feed_video_products vp ON v.id = vp.video_id
            WHERE vp.product_id = :product_id AND v.is_published = 1
            ORDER BY v.created_at DESC
        ');
        $stmt->execute([':product_id' => $productId]);
        return $stmt->fetchAll();
    }

    /**
     * Increment view count
     */
    public function incrementViewCount($videoId) {
        $stmt = $this->pdo->prepare('UPDATE feed_videos SET view_count = view_count + 1 WHERE id = :id');
        $stmt->execute([':id' => $videoId]);
    }

    /**
     * Toggle like
     */
    public function toggleLike($videoId, $ip) {
        $stmt = $this->pdo->prepare('
            SELECT id FROM feed_video_analytics 
            WHERE video_id = :vid AND action_type = "like" AND ip_address = :ip
        ');
        $stmt->execute([':vid' => $videoId, ':ip' => $ip]);
        
        if ($stmt->fetch()) {
            $stmt = $this->pdo->prepare('
                DELETE FROM feed_video_analytics 
                WHERE video_id = :vid AND action_type = "like" AND ip_address = :ip
            ');
            $stmt->execute([':vid' => $videoId, ':ip' => $ip]);
            $this->pdo->prepare('UPDATE feed_videos SET like_count = GREATEST(like_count - 1, 0) WHERE id = :id')
                ->execute([':id' => $videoId]);
            return false;
        } else {
            $stmt = $this->pdo->prepare('
                INSERT INTO feed_video_analytics (video_id, action_type, ip_address, user_agent)
                VALUES (:vid, "like", :ip, :ua)
            ');
            $stmt->execute([
                ':vid' => $videoId,
                ':ip' => $ip,
                ':ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)
            ]);
            $this->pdo->prepare('UPDATE feed_videos SET like_count = like_count + 1 WHERE id = :id')
                ->execute([':id' => $videoId]);
            return true;
        }
    }

    /**
     * Record analytics event
     */
    public function recordAnalytics($videoId, $actionType) {
        $stmt = $this->pdo->prepare('
            INSERT INTO feed_video_analytics (video_id, action_type, ip_address, user_agent)
            VALUES (:vid, :action, :ip, :ua)
        ');
        $stmt->execute([
            ':vid' => $videoId,
            ':action' => $actionType,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ':ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)
        ]);
    }

    /**
     * Check if IP has liked a video
     */
    public function hasLiked($videoId, $ip) {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) FROM feed_video_analytics 
            WHERE video_id = :vid AND action_type = "like" AND ip_address = :ip
        ');
        $stmt->execute([':vid' => $videoId, ':ip' => $ip]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ===== Admin Methods =====

    /**
     * Get all videos for admin
     */
    public function getAllVideos() {
        $stmt = $this->pdo->query('
            SELECT v.*, 
                   COUNT(DISTINCT vp.id) as product_count,
                   COUNT(DISTINCT va.id) as analytics_count
            FROM feed_videos v
            LEFT JOIN feed_video_products vp ON v.id = vp.video_id
            LEFT JOIN feed_video_analytics va ON v.id = va.video_id
            GROUP BY v.id
            ORDER BY v.sort_order ASC, v.created_at DESC
        ');
        return $stmt->fetchAll();
    }

    /**
     * Create video (with upload support)
     */
    public function createVideo($data) {
        $stmt = $this->pdo->prepare('
            INSERT INTO feed_videos (title, description, video_url, video_file_path, video_type, thumbnail_url, duration, sort_order, is_published, tags)
            VALUES (:title, :desc, :url, :file_path, :type, :thumb, :dur, :sort, :pub, :tags)
        ');
        $stmt->execute([
            ':title' => $data['title'],
            ':desc' => $data['description'] ?? '',
            ':url' => $data['video_url'],
            ':file_path' => $data['video_file_path'] ?? null,
            ':type' => $data['video_type'] ?? 'youtube',
            ':thumb' => $data['thumbnail_url'] ?? null,
            ':dur' => $data['duration'] ?? null,
            ':sort' => $data['sort_order'] ?? 0,
            ':pub' => $data['is_published'] ?? 1,
            ':tags' => $data['tags'] ?? null,
        ]);
        return $this->pdo->lastInsertId();
    }

    /**
     * Update video
     */
    public function updateVideo($id, $data) {
        $stmt = $this->pdo->prepare('
            UPDATE feed_videos SET
                title = :title,
                description = :desc,
                video_url = :url,
                video_file_path = :file_path,
                video_type = :type,
                thumbnail_url = :thumb,
                duration = :dur,
                sort_order = :sort,
                is_published = :pub,
                tags = :tags
            WHERE id = :id
        ');
        $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'],
            ':desc' => $data['description'] ?? '',
            ':url' => $data['video_url'],
            ':file_path' => $data['video_file_path'] ?? null,
            ':type' => $data['video_type'] ?? 'youtube',
            ':thumb' => $data['thumbnail_url'] ?? null,
            ':dur' => $data['duration'] ?? null,
            ':sort' => $data['sort_order'] ?? 0,
            ':pub' => $data['is_published'] ?? 1,
            ':tags' => $data['tags'] ?? null,
        ]);
    }

    /**
     * Update AI generated content
     */
    public function updateVideoAiContent($id, $data) {
        $stmt = $this->pdo->prepare('
            UPDATE feed_videos SET
                ai_generated_title = :ai_title,
                ai_generated_description = :ai_desc,
                ai_seo_article = :seo_article,
                seo_article_published = :seo_pub,
                title_variant_a = :title_a,
                title_variant_b = :title_b,
                desc_variant_a = :desc_a,
                desc_variant_b = :desc_b,
                active_variant = :variant,
                title = :display_title,
                description = :display_desc
            WHERE id = :id
        ');
        $stmt->execute([
            ':id' => $id,
            ':ai_title' => $data['title_variant_a'] ?? null,
            ':ai_desc' => $data['desc_variant_a'] ?? null,
            ':seo_article' => $data['seo_article'] ?? null,
            ':seo_pub' => $data['seo_article_published'] ?? 1,
            ':title_a' => $data['title_variant_a'] ?? null,
            ':title_b' => $data['title_variant_b'] ?? null,
            ':desc_a' => $data['desc_variant_a'] ?? null,
            ':desc_b' => $data['desc_variant_b'] ?? null,
            ':variant' => 'A',
            ':display_title' => $data['title_variant_a'] ?? '',
            ':display_desc' => $data['desc_variant_a'] ?? '',
        ]);
    }

    /**
     * Set active A/B variant
     */
    public function setActiveVariant($id, $variant) {
        $video = $this->getVideo($id);
        if (!$video) return;

        $title = $variant === 'A' ? $video['title_variant_a'] : $video['title_variant_b'];
        $desc = $variant === 'A' ? $video['desc_variant_a'] : $video['desc_variant_b'];

        $stmt = $this->pdo->prepare('
            UPDATE feed_videos SET 
                active_variant = :variant,
                title = :title,
                description = :desc
            WHERE id = :id
        ');
        $stmt->execute([
            ':id' => $id,
            ':variant' => $variant,
            ':title' => $title ?: $video['title'],
            ':desc' => $desc ?: $video['description'],
        ]);
    }

    /**
     * Toggle SEO article published
     */
    public function toggleSeoArticle($id) {
        $stmt = $this->pdo->prepare('
            UPDATE feed_videos SET seo_article_published = NOT seo_article_published WHERE id = :id
        ');
        $stmt->execute([':id' => $id]);
    }

    /**
     * Increment A/B variant views
     */
    public function incrementVariantView($videoId, $variant) {
        $col = $variant === 'B' ? 'variant_b_views' : 'variant_a_views';
        $stmt = $this->pdo->prepare("UPDATE feed_videos SET {$col} = {$col} + 1 WHERE id = :id");
        $stmt->execute([':id' => $videoId]);
    }

    /**
     * Increment A/B variant clicks
     */
    public function incrementVariantClick($videoId, $variant) {
        $col = $variant === 'B' ? 'variant_b_clicks' : 'variant_a_clicks';
        $stmt = $this->pdo->prepare("UPDATE feed_videos SET {$col} = {$col} + 1 WHERE id = :id");
        $stmt->execute([':id' => $videoId]);
    }

    /**
     * Delete video
     */
    public function deleteVideo($id) {
        // Get video to delete file if uploaded
        $video = $this->getVideo($id);
        if ($video && $video['video_file_path'] && file_exists($video['video_file_path'])) {
            unlink($video['video_file_path']);
        }
        $stmt = $this->pdo->prepare('DELETE FROM feed_videos WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /**
     * Set video products
     */
    public function setVideoProducts($videoId, $productIds) {
        $stmt = $this->pdo->prepare('DELETE FROM feed_video_products WHERE video_id = :vid');
        $stmt->execute([':vid' => $videoId]);
        
        if (!empty($productIds)) {
            $stmt = $this->pdo->prepare('
                INSERT INTO feed_video_products (video_id, product_id, sort_order)
                VALUES (:vid, :pid, :sort)
            ');
            foreach ($productIds as $i => $pid) {
                $stmt->execute([':vid' => $videoId, ':pid' => $pid, ':sort' => $i]);
            }
        }
    }

    /**
     * Get product IDs linked to a video
     */
    public function getVideoProductIds($videoId) {
        $stmt = $this->pdo->prepare('SELECT product_id FROM feed_video_products WHERE video_id = :vid ORDER BY sort_order');
        $stmt->execute([':vid' => $videoId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get all EC-CUBE products for admin
     */
    public function getEcCubeProducts() {
        $stmt = $this->pdo->query('
            SELECT p.id, p.name,
                   (SELECT pi.file_name FROM dtb_product_image pi WHERE pi.product_id = p.id ORDER BY pi.sort_no ASC LIMIT 1) as image_file,
                   (SELECT pc.price02 FROM dtb_product_class pc WHERE pc.product_id = p.id LIMIT 1) as price
            FROM dtb_product p 
            WHERE p.product_status_id = 1
            ORDER BY p.id DESC
        ');
        return $stmt->fetchAll();
    }

    // ===== Comment Methods =====

    public function getComments($videoId, $limit = 50) {
        $stmt = $this->pdo->prepare('
            SELECT * FROM feed_comments
            WHERE video_id = :vid
            ORDER BY created_at DESC
            LIMIT :limit
        ');
        $stmt->bindValue(':vid', $videoId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCommentCount($videoId) {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM feed_comments WHERE video_id = :vid');
        $stmt->execute([':vid' => $videoId]);
        return (int) $stmt->fetchColumn();
    }

    public function addComment($videoId, $nickname, $text, $ip) {
        $stmt = $this->pdo->prepare('
            INSERT INTO feed_comments (video_id, nickname, comment_text, ip_address)
            VALUES (:vid, :nick, :text, :ip)
        ');
        $stmt->execute([
            ':vid' => $videoId,
            ':nick' => $nickname ?: '訪客',
            ':text' => $text,
            ':ip' => $ip
        ]);
        return $this->pdo->lastInsertId();
    }

    // ===== Bookmark Methods =====

    public function toggleBookmark($videoId, $ip) {
        $stmt = $this->pdo->prepare('SELECT id FROM feed_bookmarks WHERE video_id = :vid AND ip_address = :ip');
        $stmt->execute([':vid' => $videoId, ':ip' => $ip]);
        if ($stmt->fetch()) {
            $this->pdo->prepare('DELETE FROM feed_bookmarks WHERE video_id = :vid AND ip_address = :ip')
                ->execute([':vid' => $videoId, ':ip' => $ip]);
            return false;
        } else {
            $this->pdo->prepare('INSERT INTO feed_bookmarks (video_id, ip_address) VALUES (:vid, :ip)')
                ->execute([':vid' => $videoId, ':ip' => $ip]);
            return true;
        }
    }

    public function hasBookmarked($videoId, $ip) {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM feed_bookmarks WHERE video_id = :vid AND ip_address = :ip');
        $stmt->execute([':vid' => $videoId, ':ip' => $ip]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function getBookmarkCount($videoId) {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM feed_bookmarks WHERE video_id = :vid');
        $stmt->execute([':vid' => $videoId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get analytics summary
     */
    public function getAnalyticsSummary($days = 30) {
        $stmt = $this->pdo->prepare('
            SELECT 
                action_type,
                COUNT(*) as count,
                COUNT(DISTINCT ip_address) as unique_users
            FROM feed_video_analytics
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY action_type
        ');
        $stmt->execute([':days' => $days]);
        return $stmt->fetchAll();
    }
}
