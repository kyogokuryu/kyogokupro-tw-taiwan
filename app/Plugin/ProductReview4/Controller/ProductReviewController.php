<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ProductReview4\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\Product;
use Eccube\Entity\OrderItem;
use Plugin\ProductReview4\Entity\ProductReview;
use Plugin\ProductReview4\Entity\ProductReviewStatus;
use Plugin\ProductReview4\Form\Type\ProductReviewType;
use Plugin\ProductReview4\Repository\ProductReviewRepository;
use Plugin\ProductReview4\Repository\ProductReviewStatusRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Knp\Component\Pager\Paginator;

use Eccube\Repository\OrderItemRepository;

/**
 * Class ProductReviewController front.
 */
class ProductReviewController extends AbstractController
{
    /**
     * @var ProductReviewStatusRepository
     */
    private $productReviewStatusRepository;

    /**
     * @var ProductReviewRepository
     */
    private $productReviewRepository;


    private $orderItemRepository;

    /**
     * ProductReviewController constructor.
     *
     * @param ProductReviewStatusRepository $productStatusRepository
     * @param ProductReviewRepository $productReviewRepository
     */
    public function __construct(
        ProductReviewStatusRepository $productStatusRepository,
        ProductReviewRepository $productReviewRepository,
        OrderItemRepository $orderItemRepository
    ) {
        $this->productReviewStatusRepository = $productStatusRepository;
        $this->productReviewRepository = $productReviewRepository;
        $this->orderItemRepository = $orderItemRepository;
    }

    /**
     * @Route("/product_review/{id}/review", name="product_review_index", requirements={"id" = "\d+"})
     *
     * @param Request $request
     * @param Product $Product
     *
     * @return RedirectResponse|Response
     */
    public function index(Request $request, Product $Product)
    {
        if (!$this->session->has('_security_admin') && $Product->getStatus()->getId() !== ProductStatus::DISPLAY_SHOW) {
            //log_info('Product review', ['status' => 'Not permission']);
            //throw new NotFoundHttpException();
            return;
        }

        $ProductReview = new ProductReview();
        $form = $this->createForm(ProductReviewType::class, $ProductReview);

        $order_item_id = $request->get('order_item_id') ? $request->get('order_item_id') : 0;
        $add_point = 0;
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var $ProductReview ProductReview */
            $ProductReview = $form->getData();

            switch ($request->get('mode')) {
                case 'confirm':
                    log_info('Product review config confirm');

                    return $this->render('@ProductReview4/default/confirm.twig', [
                        'form' => $form->createView(),
                        'Product' => $Product,
                        'ProductReview' => $ProductReview,
                        'order_item_id' => $order_item_id,
                    ]);
                    break;

                case 'complete':
                    log_info('Product review complete');
                    if ($this->isGranted('ROLE_USER')) {
                        $Customer = $this->getUser();
                        $ProductReview->setCustomer($Customer);

                        if($order_item_id > 0){
                            $OrderItem = $this->orderItemRepository->find($order_item_id);
                            if($OrderItem){
                                if($OrderItem->getReviewId() == 0){
                                    if($ProductReview->getPic1() || $ProductReview->getPic2() || $ProductReview->getPic3() || $ProductReview->getPic4()){
                                        $add_point = 15;
                                    }else{
                                        $add_point = 10;
                                    }
                                }
                                $ProductReview->setOrderItem($OrderItem);
                            }
                        }
                    }

                    $ProductReview->setProduct($Product);
                    $ProductReview->setStatus($this->productReviewStatusRepository->find(ProductReviewStatus::SHOW));
                    $this->entityManager->persist($ProductReview);
                    $this->entityManager->flush($ProductReview);

                    log_info('Product review complete', ['id' => $Product->getId()]);


                    // point
                    if($order_item_id > 0 && $add_point > 0){
                        // Point
                        $OrderItem = $this->orderItemRepository->find($order_item_id);
                        $OrderItem->setReviewId($ProductReview->getId());
                        $OrderItem->setReviewPoint($add_point);
                        $this->entityManager->persist($OrderItem);
                        $this->entityManager->flush($OrderItem);

                        // ポイント付与
                        $point = $Customer->getPoint() + $add_point;
                        $Customer->setPoint($point);
                        $this->entityManager->persist($Customer);
                        $this->entityManager->flush($Customer);

                    }
                    $this->session->getFlashBag()->set('add_point', $add_point);

                    return $this->redirectToRoute('product_review_complete', ['id' => $Product->getId()]);
                    break;

                case 'back':
                    // 確認画面から投稿画面へ戻る
                    break;

                default:
                    // do nothing
                    break;
            }
        }

