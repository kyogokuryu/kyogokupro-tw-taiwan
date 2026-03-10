<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
use Eccube\Kernel;
use Symfony\Component\Debug\Debug;
use Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

// システム要件チェック
if (version_compare(PHP_VERSION, '7.1.3') < 0) {
    die('Your PHP installation is too old. EC-CUBE requires at least PHP 7.1.3. See the <a href="http://www.ec-cube.net/product/system.php" target="_blank">system requirements</a> page for more information.');
}

$autoload = __DIR__.'/vendor/autoload.php';

if (!file_exists($autoload) && !is_readable($autoload)) {
    die('Composer is not installed.');
}
require $autoload;

// The check is to ensure we don't use .env in production
if (!isset($_SERVER['APP_ENV'])) {
    if (!class_exists(Dotenv::class)) {
        throw new \RuntimeException('APP_ENV environment variable is not defined. You need to define environment variables for configuration or add "symfony/dotenv" as a Composer dependency to load variables from a .env file.');
    }

    if (file_exists(__DIR__.'/.env')) {
        (new Dotenv(__DIR__))->overload();

        if (strpos(getenv('DATABASE_URL'), 'sqlite') !== false && !extension_loaded('pdo_sqlite')) {
            (new Dotenv(__DIR__, '.env.install'))->overload();
        }
    } else {
        (new Dotenv(__DIR__, '.env.install'))->overload();
    }
}

$env = isset($_SERVER['APP_ENV']) ? $_SERVER['APP_ENV'] : 'dev';
$debug = isset($_SERVER['APP_DEBUG']) ? $_SERVER['APP_DEBUG'] : ('prod' !== $env);

if ($debug) {
    umask(0000);

    Debug::enable();
}

// アフィリエイト用
$abm = filter_input(INPUT_GET, "abm");
if($abm){
    if($debug){
        setcookie('afbcookie', $abm, time() + 776000, "/", "xs564860.xsrv.jp");
    }else{
        setcookie('afbcookie', $abm, time() + 776000, "/", "kyogokupro.com", true, true);
    }
}

$deny_file = __DIR__ . "/.denyip";
if(file_exists($deny_file)){
    $ip = $_SERVER['REMOTE_ADDR'];
    $deny_ips = file_get_contents(__DIR__ . "/.denyip");
    $deny_ip = explode("\n", $deny_ips);
    if(in_array($ip, $deny_ip)){
        header("HTTP/1.1 404 Not Found");
        exit;
    }
}

# とりあえずIPBlockは解除（2023-09-15）
if(false && in_array($_SERVER["REQUEST_URI"],[
    "/cart",
    "/mypage/",
    "/mypage/gmo_card_edit",
    "/mypage/eccube_payment_lite/credit_card",
    ])){
    $allow_file = __DIR__ . "/.allowip";
    if(file_exists($allow_file)){
        $block = new \Customize\Service\IpBlock($allow_file);
        if( $block->is_allow() == false){
            header("HTTP/1.1 404 Not Found");
            exit;
        }
    }
}
$deny_path = ["/&id1", "/&id1="];
foreach($deny_path as $dp){
    if(preg_match('/^'. preg_quote($dp,'/') . '.*/', $_SERVER["REQUEST_URI"])){
        header("HTTP/1.1 404 Not Found");
        exit;
    }
}



$trustedProxies = isset($_SERVER['TRUSTED_PROXIES']) ? $_SERVER['TRUSTED_PROXIES'] : false;
if ($trustedProxies) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
}

$trustedHosts = isset($_SERVER['TRUSTED_HOSTS']) ? $_SERVER['TRUSTED_HOSTS'] : false;
if ($trustedHosts) {
    Request::setTrustedHosts(explode(',', $trustedHosts));
}

$request = Request::createFromGlobals();

$maintenanceFile = env('ECCUBE_MAINTENANCE_FILE_PATH', __DIR__.'/.maintenance');

if (file_exists($maintenanceFile)) {
    $pathInfo = \rawurldecode($request->getPathInfo());
    $adminPath = env('ECCUBE_ADMIN_ROUTE', 'admin');
    $adminPath = '/'.\trim($adminPath, '/').'/';
    if (\strpos($pathInfo, $adminPath) !== 0) {
        $locale = env('ECCUBE_LOCALE');
        $templateCode = env('ECCUBE_TEMPLATE_CODE');
        $baseUrl = \htmlspecialchars(\rawurldecode($request->getBaseUrl()), ENT_QUOTES);

        header('HTTP/1.1 503 Service Temporarily Unavailable');
        require __DIR__.'/maintenance.php';
        return;
    }
}

$kernel = new Kernel($env, $debug);
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
