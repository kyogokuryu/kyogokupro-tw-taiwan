<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/17
 */

namespace Plugin\KokokaraSelect\Service;


use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\CartItem;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Request\Context;

class KsService
{

    protected $activeKsCartKey;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var EccubeConfig */
    protected $eccubeService;

    public const IS_KS_ORDER_DEFAULT = 0;

    public const IS_KS_ORDER_NORMAL = 1;

    public const IS_KS_ORDER_DIRECT = 2;

    /** @var Context */
    protected $context;

    public function __construct(
        EntityManagerInterface $entityManager,
        EccubeConfig $eccubeConfig,
        Context $context
    )
    {
        $this->entityManager = $entityManager;
        $this->eccubeService = $eccubeConfig;
        $this->context = $context;
    }

    /**
     * 選択商品か判定
     *
     * @param CartItem|OrderItem|ProductClass|Product $target
     * @param bool $directMode true:自動投入の場合false返却にする
     * @return bool true:選択商品, false:通常商品
     */
    public function isKsProduct($target, $directMode = false)
    {
        $product = null;

        if ($target instanceof CartItem) {
            $productClass = $target->getProductClass();
            $product = $productClass->getProduct();
        } else if ($target instanceof ProductClass) {
            $productClass = $target;
            $product = $productClass->getProduct();
        } else if ($target instanceof OrderItem) {

            if (!$target->isProduct()) {
                return false;
            }

            $productClass = $target->getProductClass();

            if (!$productClass) {
                $productClass = $target->getKsTmpProductClass();
            }
            $product = $productClass->getProduct();

        } else if ($target instanceof Product) {
            $product = $target;
        }

        if (!$product) {
            return false;
        }

        $ksProduct = $product->getKsProduct();

        if ($ksProduct) {

            if ($directMode) {
                // 自動投入ONの場合、自動投入が有効の場合はfalse
                if ($ksProduct->isDirectSelect()) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * 固定セット商品対象の商品か判定
     *
     * @param ProductClass $productClass
     * @return bool true 固定セット商品
     */
    public function isDirectSelectKsProduct(ProductClass $productClass)
    {
        if ($this->isKsProduct($productClass)) {
            $ksProduct = $productClass->getProduct()->getKsProduct();

            if (!$ksProduct) {
                return false;
            }

            return $ksProduct->isDirectSelect();
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getActiveKsCartKey()
    {
        return $this->activeKsCartKey;
    }

    /**
     * @param mixed $activeKsCartKey
     * @return KsService
     */
    public function setActiveKsCartKey($activeKsCartKey)
    {
        $this->activeKsCartKey = $activeKsCartKey;
        return $this;
    }

    /**
     * 選択商品が含まれた受注か確認
     *
     * @param $orderId
     * @param int $checkMode 0:デフォルト 1:通常の選択商品含む=true 2:自動選択の商品含む=true
     * @return bool true:選択商品が含まれている
     */
    public function isKsOrder($orderId, $checkMode = self::IS_KS_ORDER_DEFAULT)
    {
        if ($orderId) {

            if ($orderId instanceof Order) {
                $order = $orderId;
            } else {
                /** @var Order $order */
                $order = $this->entityManager->getRepository(Order::class)->findOneBy([
                    'pre_order_id' => $orderId,
                    'OrderStatus' => OrderStatus::PROCESSING,
                ]);
            }

            if ($order) {
                /** @var OrderItem $orderItem */
                foreach ($order->getOrderItems() as $orderItem) {
                    if ($this->isKsProduct($orderItem)) {

                        $ksProduct = $orderItem->getProductClass()->getProduct()->getKsProduct();

                        switch ($checkMode) {
                            case self::IS_KS_ORDER_NORMAL:
                                // 通常含む
                                if (!$ksProduct->isDirectSelect()) {
                                    return true;
                                }
                                break;
                            case self::IS_KS_ORDER_DIRECT:
                                // 自動含む
                                if ($ksProduct->isDirectSelect()) {
                                    return true;
                                }
                                break;
                            default:
                                // デフォ
                                return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * 管理画面での処理かチェック
     *
     * @return bool true: Admin
     */
    public function isAdminRoute()
    {
        return $this->context->isAdmin();
    }
}
