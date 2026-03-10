<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/03
 */

namespace Plugin\KokokaraSelect\Controller;


use Eccube\Controller\AbstractController;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\CartItem;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Form\Type\AddCartType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\ProductClassRepository;
use Eccube\Service\CartService;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Eccube\Service\PurchaseFlow\PurchaseFlowResult;
use Plugin\KokokaraSelect\Config\ConfigSetting;
use Plugin\KokokaraSelect\Service\KsCartHelper;
use Plugin\KokokaraSelect\Service\KsService;
use Plugin\KokokaraSelect\Service\PlgConfigService\ConfigService;
use Plugin\KokokaraSelect\Service\PluginLinkService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Eccube\Repository\CustomerFavoriteProductRepository;//お気に入り判定 20211123 kikuzawa


class KokokaraSelectController extends AbstractController
{

    /**
     * @var ProductClassRepository
     */
    protected $productClassRepository;

    /** @var CartService */
    protected $cartService;

    /** @var KsCartHelper */
    protected $ksCartHelper;

    /** @var PurchaseFlow */
    protected $purchaseFlow;

    /** @var KsService */
    protected $ksService;

    /** @var ConfigService */
    protected $configService;

    /** @var PluginLinkService */
    protected $pluginLinkService;

    /** @var BaseInfo */
    protected $BaseInfo;

    /**
     * @var CustomerFavoriteProductRepository
     */
    protected $customerFavoriteProductRepository;//お気に入り判定 20211123 kikuzawa

    public function __construct(
        ProductClassRepository $productClassRepository,
        CartService $cartService,
        KsCartHelper $ksCartHelper,
        PurchaseFlow $cartPurchaseFlow,
        KsService $ksService,
        ConfigService $configService,
        PluginLinkService $pluginLinkService,
        CustomerFavoriteProductRepository $customerFavoriteProductRepository,//お気に入り判定 20211123 kikuzawa
        BaseInfoRepository $baseInfoRepository
    )
    {
        $this->productClassRepository = $productClassRepository;
        $this->cartService = $cartService;
        $this->ksCartHelper = $ksCartHelper;
        $this->purchaseFlow = $cartPurchaseFlow;
        $this->ksService = $ksService;
        $this->configService = $configService;
        $this->pluginLinkService = $pluginLinkService;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->customerFavoriteProductRepository = $customerFavoriteProductRepository;//お気に入り判定 20211123 kikuzawa
    }

    /**
     * @Route("/products/select/list/{id}/new", name="kokokara_select_list", methods={"GET"}, requirements={"id" = "\d+"})
     * @Route("/products/select/list/{id}/edit/{editId}", name="kokokara_select_list_edit", methods={"GET"}, requirements={"id" = "\d+", "eidtId" = "\d+"})
     * @Template("KokokaraSelect/Resource/template/default/Product/list.twig")
     *
     * @param Request $request
     * @param Product $product
     * @param string $editId
     * @return string[]
     */
    public function index(Request $request, Product $product, $editId = null)
    {

        if (!$this->checkVisibility($product)) {
            throw new NotFoundHttpException();
        }

        // 選択商品用CartKeyチェック
        if (!$this->ksCartHelper->checkKsCartKey($this->cartService->getCarts(), $product, $editId)) {
            throw new NotFoundHttpException();
        }

        $builder = $this->formFactory->createNamedBuilder(
            '',
            AddCartType::class,
            null,
            [
                'product' => $product,
                'id_add_product_id' => false,
                'kokokara_select' => true,
                'ks_cart_edit_id' => $editId
            ]
        );

        $form = $builder->getForm();

        // デフォルトのグループ名称取得
        $defaultGroupName = $this->configService->getKeyString(ConfigSetting::SETTING_KEY_GROUP_DEFAULT_NAME);

        // プラグイン連携チェック
        $tabExActive = $this->pluginLinkService->isActivePlugin('TagEx2');

        // Base 在庫切れ商品の非表示判定
        $nonStockHidden = $this->BaseInfo->isOptionNostockHidden();

        //お気に入り判定 20211123 kikuzawa
        $is_favorite = false;
        if ($this->isGranted('ROLE_USER')) {
            $Customer = $this->getUser();
            $is_favorite = $this->customerFavoriteProductRepository->isFavorite($Customer, $product);
        }

        //お気に入り数の合計を取得 20211127 kikuzawa
        $favorite_total = 0;
        $favorite_total = $this->customerFavoriteProductRepository->getFavoriteTotal($product);

        return [
            'subtitle' => $product->getName(),
            'Product' => $product,
            'is_favorite' => $is_favorite,//お気に入り判定 20211123 kikuzawa
            'favorite_total' => $favorite_total,//お気に入り数の合計を取得 20211127 kikuzawa
            'form' => $form->createView(),
            'editId' => $editId,
            'DefaultGroupName' => $defaultGroupName,
            'TabExActive' => $tabExActive,
            'NonStockHidden' => $nonStockHidden
        ];
    }

