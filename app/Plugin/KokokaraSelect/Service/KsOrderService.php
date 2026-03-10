<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/09
 */

namespace Plugin\KokokaraSelect\Service;


use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\CartItem;
use Eccube\Entity\ItemInterface;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Entity\Master\TaxType;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Entity\TaxRule;
use Eccube\Repository\OrderItemRepository;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Service\CartService;
use Plugin\KokokaraSelect\Entity\KsCartSelectItem;
use Plugin\KokokaraSelect\Entity\KsCartSelectItemGroup;
use Plugin\KokokaraSelect\Entity\KsOrderItemEx;
use Plugin\KokokaraSelect\Entity\KsSelectItem;
use Plugin\KokokaraSelect\Entity\KsSelectItemGroup;
use Plugin\KokokaraSelect\Service\PurchaseFlow\KsOrderItemHelper;
use Plugin\KokokaraSelect\Service\PurchaseFlow\Processor\KokokaraSelectProcessor;

class KsOrderService
{

    use ConfigHelperTrait;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var CartService */
    protected $cartService;

    /** @var TaxRuleRepository */
    protected $taxRuleRepository;

    /** @var KsOrderItemService */
    protected $ksOrderItemService;

    /** @var KsService */
    protected $ksService;

    /** @var KsSelectItemGroupService */
    protected $ksSelectItemGroupService;

    /** @var KsSelectItemService */
    protected $ksSelectItemService;

    /** @var KsOrderItemHelper */
    protected $ksOrderItemHelper;

    public function __construct(
        CartService $cartService,
        EntityManagerInterface $entityManager,
        KsOrderItemService $ksOrderItemService,
        KsService $ksService,
        KsSelectItemGroupService $ksSelectItemGroupService,
        KsSelectItemService $ksSelectItemService,
        KsOrderItemHelper $ksOrderItemHelper
    )
    {

        $this->cartService = $cartService;
        $this->entityManager = $entityManager;
        $this->ksOrderItemService = $ksOrderItemService;
        $this->ksService = $ksService;
        $this->ksSelectItemGroupService = $ksSelectItemGroupService;
        $this->ksSelectItemService = $ksSelectItemService;
        $this->ksOrderItemHelper = $ksOrderItemHelper;
    }

    /**
     * @return KsSelectItemService
     */
    public function getKsSelectItemService()
    {
        return $this->ksSelectItemService;
    }

    /**
     * 選択商品用のOrderItemType取得
     *
     * @return object|null
     */
    public function getKsOrderItemType()
    {
        return $this->ksOrderItemService->getKsOrderItemType();
    }

    /**
     * 商品用のOrderItemType取得
     *
     * @return object|null
     */
    public function getProductOrderItemType()
    {
        return $this->ksOrderItemService->getProductOrderItemType();
    }

