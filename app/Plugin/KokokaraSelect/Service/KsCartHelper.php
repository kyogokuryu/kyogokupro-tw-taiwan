<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/05
 */

namespace Plugin\KokokaraSelect\Service;


use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Cart;
use Eccube\Entity\CartItem;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Util\StringUtil;
use Plugin\KokokaraSelect\Entity\KsCartSelectItem;
use Plugin\KokokaraSelect\Entity\KsCartSelectItemGroup;
use Plugin\KokokaraSelect\Entity\KsSelectItem;
use Plugin\KokokaraSelect\Entity\KsSelectItemGroup;
use Plugin\KokokaraSelect\Repository\KsCartSelectItemGroupRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class KsCartHelper
{

    /** @var KsCartSelectItemGroupRepository */
    protected $ksCartSelectItemGroupRepository;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var KsSelectItemService */
    protected $ksSelectItemService;

    /** @var ContainerInterface */
    protected $container;

    /** @var KsService */
    protected $ksService;

    public function __construct(
        KsCartSelectItemGroupRepository $cartSelectItemGroupRepository,
        EntityManagerInterface $entityManager,
        KsSelectItemService $ksSelectItemService,
        ContainerInterface $container,
        KsService $ksService
    )
    {
        $this->ksCartSelectItemGroupRepository = $cartSelectItemGroupRepository;
        $this->entityManager = $entityManager;
        $this->ksSelectItemService = $ksSelectItemService;
        $this->container = $container;
        $this->ksService = $ksService;
    }

    /**
     * IDからKsCartKey取得
     * CartKeyチェックなし
     * @param $editId
     * @return string|null
     */
    public function getKsCartKeyForce($editId)
    {
        if (is_null($editId)) {
            return null;
        }

        /** @var KsCartSelectItemGroup $ksCartSelectItemGroup */
        $ksCartSelectItemGroup = $this->ksCartSelectItemGroupRepository->find($editId);

        if (!$ksCartSelectItemGroup) {
            return null;
        }

        return $ksCartSelectItemGroup->getKsCartKey();
    }

    /**
     * Id,CartKeyからKsCartKey取得
     *
     * @param $editId
     * @param string $cartKey
     * @return string|null
     */
    public function getKsCartKey($editId, $cartKey)
    {

        if (is_null($editId)) {
            return null;
        }

        /** @var KsCartSelectItemGroup $ksCartSelectItemGroup */
        $ksCartSelectItemGroup = $this->ksCartSelectItemGroupRepository->find($editId);

        if (!$ksCartSelectItemGroup) {
            return null;
        }

        if ($ksCartSelectItemGroup->getCartKey() != $cartKey) {
            return null;
        }

        return $ksCartSelectItemGroup->getKsCartKey();
    }

    /**
     * KsCartKey有効性チェック
     *
     * @param $ksCartKey
     * @param $cartKey
     * @return bool
     */
    public function validKsCartKey($ksCartKey, $cartKey)
    {

        /** @var KsCartSelectItemGroup $ksCartSelectItemGroup */
        $ksCartSelectItemGroup = $this->ksCartSelectItemGroupRepository->findOneBy(['ksCartKey' => $ksCartKey]);

        if ($ksCartSelectItemGroup) {
            if ($ksCartSelectItemGroup->getCartKey() == $cartKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * KsCartKey取得用ID有効性チェック
     *
     * @param $carts
     * @param Product $product
     * @param $editId
     * @return bool
     */
    public function checkKsCartKey($carts, Product $product, $editId)
    {

        if (is_null($editId)) {
            return true;
        }

        // 規格わけは発生させない
        $targetProductClass = null;
        /** @var ProductClass $productClass */
        foreach ($product->getProductClasses() as $productClass) {
            if ($productClass->isVisible()) {
                $targetProductClass = $productClass;
                break;
            }
        }

        /** @var Cart $cart */
        foreach ($carts as $cart) {

            $ksCartKey = $this->getKsCartKey($editId, $cart->getCartKey());

            /** @var CartItem $cartItem */
            foreach ($cart->getCartItems() as $cartItem) {
                if ($cartItem->getProductClass()->getId() == $targetProductClass->getId()) {

                    return $cartItem->isKsCartSelectItemGroup($ksCartKey);
                }
            }
        }

        return false;
    }

    /**
     * KsCartKey生成
     *
     * @return string
     */
    public function createKsCartKey()
    {
        do {
            $random = StringUtil::random(32);
            $tmpKsCartSelectItemGroup = $this->ksCartSelectItemGroupRepository->findOneBy(['ksCartKey' => $random]);

        } while ($tmpKsCartSelectItemGroup);

        return $random;
    }

    /**
     * 選択商品をCartItemに紐付け
     *
     * @param $carts
     * @param $ksCartKey
     * @param FormInterface $ksCartForm
     * @return string
     */
    public function setKsCartSelectItemGroup($carts, $ksCartKey, FormInterface $ksCartForm)
    {

        /** @var CartItem $addCartData */
        $addCartData = $ksCartForm->getData();
        $targetProductClass = $addCartData->getProductClass();

        // SelectItems
        $selectItemsForm = $ksCartForm->get('selectItems');

        $resultKsCartKey = "";

        // CartItemに選択商品紐付け
        /** @var Cart $cart */
        foreach ($carts as $cart) {

            $cartKey = $cart->getCartKey();
            if (!$this->validKsCartKey($ksCartKey, $cartKey)) {
                $ksCartKey = null;
            }

            /** @var CartItem $cartItem */
            foreach ($cart->getCartItems() as $cartItem) {

                // CartItem特定
                if ($cartItem->getProductClass()->getId() == $targetProductClass->getId()) {

                    // 新規設定
                    $ksCartSelectItemGroup = $cartItem->getKsCartSelectItemGroup($ksCartKey);
                    if (!$ksCartSelectItemGroup) {

                        $random = $this->createKsCartKey();

                        // カート persist
                        if (is_null($cartItem->getId())) {
                            $cart = $cartItem->getCart();
                            if (is_null($cart->getId())) {
                                $this->entityManager->persist($cartItem->getCart());
                            }

                            $this->entityManager->persist($cartItem);
                        }

                        $ksCartSelectItemGroup = new KsCartSelectItemGroup();
                        $ksCartSelectItemGroup
                            ->setCartKey($cartKey)
                            ->setCartItem($cartItem)
                            ->setKsCartKey($random);

                        $this->entityManager->persist($ksCartSelectItemGroup);

                    } else {

                        // 更新
                        /** @var KsCartSelectItem $ksCartSelectItem */
                        foreach ($ksCartSelectItemGroup->getKsCartSelectItems() as $ksCartSelectItem) {
                            $this->entityManager->remove($ksCartSelectItem);
                        }
                        $ksCartSelectItemGroup->clearKsCartSelectItem();
                        $cartItem->removeKsCartSelectItemGroup($ksCartSelectItemGroup);
                    }

                    /** @var FormInterface $selectItemForm */
                    foreach ($selectItemsForm as $selectItemForm) {

                        $selectQuantity = $selectItemForm->get('quantity')->getData();

                        if ($selectQuantity == 0) {
                            // 0件のものはカートへ追加しない
                            continue;
                        }

                        $ksCartSelectItem = new KsCartSelectItem();
                        $ksCartSelectItem
                            ->setKsCartSelectItemGroup($ksCartSelectItemGroup)
                            ->setKsSelectItem($selectItemForm->get('KsSelectItem')->getData())
                            ->setQuantity($selectQuantity);

                        $this->entityManager->persist($ksCartSelectItem);

                        $ksCartSelectItemGroup
                            ->addKsCartSelectItem($ksCartSelectItem);
                    }

                    $this->entityManager->persist($ksCartSelectItemGroup);

                    $cartItem->addKsCartSelectItemGroup($ksCartSelectItemGroup);
                    $resultKsCartKey = $ksCartSelectItemGroup->getKsCartKey();

                    return $resultKsCartKey;
                }
            }
        }

        return $resultKsCartKey;
    }

    /**
     * CartTypeのselectItemsに設定するdataを取得
     *
     * @param Product $product
     * @param $editId
     * @return array
     */
    public function getFormSelectItemsData(Product $product, $editId)
    {
        $KsProduct = $product->getKsProduct();

        if ($editId) {
            /** @var KsCartSelectItemGroup $ksCartSelectItemGroup */
            $ksCartSelectItemGroup = $this->ksCartSelectItemGroupRepository->find($editId);
        } else {
            $ksCartSelectItemGroup = null;
        }

        $dataList = [];
        if ($ksCartSelectItemGroup) {
            /** @var KsCartSelectItem $ksCartSelectItem */
            foreach ($ksCartSelectItemGroup->getKsCartSelectItems() as $ksCartSelectItem) {
                $dataList[$ksCartSelectItem->getKsSelectItem()->getId()] = $ksCartSelectItem->getQuantity();
            }
        }

        $data = [];
        /** @var KsSelectItemGroup $ksSelectItemGroup */
        foreach ($KsProduct->getKsSelectItemGroups() as $ksSelectItemGroup) {
            /** @var KsSelectItem $ksSelectItem */
            foreach ($ksSelectItemGroup->getKsSelectItems() as $ksSelectItem) {

                if ($ksSelectItem->getProductClass()->getProduct()->getStatus()->getId() != ProductStatus::DISPLAY_SHOW) {
                    continue;
                }

                if (isset($dataList[$ksSelectItem->getId()])) {
                    $quantity = $dataList[$ksSelectItem->getId()];
                } else {
                    $quantity = 0;
                }

                $data[] = [
                    'quantity' => $quantity,
                    'KsSelectItem' => $ksSelectItem,
                    'groupId' => $ksSelectItemGroup->getId(),
                ];
            }
        }

        return $data;
    }

    /**
     * 自動選択のForm用Data生成
     *
     * @param Product $product
     * @return array
     */
    public function createDirectSelectFormData(Product $product)
    {
        $data = [];

        if ($this->ksService->isKsProduct($product)) {

            $ksProduct = $product->getKsProduct();

            if ($ksProduct->isDirectSelect()) {
                // 自動選択
                /** @var KsSelectItemGroup $ksSelectItemGroup */
                foreach ($ksProduct->getKsSelectItemGroups() as $ksSelectItemGroup) {
                    /** @var KsSelectItem $ksSelectItem */
                    foreach ($ksSelectItemGroup->getKsSelectItems() as $ksSelectItem) {

                        $targetProduct = $ksSelectItem->getProductClass()->getProduct();
                        if ($targetProduct->getStatus()->getId() != ProductStatus::DISPLAY_SHOW) {
                            continue;
                        }

                        $data[] = [
                            'KsSelectItem' => $ksSelectItem,
                            'quantity' => (string)$ksSelectItem->getQuantity(),
                            'groupId' => (string)$ksSelectItemGroup->getId()
                        ];
                    }
                }
            }
        }

        return $data;
    }

    /**
     * カートより選択商品グループを削除
     *
     * @param $Carts
     * @param $ksCartKey
     * @return bool|int
     */
    public function removeKsCartSelectItemGroup($Carts, $ksCartKey)
    {
        /** @var Cart $cart */
        foreach ($Carts as $cart) {
            /** @var CartItem $cartItem */
            foreach ($cart->getCartItems() as $cartItem) {

                $ksCartSelectItemGroup = $cartItem->getKsCartSelectItemGroup($ksCartKey);
                if ($ksCartSelectItemGroup) {
                    // 削除
                    $cartItem->removeKsCartSelectItemGroup($ksCartSelectItemGroup);
                    $this->entityManager->remove($ksCartSelectItemGroup);

                    return $cartItem->getKsCartSelectItemGroups()->count();
                }
            }
        }

        return false;
    }

    /**
     * カート上の選択商品グループに無効な商品が含まれていないかチェック
     *
     * @param Product $product
     * @param KsCartSelectItemGroup $ksCartSelectItemGroup
     * @return bool
     */
    public function validKsCartSelectItemGroupProduct(Product $product, KsCartSelectItemGroup $ksCartSelectItemGroup)
    {

        if (!$product->getKsProduct()) {
            return true;
        }

        $ksWhiteSelectItems = [];
        $ksProduct = $product->getKsProduct();
        /** @var KsSelectItemGroup $ksSelectItemGroup */
        foreach ($ksProduct->getKsSelectItemGroups() as $ksSelectItemGroup) {
            /** @var KsSelectItem $ksSelectItem */
            foreach ($ksSelectItemGroup->getKsSelectItems() as $ksSelectItem) {
                // 選択可能な選択商品情報を設定
                $ksWhiteSelectItems[$ksSelectItemGroup->getId()][] = $ksSelectItem->getId();
            }
        }

        /** @var KsCartSelectItem $ksCartSelectItem */
        foreach ($ksCartSelectItemGroup->getKsCartSelectItems() as $ksCartSelectItem) {

            $ksSelectItem = $ksCartSelectItem->getKsSelectItem();

            /** @var Product $product */
            $product = $ksSelectItem->getProductClass()->getProduct();
            if ($product->getStatus()->getId() != ProductStatus::DISPLAY_SHOW) {
                // カートより商品削除
                $ksCartSelectItemGroup->removeKsCartSelectItem($ksCartSelectItem);
                return false;
            }

            $groupId = $ksSelectItem->getKsSelectItemGroup()->getId();

            if (!in_array($ksSelectItem->getId(), $ksWhiteSelectItems[$groupId])) {
                // カートより商品削除
                $ksCartSelectItemGroup->removeKsCartSelectItem($ksCartSelectItem);
                return false;
            }
        }

        return true;
    }

    /**
     * カート上の選択商品グループの数量が適切かチェック
     *
     * @param Product $product
     * @param KsCartSelectItemGroup $ksCartSelectItemGroup
     * @return bool
     */
    public function validKsCartSelectItemGroupQuantity(Product $product, KsCartSelectItemGroup $ksCartSelectItemGroup)
    {
        $groupLimitQuantity = [];
        $groupSelectQuantity = [];

        if (!$product->getKsProduct()) {
            return true;
        }

        $ksProduct = $product->getKsProduct();
        /** @var KsSelectItemGroup $ksSelectItemGroup */
        foreach ($ksProduct->getKsSelectItemGroups() as $ksSelectItemGroup) {

            $groupQuantity = $ksSelectItemGroup->getQuantity();

            $skip = true;
            /** @var KsSelectItem $ksSelectItem */
            foreach ($ksSelectItemGroup->getKsSelectItems() as $ksSelectItem) {
                if ($ksSelectItem->getProductClass()->getProduct()->getStatus()->getId() == ProductStatus::DISPLAY_SHOW) {
                    $skip = false;
                    break;
                }
            }

            if ($skip) {
                continue;
            }

            // 選択必須数量保持
            $groupLimitQuantity[$ksSelectItemGroup->getId()] = $groupQuantity;
        }

        /** @var KsCartSelectItem $ksCartSelectItem */
        foreach ($ksCartSelectItemGroup->getKsCartSelectItems() as $ksCartSelectItem) {

            $ksSelectItem = $ksCartSelectItem->getKsSelectItem();
            $ksSelectItemGroup = $ksSelectItem->getKsSelectItemGroup();

            // 登録グループID取得
            $groupKey = $ksSelectItemGroup->getId();

            // 選択数量のカウント
            if (!isset($groupSelectQuantity[$groupKey])) {
                $groupSelectQuantity[$groupKey] = (int)$ksCartSelectItem->getQuantity();
            } else {
                $groupSelectQuantity[$groupKey] += (int)$ksCartSelectItem->getQuantity();
            }

        }

        if (empty($groupSelectQuantity)) {
            // 選択無し
            return false;
        }

        foreach ($groupLimitQuantity as $key => $quantity) {

            if (!isset($groupSelectQuantity[$key])) {
                // 選択数量なし
                return false;
            }

            if ($groupSelectQuantity[$key] != $quantity) {
                // 選択数量が適切でない
                return false;
            }
        }

        return true;
    }

    /**
     * カート上の選択商品構成品在庫チェック
     *
     * @param KsCartSelectItem $ksCartSelectItem
     * @return array
     */
    public function checkKsCartSelectItemStockSingle(KsCartSelectItem $ksCartSelectItem)
    {
        $ksSelectItem = $ksCartSelectItem->getKsSelectItem();
        $stock = $ksCartSelectItem->getQuantity();

        return $this->ksSelectItemService->validStock($ksSelectItem, $stock);
    }

    /**
     * CartItem単位で集計した数量を返却
     *
     * @param CartItem $cartItem
     * @return array
     */
    public function getKsCartSelectItemQuantities(CartItem $cartItem)
    {
        if ($cartItem->getKsCartSelectItemGroups()->count() == 0) {
            return [];
        }

        $carItemQuantities = [];

        /** @var KsCartSelectItemGroup $ksCartSelectItemGroup */
        foreach ($cartItem->getKsCartSelectItemGroups() as $ksCartSelectItemGroup) {

            /** @var KsCartSelectItem $ksCartSelectItem */
            foreach ($ksCartSelectItemGroup->getKsCartSelectItems() as $ksCartSelectItem) {

                $ksSelectItem = $ksCartSelectItem->getKsSelectItem();

                if (isset($carItemQuantities[$ksSelectItem->getId()])) {

                    // 数量加算
                    $carItemQuantities[$ksSelectItem->getId()]['quantity'] += $ksCartSelectItem->getQuantity();

                } else {
                    $carItemQuantities[$ksSelectItem->getId()] = [
                        'ksSelectItem' => $ksSelectItem,
                        'quantity' => $ksCartSelectItem->getQuantity(),
                    ];
                }

            }
        }

        return $carItemQuantities;
    }

    /**
     * カート上の選択商品在庫チェック
     *
     * @param CartItem $cartItem
     * @return bool
     */
    public function checkKsCartSelectItemStockGroup(CartItem $cartItem)
    {
        if ($cartItem->getKsCartSelectItemGroups()->count() == 0) {
            return true;
        }

        // KsCartItem単位で集計


        return false;
    }

    /**
     * 選択商品カート追加時のリクエストか判定
     *
     * @return bool true:カート追加時のリクエスト
     */
    public function isSelectItemAddCartRoute()
    {
        /** @var Request $request */
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $route = $request->attributes->get('_route');

        if ($route === "product_select_add_cart"
            || $route === 'product_add_cart') {
            return true;
        }

        return false;
    }

    /**
     * 受注から選択商品をカートへセット
     *
     * @param $carts
     * @param $selectItemData
     */
    public function setKsCartSelectItemGroupByOrder($carts, $selectItemData)
    {
        // カート商品へ紐付け
        /** @var Cart $cart */
        foreach ($carts as $cart) {

            $cartKey = $cart->getCartKey();

            /** @var CartItem $cartItem */
            foreach ($cart->getCartItems() as $cartItem) {

                $cartProductClass = $cartItem->getProductClass();
                if (!$this->ksService->isKsProduct($cartItem)) {
                    continue;
                }

                if (!isset($selectItemData[$cartProductClass->getId()])) {
                    // 有効な選択商品なし
                    continue;
                }

                if (count($selectItemData[$cartProductClass->getId()]) == 0) {
                    // 有効な選択商品なし
                    continue;
                }


                foreach ($selectItemData[$cartProductClass->getId()] as $selectItemDataGroup) {

                    // カートにセット
                    $random = $this->createKsCartKey();

                    $ksCartSelectItemGroup = new KsCartSelectItemGroup();
                    $ksCartSelectItemGroup
                        ->setCartKey($cartKey)
                        ->setCartItem($cartItem)
                        ->setKsCartKey($random);

                    $this->entityManager->persist($ksCartSelectItemGroup);

                    foreach ($selectItemDataGroup as $selectItemDatum) {

                        $selectQuantity = $selectItemDatum['quantity'];

                        if ($selectQuantity == 0) {
                            // 0件のものはカートへ追加しない
                            continue;
                        }

                        /** @var KsSelectItem $ksSelectItem */
                        $ksSelectItem = $selectItemDatum['KsSelectItem'];

                        $ksCartSelectItem = new KsCartSelectItem();
                        $ksCartSelectItem
                            ->setKsCartSelectItemGroup($ksCartSelectItemGroup)
                            ->setKsSelectItem($ksSelectItem)
                            ->setQuantity($selectQuantity);

                        $this->entityManager->persist($ksCartSelectItem);

                        $ksCartSelectItemGroup
                            ->addKsCartSelectItem($ksCartSelectItem);
                    }

                    $this->entityManager->persist($ksCartSelectItemGroup);

                    $cartItem->addKsCartSelectItemGroup($ksCartSelectItemGroup);
                }
            }
        }

    }

    /**
     * 選択商品カート数量の最適化
     *
     * @param $carts
     * @param $parentQuantityList
     * @return array
     */
    public function optimizeData($carts, $parentQuantityList)
    {
        $optimizeList = [];

        /** @var Cart $cart */
        foreach ($carts as $cart) {

            /** @var CartItem $cartItem */
            foreach ($cart->getCartItems() as $cartItem) {

                $productClass = $cartItem->getProductClass();

                // 選択商品の場合
                if (isset($parentQuantityList[$productClass->getId()])) {

                    $parentQuantity = $parentQuantityList[$productClass->getId()];

                    // CartItemの状態が適切か判定
                    if ($parentQuantity > $cartItem->getKsCartSelectItemGroups()->count()) {
                        $diff = $parentQuantity - $cartItem->getKsCartSelectItemGroups()->count();
                        if ($diff == $parentQuantity) {
                            // 削除
                            $optimizeList[$productClass->getId()] = 0;
                        } else {
                            // 減らす
                            $optimizeList[$productClass->getId()] = $diff;
                        }
                    }
                }
            }
        }

        return $optimizeList;
    }
}
