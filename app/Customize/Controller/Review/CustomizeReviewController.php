<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Customize\Controller\Review;

use Eccube\Controller\AbstractController;
use Eccube\Controller\Mypage\MypageController;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Customer;
use Eccube\Entity\Product;
use Eccube\Entity\Order;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Exception\CartException;
use Eccube\Form\Type\Front\CustomerLoginType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\CustomerFavoriteProductRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Service\CartService;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Doctrine\ORM\Query\ResultSetMapping;

use Eccube\Entity\Master\ProductStatus;
use Eccube\Form\Type\AddCartType;
use Eccube\Form\Type\Master\ProductListMaxType;
use Eccube\Form\Type\Master\ProductListOrderByType;
use Eccube\Form\Type\SearchProductType;
use Eccube\Repository\Master\ProductListMaxRepository;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Plugin\ProductReview4\Repository\ProductReviewRepository;


class CustomizeReviewController extends AbstractController
{

    /**
     * @var PurchaseFlow
     */
    protected $purchaseFlow;

    /**
     * @var CustomerFavoriteProductRepository
     */
    protected $customerFavoriteProductRepository;

    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var AuthenticationUtils
     */
    protected $helper;

    /**
     * @var ProductListMaxRepository
     */
    protected $productListMaxRepository;

    private $title = '';

    /**
     * @var ProductReviewRepository
     */
    protected $productReviewRepository;

    /**
     * ProductController constructor.
     *
     * @param PurchaseFlow $cartPurchaseFlow
     * @param CustomerFavoriteProductRepository $customerFavoriteProductRepository
     * @param CartService $cartService
     * @param ProductRepository $productRepository
     * @param BaseInfoRepository $baseInfoRepository
     * @param AuthenticationUtils $helper
     * @param ProductListMaxRepository $productListMaxRepository
     * @param ProductReviewRepository $productReviewRepository
     */
    public function __construct(
        PurchaseFlow $cartPurchaseFlow,
        CustomerFavoriteProductRepository $customerFavoriteProductRepository,
        CartService $cartService,
        ProductRepository $productRepository,
        BaseInfoRepository $baseInfoRepository,
        AuthenticationUtils $helper,
        ProductListMaxRepository $productListMaxRepository,
        ProductReviewRepository $productReviewRepository
    ) {
        $this->purchaseFlow = $cartPurchaseFlow;
        $this->customerFavoriteProductRepository = $customerFavoriteProductRepository;
        $this->cartService = $cartService;
        $this->productRepository = $productRepository;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->helper = $helper;
        $this->productListMaxRepository = $productListMaxRepository;
        $this->productReviewRepository = $productReviewRepository;
    }