    /**
     * 受注明細へ選択商品情報追加
     *
     * @param Order $Order
     */
    public function addKsOrderItem(Order $Order)
    {

        $cart = $this->cartService->getCart();
        if($cart == null){ return; }

        // 選択商品用のOrderItemType取得
        /** @var OrderItemType $ksOrderItemType */
        $ksOrderItemType = $this->getKsOrderItemType();

        // 税関連
        $taxRuleRepository = $this->entityManager->getRepository(TaxRule::class);
        $TaxExcluded = $this->entityManager
            ->find(TaxDisplayType::class, TaxDisplayType::EXCLUDED);
        $taxType = $this->entityManager->find(TaxType::class, TaxType::TAXATION);

        /** @var CartItem $cartItem */
        foreach ($cart->getCartItems() as $key => $cartItem) {

            $baseProduct = $cartItem->getProductClass()->getProduct();
            $index = 1;

            /** @var KsCartSelectItemGroup $ksCartSelectItemGroup */
            foreach ($cartItem->getKsCartSelectItemGroups() as $ksCartSelectItemGroup) {

                $parentProductClass = $ksCartSelectItemGroup->getCartItem()->getProductClass();

                // 親となるOrderItem取得
                /** @var OrderItem $parentOrderItem */
                $parentOrderItem = $this->getParentOrderItem($Order, $parentProductClass);

                // 配送先情報取得
                $targetShipping = $parentOrderItem->getShipping();

                if (!$parentOrderItem) {
                    // 親情報なし
                    continue;
                }
                if($parentOrderItem->getQuantity() < $index){
                    break;
                }


                /** @var KsCartSelectItem $ksCartSelectItem */
                foreach ($ksCartSelectItemGroup->getKsCartSelectItems() as $ksCartSelectItem) {

                    // 0点のデータは作成しない
                    if ($ksCartSelectItem->getQuantity() == 0) continue;

                    $productClass = $ksCartSelectItem->getKsSelectItem()->getProductClass();
                    $product = $productClass->getProduct();

                    $ksSelectItem = $ksCartSelectItem->getKsSelectItem();

                    $parentKsProduct = $baseProduct->getKsProduct();

                    $name = $this->ksOrderItemHelper->getKsSelectOrderItemName(
                        $parentKsProduct,
                        '',
                        $baseProduct->getName(),
                        $index,
                        $this->ksSelectItemService->getKsProductClassName($productClass)
                    );

                    $orderItem = new OrderItem();

                    // ベース情報設定
                    $orderItem
                        ->setOrder($Order)
                        ->setShipping($targetShipping)
                        ->setProduct($product)
                        ->setProductClass($productClass)
                        ->setProductName($name)
                        ->setProductCode($productClass->getCode())
                        ->setPrice(0)
                        ->setQuantity($ksCartSelectItem->getQuantity())
                        ->setOrderItemType($ksOrderItemType)
                        ->setProcessorName(KokokaraSelectProcessor::class);

                    $TaxRule = $taxRuleRepository->getByRule($product, $productClass);

                    // 税関連情報設定
                    $orderItem
                        ->setTax(0)
                        ->setTaxRate($TaxRule->getTaxRate())
                        ->setRoundingType($TaxRule->getRoundingType())
                        ->setTaxRuleId($TaxRule->getId())
                        ->setTaxType($taxType)
                        ->setTaxDisplayType($TaxExcluded);

                    // 拡張情報設定
                    $ksOrderItemEx = new KsOrderItemEx();
                    $ksOrderItemEx
                        ->setOrderItem($orderItem)
                        ->setParentOrderItem($parentOrderItem)
                        ->setKsSelectItemId($ksSelectItem->getId())
                        ->setKsSelectItemGroupId($ksSelectItem->getKsSelectItemGroup()->getId())
                        ->setGroupId($index)
                        ->setProductName($this->ksSelectItemService->getKsProductClassName($productClass));

                    $this->entityManager->persist($ksOrderItemEx);

                    // 受注明細へ紐付け
                    $orderItem->setKsOrderItemEx($ksOrderItemEx);

                    // 親OrderItemとリレーション設定
                    $parentOrderItem
                        ->addKsOrderItemChildren($ksOrderItemEx);

                    $Order->addOrderItem($orderItem);

                }

                $index++;
            }

        }

    }

    /**
     * 受注での選択商品チェック
     *
     * @param Order $order
     * @return array
     */
    public function validOrderItem(Order $order)
    {

        $validResults = [];
        $targetQuantities = [];

        /** @var OrderItem $orderItem */
        foreach ($order->getOrderItems() as $orderItem) {

            if (!$orderItem->isProduct()) continue;

            $productClass = $orderItem->getProductClass();

            // 選択商品(親)の場合 適切に選択されているか確認
            if ($this->ksService->isKsProduct($orderItem)) {
                $validResults[$productClass->getId()] = $this->ksOrderItemService->validOrderItem($orderItem);

                if ($validResults[$productClass->getId()]['valid']) {
                    // 数量計対象取得
                    /** @var KsOrderItemEx $ksOrderItemChild */
                    foreach ($orderItem->getKsOrderItemChildren() as $ksOrderItemChild) {
                        if ($ksOrderItemChild->getKsSelectItem()) {
                            $ksSelectItemId = $ksOrderItemChild->getKsSelectItem()->getId();
                        } else {
                            $ksSelectItemId = $ksOrderItemChild->getKsSelectItemId();
                        }
                        $targetQuantities[$ksSelectItemId] = $ksOrderItemChild;
                    }
                }
            }
        }

        // 数量取得
        $quantities = $this->getQuantityByKsSelectItem($order);

        // 在庫チェック
        foreach ($quantities as $ksSelectItemId => $quantity) {

            if (!isset($targetQuantities[$ksSelectItemId])) {
                // 構成要素エラーとなったものは対象外
                continue;
            }

            /** @var KsOrderItemEx $ksOrderItemEx */
            $ksOrderItemEx = $targetQuantities[$ksSelectItemId];
            $ksSelectItem = $ksOrderItemEx->getKsSelectItem();

            if ($ksSelectItem) {
                $stockValid = $this->ksSelectItemService->validStock($ksSelectItem, $quantity);

                if (!$stockValid['result']) {
                    $validResults[$ksSelectItem->getProductClass()->getId()]['valid'] = false;
                    $validResults[$ksSelectItem->getProductClass()->getId()]['NGStock'] = $stockValid['stock'];
                    $validResults[$ksSelectItem->getProductClass()->getId()]['TargetKsSelectItem'] = $ksSelectItem;
                }
            }
        }

        return $validResults;
    }

