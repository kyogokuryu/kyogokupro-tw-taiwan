<?php
namespace Customize\Twig\Extension;

use Doctrine\ORM\EntityManagerInterface;//
use Eccube\Repository\ProductRepository;//
use Eccube\Repository\OrderRepository;//
use Plugin\EccubePaymentLite4\Repository\RegularDiscountRepository;//
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Eccube\Entity\OrderItem;
use Customize\Entity\CLog;

class EccubeExtension extends AbstractExtension
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    private $regularDiscountRepository;

    private $orderRepository;

    /**
     * FsBuyTogether constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ProductRepository $productRepository
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ProductRepository $productRepository,
        RegularDiscountRepository $regularDiscountRepository,
        OrderRepository $orderRepository
    ) {
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
        $this->regularDiscountRepository = $regularDiscountRepository;
        $this->orderRepository = $orderRepository;
   }

    public function getFunctions()
    {
        return [
            new TwigFunction('get_instadata', [$this, 'get_instadata']),
            new TwigFunction('get_youtube', [$this, 'get_youtube']),
            new TwigFunction('get_recipelist', [$this, 'get_recipelist']),
            new TwigFunction('get_toprecipelist', [$this, 'get_toprecipelist']),
            new TwigFunction('get_newslist', [$this, 'get_newslist']),
            new TwigFunction('get_newsinfo', [$this, 'get_newsinfo']),
            new TwigFunction('get_reviewlist', [$this, 'get_reviewlist']),
            new TwigFunction('get_artistlist', [$this, 'get_artistlist']),
            new TwigFunction('get_artistinfo', [$this, 'get_artistinfo']),
            new TwigFunction('get_favorite_total', [$this, 'get_favorite_total']),//商品のお気に入り数合計を取得 20211127 kikuzawa
            new TwigFunction('get_random_products', [$this, 'get_random_products']),//ランダムに商品を取得 20220908 kikuzawa
            new TwigFunction('get_random_array', [$this, 'get_random_array']),//ランダムに配列 20220921 kikuzawa
            new TwigFunction('get_wp_feed',[$this, 'get_wp_feed']),
            new TwigFunction('get_wp_feed_slider',[$this, 'get_wp_feed_slider']),
            new TwigFunction('is_prime', [$this, 'is_prime']),
            new TwigFunction('is_family_product', [$this, 'is_family_product']),
            new TwigFunction('get_prime_product_id', [$this, 'get_prime_product_id']),
            new TwigFunction('get_regular_discount', [$this, 'get_regular_discount']),
            new TwigFunction('get_point', [$this, 'get_point']),
            new TwigFunction('is_link_product', [$this, 'is_link_product']),
            new TwigFunction('is_sp', [$this, 'is_sp']),
            new TwigFunction('get_wp_news',[$this, 'get_wp_news']),
            new TwigFunction('is_debug', [$this, 'is_debug']),
            new TwigFunction('get_order_count', [$this, 'get_order_count']),
            new TwigFunction('get_product_sell_count', [$this, 'get_product_sell_count']),
            new TwigFunction('is_urank_product', [$this, 'is_urank_product']),
            new TwigFunction('get_category_name', [$this, 'get_category_name']),
            new TwigFunction('get_favorite', [$this, 'get_favorite']),
            new TwigFunction('get_sale_rate_suplier', [$this, 'get_sale_rate_suplier']),
            new TwigFunction('parseLlmoValue', [$this, 'parseLlmoValue']),
            new TwigFunction('getClogCategories', [$this, 'getClogCategories']),
            new TwigFunction('getClogCategoryName', [$this, 'getClogCategoryName']),
        ];
    }

    function is_urank_product($Customer, $Product){
        $flg = 0;
        foreach($Product->getProductCategories() as $category){
            if($category->getCategoryId() == 48){
                $flg = true;
            }
        }
        if($flg && $Customer){
            if( $Customer->getOwnerRank() == 3){
                return 2;
            }else{
                return 1;
            }
        }
        return 0;
    }

    function is_sp(){
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";
        if((strpos($ua, 'iPhone') !== false) || (strpos($ua, 'iPod') !== false) || (strpos($ua, 'Android') !== false)){
            return true;
        }
        return false;
    }

    function get_instadata() {
        echo file_get_contents('/home/xs679489/kyogokupro.com/public_html/app/Customize/Twig/Extension/save_instadate.dat');
    }

    function get_youtube($displimit = 5, $category = '') {
        //----------------------------
        // Youtube RSS 取得
        //----------------------------
        // Educationトップ
        $url = 'https://www.youtube.com/feeds/videos.xml?playlist_id=PLeQhOCslRYu3kk26oDYT9F5OieA3ctjjp';
        // White → Kyogoku ホワイト系
        if ($category == 'white') {
            $url = 'https://www.youtube.com/feeds/videos.xml?playlist_id=PLeQhOCslRYu1wVWYaePWhv2CTWxJ5ONeL';
        } elseif ($category == 'greige') {
            // Greige → Kyogoku グレージュ系
            $url = 'https://www.youtube.com/feeds/videos.xml?playlist_id=PLeQhOCslRYu18Cp3ygs9gnST1WOwUUIkE';
        } elseif ($category == 'purple') {
            // Purple → Kyogoku パープル系
            $url = 'https://www.youtube.com/feeds/videos.xml?playlist_id=PLeQhOCslRYu1PRaH0IO7wjrcoEoPl1diR';
        } elseif ($category == 'blue') {
            // Blue → Kyogoku ブルー系
            $url = 'https://www.youtube.com/feeds/videos.xml?playlist_id=PLeQhOCslRYu3fHgCfi7KE85INGl6i3fo-';
        } elseif ($category == 'beige') {
            // Beige → Kyogoku ベージュ系
            $url = 'https://www.youtube.com/feeds/videos.xml?playlist_id=PLeQhOCslRYu3_b5JXbcHmQ4945LQ6HkOV';
        } elseif ($category == 'blonde') {
            // Blonde → Kyogoku ブロンド系
            $url = 'https://www.youtube.com/feeds/videos.xml?playlist_id=PLeQhOCslRYu1sQLK7YZUAFd8fdrRxvft1';
        } elseif ($category == 'milktea') {
            // MilkTea → Kyogoku ミルクティー系
            $url = 'https://www.youtube.com/feeds/videos.xml?playlist_id=PLeQhOCslRYu3zDqV8EPr1D1jWgRxFb9o8';
        } elseif ($category == 'mat') {
            // Mat → Kyogoku マット系
            $url = 'https://www.youtube.com/feeds/videos.xml?playlist_id=PLeQhOCslRYu1Bn7X82-AZ-RPjIUc32ij8';
        } elseif ($category == 'pink') {
            // Pink → Kyogoku ピンク系
            $url = 'https://www.youtube.com/feeds/videos.xml?playlist_id=PLeQhOCslRYu375P3QDIRJHG_PVqO8lbjS';
        } elseif ($category == 'silver') {
            // Silver → Kyogoku シルバー系
            $url = 'https://www.youtube.com/feeds/videos.xml?playlist_id=PLeQhOCslRYu3y7fk1YlJ_woHCmCova4dp';
        } elseif ($category == 'colour_shampoo') {
            // Colour Shampoo → Kyogoku カラーシャンプー
            $url = 'https://www.youtube.com/feeds/videos.xml?playlist_id=PLeQhOCslRYu0V7TXAe8E-O3Zz0pmJ7SCF';
        } else {
            $displimit = 5;
        }
        $youtube_xml = simplexml_load_file($url);
        if ($youtube_xml->entry) {
            $dispcnt = 0;
            foreach ($youtube_xml->entry as $content) {
                //dump($content);die;
                $id = $content->id;
                $id = str_replace('yt:video:','',$id);
                echo "<div><iframe src=\"https://www.youtube.com/embed/{$id}\" frameborder=\"0\" allow=\"accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe></div>";
                $dispcnt++;
                if ($dispcnt >= $displimit) {
                    break;
                }
            }
        }
    }

    function get_recipelist() {
        require('/home/xs679489/kyogokupro.com/public_html/recipemaster/block/get_recipelist.php');
        // require('/virtual/uver/public_html/kyogokutest/recipemaster/block/get_recipelist.php');
    }

    function get_toprecipelist() {
        require('/home/xs679489/kyogokupro.com/public_html/recipemaster/block/get_toprecipelist.php');
        // require('/virtual/uver/public_html/kyogokutest/recipemaster/block/get_toprecipelist.php');
    }

    function get_newslist($category = '', $page = '') {
        require('/home/xs679489/kyogokupro.com/public_html/recipemaster/block/get_newslist.php');
        // require('/virtual/uver/public_html/kyogokutest/recipemaster/block/get_newslist.php');
    }

    function get_newsinfo($targetno = '') {
        require('/home/xs679489/kyogokupro.com/public_html/recipemaster/block/get_newsinfo.php');
        // require('/virtual/uver/public_html/kyogokutest/recipemaster/block/get_newsinfo.php');
    }

    function get_reviewlist($page = '') {
        require('/home/xs679489/kyogokupro.com/public_html/recipemaster/block/get_reviewlist.php');
        // require('/virtual/uver/public_html/kyogokutest/recipemaster/block/get_reviewlist.php');
    }

    function get_artistlist() {
        require('/home/xs679489/kyogokupro.com/public_html/recipemaster/block/get_artistlist.php');
        // require('/virtual/uver/public_html/kyogokutest/recipemaster/block/get_artistlist.php');
    }

    function get_artistinfo($targetno = '') {
        require('/home/xs679489/kyogokupro.com/public_html/recipemaster/block/get_artistinfo.php');
        // require('/virtual/uver/public_html/kyogokutest/recipemaster/block/get_artistinfo.php');
    }

    function get_favorite_total() {
        if($_SERVER['REMOTE_ADDR'] == '60.100.205.99'){
            $qb = $this->entityManager->createQueryBuilder();
           //  $query = $qb->select("plob")
           //      ->from("Eccube\\Entity\\Master\\ProductListOrderBy", "plob")
           //      ->where('plob.id = :id')
           //      ->setParameter('id', $this->eccubeConfig['eccube_product_order_newer'])
           //      ->getQuery();
           //  $result = $query->getResult();

           // dump($result);
           exit();
        }
    }

    //ランダムに商品を取得 20220908 kikuzawa
    function get_random_products() {
        $cat_id = 40;//ランダム商品カテゴリー
        $qb = $this->entityManager->createQueryBuilder();
        $query = $qb->select("ctg")
            ->from("Eccube\\Entity\\Category", "ctg")
            ->where('ctg.id = :id')
            ->setParameter('id', $cat_id)
            ->getQuery();
        $searchData['category_id'] = $query->getOneOrNullResult();

        $qb = $this->productRepository->getQueryBuilderBySearchData($searchData);
        $query = $qb->setMaxResults(999)->getQuery();
        $temp_products = $query->getResult();
        shuffle($temp_products);
        $Products = array_slice($temp_products, 0, 30);//取得数

        return $Products;
    }

    //ランダムに配列 20220921 kikuzawa
    function get_random_array($array, $num) {
        if(is_array($array)){
            shuffle($array);
            if(count($array) >= $num){
                $array = array_slice($array, 0, $num);
            }
        }

        return $array;
    }

    function get_wp_news($cate=null){

        $param = $this->cache('EccubeExtension.get_wp_news', function(){
            $url = 'https://kyogokupro.com/wp-news.php';
            $html = file_get_contents($url); //"https://kyogokupro.com/note/special/verification/feed");
            return $html;            
        }, 60 * 60);
        return $param;
    }

    function get_wp_feed($cate=null){

        $param = $this->cache('EccubeExtension.get_wp_feed:' . strtr($cate, ["/"=>"_"]), function()use($cate){
        
            //https://kyogokupro.com/note/special/verification/feed
            //https://kyogokupro.com/note/special/kg/feed
            $url = $cate ? sprintf('https://kyogokupro.com/note/%s/feed', $cate) : 'https://kyogokupro.com/note/feed';
            $html = file_get_contents($url); //"https://kyogokupro.com/note/special/verification/feed");
            //$obj = simplexml_load_string($html, LIBXML_NOCDATA);
            $rss = simplexml_load_string($html,'SimpleXMLElement', LIBXML_NOCDATA);
            $item = $rss->channel->item[0];
            
            $title = $item->title;
            $link = $item->link;
            $desc = $item->description;

            $rss_html = simplexml_load_string("<html>".$desc."</html>");
                    //echo "<pre>";
            //var_dump(simplexml_load_string("<html>".$desc."</html>"));
            //var_dump($rss_html);
            //echo "</pre>";
            $src = "https://kyogokupro.com/images/logo.svg";
            if($rss_html->p[0] && $rss_html->p[0]->img){
                $src = $rss_html->p[0]->img->attributes()->src;
            }
            $summary = $rss_html->p[1];

            $param =  [
                "title"=>(String)$title,
                "src"=>(String)$src,
                "desc"=>(String)$desc,
                "link"=>(String)$link,
                "summary"=>(String)$summary,
            ];


            return $param;

        }, 60*60);

        return $param;
    }




    function get_wp_feed_slider(){


        $param = $this->cache('EccubeExtension.get_wp_feed_slider:2022:', function(){

            //https://kyogokupro.com/note/special/verification/feed
            //https://kyogokupro.com/note/special/kg/feed
            //$url = $cate ? sprintf('https://kyogokupro.com/note/%s/feed', $cate) : 'https://kyogokupro.com/note/feed';
            $url = 'https://kyogokupro.com/note/wp-feed.php';
            $html = file_get_contents($url); //"https://kyogokupro.com/note/special/verification/feed");
            //$html = strtr($html,['decoding="async" >'=>'decoding="async" />', '</noscript>'=>'</img></noscript>']);
            //$obj = simplexml_load_string($html, LIBXML_NOCDATA);

            return ["html" => $html];

        }, 60*60);


        return $param["html"];

//        return $param;
    }

    function is_prime(){

        $product_id = \Plugin\EccubePaymentLite4\Entity\Config::PRIME_PRODUCT_ID;

        $Product = $this->productRepository->find($product_id);

        iF($Product && $Product->getStatus()->getId() == 1){
            return true;
        }
        return false;
    }


    function is_family_product($RegularOrder){

        $product_id = \Plugin\EccubePaymentLite4\Entity\Config::PRIME_PRODUCT_ID;
        $lite_id = \Plugin\EccubePaymentLite4\Entity\Config::PRIME_LIGHT_RODUCT_ID;

        foreach($RegularOrder->getRegularShippings() as $RegularShipping){
            foreach($RegularShipping->getRegularProductOrderItems() as $regularProductOrderItem){
                $val = $regularProductOrderItem->getProduct()->getId();
                if($val == $product_id){
                    return true;
                }

                if($val == $lite_id){
                    return true;
                }
            }
        }
        return false;
    }


    function get_prime_product_id(){
        $product_id = \Plugin\EccubePaymentLite4\Entity\Config::PRIME_PRODUCT_ID;
        return $product_id;
    }

    function get_regular_discount($product_id){

        $Product = $this->productRepository->find($product_id);
        //向井修正　とりあえずオフ
        // $one = $Product->getProductClasses()[0]->getRegularDiscount();

        // if($one){
        //     $discount_rate = $one->getDiscountRate();
        // }else{
        //     $discount_rate = 0;
        // }
        // return $discount_rate;
        return 0;
    }

    function get_point($price, $prime=false,$urank=0){

        $alpha = 0;
        if($prime){
            //return number_format($price * 0.03);
            $alpha = 3;
        }else{
            //return number_format($price * 0.01);    
            //$alpha = 0.01;
        }

        if($urank == 0){
            $alpha = $alpha + 1;
        }elseif($urank == 1){
            $alpha = $alpha + 2;
        }elseif($urank == 2){
            $alpha = $alpha + 3;
        }elseif($urank == 3){
            $alpha = $alpha + 4;
        }

        return number_format( floor(strval($price * $alpha / 100)));
    }

    function is_link_product($Order){

        foreach ($Order->getProductOrderItems() as $OrderItem){
            $Product = $OrderItem->getProduct();
            foreach($Product->getProductCategories() as $pc) {
                if( in_array($pc->getCategory()->getId(), [8,9]) ){
                    return true;
                } 
            }
        }
        return false;
    }
    /**
     */
    function is_debug(){
        $env = isset($_SERVER['APP_ENV']) ? $_SERVER['APP_ENV'] : 'dev';
        $debug = isset($_SERVER['APP_DEBUG']) ? $_SERVER['APP_DEBUG'] : ('prod' !== $env);
        return $debug;
    }

    /**
     *
     *
     */
    protected function cache($name, $callback, $expire=600){
        $dir = realpath(__DIR__ . "/../../../../var/cache/prod/");
        $cache_file = $dir ."/" . $name . ".cache";
        if(file_exists($cache_file)){
            if(filemtime($cache_file) + $expire > time()){
                $enc_data = file_get_contents($cache_file);
                if($enc_data){
                    $data = unserialize($enc_data);
                    return $data;
                }
            }
        }
        $data = $callback();
        file_put_contents($cache_file, serialize($data));
        return $data;
    }

    /**
     *
     */
    function get_order_count($Customer=null){

//var_dump($Customer);
        if($Customer){
            $qb = $this->orderRepository->getQueryBuilderByCustomer($Customer);
            $Orders = $qb->getQuery()->getResult();

            return count($Orders);
        }
        return 0;
    }


    function get_product_sell_count($product_id){

        $param = $this->cache('EccubeExtension.get_product_sell_count:'.$product_id, function() use($product_id){

            $product = $this->productRepository->find($product_id);
            $init = $product->getViewData(3);
            if($init == null){ $init = 0; }

            $cnt = $init;
            $sql = "select count(*) as cnt from dtb_order_item where product_id = :product_id";
            $stmt = $this->entityManager->getConnection()->prepare($sql);
            $stmt->execute(["product_id"=>$product_id]);
            $result = $stmt->fetchAll();
            foreach($result as $row){
                $cnt += $row["cnt"];
            }
            //$count = $this->entityManager->getRepository(OrderItem::class)->findBy(['id' => $product_id])->count();
            if(100 < $cnt && $cnt < 10000){
                $base = floor($cnt / 100);
                return sprintf("%s+", number_format($base * 100));
            }elseif(10000 <= $cnt){
                $base = $cnt / 10000;
                return sprintf("%.1f万", $base);
            }elseif(100000 <= $cnt){
                $base = $cnt / 10000;
                return sprintf("%d万+", $base);
            }else{
                return 0;
            }

        }, 60*60);

        return $param;
    }

    function get_category_name($category_id) {
        $category = $this->entityManager->getRepository('Eccube\Entity\Category')->find($category_id);
        return $category->getName();
    }

    function get_favorite($Customer, $Product){
        $qb = $this->entityManager->createQueryBuilder();
        $query = $qb->select("cfp")
            ->from("Eccube\\Entity\\CustomerFavoriteProduct", "cfp")
            ->where('cfp.Customer = :Customer')
            ->andWhere('cfp.Product = :Product')
            ->setParameter('Customer', $Customer)
            ->setParameter('Product', $Product)
            ->getQuery();
        $result = $query->getResult();
        if(count($result) > 0){
            return true;
        }
        return false;
    }

    function get_sale_rate_suplier($productId) {
        $sale_rate = 0;
        $product = $this->productRepository->find($productId);
        if ($product->getProductClasses()[0]->getPrice01IncTax()) {
            $price = $product->getProductClasses()[0]->getPrice01IncTax();
            $regular_price = $product->getProductClasses()[0]->getPrice02IncTax();
            $sale_rate = ($price - $regular_price)/$price * 100;
        }   
        // dump($sale_rate);die;
        return $sale_rate;
    }

    function parseLlmoValue($value)
    {
        return array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $value)));
    }

    function getClogCategories(){
        return CLog::CLOG_CATEGORIES;
    }

    function getClogCategoryName($value){
        $cateArray = CLog::CLOG_CATEGORIES;
        foreach ($cateArray as $key => $cateValue) {
            if($value === $cateValue) return  $key;
        }
        return "";
    }
}
