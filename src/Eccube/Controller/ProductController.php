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

namespace Eccube\Controller;

use Eccube\Entity\BaseInfo;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\Product;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\AddCartType;
use Eccube\Form\Type\Master\ProductListMaxType;
use Eccube\Form\Type\Master\ProductListOrderByType;
use Eccube\Form\Type\SearchProductType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\CustomerFavoriteProductRepository;
use Eccube\Repository\Master\ProductListMaxRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Service\CartService;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Eccube\Entity\ProductClass;//20220829 kikuzawa
use Plugin\ProductOption\Entity\Option;//20220829 kikuzawa
use Plugin\ProductOption\Service\ProductOptionCartService;//20220829 kikuzawa
use Eccube\Repository\CustomerRepository;//20220812 kikuzawa

class ProductController extends AbstractController
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

    //20220812 kikuzawa
    protected $customerRepository;

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
     */
    public function __construct(
        PurchaseFlow $cartPurchaseFlow,
        CustomerFavoriteProductRepository $customerFavoriteProductRepository,
        // CartService $cartService,
        ProductRepository $productRepository,
        BaseInfoRepository $baseInfoRepository,
        AuthenticationUtils $helper,
        CustomerRepository $customerRepository,//20220812 kikuzawa
        ProductOptionCartService $cartService,//20220812 kikuzawa
        ProductListMaxRepository $productListMaxRepository
    ) {
        $this->purchaseFlow = $cartPurchaseFlow;
        $this->customerFavoriteProductRepository = $customerFavoriteProductRepository;
        $this->cartService = $cartService;
        $this->productRepository = $productRepository;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->helper = $helper;
        $this->productListMaxRepository = $productListMaxRepository;
        $this->customerRepository = $customerRepository;//20220812 kikuzawa
    }

    /**
     * 商品一覧画面.
     *
     * @Route("/products/list", name="product_list")
     * @Template("Product/list.twig")
     */
    public function index(Request $request, Paginator $paginator)
    {
        $Customer = $this->getUser();
        // Doctrine SQLFilter
        if ($this->BaseInfo->isOptionNostockHidden()) {
            $this->entityManager->getFilters()->enable('option_nostock_hidden');
        }

        // handleRequestは空のqueryの場合は無視するため
        if ($request->getMethod() === 'GET') {
            $request->query->set('pageno', $request->query->get('pageno', ''));
        }

        // searchForm
        /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
        $builder = $this->formFactory->createNamedBuilder('', SearchProductType::class);

        if ($request->getMethod() === 'GET') {
            $builder->setMethod('GET');
        }

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_INDEX_INITIALIZE, $event);

        /* @var $searchForm \Symfony\Component\Form\FormInterface */
        $searchForm = $builder->getForm();

        $searchForm->handleRequest($request);

        // paginator
        $searchData = $searchForm->getData();
        if($Customer){
            $qb = $this->productRepository->getQueryBuilderBySearchData($searchData, $Customer->getOwnerRank());
        }else{
            $qb = $this->productRepository->getQueryBuilderBySearchData($searchData);
        }

        $event = new EventArgs(
            [
                'searchData' => $searchData,
                'qb' => $qb,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_INDEX_SEARCH, $event);
        $searchData = $event->getArgument('searchData');
        $query = $qb->getQuery()
            ->useResultCache(true, $this->eccubeConfig['eccube_result_cache_lifetime_short']);
        /** @var SlidingPagination $pagination */
        $pagination = $paginator->paginate(
            $query,
            !empty($searchData['pageno']) ? $searchData['pageno'] : 1,
            !empty($searchData['disp_number']) ? $searchData['disp_number']->getId() : $this->productListMaxRepository->findOneBy([], ['sort_no' => 'ASC'])->getId()
        );
        // if (isset($searchData['category_id']) && $searchData['category_id']) {
        //     // カテゴリ表示時は全て表示
        //     $pagination = $paginator->paginate($query,1,99);
        // } else {
        //     // BEST OF BEST表示時は４件のみ
        //     $pagination = $paginator->paginate($query,1,4);
        // }

        $ids = [];
        $ReviewAveList = array();
        $ReviewCntList = array();
        foreach ($pagination as $Product) {
            $ids[] = $Product->getId();
            $rate = $this->entityManager->getRepository('Plugin\ProductReview4\Entity\ProductReview')->getAvgAll($Product);
            $ReviewAveList[$Product->getId()] = round($rate['recommend_avg']);
            $ReviewCntList[$Product->getId()] = intval($rate['review_count']);
        }
        $ProductsAndClassCategories = $this->productRepository->findProductsWithSortedClassCategories($ids, 'p.id');

        // addCart form
        $forms = [];
        foreach ($pagination as $Product) {
            /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
            $builder = $this->formFactory->createNamedBuilder(
                '',
                AddCartType::class,
                null,
                [
                    'product' => $ProductsAndClassCategories[$Product->getId()],
                    'allow_extra_fields' => true,
                ]
            );
            $addCartForm = $builder->getForm();

            $forms[$Product->getId()] = $addCartForm->createView();
        }

        // 表示件数
        $builder = $this->formFactory->createNamedBuilder(
            'disp_number',
            ProductListMaxType::class,
            null,
            [
                'required' => false,
                'allow_extra_fields' => true,
            ]
        );
        if ($request->getMethod() === 'GET') {
            $builder->setMethod('GET');
        }

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_INDEX_DISP, $event);

        $dispNumberForm = $builder->getForm();

        $dispNumberForm->handleRequest($request);

        // ソート順
        $builder = $this->formFactory->createNamedBuilder(
            'orderby',
            ProductListOrderByType::class,
            null,
            [
                'required' => false,
                'allow_extra_fields' => true,
            ]
        );
        if ($request->getMethod() === 'GET') {
            $builder->setMethod('GET');
        }

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_INDEX_ORDER, $event);

        $orderByForm = $builder->getForm();

        $orderByForm->handleRequest($request);

        $Category = $searchForm->get('category_id')->getData();

        return [
            'subtitle' => $this->getPageTitle($searchData),
            'pagination' => $pagination,
            'search_form' => $searchForm->createView(),
            'disp_number_form' => $dispNumberForm->createView(),
            'order_by_form' => $orderByForm->createView(),
            'forms' => $forms,
            'Category' => $Category,
            'ReviewAveList' => $ReviewAveList,
            'ReviewCntList' => $ReviewCntList,
        ];
    }

    /**
     * 商品詳細画面.
     *
     * @Route("/products/detail/{id}", name="product_detail", methods={"GET"}, requirements={"id" = "\d+"})
     * @Template("Product/detail.twig")
     * @ParamConverter("Product", options={"repository_method" = "findWithSortedClassCategories"})
     *
     * @param Request $request
     * @param Product $Product
     *
     * @return array
     */
    public function detail(Request $request, Product $Product)
    {
        $Customer = $this->getUser();

        if ((!$this->getUser() && !$this->session->get("IS_SUPPLIER")) || ($this->getUser() && !$this->getUser()->getIsSupplier())) {
            if($this->handlerSupplierProduct($Product)){
                return $this->redirectToRoute('salon-suppliers');
            }

        }

        if (!$this->checkVisibility($Product)) {
            throw new NotFoundHttpException();
        }

//        if (strrpos($Product->getName(), '仕入れ') && !$Customer->getIsSupplier()) {
//            $this->session->set('supplier_product_id', $Product->getId());
//
//            return $this->redirectToRoute('salon-suppliers');
//        }

        $builder = $this->formFactory->createNamedBuilder(
            '',
            AddCartType::class,
            null,
            [
                'product' => $Product,
                'id_add_product_id' => false,
            ]
        );

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Product' => $Product,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_DETAIL_INITIALIZE, $event);

        $is_favorite = false;
        if ($this->isGranted('ROLE_USER')) {
            $Customer = $this->getUser();
            $is_favorite = $this->customerFavoriteProductRepository->isFavorite($Customer, $Product);
        }

        //お気に入り数の合計を取得 20211127 kikuzawa
        $favorite_total = 0;
        $favorite_total = $this->customerFavoriteProductRepository->getFavoriteTotal($Product);

        $user_id = "";
        $Customer = $this->getUser();
        if($Customer){
            $user_id = $Customer->getId();
        }

        if($Product->getOwnerProductId()){
            // ダイヤモンド
            if($Customer && $Customer->getOwnerRank() == 3){
                return $this->redirectToRoute('product_detail', ["id"=>$Product->getOwnerProductId()]);
            }
        }

        $faqs = $this->productRepository->parseFAQs($Product->getLlmoFaq());

        // Compute free shipping status and carts (for default_frame badge)
        $Carts = $this->cartService->getCarts();
        $least = [];
        $quantity = [];
        $isDeliveryFree = [];
        $totalPrice = 0;
        $totalQuantity = 0;
        foreach ($Carts as $Cart) {
            $quantity[$Cart->getCartKey()] = 0;
            $isDeliveryFree[$Cart->getCartKey()] = false;

            if ($this->BaseInfo->getDeliveryFreeQuantity()) {
                if ($this->BaseInfo->getDeliveryFreeQuantity() > $Cart->getQuantity()) {
                    $quantity[$Cart->getCartKey()] = $this->BaseInfo->getDeliveryFreeQuantity() - $Cart->getQuantity();
                } else {
                    $isDeliveryFree[$Cart->getCartKey()] = true;
                }
            }

            if ($this->BaseInfo->getDeliveryFreeAmount()) {
                if (!$isDeliveryFree[$Cart->getCartKey()] && $this->BaseInfo->getDeliveryFreeAmount() <= $Cart->getTotalPrice()) {
                    $isDeliveryFree[$Cart->getCartKey()] = true;
                } else {
                    $least[$Cart->getCartKey()] = $this->BaseInfo->getDeliveryFreeAmount() - $Cart->getTotalPrice();
                }
            }

            $totalPrice += $Cart->getTotalPrice();
            $totalQuantity += $Cart->getQuantity();

            $items = $Cart->getCartItems();
            $numberOfItems = count($items);
            $deliveryFreeItemCount = 0;
            for ($i = 0; $i < $numberOfItems; $i++) {
                $item = $items[$i];
                if ($item) {
                    $productClass = $item->getProductClass();
                    if ($productClass->delivery_fee_free) {
                        $deliveryFreeItemCount++;
                    }
                }
            }
            $allItemsDeliveryFree = $deliveryFreeItemCount === $numberOfItems;
            if ($allItemsDeliveryFree) {
                $isDeliveryFree[$Cart->getCartKey()] = true;
            }
        }

        return [
            'title' => $this->title,
            'subtitle' => $Product->getName(),
            'form' => $builder->getForm()->createView(),
            'Product' => $Product,
            'is_favorite' => $is_favorite,
            'favorite_total' => $favorite_total,//お気に入り数の合計を取得 20211127 kikuzawa
            'user_id' => $user_id,
            'faqs' => $faqs,
            'is_delivery_free' => $isDeliveryFree,
            'Carts' => $Carts,
        ];
    }

    /**
     * お気に入り追加.
     *
     * @Route("/products/add_favorite/{id}", name="product_add_favorite", requirements={"id" = "\d+"})
     */
    public function addFavorite(Request $request, Product $Product)
    {
        $this->checkVisibility($Product);

        $event = new EventArgs(
            [
                'Product' => $Product,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_FAVORITE_ADD_INITIALIZE, $event);

        if ($this->isGranted('ROLE_USER')) {
            $Customer = $this->getUser();
            $this->customerFavoriteProductRepository->addFavorite($Customer, $Product);
            $this->session->getFlashBag()->set('product_detail.just_added_favorite', $Product->getId());

            $event = new EventArgs(
                [
                    'Product' => $Product,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_FAVORITE_ADD_COMPLETE, $event);
            if ($request->get("supplier_page")) {
                return $this->json(['status' => 'success', 'message' => '商品はリストに保存されました']);
            }

            return $this->redirectToRoute('product_detail', ['id' => $Product->getId()]);
        } else {
            // 非会員の場合、ログイン画面を表示
            //  ログイン後の画面遷移先を設定
            $this->setLoginTargetPath($this->generateUrl('product_add_favorite', ['id' => $Product->getId()]));
            $this->session->getFlashBag()->set('eccube.add.favorite', true);

            $event = new EventArgs(
                [
                    'Product' => $Product,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_FAVORITE_ADD_COMPLETE, $event);

            return $this->redirectToRoute('mypage_login');
        }
    }

    /**
     * カートに追加.
     *
     * @Route("/products/add_cart/{id}", name="product_add_cart", methods={"POST","GET"}, requirements={"id" = "\d+"})
     */
    //app/Plugin/ProductOption/Controller/AddcartController.phpの内容を移植 20220829 kikuzawa
    public function addCart(Request $request, Product $Product)
    {
        // エラーメッセージの配列
        $errorMessages = [];
        if (!$this->checkVisibility($Product)) {
            throw new NotFoundHttpException();
        }

        $builder = $this->formFactory->createNamedBuilder(
            '',
            AddCartType::class,
            null,
            [
                'product' => $Product,
                'id_add_product_id' => false,
            ]
        );

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Product' => $Product,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_CART_ADD_INITIALIZE, $event);

        /* @var $form \Symfony\Component\Form\FormInterface */
        $form = $builder->getForm();
        $form->handleRequest($request);

        //送り先のメールアドレス(option1)が会員情報に存在するかチェック 20220812 kikuzawa
        if(isset($form->all()['productoption1'])){
            $recipientEmail = $form->get('productoption1')->getData();
            if($recipientEmail){
                $customer = $this->customerRepository->findBy(array('email' => $recipientEmail));
                if(!$customer){
                    $errorMessages[] = '存在しないメールアドレスです。';
                }
            }
        }

        if (!$form->isValid()) {
            foreach($form->all() as $child){
                $config = $child->getConfig();
                foreach($child->getErrors() as $error){
                    $errorMessages[] = $config->getOption('label') .':'. $error->getMessage();
                }
            }
        }

        $addCartData = $form->getData();
        $ProductClass = $this->entityManager->getRepository(ProductClass::class)->find($addCartData['product_class_id']);
        $limit = $ProductClass->getSaleLimit();
        if(!$ProductClass->isStockUnlimited()){
            $stock = $ProductClass->getStock();
        }
        if (!is_null($limit) || isset($stock)) {
            $Carts = $this->cartService->getCarts();
            $quantity = $addCartData['quantity'];
            foreach($Carts as $Cart){
                foreach($Cart->getCartItems() as $item){
                    if($item->getProductClass()->getId() == $ProductClass->getId())$quantity += $item->getQuantity();
                }
            }
            $productName = $ProductClass->getProduct()->getName();
            if ($ProductClass->hasClassCategory1()) {
                $productName .= ' - '.$ProductClass->getClassCategory1()->getName();
            }
            if ($ProductClass->hasClassCategory2()) {
                $productName .= ' - '.$ProductClass->getClassCategory2()->getName();
            }
            if (!is_null($limit) && $limit < $quantity ) {
                $errorMessages[] = trans('front.shopping.over_sale_limit', ['%product%' => $productName]);
            }
            if (isset($stock) && $stock < $quantity ) {
                $errorMessages[] = trans('front.shopping.out_of_stock', ['%product%' => $productName]);
            }
        }

        if(count($errorMessages) == 0){
            log_info(
                'カート追加処理開始',
                [
                    'product_id' => $Product->getId(),
                    'product_class_id' => $addCartData['product_class_id'],
                    'quantity' => $addCartData['quantity'],
                ]
            );

            // カートへ追加
            $ProductOptions = $Product->getProductOptions();

            $Options = [];
            foreach($ProductOptions as $ProductOption){
                $Option = $ProductOption->getOption();
                $option_key = 'productoption'. $Option->getId();
                $value = $form->get($option_key)->getData();
                if($Option){
                    $add = true;
                    if($Option->getType() == Option::SELECT_TYPE || $Option->getType() == Option::RADIO_TYPE ){
                        if($Option->getDisableCategory()){
                            if($Option->getDisableCategory() == $value){
                                $add = false;
                            }
                        }
                        $value = $value->getId();
                        if(strlen($value) == 0)$add = false;
                    }elseif($Option->getType() == Option::TEXT_TYPE || $Option->getType() == Option::TEXTAREA_TYPE || $Option->getType() == Option::NUMBER_TYPE){
                        if(strlen($value) == 0)$add = false;
                    }elseif($Option->getType() == Option::CHECKBOX_TYPE){
                        if(count($value) == 0){
                            $add = false;
                        }else{
                            $buff = $value;
                            $value = [];
                            foreach($buff as $categoryoption){
                                $value[] = $categoryoption->getId();
                            }
                        }
                    }elseif($Option->getType() == Option::DATE_TYPE){
                        if(is_null($value))$add = false;
                    }
                    if($add){
                        if(is_array($value)){
                            $Options[$Option->getId()] = $value;
                        }elseif(is_object($value)){
                            $Options[$Option->getId()] = $value->format('Y-m-d');
                        }else{
                            $Options[$Option->getId()] = (string)$value;
                        }
                    }
                }
            }

            $this->cartService->addProductOption($addCartData['product_class_id'], $Options, $addCartData['quantity']);

            // 明細の正規化
            $Carts = $this->cartService->getCarts();
            foreach ($Carts as $Cart) {
                $result = $this->purchaseFlow->validate($Cart, new PurchaseContext($Cart, $this->getUser()));
                // 復旧不可のエラーが発生した場合は追加した明細を削除.
                if ($result->hasError()) {
                    $this->cartService->removeProduct($addCartData['product_class_id']);
                    foreach ($result->getErrors() as $error) {
                        $errorMessages[] = $error->getMessage();
                    }
                }
                foreach ($result->getWarning() as $warning) {
                    $errorMessages[] = $warning->getMessage();
                }
            }
            $this->cartService->save();

            log_info(
                'カート追加処理完了',
                [
                    'product_id' => $Product->getId(),
                    'product_class_id' => $addCartData['product_class_id'],
                    'quantity' => $addCartData['quantity'],
                ]
            );

            $event = new EventArgs(
                [
                    'form' => $form,
                    'Product' => $Product,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_CART_ADD_COMPLETE, $event);
        }

        if ($event->getResponse() !== null) {
            return $event->getResponse();
        }

        if ($request->isXmlHttpRequest()) {
            // ajaxでのリクエストの場合は結果をjson形式で返す。

            // 初期化
            $done = null;
            $messages = [];

            if (empty($errorMessages)) {
                // エラーが発生していない場合
                $done = true;
                array_push($messages, trans('已新增到購物車'));
            } else {
                // エラーが発生している場合
                $done = false;
                $messages = $errorMessages;
            }

            $Customer = $this->getUser();
            $Point = $this->getPointCart($Cart, $Customer);
            $Carts = $this->cartService->getCarts();
            $quantity = 0;
            $Total = 0;
            foreach ($Carts as $Cart) {
                foreach ($Cart->getCartItems() as $item) {
                    if ($item->getProductClass()->getId() == $ProductClass->getId()){
                        $quantity = $item->getQuantity();
                    }
                }
                $Total = $Total + $Cart->getTotalPrice();
            }

            return $this->json(['done' => $done, 'messages' => $messages, 'total_price' => number_format($Total), 'add_point' => $Point, 'product_class_id' => $ProductClass->getId(), 'quantity' => $quantity]);
        } else {
            // ajax以外でのリクエストの場合はカート画面へリダイレクト
            if (empty($errorMessages)) {
                return $this->redirectToRoute('cart');
            }else{
                foreach ($errorMessages as $errorMessage) {
                    $this->addRequestError($errorMessage);
                }
                return $this->redirect($request->headers->get('referer'));
            }
        }
    }

    /**
     * ページタイトルの設定
     *
     * @param  null|array $searchData
     *
     * @return str
     */
    protected function getPageTitle($searchData)
    {
        if (isset($searchData['name']) && !empty($searchData['name'])) {
            return trans('front.product.search_result');
        } elseif (isset($searchData['category_id']) && $searchData['category_id']) {
            return $searchData['category_id']->getName();
        } else {
            return trans('front.product.all_products');
        }
    }

    /**
     * 閲覧可能な商品かどうかを判定
     *
     * @param Product $Product
     *
     * @return boolean 閲覧可能な場合はtrue
     */
    protected function checkVisibility(Product $Product)
    {
        $is_admin = $this->session->has('_security_admin');

        // 管理ユーザの場合はステータスやオプションにかかわらず閲覧可能.
        if (!$is_admin) {
            // 在庫なし商品の非表示オプションが有効な場合.
            // if ($this->BaseInfo->isOptionNostockHidden()) {
            //     if (!$Product->getStockFind()) {
            //         return false;
            //     }
            // }
            // 公開ステータスでない商品は表示しない.
            if ($Product->getStatus()->getId() !== ProductStatus::DISPLAY_SHOW) {
                return false;
            }
        }

        return true;
    }


    private function handlerSupplierProduct($Product)
    {
        $categorySupplier = $this->productRepository->getSupplierCategories();
        $is_supplier = false;
        foreach ($Product->getProductCategories() as $category) {
            if (in_array($category->getCategoryId(), $categorySupplier)) {
                $is_supplier = true;
            }
        }
        return $is_supplier;
    }

    function getPointCart($Cart, $Customer){
        $prime = $Customer['prime_member'];
        $urank = $Customer['owner_rank'];
        $alpha = 0;
        if($prime){
            $alpha = 3;
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

        return number_format( round(strval($Cart->getTotalPrice() * $alpha / 100)));
    }

    /**
     * @Route("/api/recently_viewed", name="api_recently_viewed", methods={"POST"})
     */
    public function getRecentlyViewed(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];

        if (empty($ids)) {
            return $this->json([]);
        }

        $products = $this->productRepository->findBy(['id' => $ids]);
        
        $productMap = [];
        foreach ($products as $product) {
            $productMap[$product->getId()] = $product;
        }

        $productData = [];
        foreach ($ids as $id) {
            if (!isset($productMap[$id])) continue;

            $product = $productMap[$id];

            $price = null;
            try {
                if (method_exists($product, 'getProductClasses')) {
                    $productClasses = $product->getProductClasses();
                    if (count($productClasses) > 0) {
                        foreach ($productClasses as $class) {
                            if ($class->isVisible()) {
                                if (method_exists($class, 'getPrice02IncTax')) {
                                    $price = $class->getPrice02IncTax();
                                    break;
                                } elseif (method_exists($class, 'getPrice02')) {
                                    $price = $class->getPrice02();
                                    break;
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                continue;
            }

            $formattedPrice = ($price !== null) ? number_format($price) : null;

            $productData[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $formattedPrice,
                'image' => '/html/upload/save_image/' . $product->getMainListImage(),
                'url' => $this->generateUrl('product_detail', ['id' => $product->getId()]),
            ];
        }

        return $this->json($productData);
    }
    
    /**
     *
     * @Route("/api/popular_products", name="api_popular_products", methods={"GET"})
     */
    public function getPopularProducts(Request $request)
    {
        $popularProducts = $this->productRepository->findTopSellingProducts(5);

        $productData = [];
        foreach ($popularProducts as $product) {
            $mainImage = $product->getMainListImage();
            $price = $product->getPrice02Min();
            $imagePath = $mainImage !== null
                ? '/html/upload/save_image/' . $mainImage->getFileName()
                : '/html/upload/save_image/no_image_product.png';
            $productData[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => number_format($price),
                'image' => $imagePath,
                'url' => $this->generateUrl('product_detail', ['id' => $product->getId()]),
            ];
        }

        return $this->json($productData);
    }
}