    /**
     * 受注情報からKsSelectItem単位での数量を取得
     * [KsSelectItem単位のため選択商品構成品のみ]
     *
     * @param Order $order
     * @return array
     */
    public function getQuantityByKsSelectItem(Order $order)
    {
        $quantities = [];

        /** @var OrderItem $orderItem */
        foreach ($order->getOrderItems() as $orderItem) {

            if ($orderItem->isKsSelectItem()) {
                // 選択商品カウント
                $ksOrderItemEx = $orderItem->getKsOrderItemEx();
                $ksSelectItemId = $ksOrderItemEx->getKsSelectItemId();
                if (isset($quantities[$ksSelectItemId])) {
                    $quantities[$ksSelectItemId] += $ksOrderItemEx->getOrderItem()->getQuantity();
                } else {
                    $quantities[$ksSelectItemId] = $ksOrderItemEx->getOrderItem()->getQuantity();
                }
            }
        }

        return $quantities;
    }

    /**
     * 受注情報からProductClass単位での数量を取得
     *
     * @param Order $order
     * @return array
     */
    public function getQuantityByProductClass(Order $order)
    {
        $quantities = [];

        /** @var OrderItem $orderItem */
        foreach ($order->getOrderItems() as $orderItem) {
            if ($orderItem->isProduct() || $orderItem->isKsSelectItem()) {

                if (isset($quantities[$orderItem->getProductClass()->getId()])) {
                    $quantities[$orderItem->getProductClass()->getId()]['quantity'] += $orderItem->getQuantity();
                } else {
                    $quantities[$orderItem->getProductClass()->getId()] = [
                        'productClass' => $orderItem->getProductClass(),
                        'quantity' => $orderItem->getQuantity()
                    ];
                }
            }
        }

        return $quantities;
    }

    /**
     * 販売済みチェック
     *
     * @param Product $product
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isBuyIngProduct(Product $product)
    {
        if ($product && $product->getId()) {

            /** @var OrderItemType $productOrderItemType */
            $productOrderItemType = $this->getProductOrderItemType();

            /** @var OrderItemRepository $orderItemRepository */
            $orderItemRepository = $this->entityManager->getRepository(OrderItem::class);
            $qb = $orderItemRepository->createQueryBuilder('oi')
                ->select('count(oi.id)')
                ->andWhere('oi.OrderItemType = :orderItemType')
                ->setParameter('orderItemType', $productOrderItemType)
                ->andWhere('oi.Product = :product')
                ->setParameter('product', $product);

            $result = $qb->getQuery()->getSingleScalarResult();

