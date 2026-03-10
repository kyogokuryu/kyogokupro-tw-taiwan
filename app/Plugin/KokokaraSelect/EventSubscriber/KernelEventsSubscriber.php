<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/17
 */

namespace Plugin\KokokaraSelect\EventSubscriber;


use Eccube\Entity\Product;
use Eccube\Repository\ProductRepository;
use Eccube\Service\CartService;
use Plugin\KokokaraSelect\Controller\KokokaraSelectController;
use Plugin\KokokaraSelect\Entity\KsCartSelectItem;
use Plugin\KokokaraSelect\Entity\KsCartSelectItemGroup;
use Plugin\KokokaraSelect\Service\ConfigHelperTrait;
use Plugin\KokokaraSelect\Service\KsCartHelper;
use Plugin\KokokaraSelect\Service\KsService;
use Plugin\KokokaraSelect\Service\VersionHelperTrait;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class KernelEventsSubscriber implements EventSubscriberInterface
{

    use ConfigHelperTrait;

    use VersionHelperTrait;

    protected $router;

    /** @var Session */
    protected $session;

    /** @var ProductRepository */
    protected $productRepository;

    /** @var CartService */
    protected $cartService;

    /** @var KsCartHelper */
    protected $ksCartHelper;

    /** @var KsService */
    protected $ksService;

    /** @var ContainerInterface */
    protected $container;

    public function __construct(
        Router $router,
        SessionInterface $session,
        ProductRepository $productRepository,
        CartService $cartService,
        KsCartHelper $ksCartHelper,
        KsService $ksService,
        ContainerInterface $container
    )
    {
        $this->router = $router;
        $this->session = $session;
        $this->productRepository = $productRepository;
        $this->cartService = $cartService;
        $this->ksCartHelper = $ksCartHelper;
        $this->ksService = $ksService;
        $this->container = $container;
    }

    /**
     * コントローラ制御
     *
     * @param FilterControllerEvent $event
     */
    public function onControllerBefore(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if ($route === 'admin_product_product_class') {
            // 選択商品が設定された商品かチェック
            $id = $request->get('id');

            /** @var Product $product */
            $product = $this->productRepository->find($id);

            if ($product) {

                $ksProduct = $product->getKsProduct();
                if ($ksProduct) {
                    // 規格設定は不可
                    $message = trans('kokokara_select.admin.setting.ks_product.valid');
                    $this->session->getFlashBag()->add('eccube.admin.warning', $message);

                    $event->setController(function () use ($id) {
                        $url = $this->router->generate('admin_product_product_edit', ['id' => $id]);
                        return new RedirectResponse($url);
                    });

                }
            }
        } else if ($route === "product_detail") {
            //　商品詳細
            $id = $request->get('id');

            /** @var Product $product */
            $product = $this->productRepository->find($id);

            if ($product) {

                $ksProductOption = $product->getKsProductOption();
                if ($ksProductOption && $ksProductOption->isSelectOnly()) {
                    // 選択専用商品の場合
                    throw new NotFoundHttpException();
                }

                if ($this->ksService->isKsProduct($product, true)) {
                    // 専用画面へ遷移
                    $event->setController(function () use ($id) {
                        $url = $this->router->generate('kokokara_select_list', ['id' => $id]);
                        return new RedirectResponse($url);
                    });
                }
            }
        } else if ($route === 'cart_buystep') {

            $cart = $this->cartService->getCart();
            foreach ($cart->getCartItems() as $cartItem) {

                $valid = true;
                $product = $cartItem->getProductClass()->getProduct();

                // 選択商品のみチェック
                if (!$this->ksService->isKsProduct($product)) {
                    continue;
                }

                if ($cartItem->getKsCartSelectItemGroups()->count() == 0) {
                    $valid = false;
                } else {
                    /** @var KsCartSelectItemGroup $ksCartSelectItemGroup */
                    foreach ($cartItem->getKsCartSelectItemGroups() as $ksCartSelectItemGroup) {


                        if (!$this->ksCartHelper->validKsCartSelectItemGroupProduct($product, $ksCartSelectItemGroup)) {
                            // 選択可能商品チェック
                            $valid = false;
                            break;
                        } else if (!$this->ksCartHelper->validKsCartSelectItemGroupQuantity($product, $ksCartSelectItemGroup)) {
                            // 数量チェック
                            $valid = false;
                            break;
                        }

                        /** @var KsCartSelectItem $ksCartSelectItem */
                        foreach ($ksCartSelectItemGroup->getKsCartSelectItems() as $ksCartSelectItem) {
                            $result = $this->ksCartHelper->checkKsCartSelectItemStockSingle($ksCartSelectItem);
                            if (!$result['result']) {
                                $valid = false;
                                break;
                            }
                        }
                    }
                }


                if (!$valid) {
                    // カート画面再表示
                    $event->setController(function () {
                        $url = $this->router->generate('cart');
                        return new RedirectResponse($url);
                    });
                }
            }
        } else if ($route === 'shopping_shipping_multiple') {

            // EC-CUBE 4.0.3 以降のみ固定選択商品の複数配送許可
            if ($this->isMultipleVersion()) {
                $checkOption = KsService::IS_KS_ORDER_NORMAL;
            } else {
                // 4.0.2以下
                $checkOption = KsService::IS_KS_ORDER_DEFAULT;
            }

            // 選択商品が選ばれている場合は利用不可
            $preOrderId = $this->cartService->getPreOrderId();
            if ($this->ksService->isKsOrder($preOrderId, $checkOption)) {

                $message = trans('kokokara_select.shopping.multi.cut', ['%kokokara_select%' => $this->getKokokaraSelectName()]);
                $this->session->getFlashBag()->add('eccube.front.warning', $message);

                $event->setController(function () {
                    $url = $this->router->generate('shopping');
                    return new RedirectResponse($url);
                });
            }
        } else if ($route === 'product_add_cart') {

            // カート投入
            // 選択商品が設定された商品かチェック
            $id = $request->get('id');

            /** @var Product $product */
            $product = $this->productRepository->find($id);
            if ($product) {
                if ($this->ksService->isKsProduct($product)
                    && $product->getKsProduct()->isDirectSelect()) {

                    /** @var KokokaraSelectController $addCartController */
                    $addCartController = $this->container->get(KokokaraSelectController::class);
                    $addCartController->setContainer($this->container);

                    // 不足リクエスト生成
                    $event->getRequest()->request
                        ->set('ksCartKey', "");

                    $event->getRequest()
                        ->request
                        ->set('selectItems', $this->ksCartHelper->createDirectSelectFormData($product));

                    $event->setController([$addCartController, 'addCart']);

                }
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => ["onControllerBefore"],
        ];
    }
}