    /**
     * カートに追加
     *
     * @Route("/products/select/add_cart/{id}", name="product_select_add_cart", methods={"POST", "GET"}, requirements={"id" = "\d+"})
     *
     * @param Request $request
     * @param Product $product
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addCart(Request $request, Product $product)
    {

        // エラーメッセージの配列
        $errorMessages = [];
        if (!$this->checkVisibility($product)) {
            throw new NotFoundHttpException();
        }

        $builder = $this->formFactory->createNamedBuilder(
            '',
            AddCartType::class,
            null,
            [
                'product' => $product,
                'id_add_product_id' => false,
                'kokokara_select' => true,
            ]
        );

        /* @var $form \Symfony\Component\Form\FormInterface */
        $form = $builder->getForm();
        $form->handleRequest($request);

        if (!$form->isValid()) {
            throw new NotFoundHttpException();
        }

        /** @var CartItem $addCartData */
        $addCartData = $form->getData();

        log_info(
            '[KokokaraSelect]選択商品カート追加処理開始',
            [
                'product_id' => $product->getId(),
                'product_class_id' => $addCartData['product_class_id'],
                'quantity' => $addCartData['quantity'],
                'selectItems' => $addCartData['selectItems'],
            ]
        );

        // 事前に選択商品用カートキー取得
        $ksCartKey = $this->ksCartHelper->getKsCartKeyForce($form->get('ksCartKey')->getData());

        if (!$ksCartKey) {
            // カート追加
            $this->cartService->addProduct($addCartData['product_class_id'], $addCartData['quantity']);
        }

        // CartItemに選択商品紐付け
        $activeKsCartKey = $this->ksCartHelper->setKsCartSelectItemGroup($this->cartService->getCarts(), $ksCartKey, $form);
        $this->ksService->setActiveKsCartKey($activeKsCartKey);

