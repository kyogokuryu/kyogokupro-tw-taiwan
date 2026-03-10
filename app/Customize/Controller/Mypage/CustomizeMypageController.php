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

namespace Customize\Controller\Mypage;

use Eccube\Controller\Mypage\MypageController;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Customer;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Entity\Order;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Exception\CartException;
use Eccube\Form\Type\Front\CustomerLoginType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\ProductClassRepository;
use Eccube\Repository\CustomerFavoriteProductRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Repository\CustomerRepository;
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
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Master\ProductStatus;
/**
 *
 *  ※ 新規にURLを追加する場合は、 Eccube/Controller/UserDataController.php の route に除外設定を入れる必要がある。
 *     カスタマイズ用に、cm_xxxxx のURLは、除外対象になる。
 *
 *
 */
class CustomizeMypageController extends MypageController
{

    /**
     * @var ProductClassRepository
     */
    protected $productClassRepository;
    /**
     *
     *
     */
     protected $customerRepository;


    /**
     * @var array 売り上げ状況用受注状況
     */
    private $excludes = [OrderStatus::CANCEL, OrderStatus::PENDING, OrderStatus::PROCESSING, OrderStatus::RETURNED];


    /**
     * MypageController constructor.
     *
     * @param OrderRepository $orderRepository
     * @param CustomerFavoriteProductRepository $customerFavoriteProductRepository
     * @param CartService $cartService
     * @param BaseInfoRepository $baseInfoRepository
     * @param PurchaseFlow $purchaseFlow
     * @param CustomerRepository $customerRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        OrderRepository $orderRepository,
        CustomerFavoriteProductRepository $customerFavoriteProductRepository,
        CartService $cartService,
        ProductRepository $productRepository,
        BaseInfoRepository $baseInfoRepository,
        PurchaseFlow $purchaseFlow,
        CustomerRepository $customerRepository,
        EntityManagerInterface $entityManager,
        ProductClassRepository $productClassRepository
    ) {
        $this->productClassRepository = $productClassRepository;
        $this->orderRepository = $orderRepository;
        $this->customerFavoriteProductRepository = $customerFavoriteProductRepository;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->cartService = $cartService;
        $this->purchaseFlow = $purchaseFlow;
        $this->customerRepository = $customerRepository;
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
    }

    /**
     *  @Route("/mypage/urank", name="mypage_urank")
     *  @Template("Mypage/urank.twig")
     *
     */
    public function urank(Request $request){


        $Customer = $this->getUser();
     //   $Customer = $this->customerRepository->find(11);

        $result = $this->getSalesByYear($Customer);

        return [
            "Customer"=>$Customer,
            "saleOrder"=>$result
        ];
    }


    /**
     * @param \DateTime $dateTime
     *
     * @return array|mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getSalesByYear($Customer)
    {
        $ymd = date('Y-m-1', strtotime('-1 year'));

        $qb = $this->orderRepository
            ->createQueryBuilder('o')
            ->select('
            SUM(o.payment_total) AS order_amount,
            COUNT(o) AS order_count')
            ->setParameter(':cid', $Customer)
            ->setParameter(':excludes', $this->excludes)
            ->setParameter(':targetDateStart', new \DateTime($ymd))
        //    ->setParameter(':targetDateEnd', $dateTimeEnd)
            ->andWhere(':targetDateStart <= o.order_date')
            ->andWhere('o.OrderStatus NOT IN (:excludes)')
            ->andWhere('o.Customer = :cid')
            ;
        $q = $qb->getQuery();

        $result = [];
        try {
            $result = $q->getSingleResult();
        } catch (NoResultException $e) {
            // 結果がない場合は空の配列を返す.
        }

        return $result;
    }

    /**
     * お気に入り商品を削除する.
     *
     * @Route("/mypage/favorite/{id}/delete", name="mypage_favorite_delete", methods={"DELETE"}, requirements={"id" = "\d+"})
     */
    public function delete(Request $request, Product $Product)
    {
        $this->isTokenValid();

        $Customer = $this->getUser();

        log_info('お気に入り商品削除開始', [$Customer->getId(), $Product->getId()]);

        $CustomerFavoriteProduct = $this->customerFavoriteProductRepository->findOneBy(['Customer' => $Customer, 'Product' => $Product]);

        if ($CustomerFavoriteProduct) {
            $this->customerFavoriteProductRepository->delete($CustomerFavoriteProduct);
        } else {
            throw new BadRequestHttpException();
        }

        $event = new EventArgs(
            [
                'Customer' => $Customer,
                'CustomerFavoriteProduct' => $CustomerFavoriteProduct,
            ], $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_MYPAGE_DELETE_COMPLETE, $event);

        log_info('お気に入り商品削除完了', [$Customer->getId(), $CustomerFavoriteProduct->getId()]);

        //商品ページから削除した場合は該当のページへもどる 20211126 kikuzawa
        $url = $this->generateUrl('mypage_favorite');
        $referer = $_SERVER['HTTP_REFERER'];
        if(strpos($referer, 'detail') || strpos($referer, 'list') || strpos($referer, 'salon-suppliers')) $url = $referer;

        return $this->redirect($url);
    }