    /**
     * レビュー図一覧
     *
     * @Route("/review_list", name="review_list")
     * @Template("Review/index.twig")
     */
    public function index(Request $request, Paginator $paginator)
    {

        // Page指定
        $currentPage = 1;
        if (isset($_GET['pageno'])) {
            $currentPage = $_GET['pageno'];
        }
        $limitPerPage = 10;
        $offset = ($currentPage - 1) * $limitPerPage;
        
        $options = [];
        

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('a')->from('Plugin\ProductReview4\Entity\ProductReview','a');

        $orderby_name = "新着順";
        $orderby  = 1;
        if(isset($_GET["orderby"])){
            $orderby = $_GET["orderby"];
            if($orderby == 1){
                $qb->orderBy('a.id','desc');
                $orderby_name = "新着順";
            }elseif($orderby == 2){
                $qb->orderBy('a.recommend_level','desc');
                $qb->addorderBy('a.id','desc');
                $orderby_name = "評価の高い順";
            }elseif($orderby == 3){
                $qb->orderBy('a.recommend_level','asc');
                $qb->addorderBy('a.id','desc');                
                $orderby_name = "評価の低い順";
            }elseif($orderby == 4){
                $qb->orderBy('a.ref_count','desc');
                $qb->addorderBy('a.id','desc');                
                $orderby_name = "参考になったの多い順";
            }
        }else{
            $qb->orderBy('a.id','desc');
        }
        $qb->where('a.Status = 1');

        $where = false;
        $category_name = "絞り込み";
        $category_id = "";
        if(isset($_GET["category_id"]) && $_GET["category_id"]){
            $category_id = $_GET["category_id"];
            $qb->join('Eccube\Entity\Product', 'p', \Doctrine\ORM\Query\Expr\Join::WITH, 'a.Product = p.id');
            $qb->join('Eccube\Entity\ProductCategory', 'pc', \Doctrine\ORM\Query\Expr\Join::WITH, 'pc.product_id = p.id');
            //$qb->join('Eccube\Entity\Category', 'c', \Doctrine\ORM\Query\Expr\Join::WITH, 'pc.category_id = c.id');
            $qb->andWhere('pc.category_id = :category_id')->setParameter('category_id',$category_id);

            $cate = $this->entityManager->getRepository('Eccube\Entity\Category')->findOneBy(['id'=>$category_id]);
            $category_name = $cate->getName();
            $where = true;
        }

        $categories = $this->entityManager->getRepository('Eccube\Entity\Category')->findBy(["id"=>[7,18,8,12,17,11,9,19,20,21,10,22]]);
        $category_map = [];
        foreach($categories as $cate){
            $category_map[] = $cate->getId();
        }

        $keyword = "";
        if(isset($_GET["keyword"]) && $_GET["keyword"]){
            $keyword = $_GET["keyword"];
            if($where){
                $qb->andwhere('(a.title like :keyword or a.comment like :keyword or p.name like :keyword)')->setParameter('keyword' , '%'.$keyword.'%');
            }else{
                $qb->join('Eccube\Entity\Product', 'p', \Doctrine\ORM\Query\Expr\Join::WITH, 'a.Product = p.id');
                $qb->andWhere('(a.title like :keyword or a.comment like :keyword or p.name like :keyword)')->setParameter('keyword' , '%'.$keyword.'%');
            }
        }

        
        //echo $qb->getDql();
        //exit;

        $data = $qb->getQuery();//->select('a')
                //->select('p.name')
                //->from('Plugin\ProductReview4\Entity\ProductReview','a')
                //->join(sprintf('(%s)', $sub_qb->getDql()), 'b',\Doctrine\ORM\Query\Expr\Join::WITH, 'b.product_id = a.product_id')
                //->join('Eccube\Entity\Product', 'p', \Doctrine\ORM\Query\Expr\Join::WITH, 'a.product_id = p.id')
                //->getQuery();
                //;

        $pagination = $paginator->paginate($data, $currentPage, $limitPerPage, $options);
        //var_dump($pagination);
        //$ids = [];
        //$ProductList = array();
        

        
        $ReviewAveList = array();
        $ReviewCntList = array();
        foreach ($pagination as $Review) {
        //    foreach($Reviews as $Review){
                $Product = $Review->getProduct();//getProduct();
                $ids[] = $Product->getId();
                $rate = $this->entityManager->getRepository('Plugin\ProductReview4\Entity\ProductReview')->getAvgAll($Product);
                $ReviewAveList[$Product->getId()] = round($rate['recommend_avg']);
                $ReviewCntList[$Product->getId()] = intval($rate['review_count']);

        //    }
        }

        $user_id = "";
        $Customer = $this->getUser();
        if($Customer){
            $user_id = $Customer->getId();
        }

        return [
        //    'subtitle' => $this->getPageTitle($searchData),
            'pagination' => $pagination,
        //    'search_form' => $searchForm->createView(),
        //    'disp_number_form' => $dispNumberForm->createView(),
        //    'order_by_form' => $orderByForm->createView(),
        //    'forms' => $forms,
        //    'Category' => $Category,
            'categories' => $categories,
            'ReviewAveList' => $ReviewAveList,
            'ReviewCntList' => $ReviewCntList,
            'orderby_name' => $orderby_name,
            "keyword" => $keyword,
            "category_id"=>$category_id,
            "category_name"=>$category_name,
            "orderby"=>$orderby,
            "category_map"=>$category_map,
            "user_id" => $user_id,
        //    "ProductReviews" => $ProductReviews,
        ];
    }



