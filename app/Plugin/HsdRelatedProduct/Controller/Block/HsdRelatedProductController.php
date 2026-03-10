<?php

namespace Plugin\HsdRelatedProduct\Controller\Block;

use Eccube\Controller\AbstractController;
use Plugin\HsdRelatedProduct\Repository\ConfigRepository;
use Plugin\HsdRelatedProduct\Entity\HsdRelatedProduct;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class HsdRelatedProductController extends AbstractController
{
    //タイトル
    private $_title = 'この商品をみた人はこんな商品もみています';

    // 表示個数
    private $_show_count = 4; //初期値4

    // 関連商品用データ
    private $_rp = array();

    // 価格の表示/非表示
    private $_show_price = null;

    // 表示タイプ
    private $_show_type = 'normal'; //初期値 スライダーなし

    // ページネーション
    private $_pagination = 'true'; //初期値 あり

    // ナビゲーション
    private $_navbuttons = 'true'; //初期値 あり

    // 表示の自動ループ
    private $_showloop = 'true'; //初期値 あり

    // Anna用
    private $_save_from_id = 0;
    private $_save_to_id = 0;
    private $_save_from_product_name = '';
    private $_save_to_product_name = '';

    private $_def_max_row = 1000; //データの最大保持数の初期値

    /**
     * HsdRelatedProductController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(ConfigRepository $configRepository, RequestStack $requestStack)
    {
        $this->_configRepository = $configRepository;
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("block_hsd_related_product", name="block_hsd_related_product")
     * @Template("@Block/hsd_related_product.twig")
     */
    public function index(Request $request)
    {
        /*
         * 詳細画面の場合のみ動作
         * /products/detail/[product_id] を想定
         */
        $request_stack = $this->get('request_stack')->getMasterRequest();
        $chkpath = explode('/', $request_stack->getPathInfo());

        //セット商品プラグインの商品ページにも対応 20211123 kikuzawa
        if( ($chkpath[1] == 'products' && $chkpath[2] == 'detail') || ($chkpath[1] == 'products' && $chkpath[2] == 'select') ){

            if($chkpath[2] == 'detail'){
                $id = $chkpath[3];
            }
            else{
                $id = $chkpath[4];
            }

            $max_row = $this->_def_max_row; // 初期値1000

            // 保持する最大データ数を取得し、1回の削除数を設定（20%を設定）
            $setting = $this->_configRepository->get();
            if( $setting != null ){
                if( !empty($setting->getMaxNum()) && is_numeric($setting->getMaxNum()) ) {
                    $this->_show_count = $setting->getMaxNum();
                }

                if( !empty($setting->getMaxRowNum()) && is_numeric($setting->getMaxRowNum()) ) {
                    $max_row = $setting->getMaxRowNum();
                    $del_rows = intval($max_row * 0.2);
                }else{
                    $del_rows = 200;
                }

                if( !empty($setting->getTitle()) ) {
                    $this->_title = $setting->getTitle();
                }

                if( !empty($setting->getShowPrice()) ) {
                    $this->_show_price = $setting->getShowPrice();
                }

                if( !empty($setting->getShowType()) ) {
                    $this->_show_type = $setting->getShowType();
                }

                if( !empty($setting->getPagination()) ) {
                    $this->_pagination = $setting->getPagination();
                }

                if( !empty($setting->getNavbuttons()) ) {
                    $this->_navbuttons = $setting->getNavbuttons();
                }

                if( !empty($setting->getShowloop()) ) {
                    $this->_showloop = $setting->getShowloop();
                }
            }

            $em = $this->entityManager;
            $con_db_type = $em->getConnection()->getDatabasePlatform()->getName(); // postgresql or mysql

            // データ保持数に達していたら削除
            $stmt = $em->getConnection()->prepare(
                    'select count(rp.id) cn from plg_hsd_related_product rp'
                );
            $stmt->execute();
            $rs = $stmt->fetchAll();
            if($rs[0]['cn'] > $max_row){
                // 古いrowを削除
                if($rs[0]['cn'] > $max_row){
                    $del_rows = $rs[0]['cn'] - $max_row;
                }
                $stmt = $em->getConnection()->prepare('delete from plg_hsd_related_product order by updated_at asc limit ' . $del_rows);
                $stmt->execute();
            }

            /*
            * もしセッションにsave_pr_idが保持されていたら処理を行う
            */
            if( isset($_SESSION['ec_save_pr_id']) ) {
                $_from_id = $_SESSION['ec_save_pr_id'];

                // DB更新：もしfromとtoが異なる場合は保持
                if ($_from_id != $id) {
                    $rp_obj = new HsdRelatedProduct();
                    $rp_obj->setId(uniqid('rp_'))
                        ->setFromId($_from_id)
                        ->setToId($id)
                        ->setUpdatedAt(new \DateTime());

                    $em->persist($rp_obj);
                    $em->flush($rp_obj);

                    // Anna用
                    $this->_save_from_id = $_from_id;
                    $stmt_from = $em->getConnection()->prepare("select name from dtb_product where id = " . $_from_id);
                    $stmt_from->execute();
                    $rs_from = $stmt_from->fetchAll();
                    $this->_save_from_product_name = $rs_from[0]['name'];

                    $this->_save_to_id = $id;
                    $stmt_to = $em->getConnection()->prepare("select name from dtb_product where id = " . $id);
                    $stmt_to->execute();
                    $rs_to = $stmt_to->fetchAll();
                    $this->_save_to_product_name = $rs_to[0]['name'];

                }
            }

            // 現在の商品IDをもとに、次の商品IDを取得
            $stmt = $em->getConnection()->prepare("
                SELECT count(rp.to_id) cn, rp.to_id FROM plg_hsd_related_product rp, dtb_product as p WHERE rp.from_id='" . $id . "' AND rp.to_id = p.id AND p.product_status_id = 1 GROUP BY rp.from_id, rp.to_id ORDER BY cn DESC
                ");
            $stmt->execute();
            $rs = $stmt->fetchAll();

            // 関連商品自動表示ブロックの設定
            $or_str = '';
            foreach($rs as $item){
                $or_str .= '(ecp.id=' . $item['to_id'] . ' AND ecp.id = ecpi.product_id) or ';
            }
            $or_str = substr($or_str, 0, strlen($or_str)-4);
            if(strlen($or_str) > 1) {
                $sql = '';
                if( $con_db_type == 'postgresql' ){
                    $sql = 'SELECT ecp.id, ecp.name, ecp.description_detail, (select in_ecpi.file_name FROM dtb_product_image in_ecpi WHERE in_ecpi.product_id = ecp.id AND in_ecpi.sort_no = 1 ) file_name, (select MIN(in_pcl.price02) FROM dtb_product_class in_pcl WHERE in_pcl.product_id = ecp.id and in_pcl.visible::int = 1 GROUP BY in_pcl.product_id) min_price, (select MAX(in_pcl.price02) FROM dtb_product_class in_pcl WHERE in_pcl.product_id = ecp.id and in_pcl.visible::int = 1 GROUP BY in_pcl.product_id) max_price FROM dtb_product ecp, dtb_product_image ecpi WHERE ' . $or_str . ' GROUP BY ecp.id';
                }else{
                    $sql = 'SELECT ecp.id, ecp.name, ecp.description_detail, (select in_ecpi.file_name FROM dtb_product_image in_ecpi WHERE in_ecpi.product_id = ecp.id AND in_ecpi.sort_no = 1 ) file_name, (select MIN(in_pcl.price02) FROM dtb_product_class in_pcl WHERE in_pcl.product_id = ecp.id and in_pcl.visible = 1 GROUP BY in_pcl.product_id) min_price, (select MAX(in_pcl.price02) FROM dtb_product_class in_pcl WHERE in_pcl.product_id = ecp.id and in_pcl.visible = 1 GROUP BY in_pcl.product_id) max_price FROM dtb_product ecp, dtb_product_image ecpi WHERE ' . $or_str . ' GROUP BY ecp.id';
                }
                $stmt = $em->getConnection()->prepare($sql);
                $stmt->execute();
                $this->_rp = $stmt->fetchAll();
            }

            foreach($this->_rp as $k=>$product){
                $pr = $this->entityManager->getRepository('Eccube\Entity\Product')->find($product["id"]);
                $rate = $this->entityManager->getRepository('Plugin\ProductReview4\Entity\ProductReview')->getAvgAll($pr);
                $product["review_ave"] = round($rate['recommend_avg']);
                $product["review_cnt"] = intval($rate['review_count']);
                $this->_rp[$k] = $product;
            }

            /*
             * 現在の商品id をセッションに保持
             */
            $_SESSION['ec_save_pr_id'] = $id;

            return $this->render("Block/hsd_related_product.twig", array(
                'title' => $this->_title,
                'max_count' => $this->_show_count,
                'rp_count' => count($this->_rp),
                'hsd_related_product' => $this->_rp,
                'show_price' => $this->_show_price,
                'show_type' => $this->_show_type,
                'pagination' => $this->_pagination,
                'navbuttons' => $this->_navbuttons,
                'showloop' => $this->_showloop,
                'from_id' => $this->_save_from_id,
                'from_product_name' => $this->_save_from_product_name,
                'to_id' => $this->_save_to_id,
                'to_product_name' => $this->_save_to_product_name
            ));

        }

        return $this->render("Block/hsd_related_product.twig", array(
            'title' => $this->_title,
            'max_count' => $this->_show_count,
            'rp_count' => count($this->_rp),
            'hsd_related_product' => $this->_rp,
            'show_price' => $this->_show_price,
            'show_type' => $this->_show_type,
            'pagination' => $this->_pagination,
            'navbuttons' => $this->_navbuttons,
            'showloop' => $this->_showloop,
            'from_id' => $this->_save_from_id,
            'from_product_name' => $this->_save_from_product_name,
            'to_id' => $this->_save_to_id,
            'to_product_name' => $this->_save_to_product_name
        ));

    }

}