    /**
     *
     * ログインポイントを付与する
     * 
     * @Route("/mypage/add_login_poiint", name="mypage_add_login_point", methods={"POST"})
     *
     *
     */
    public function add_login_point(){
    
        //$this->session->getFlashBag()->set('add_login_point_success', '更新しました。');
        $Customer =  $this->getUser();

        $day = $Customer->getLoginPointDay();
        $last_point_date = $Customer->getLastLoginDate();

        if($Customer->isLoginPoint()){
            // ポイント付与可
            //付与日
            $day = $Customer->getActiveLoginPointDay();
        }else{
            $url = $this->generateUrl('mypage');
            return $this->redirect($url);
        
        }

        // 付与ポイント
        $point = $Customer->getLoginPoint();//self::POINT_DAY_LIST[$day % 7];

        $next_day = $day;
        // $next_point = self::POINT_DAY_LIST[$day % 7];

        $Customer->setLastLoginDate( new \DateTime('now') );
        $Customer->setLoginPointDay($next_day);
        $Customer->setPoint($Customer->getPoint() + $point);
        $this->entityManager->persist($Customer);
        $this->entityManager->flush();




        $url = $this->generateUrl('mypage');

//        $this['session']->getFlashBag()->set('add_login_point_success','test');
        $this->session->getFlashBag()->set('add_login_point_success', '更新しました。');

        return $this->redirect($url);
    }