        return $this->render('@ProductReview4/default/index.twig', [
            'Product' => $Product,
            'ProductReview' => $ProductReview,
            'form' => $form->createView(),
            'order_item_id' => $order_item_id,
            'add_point' => $add_point,
        ]);
    }



    /**
     * Complete.
     *
     * @Route("/product_review/{id}/complete", name="product_review_complete", requirements={"id" = "\d+"})
     * @Template("@ProductReview4/default/complete.twig")
     *
     * @param $id
     *
     * @return array
     */
    public function complete($id)
    {

        $Product = $this->entityManager->getRepository('Eccube\Entity\Product')->findOneBy(['id'=>$id]);

        //https://kyogokupro.com/note/special/verification/feed
        //https://kyogokupro.com/note/special/kg/feed

        $html = file_get_contents("https://kyogokupro.com/note/special/verification/feed");
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

        $src = $rss_html->p[0]->img->attributes()->src;
        $summary = $rss_html->p[1];
/*
echo "<pre>";
var_dump($rss_html->p[0]->img);

var_dump($rss_html->p[0]->img->attributes());
echo $src;
echo "</pre>";
*/
        //$src = "";
        //if(preg_match('/src="([^"]+)"/', $desc, $mat)){
        //    $src = $mat[1];
        //    //var_dump($mat);
        //}
        //$rss_html = simplexml_load_string($desc);


        /*
        $domDocument = new \DOMDocument();
        $domDocument->loadHTML($html);
        $xmlString = $domDocument->saveXML();
        $xmlObject = simplexml_load_string($xmlString);
        var_dump($xmlObject);
        */
        //$pattern = '/<ul(.+)<\\//';
        //if(preg_match($pattern, $html, $mat)){
        //    var_dump($mat);
        //}
        //exit;
        return ['Product'=>$Product, 'id' => $id, 
        'item'=>[
            "title"=>$title,
            "src"=>$src,
            "desc"=>$desc,
            "link"=>$link,
            "summary"=>$summary,
        ]];
    }


    /**
     * @Route("/product_review/{id}/list", name="product_review_list", requirements={"id" = "\d+"})
     *
     * @param Request $request
     * @param Product $Product
     *
     * @return RedirectResponse|Response
     */
    public function list(Request $request, Product $Product,  Paginator $paginator)
    {

        // Page指定
        $currentPage = 1;
        if (isset($_GET['pageno'])) {
            $currentPage = $_GET['pageno'];
        }
        $limitPerPage = 10;
        $offset = ($currentPage - 1) * $limitPerPage;

        $level = null;
        if(isset($_GET["l"])){
            $level = $_GET["l"];
        }

        $pic = null;
        if(isset($_GET["pic"])){
            $pic = $_GET["pic"];
        }

        
        $options = [];

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('a')->from('Plugin\ProductReview4\Entity\ProductReview','a');
        $qb->where('a.Product = :product_id')->setParameter('product_id', $Product->getId());
        $qb->andwhere('a.Status = 1');
        $qb->orderBy('a.id','desc');

        if($level){
            $qb->andwhere('a.recommend_level = :recommend_level')->setParameter('recommend_level',$level);
        }
        if($pic){
            $qb->andwhere('a.pic1 is not null');
        }

        $data = $qb->getQuery();
        
        $pagination = $paginator->paginate($data, $currentPage, $limitPerPage, $options);


        $user_id = "";
        $Customer = $this->getUser();
        if($Customer){
            $user_id = $Customer->getId();
        }

        $categories = $this->entityManager->getRepository('Eccube\Entity\Category')->findBy(["id"=>[7,18,8,12,17,11,9,19,20,21,10,22]]);
        $category_map = [];
        foreach($categories as $cate){
            $category_map[] = $cate->getId();
        }

        $rateAll = $this->entityManager->getRepository('Plugin\ProductReview4\Entity\ProductReview')->getAvgAll($Product);
        $rateStar = $this->entityManager->getRepository('Plugin\ProductReview4\Entity\ProductReview')->getStarAll($Product);

        $rateStar2 = [
            5 => ["recommend_level"=>5, "count"=>0, "per"=>0],
            4 => ["recommend_level"=>4, "count"=>0, "per"=>0],
            3 => ["recommend_level"=>3, "count"=>0, "per"=>0],
            2 => ["recommend_level"=>2, "count"=>0, "per"=>0],
            1 => ["recommend_level"=>1, "count"=>0, "per"=>0],
        ];
        foreach($rateStar as $star){
            $rateStar2[$star["recommend_level"]]["count"] = $star["recommend_count"];
            $rateStar2[$star["recommend_level"]]["per"] = round( $star["recommend_count"] * 100 / $rateAll["review_count"] );
        }

        $rateAll["recommend_avg_text"] = sprintf("%.1f", $rateAll["recommend_avg"]);


        return $this->render('@ProductReview4/default/list.twig', [
            'Product' => $Product,
            'pagination' => $pagination,
            "category_map"=>$category_map,
            'rateAll' => $rateAll,
            'rateStar' => $rateStar2,
            "user_id" => $user_id,
        //    'form' => $form->createView(),
        ]);
    }

}