        // 明細の正規化
        $Carts = $this->cartService->getCarts();
        foreach ($Carts as $Cart) {
            // 受注との紐付けをリセット
            $Cart->setPreOrderId(null);

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

        $selectItemGroupCount = 0;
        // 新規登録でかつエラーがある場合追加商品を外す
        if (count($errorMessages) > 0 && !$ksCartKey) {
            $selectItemGroupCount = $this->ksCartHelper->removeKsCartSelectItemGroup($Carts, $activeKsCartKey);
        }

        $this->cartService->save();

        if (count($errorMessages) > 0 && !$ksCartKey) {
            if ($selectItemGroupCount === 0) {
                // Group削除により0件となった場合
                $this->cartService->removeProduct($addCartData['product_class_id']);
            } else if ($selectItemGroupCount > 0) {
                foreach ($this->cartService->getCarts() as $Cart) {
                    foreach ($Cart->getCartItems() as $cartItem) {
                        if ($cartItem->getProductClass()->getId() == $addCartData['product_class_id']) {
                            if ($cartItem->getQuantity() > $selectItemGroupCount) {
                                // 数量が合わない場合減らす
                                $this->cartService->addProduct($addCartData['product_class_id'], -1);
                            }
                        }
                    }
                }
            }

            // 明細正常化
            foreach ($this->cartService->getCarts() as $Cart) {
                $this->purchaseFlow->validate($Cart, new PurchaseContext($Cart, $this->getUser()));
            }
            $this->cartService->save();
        }

        $this->ksService->setActiveKsCartKey(null);

        log_info(
            '[KokokaraSelect]選択商品カート追加処理完了',
            [
                'product_id' => $product->getId(),
                'product_class_id' => $addCartData['product_class_id'],
                'quantity' => $addCartData['quantity'],
                'selectItems' => $addCartData['selectItems'],
            ]
        );

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

            return $this->json(['done' => $done, 'messages' => $messages]);
        } else {
            // ajax以外でのリクエストの場合はカート画面へリダイレクト
            foreach ($errorMessages as $errorMessage) {
                $this->addRequestError($errorMessage);
            }

            return $this->redirectToRoute('cart');
        }

    }

    /**
     * @Route("/products/select/list/{id}/delete/{editId}", name="kokokara_select_list_delete", methods={"PUT"}, requirements={"id" = "\d+", "eidtId" = "\d+"})
     *
     * @param Request $request
     * @param ProductClass $productClass
     * @param null $editId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteCartSelectItemGroup(Request $request, ProductClass $productClass, $editId)
    {

        log_info('[KokokaraSelect]選択商品の削除処理開始', ['product_class_id' => $productClass->getId(), 'editId' => $editId]);

        $product = $productClass->getProduct();

        if (!$this->checkVisibility($product)) {
            log_info('商品が存在しないため、カート画面へredirect', ['product_class_id' => $productClass->getId()]);

            return $this->redirectToRoute('cart');
        }

        // Keyチェック
        if (!$this->ksCartHelper->checkKsCartKey($this->cartService->getCarts(), $product, $editId)) {
            log_info('無効な選択商品用ID', ['editId' => $editId]);
            throw new NotFoundHttpException();
        }

        // カート情報から削除
        $Carts = $this->cartService->getCarts();
        $ksCartKey = $this->ksCartHelper->getKsCartKeyForce($editId);

        $selectItemGroupCount = $this->ksCartHelper->removeKsCartSelectItemGroup($Carts, $ksCartKey);

        if ($selectItemGroupCount == 0) {
            // Group削除により0件となった場合
            $this->cartService->removeProduct($productClass);
        } else {
            $this->cartService->addProduct($productClass, -1);
        }

        // カートを取得して明細の正規化を実行
        $Carts = $this->cartService->getCarts();
        $this->execPurchaseFlow($Carts);

        log_info('[KokokaraSelect]選択商品の削除処理終了', ['product_class_id' => $productClass->getId(), 'editId' => $editId]);

        return $this->redirectToRoute('cart');
    }

    /**
     * 閲覧可能な商品かどうかを判定
     *
     * @param Product $Product
     *
     * @return boolean 閲覧可能な場合はtrue
     */
    private function checkVisibility(Product $Product)
    {
        $is_admin = $this->session->has('_security_admin');

        // 管理ユーザの場合はステータスやオプションにかかわらず閲覧可能.
        if (!$is_admin) {
            // 公開ステータスでない商品は表示しない.
            if ($Product->getStatus()->getId() !== ProductStatus::DISPLAY_SHOW) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $Carts
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function execPurchaseFlow($Carts)
    {
        /** @var PurchaseFlowResult[] $flowResults */
        $flowResults = array_map(function ($Cart) {
            $purchaseContext = new PurchaseContext($Cart, $this->getUser());

            return $this->purchaseFlow->validate($Cart, $purchaseContext);
        }, $Carts);

        // 復旧不可のエラーが発生した場合はカートをクリアして再描画
        $hasError = false;
        foreach ($flowResults as $result) {
            if ($result->hasError()) {
                $hasError = true;
                foreach ($result->getErrors() as $error) {
                    $this->addRequestError($error->getMessage());
                }
            }
        }
        if ($hasError) {
            $this->cartService->clear();

            return $this->redirectToRoute('cart');
        }

        $this->cartService->save();

        foreach ($flowResults as $index => $result) {
            foreach ($result->getWarning() as $warning) {
                if ($Carts[$index]->getItems()->count() > 0) {
                    $cart_key = $Carts[$index]->getCartKey();
                    $this->addRequestError($warning->getMessage(), "front.cart.${cart_key}");
                } else {
                    // キーが存在しない場合はグローバルにエラーを表示する
                    $this->addRequestError($warning->getMessage());
                }
            }
        }
    }
}
