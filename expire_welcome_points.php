<?php
/**
 * 台湾EC-CUBE 新規登録500點數 4時間有効期限 自動失効スクリプト
 * 
 * 動作:
 * - 新規登録から4時間経過した顧客を検索
 * - 注文で500ポイントを使用していない場合、ポイントを差し引く
 * - dtb_point_logに失効記録を残す
 * 
 * cronで10分ごとに実行
 */

date_default_timezone_set('Asia/Taipei');

// DB接続設定
$dbHost = 'localhost';
$dbName = 'xs679489_taiwan';
$dbUser = 'xs679489_taiwan';
$dbPass = 'SZsnd7tDN7re';

$welcomePoints = 500;
$expireHours = 4;

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $now = date('Y-m-d H:i:s');
    $expireThreshold = date('Y-m-d H:i:s', strtotime("-{$expireHours} hours"));

    // 対象: 新規登録から4時間経過 AND ポイントが500以上 AND まだ失効処理されていない
    // 失効処理済みかどうかは dtb_point_log の memo に '限時點數到期' があるかで判定
    $sql = "
        SELECT c.id, c.point, c.create_date
        FROM dtb_customer c
        WHERE c.create_date <= :expire_threshold
          AND c.create_date >= :recent_limit
          AND c.point >= :welcome_points
          AND c.id NOT IN (
            SELECT DISTINCT customer_id FROM dtb_point_log WHERE memo = '限時500點數到期自動扣除'
          )
    ";

    // recent_limitは過去7日以内の登録のみ対象（古い顧客は対象外）
    $recentLimit = date('Y-m-d H:i:s', strtotime('-7 days'));

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':expire_threshold' => $expireThreshold,
        ':recent_limit' => $recentLimit,
        ':welcome_points' => $welcomePoints,
    ]);

    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($customers)) {
        echo "[{$now}] No customers to expire.\n";
        exit(0);
    }

    echo "[{$now}] Found " . count($customers) . " customers to expire welcome points.\n";

    foreach ($customers as $customer) {
        $customerId = $customer['id'];
        $currentPoint = $customer['point'];
        $newPoint = max(0, $currentPoint - $welcomePoints);

        $pdo->beginTransaction();
        try {
            // ポイントを差し引く
            $updateStmt = $pdo->prepare("UPDATE dtb_customer SET point = :new_point WHERE id = :id");
            $updateStmt->execute([':new_point' => $newPoint, ':id' => $customerId]);

            // ポイントログに記録
            $logStmt = $pdo->prepare("
                INSERT INTO dtb_point_log (customer_id, point1, point2, memo, create_date, update_date, discriminator_type)
                VALUES (:customer_id, :point1, :point2, '限時500點數到期自動扣除', :create_date, :update_date, 'pointlog')
            ");
            $logStmt->execute([
                ':customer_id' => $customerId,
                ':point1' => $currentPoint,
                ':point2' => $newPoint,
                ':create_date' => $now,
                ':update_date' => $now,
            ]);

            $pdo->commit();
            echo "[{$now}] Customer #{$customerId}: {$currentPoint} -> {$newPoint} (expired {$welcomePoints} welcome points)\n";
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "[{$now}] ERROR for customer #{$customerId}: " . $e->getMessage() . "\n";
        }
    }

    echo "[{$now}] Done.\n";

} catch (PDOException $e) {
    echo "[" . date('Y-m-d H:i:s') . "] DB Connection Error: " . $e->getMessage() . "\n";
    exit(1);
}
