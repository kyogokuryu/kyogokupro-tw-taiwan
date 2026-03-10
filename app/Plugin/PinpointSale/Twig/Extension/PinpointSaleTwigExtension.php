<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/24
 */

namespace Plugin\PinpointSale\Twig\Extension;


use Eccube\Entity\OrderItem;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Repository\ProductRepository;
use Plugin\PinpointSale\Entity\Pinpoint;
use Plugin\PinpointSale\Entity\PinpointRepeat;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class PinpointSaleTwigExtension extends AbstractExtension
{

    /** @var ProductRepository */
    protected $productRepository;

    public function __construct(
        ProductRepository $productRepository
    )
    {
        $this->productRepository = $productRepository;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('get_pinpoint_sale_default_prices', [$this, 'getPinpointSaleDefaultPrices']),
            new TwigFunction('pinpoint_sale_history_suffix', [$this, 'getPinpointSaleHistorySuffix']),
            new TwigFunction('pinpoint_repeat_week', [$this, 'getPinpointRepeatWeeks']),
            new TwigFunction('is_pinpoint_sale_setting', [$this, 'isPinpointSaleSetting']),
            new TwigFunction('pinpoint_sale_view', [$this, 'getPinpointSaleView']),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('pinpoint_sale_time', [$this, 'pinpointSaleTime'], ['needs_environment' => true]),
        ];
    }

    /**
     * 購入履歴用Key取得
     *
     * @param OrderItem $orderItem
     * @return string
     */
    public function getPinpointSaleHistorySuffix(OrderItem $orderItem)
    {
        $shippingId = $orderItem->getShipping()->getId();
        $productClassId = $orderItem->getProductClass()->getId();

        return $shippingId . '-' . $productClassId;
    }

    /**
     * タイムセール前価格情報取得
     * タイムセールのみ価格情報セット
     *
     * @param $product_id
     * @return false|string
     */
    public function getPinpointSaleDefaultPrices($product_id)
    {

        /** @var Product $product */
        $product = $this->productRepository->find($product_id);

        $result = [];
        $minPrice = -1;
        $maxPrice = -1;
        $saleActive = false;

        /** @var ProductClass $productClass */
        foreach ($product->getProductClasses() as $productClass) {

            $pinpointSaleItem = $productClass->getPinpointSaleItem();

            if ($pinpointSaleItem) {
                if ($pinpointSaleItem->isActive()) {

                    $saleActive = true;

                    // 通常の販売価格取得
                    $defaultPrice = $productClass->getPinpointSaleOriginPrice02IncTax();
                    $result[$productClass->getId()] = number_format($defaultPrice);

                } else {
                    $result[$productClass->getId()] = '';

                    $defaultPrice = $productClass->getPrice02IncTax();
                }
            } else {
                $result[$productClass->getId()] = '';
                $defaultPrice = $productClass->getPrice02IncTax();
            }

            if ($minPrice > $defaultPrice || $minPrice < 0) {
                $minPrice = $defaultPrice;
            }

            if ($maxPrice < $defaultPrice || $maxPrice < 0) {
                $maxPrice = $defaultPrice;
            }

        }

        if($saleActive) {
            $result['min'] = $minPrice;
            $result['max'] = $maxPrice;
        } else {
            $result['min'] = -1;
            $result['max'] = -1;
        }

        $result['min_view'] = '￥' . number_format($minPrice);
        $result['max_view'] = '￥' . number_format($maxPrice);

        return json_encode($result);
    }

    /**
     * 有効な曜日返却
     *
     * @param PinpointRepeat $pinpointRepeat
     * @return array
     */
    public function getPinpointRepeatWeeks(PinpointRepeat $pinpointRepeat)
    {
        $result = [];

        $activeWeeks = $pinpointRepeat->getActiveWeeks();

        foreach ($activeWeeks as $key => $activeWeek) {
            $result[$key] = trans('pinpoint_sale.pinpoint_repeat_week' . $key);
        }

        return $result;
    }

    /**
     * タイムセール情報設定判定
     *
     * @param Product $product
     * @return bool true:タイムセール設定
     */
    public function isPinpointSaleSetting(Product $product)
    {
        $isPinpointSale = false;

        /** @var ProductClass $productClass */
        foreach ($product->getProductClasses() as $productClass) {
            if ($productClass->getProductPinpoints()->count() > 0) {
                $isPinpointSale = true;
                break;
            }
        }

        return $isPinpointSale;
    }

    /**
     * 値引き後金額or割引率表示
     *
     * @param Pinpoint $pinpoint
     * @return mixed
     */
    public function getPinpointSaleView(Pinpoint $pinpoint)
    {

        if ($pinpoint->getSaleType() == Pinpoint::TYPE_PRICE) {
            // 金額
            $result = trans('pinpoint_sale.admin.list.pinpoint_sale_price',
                ['%price%' => number_format($pinpoint->getSalePrice())]);
        } else {
            // レート
            $result = trans('pinpoint_sale.admin.list.pinpoint_sale_rate',
                ['%rate%' => $pinpoint->getSaleRate()]);

            if ($pinpoint->isSaleTypeCommon()) {
                // 共通の場合名称追加
                $result .= trans('pinpoint_sale.admin.list.common');
            }
        }

        return $result;
    }

    /**
     * xxxxをxx:xxに変換
     *
     * @param Environment $env
     * @param $time
     * @return string
     */
    public function pinpointSaleTime(Environment $env, $time)
    {
        if (!$time) {
            return '';
        }

        $hour = substr($time, 0, 2);
        $minute = substr($time, -2);

        return $hour . ':' . $minute;
    }
}