            return ($result == 0 ? false : true);
        }

        return false;
    }

    /**
     * Orderから選択商品の親となるOrderItemを取得
     *
     * @param Order $order
     * @param ProductClass $targetProductClass
     * @return ItemInterface|null
     */
    private function getParentOrderItem(Order $order, ProductClass $targetProductClass)
    {
        foreach ($order->getItems() as $item) {
            if ($item->isProduct()
                && $item->getProductClass()->getId() == $targetProductClass->getId()) {

                return $item;
            }
        }

        return null;
    }

    /**
     * 固定選択商品のセット内容追加
     *
     * @param OrderItem $parentOrderItem
     * @param $diff
     * @param $groupCount
     * @param $admin
     */
    public function addKsOrderItemDirectSelect(OrderItem $parentOrderItem, $diff, $groupCount, $admin)
    {

        $ksProduct = $parentOrderItem->getKsProduct();

        if (!$ksProduct) {
            return;
        }

        if (!$ksProduct->isDirectSelect()) {
            return;
        }

        $baseProduct = $parentOrderItem->getProductClass()->getProduct();

        $Order = $parentOrderItem->getOrder();
        $targetShipping = $parentOrderItem->getShipping();

        // 選択商品用のOrderItemType取得
        /** @var OrderItemType $ksOrderItemType */
        $ksOrderItemType = $this->getKsOrderItemType();

        // OrderFlow用に商品のOrderItemType取得
        /** @var OrderItemType $productOrderItemType */
        $productOrderItemType = $this->getProductOrderItemType();

        // 税関連
        $taxRuleRepository = $this->entityManager->getRepository(TaxRule::class);
        $TaxExcluded = $this->entityManager
            ->find(TaxDisplayType::class, TaxDisplayType::EXCLUDED);
        $taxType = $this->entityManager->find(TaxType::class, TaxType::TAXATION);

        $index = ($groupCount + 1);

        for ($i = 0; $i < $diff; $i++) {

            /** @var KsSelectItemGroup $ksSelectItemGroup */
            foreach ($ksProduct->getKsSelectItemGroups() as $ksSelectItemGroup) {

                /** @var KsSelectItem $ksSelectItem */
                foreach ($ksSelectItemGroup->getKsSelectItems() as $ksSelectItem) {

                    $productClass = $ksSelectItem->getProductClass();
                    $product = $productClass->getProduct();

                    if ($product->getStatus()->getId() != ProductStatus::DISPLAY_SHOW) {
                        continue;
                    }

                    $parentKsProduct = $baseProduct->getKsProduct();

                    $name = $this->ksOrderItemHelper->getKsSelectOrderItemName(
                        $parentKsProduct,
                        '',
                        $baseProduct->getName(),
                        $index,
                        $this->ksSelectItemService->getKsProductClassName($productClass)
                    );

                    $orderItem = new OrderItem();

                    // ベース情報設定
                    $orderItem
                        ->setOrder($Order)
                        ->setShipping($targetShipping)
                        ->setProduct($product)
                        ->setProductClass($productClass)
                        ->setProductName($name)
                        ->setProductCode($productClass->getCode())
                        ->setPrice(0)
                        ->setQuantity($ksSelectItem->getQuantity())
                        ->setOrderItemType($ksOrderItemType)
                        ->setProcessorName(KokokaraSelectProcessor::class);

                    $TaxRule = $taxRuleRepository->getByRule($product, $productClass);

                    // 税関連情報設定
                    $orderItem
                        ->setTax(0)
                        ->setTaxRate($TaxRule->getTaxRate())
                        ->setRoundingType($TaxRule->getRoundingType())
                        ->setTaxRuleId($TaxRule->getId())
                        ->setTaxType($taxType)
                        ->setTaxDisplayType($TaxExcluded);

                    // 拡張情報設定
                    $ksOrderItemEx = new KsOrderItemEx();
                    $ksOrderItemEx
                        ->setOrderItem($orderItem)
                        ->setParentOrderItem($parentOrderItem)
                        ->setKsSelectItemId($ksSelectItem->getId())
                        ->setKsSelectItemGroupId($ksSelectItem->getKsSelectItemGroup()->getId())
                        ->setGroupId($index)
                        ->setProductName($this->ksSelectItemService->getKsProductClassName($productClass));

                    $this->entityManager->persist($ksOrderItemEx);

                    // 受注明細へ紐付け
                    $orderItem->setKsOrderItemEx($ksOrderItemEx);

                    $this->entityManager->persist($orderItem);

                    // 更新後自動的に選択商品種別が設定されるためOrderFlowの場合は商品レコードへ更新する
                    if ($admin) {
                        $orderItem->setOrderItemType($productOrderItemType);
                    }

                    // 親OrderItemとリレーション設定
                    $parentOrderItem
                        ->addKsOrderItemChildren($ksOrderItemEx);

                    // Shippingにも追加
                    $shipping = $parentOrderItem->getShipping();
                    if ($shipping) {
                        $shipping
                            ->addOrderItem($orderItem);
                    }

                    // Orderにも追加
                    $parentOrderItem->getOrder()
                        ->addOrderItem($orderItem);
                }
            }

            $index += 1;
        }
    }
}