    /**
     * 再購入を行う.
     *
     * @Route("/mypage/reorder/{order_no}/{product_id}", name="mypage_reorder", methods={"PUT"})
     */
    public function reorder(Request $request, $order_no, $product_id)
    {
        $this->isTokenValid();

        log_info('再注文開始', [$order_no]);

        $Customer = $this->getUser();

        /* @var $Order \Eccube\Entity\Order */
        $Order = $this->orderRepository->findOneBy(
            [
                'order_no' => $order_no,
                'Customer' => $Customer,
            ]
        );

        $event = new EventArgs(
            [
                'Order' => $Order,
                'Customer' => $Customer,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_MYPAGE_ORDER_INITIALIZE, $event);

        if (!$Order) {
            log_info('対象の注文が見つかりません', [$order_no]);
            throw new NotFoundHttpException();
        }

        // エラーメッセージの配列
        $errorMessages = [];

        foreach ($Order->getOrderItems() as $OrderItem) {
            try {
                if ($OrderItem->getProduct() && $OrderItem->getProductClass()) {
                    if($OrderItem->getProduct()->getId() == $product_id){
                        $this->cartService->addProduct($OrderItem->getProductClass(), $OrderItem->getQuantity());

                        // 明細の正規化
                        $Carts = $this->cartService->getCarts();
                        foreach ($Carts as $Cart) {
                            $result = $this->purchaseFlow->validate($Cart, new PurchaseContext($Cart, $this->getUser()));
                            // 復旧不可のエラーが発生した場合は追加した明細を削除.
                            if ($result->hasError()) {
                                $this->cartService->removeProduct($OrderItem->getProductClass());
                                foreach ($result->getErrors() as $error) {
                                    $errorMessages[] = $error->getMessage();
                                }
                            }
                            foreach ($result->getWarning() as $warning) {
                                $errorMessages[] = $warning->getMessage();
                            }
                        }

                        $this->cartService->save();
                    }
                }
            } catch (CartException $e) {
                log_info($e->getMessage(), [$order_no]);
                $this->addRequestError($e->getMessage());
            }
        }

        foreach ($errorMessages as $errorMessage) {
            $this->addRequestError($errorMessage);
        }

        $event = new EventArgs(
            [
                'Order' => $Order,
                'Customer' => $Customer,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_MYPAGE_ORDER_COMPLETE, $event);

        if ($event->getResponse() !== null) {
            return $event->getResponse();
        }

        log_info('再注文完了', [$order_no]);

        return $this->redirect($this->generateUrl('cart'));
    }




    /**
     * 直接購入を行う.
     *
     * @Route("/cm_direct_order/{product_id}", name="cm_direct_order", methods={"GET"})
     *
     * @param Request $request
     * @param Product $product_id
     *
     */
    public function direct_order(Request $request, $product_id)
    {


        $Product = $this->productRepository->find($product_id);

        log_info('ダイレクト注文開始', [$Product->getId()]);
        //var_dump($Product->getId());

        //--------------------------------------------------
        $order_no = $request->get('pre_order_no');
        if($order_no){
            $this->set_pre_soryo($request, $order_no);        
        }
        //--------------------------------------------------


        $Customer = $this->getUser();
        $flg = true;
        $Carts = $this->cartService->getCarts();
        foreach ($Carts as $Cart) {
            foreach($Cart->getItems() as $item){
                if($item->getProductClass()->getProduct()->getId() == $Product->getId()){
                    $flg = false;
                }
            }
        }
        if($flg){
        
            $ProductClass = $this->productClassRepository
                ->findOneBy(["Product"=>$Product]);
            $this->cartService->addProduct($ProductClass, 1);
            
        }

        $Carts = $this->cartService->getCarts();
        $cart_key = null;
        foreach ($Carts as $Cart) {
            $result = $this->purchaseFlow->validate($Cart, new PurchaseContext($Cart, $this->getUser()));
            // 復旧不可のエラーが発生した場合は追加した明細を削除.
            if ($result->hasError()) {
                $this->cartService->removeProduct($Product);
                foreach ($result->getErrors() as $error) {
                    $errorMessages[] = $error->getMessage();
                }
            }
            foreach ($result->getWarning() as $warning) {
                $errorMessages[] = $warning->getMessage();
            }

            foreach($Cart->getItems() as $item){
                if($item->getProductClass()->getProduct()->getId() == $Product->getId()){
                    $cart_key = $Cart->getCartKey();
                }
            }
        }


        $this->cartService->save();

        if($order_no){
        }else{
            $session = $request->getSession();
            $session->getFlashBag()->set('direct_order', true);
            $session->getFlashBag()->set('credit_order', true);
        }        
        return $this->redirect($this->generateUrl('cart_buystep',["cart_key"=>$cart_key]));
    }



    /**
     * ワンクリック購入を行う.
     *
     * @Route("/cm_onclick_order/{product_id}", name="cm_oneclick_order", methods={"GET"})
     *
     * @param Request $request
     * @param Product $product_id
     *
     */
    public function onclick_order(Request $request, $product_id)
    {


        $Product = $this->productRepository->find($product_id);

        log_info('ワンクリック注文開始', [$Product->getId()]);
        //var_dump($Product->getId());

        $Customer = $this->getUser();
        $flg = true;
        $Carts = $this->cartService->getCarts();
        foreach ($Carts as $Cart) {
            foreach($Cart->getItems() as $item){
                if($item->getProductClass()->getProduct()->getId() == $Product->getId()){
                    $flg = false;
                }else{            
                    $this->cartService->removeProduct($Product);
                }
            }
        }
        if($flg){
        
            $ProductClass = $this->productClassRepository
                ->findOneBy(["Product"=>$Product]);
            $this->cartService->addProduct($ProductClass, 1);
            
        }

        $Carts = $this->cartService->getCarts();
        $cart_key = null;
        foreach ($Carts as $Cart) {
            $result = $this->purchaseFlow->validate($Cart, new PurchaseContext($Cart, $this->getUser()));
            // 復旧不可のエラーが発生した場合は追加した明細を削除.
            if ($result->hasError()) {
                $this->cartService->removeProduct($Product);
                foreach ($result->getErrors() as $error) {
                    $errorMessages[] = $error->getMessage();
                }
            }
            foreach ($result->getWarning() as $warning) {
                $errorMessages[] = $warning->getMessage();
            }

            foreach($Cart->getItems() as $item){
                if($item->getProductClass()->getProduct()->getId() == $Product->getId()){
                    $cart_key = $Cart->getCartKey();
                }
                log_info("Cart->getItems getId", [$item->getProductClass()->getProduct()->getName()]);
            }

            log_info("cart_key", [$Cart->getCartKey()]);
        }
        $this->cartService->save();

        /*
        if($order_no){
        }else{
            $session = $request->getSession();
            $session->getFlashBag()->set('direct_order', true);
            $session->getFlashBag()->set('credit_order', true);
        } 
        */       
        return $this->redirect($this->generateUrl('cart_buystep',["cart_key"=>$cart_key]));
    }


    private function set_pre_soryo($request, $order_no){

        $Customer = $this->getUser();
        $Order = $this->orderRepository->findOneBy(
            [
                'order_no' => $order_no,
                'Customer' => $Customer,
            ]
        );            

        if($Customer->getPreOrderDiscountPrice() == 0){
            $price = 0;
            foreach ($Order->getOrderItems() as $OrderItem) {
                try {
                    if ($OrderItem->getProduct()  == null) {
                        if($OrderItem->getProductName() == "送料"){
                            $price = $OrderItem->getPrice();   
                        }
                        log_info("前回購入：", [$OrderItem->getProductName()]);
                    }
                } catch (CartException $e) {
                    log_info($e->getMessage(), [$order_no]);
                    $this->addRequestError($e->getMessage());
                }
            }

            if($price > 0 && $Customer->getPrimeMember() == 0){
                $Customer->setPreOrderDiscountPrice($price);
                $this->entityManager->persist($Customer);
                $this->entityManager->flush();
            }    
            //$_SESSION['pre_order_discount_price'] = ["price"=>(int)$price,"order_no"=>$order_no];
            log_info('[送料ポイントバック] set_pre_soryo', [$price]);
        }
    }
}
