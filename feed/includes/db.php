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
        // Check if already liked
        $stmt = $this->pdo->prepare('
            SELECT id FROM feed_video_analytics 
            WHERE video_id = :vid AND action_type = "like" AND ip_address = :ip
        ');
        $stmt->execute([':vid' => $videoId, ':ip' => $ip]);
        
        if ($stmt->fetch()) {
            // Unlike
            $stmt = $this->pdo->prepare('
                DELETE FROM feed_video_analytics 
                WHERE video_id = :vid AND action_type = "like" AND ip_address = :ip
            ');
            $stmt->execute([':vid' => $videoId, ':ip' => $ip]);
            $this->pdo->prepare('UPDATE feed_videos SET like_count = GREATEST(like_count - 1, 0) WHERE id = :id')
                ->execute([':id' => $videoId]);
            return false; // unliked
        } else {
            // Like
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
            return true; // liked
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
     * Create video
     */
    public function createVideo($data) {
        $stmt = $this->pdo->prepare('
            INSERT INTO feed_videos (title, description, video_url, video_type, thumbnail_url, duration, sort_order, is_published, tags)
            VALUES (:title, :desc, :url, :type, :thumb, :dur, :sort, :pub, :tags)
        ');
        $stmt->execute([
            ':title' => $data['title'],
            ':desc' => $data['description'] ?? '',
            ':url' => $data['video_url'],
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
            ':type' => $data['video_type'] ?? 'youtube',
            ':thumb' => $data['thumbnail_url'] ?? null,
            ':dur' => $data['duration'] ?? null,
            ':sort' => $data['sort_order'] ?? 0,
            ':pub' => $data['is_published'] ?? 1,
            ':tags' => $data['tags'] ?? null,
        ]);
    }

    /**
     * Delete video
     */
    public function deleteVideo($id) {
        $stmt = $this->pdo->prepare('DELETE FROM feed_videos WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /**
     * Set video products
     */
    public function setVideoProducts($videoId, $productIds) {
        // Remove existing
        $stmt = $this->pdo->prepare('DELETE FROM feed_video_products WHERE video_id = :vid');
        $stmt->execute([':vid' => $videoId]);
        
        // Add new
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
     * Get all EC-CUBE products for admin dropdown
     */
    public function getEcCubeProducts() {
        $stmt = $this->pdo->query('
            SELECT p.id, p.name 
            FROM dtb_product p 
            WHERE p.product_status_id = 1
            ORDER BY p.id DESC
        ');
        return $stmt->fetchAll();
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
