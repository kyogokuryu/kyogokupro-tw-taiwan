<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/17
 */

namespace Plugin\PinpointSale\Service;


use Eccube\Entity\Master\RoundingType;
use Eccube\Entity\ProductClass;
use Eccube\Service\TaxRuleService;
use Plugin\PinpointSale\Config\ConfigSetting;
use Plugin\PinpointSale\Entity\Pinpoint;
use Plugin\PinpointSale\Entity\PinpointSaleItem;
use Plugin\PinpointSale\Entity\ProductPinpoint;
use Plugin\PinpointSale\Service\PlgConfigService\ConfigService;

class PinpointSaleService
{

    /** @var TaxRuleService */
    protected $taxRuleService;

    /** @var ConfigService */
    protected $configService;

    /** @var array */
    protected $pinpointSaleItems;

    protected $pinpointSaleHelper;

    /**
     * PinpointSaleService constructor.
     * @param TaxRuleService $taxRuleService
     * @param PinpointSaleHelper $pinpointSaleHelper
     */
    public function __construct(
        TaxRuleService $taxRuleService,
        PinpointSaleHelper $pinpointSaleHelper
    )
    {
        $this->taxRuleService = $taxRuleService;
        $this->pinpointSaleHelper = $pinpointSaleHelper;
    }

