<?php
/**
 * Deploy Update Script - Fetches latest files from GitHub
 * Access: /feed/deploy_update.php?key=Kyogoku2026Deploy!
 * DELETE THIS FILE AFTER USE
 */

$DEPLOY_KEY = 'Kyogoku2026Deploy!';
if (($_GET['key'] ?? '') !== $DEPLOY_KEY) {
    http_response_code(403);
    die('Forbidden');
}

$GITHUB_RAW_BASE = 'https://raw.githubusercontent.com/kyogokuryu/kyogokupro-tw-taiwan/main/feed/';

$files = [
    'admin/index.php' => __DIR__ . '/admin/index.php',
    'includes/config.php' => __DIR__ . '/includes/config.php',
];

header('Content-Type: text/plain; charset=utf-8');

// Get GitHub token from environment or use public repo
$results = [];

foreach ($files as $remote => $local) {
    $url = $GITHUB_RAW_BASE . $remote;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'KyogokuDeploy/1.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $content) {
        // Backup existing file
        if (file_exists($local)) {
            copy($local, $local . '.bak');
        }
        
        // Ensure directory exists
        $dir = dirname($local);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Write new file
        $bytes = file_put_contents($local, $content);
        $results[] = "OK: {$remote} -> {$local} ({$bytes} bytes)";
    } else {
        $results[] = "FAIL: {$remote} (HTTP {$httpCode})";
    }
}

echo "Deploy Results:\n";
echo implode("\n", $results) . "\n";
echo "\nDone. DELETE THIS FILE NOW!";