    /**
     *
     * @Route("/mypage/myreview_list", name="mypage_myreview_list")
     * @Template("Mypage/review-list.twig")
     */
    public function mypage_myreview_list(Request $request, Paginator $paginator)
    {

        $Customer = $this->getUser();
        if($Customer == null){

        }
        $review_id = 0;
        if(isset($_GET["id"])){
            $review_id = $_GET["id"];
        }

 // Page指定
        $currentPage = 1;
        if (isset($_GET['pageno'])) {
            $currentPage = $_GET['pageno'];
        }
        $limitPerPage = 10;
        //$offset = ($currentPage - 1) * $limitPerPage;
        
        $options = [];
        

        //$qb = $this->entityManager->createQueryBuilder();
        $qb = $this->productReviewRepository->createQueryBuilder('r');

        //$qb->select('a')->from('Plugin\ProductReview4\Entity\ProductReview','a');
        $qb->orderBy('r.id','desc');
        
        $qb->where('r.Status = 1');
        $qb->andWhere('r.Customer = :customer_id')
            ->setParameter('customer_id', $Customer->getId())
            ->andWhere('r.OrderItem > 0')
            ;
        if($review_id){
            $qb->andWhere('r.id = :id')->setParameter('id', $review_id);
        }

        $data = $qb->getQuery();//->select('a')

        //var_dump($data->getSql());
        //exit;

                //->select('p.name')
                //->from('Plugin\ProductReview4\Entity\ProductReview','a')
                //->join(sprintf('(%s)', $sub_qb->getDql()), 'b',\Doctrine\ORM\Query\Expr\Join::WITH, 'b.product_id = a.product_id')
                //->join('Eccube\Entity\Product', 'p', \Doctrine\ORM\Query\Expr\Join::WITH, 'a.product_id = p.id')
                //->getQuery();
                //;

        $pagination = $paginator->paginate($data, $currentPage, $limitPerPage, $options);
        //var_dump($pagination);
        //$ids = [];
        //$ProductList = array();

        return [
        //    'subtitle' => $this->getPageTitle($searchData),
            'pagination' => $pagination,
            'review_id' => $review_id,
        //    'search_form' => $searchForm->createView(),
        //    'disp_number_form' => $dispNumberForm->createView(),
        //    'order_by_form' => $orderByForm->createView(),
        //    'forms' => $forms,
        //    'Category' => $Category,
        //    'categories' => $categories,
        //    'ReviewAveList' => $ReviewAveList,
        //    'ReviewCntList' => $ReviewCntList,
        //    'orderby_name' => $orderby_name,
        //    "keyword" => $keyword,
        //    "category_id"=>$category_id,
        //    "category_name"=>$category_name,
        //    "orderby"=>$orderby,
        //    "category_map"=>$category_map,
        //    "user_id" => $user_id,
        //    "ProductReviews" => $ProductReviews,
        ];        
    }

    /**
     * レビュー
     *
     * @Route("/review_list/addref", name="review_list_addref")
     */
    public function addref(Request $request)
    {
    
        $Customer = $this->getUser();
        if($Customer == null){
            $msg = [];
            $msg["err"] = 1;
            $this->json($msg, 200);
        }

        $user_id = $Customer->getId();
        $rid = $_GET["rid"];

        $Review = $this->entityManager->getRepository('Plugin\ProductReview4\Entity\ProductReview')->findOneBy(['id'=>$rid]);
        $Review->setRefCount($Review->getRefCount() + 1);
        $ref_users = $Review->getRefUsers();
        if(!in_array($user_id, $ref_users)){
            $ref_users[] = $user_id;
            $Review->setRefUsers($ref_users);
        }
        $this->entityManager->persist($Review);
        $this->entityManager->flush();


        $msg = [];
        $msg["err"] = 0;
        $msg["success"] = 1;
        $msg["user_id"] = $user_id;
        $msg["rid"] = $rid;
        $msg["ref_count"] = $Review->getRefCount();
        return $this->json($msg, 200);
    }


    /**
     *  Login
     *
     *  @Route("/mypage/review_list", name="mypage_review_list")
     *  @throws \Exception
     *
     */
    public function login(){
        
        $Customer = $this->getUser();
        if (empty($Customer)) {
            throw new BadRequestHttpException();
        }

        return $this->redirectToRoute('review_list');
    }

    /**
     *  Login
     *
     *  @Route("/mypage/product_review/{id}", name="mypage_product_review")
     *  @param Request $request
     *  @param Product $Product
     *  @throws \Exception
     *
     */
    public function product_login(Request $request, Product $Product){
        
        $Customer = $this->getUser();
        if (empty($Customer)) {
            throw new BadRequestHttpException();
        }

        return $this->redirectToRoute('product_review',$Product->getId());
    }

}