    /**
     * @param ConfigService $configService
     * @required
     */
    public function setConfigService(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * タイムセール情報取得
     *
     * @param ProductClass $productClass
     * @return PinpointSaleItem|null
     * @throws \Exception
     */
    public function getPinpointSaleItem(ProductClass $productClass)
    {
        if (!$this->isPinpointSaleLight($productClass)) {
            return null;
        }

        $pinpointSaleItem = $this->getPinpointSaleItemEntity($productClass);

        if (!$pinpointSaleItem->isActive()) {
            return null;
        }

        return $pinpointSaleItem;
    }

    /**
     * タイムセール価格
     *
     * @param ProductClass $productClass
     * @return mixed
     * @throws \Exception
     */
    public function getPinpointSalePrice(ProductClass $productClass)
    {
        if (!$this->isPinpointSaleLight($productClass)) {
            // 設定なし
            return $productClass->getPrice02();
        }

        $pinpointSaleItem = $this->getPinpointSaleItemEntity($productClass);

        if (!$pinpointSaleItem->isActive()) {
            return $productClass->getPrice02();
        }

        return $pinpointSaleItem->getPrice();
    }

    /**
     * タイムセール税込み価格
     *
     * @param ProductClass $productClass
     * @return mixed
     * @throws \Exception
     */
    public function getPinpointSalePriceIncTax(ProductClass $productClass)
    {
        if (!$this->isPinpointSaleLight($productClass)) {
            // 設定なし
            return $productClass->getPrice02IncTax();
        }

        $pinpointSaleItem = $this->getPinpointSaleItemEntity($productClass);

        if (!$pinpointSaleItem->isActive()) {
            return $productClass->getPrice02IncTax();
        }

        return $pinpointSaleItem->getPriceIncTax();
    }

    /**
     * タイムセール情報を格納したEntity取得
     *
     * @param ProductClass $productClass
     * @param bool $force
     * @return PinpointSaleItem
     * @throws \Exception
     */
    private function getPinpointSaleItemEntity(ProductClass $productClass, $force = false)
    {

        $productClassId = $productClass->getId();
        if (isset($this->pinpointSaleItems[$productClassId]) && !$force) {

            $pinpointSaleItem = $this->pinpointSaleItems[$productClassId];
            if ($pinpointSaleItem->getProductClass()
                && ($pinpointSaleItem->getProductClass()->getId() == $productClass->getId())) {

                return $pinpointSaleItem;
            }
        }

        $pinpointSaleItem = $this->calcPinpointSalePrice($productClass);
        $this->pinpointSaleItems[$productClassId] = $pinpointSaleItem;

        return $pinpointSaleItem;
    }

    /**
     * タイムセール判定
     *
     * @param ProductClass $productClass
     * @return array
     * @throws \Exception
     */
    private function isPinpointSale(ProductClass $productClass)
    {
        log_info('[PinpointSale]タイムセール判定開始');

        // 時間判定
        $toDay = new \DateTime();

        $nowTime = (int)$toDay->format('Hi');
        $toDayWeek = $toDay->format('w');
        $checkWeekMethod = "getWeek" . $toDayWeek;

        $result = [
            'isPinpointSale' => false,
            'target' => null,
        ];

        // 優先度順にソート
        $productPinpoints = $productClass->getProductPinpoints()->toArray();
        usort($productPinpoints, function (ProductPinpoint $productPinpointA, ProductPinpoint $productPinpointB) {

            $pinpointA = $productPinpointA->getPinpoint();
            $pinpointB = $productPinpointB->getPinpoint();

            return $this->pinpointSaleHelper->sortProductPinpoint($pinpointA, $pinpointB);
        });

        /** @var ProductPinpoint $productPinpoint */
        foreach ($productPinpoints as $productPinpoint) {

            // タイムセール情報
            $targetPinpoint = $productPinpoint->getPinpoint();

            $pinpointSaleFlg = false;

            if ($targetPinpoint->getStartTime() <= $toDay
                && $toDay < $targetPinpoint->getEndTime()) {

                // タイムセール期間内
                log_info('[PinpointSale]タイムセール期間該当');

                // 繰り返し判定
                if ($targetPinpoint->isPinpointRepeat()) {
                    // 繰り返し有効
                    log_info('[PinpointSale]タイムセール繰り返し対象');

                    $pinpointRepeat = $targetPinpoint->getPinpointRepeat();
                    $startTime = (int)$pinpointRepeat->getStartTime();
                    $endTime = (int)$pinpointRepeat->getEndTime();

                    // 繰り返し時間チェック
                    if ($startTime <= $nowTime && $nowTime <= $endTime) {
                        log_info('[PinpointSale]タイムセール繰り返し時間内');

                        // 曜日判定
                        if ($pinpointRepeat->{$checkWeekMethod}() == 1) {
                            log_info('[PinpointSale]タイムセール繰り返し対象曜日');
                            $pinpointSaleFlg = true;
                        }
                    }

                } else {
                    log_info('[PinpointSale]タイムセール対象（繰り返しなし）');
                    $pinpointSaleFlg = true;
                }
            }

            if ($pinpointSaleFlg) {
                // タイムセール該当商品
                $result['isPinpointSale'] = true;
                $result['target'] = $targetPinpoint;
                break;
            }
        }

        log_info('[PinpointSale]タイムセール判定終了');

        return $result;
    }

    /**
     * タイムセール判定（簡易）
     *
     * @param ProductClass $productClass
     * @return bool
     */
    private function isPinpointSaleLight(ProductClass $productClass)
    {
        if ($productClass->getProductPinpoints()->count() == 0) {
            // 設定なし
            return false;
        }
        return true;
    }

    /**
     * タイムセール計算
     *
     * @param ProductClass $productClass
     * @return PinpointSaleItem
     * @throws \Exception
     */
    private function calcPinpointSalePrice(ProductClass $productClass)
    {
        log_info('[PinpointSale]タイムセール計算開始');

        $pinpointSaleItem = new PinpointSaleItem();

        // タイムセール状態
        if (!$this->isPinpointSaleLight($productClass)) {
            // 設定なし
            $pinpointSaleItem->setStatus(PinpointSaleItem::NONE);
            log_info('[PinpointSale]タイムセール設定なし');

        } else {

            // タイムセール対象判定
            $result = $this->isPinpointSale($productClass);

            if ($result['isPinpointSale']) {
                // タイムセール該当商品
                $pinpointSaleItem->setStatus(PinpointSaleItem::ON);

                $pinpointSaleItem->setProductClass($productClass);

                /** @var Pinpoint $pinpoint */
                $pinpoint = $result['target'];

                if ($pinpoint->getSaleType() == Pinpoint::TYPE_PRICE) {
                    // 価格
                    $pinpointSaleItem->setPrice($pinpoint->getSalePrice());

                } elseif ($pinpoint->getSaleType() == Pinpoint::TYPE_RATE
                    || $pinpoint->getSaleType() == Pinpoint::TYPE_COMMON) {
                    // 割引率
                    $price02 = $productClass->getPrice02();
                    $rate = $pinpoint->getSaleRate();

                    $roundingType = $this->configService->getKeyInteger(ConfigSetting::SETTING_KEY_RATE_TYPE);
                    $pinpointSaleItem->setPrice($this->calcPinpointSaleRatePrice($price02, $rate, $roundingType));
                }

                // 値引き額は 販売価格 - 値引き後価格にて算出
                $discountPrice = $productClass->getPrice02() - $pinpointSaleItem->getPrice();

                // 税込み価格
                $discountPriceIncTax = $this->taxRuleService->getPriceIncTax(
                        $discountPrice,
                        $productClass->getProduct(),
                        $productClass
                    );

                $pinpointSaleItem->setPriceIncTax($productClass->getPrice02IncTax() - $discountPriceIncTax);

                // 値引き額
                $pinpointSaleItem->setDiscountPrice($productClass->getPrice02() - $pinpointSaleItem->getPrice());
                $pinpointSaleItem->setDiscountPriceIncTax($productClass->getPrice02IncTax() - $pinpointSaleItem->getPriceIncTax());

            } else {
                // タイムセール対象外
                $pinpointSaleItem->setStatus(PinpointSaleItem::OFF);
            }
        }

        log_info('[PinpointSale]タイムセール計算終了');

        return $pinpointSaleItem;
    }

    /**
     * 割引率に応じた価格取得
     *
     * @param $price
     * @param $rate
     * @param int $roundingType
     * @return float|int
     */
    private function calcPinpointSaleRatePrice($price, $rate, $roundingType = 1)
    {
        $value = $price * $rate / 100;

        switch ($roundingType) {
            // 四捨五入
            case RoundingType::ROUND:
                $roundResult = round($value);
                break;
            // 切り捨て
            case RoundingType::FLOOR:
                $roundResult = floor($value);
                break;
            // 切り上げ
            case RoundingType::CEIL:
                $roundResult = ceil($value);
                break;
            // デフォルト:切り上げ
            default:
                $roundResult = ceil($value);
                break;
        }

        $discountPrice = $price - $roundResult;

        return ($discountPrice > 0 ? $discountPrice : 0);
    }
}
