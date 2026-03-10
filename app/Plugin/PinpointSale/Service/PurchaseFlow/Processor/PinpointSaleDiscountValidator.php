<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/02
 */

namespace Plugin\PinpointSale\Service\PurchaseFlow\Processor;


use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\ItemInterface;
use Eccube\Entity\OrderItem;
use Eccube\Entity\ProductClass;
use Eccube\Service\PurchaseFlow\InvalidItemException;
use Eccube\Service\PurchaseFlow\ItemValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\PinpointSale\Config\ConfigSetting;
use Plugin\PinpointSale\Entity\PinpointSaleItem;
use Plugin\PinpointSale\Service\PinpointSaleService;
use Plugin\PinpointSale\Service\PlgConfigService\ConfigService;

/**
 * @ShoppingFlow
 *
 * Class PinpointSaleDiscountValidator
 * @package Plugin\PinpointSale\Service\PurchaseFlow\Processor
 */
class PinpointSaleDiscountValidator extends ItemValidator
{

    /** @var ConfigService */
    protected $configService;

    /** @var PinpointSaleService */
    protected $pinpointSaleService;

    public function __construct(
        ConfigService $configService,
        PinpointSaleService $pinpointSaleService
    )
    {
        $this->configService = $configService;
        $this->pinpointSaleService = $pinpointSaleService;
    }

    /**
     * 妥当性検証を行う.
     *
     * @param ItemInterface $item
     * @param PurchaseContext $context
     * @throws InvalidItemException
     * @throws \Exception
     */
    protected function validate(ItemInterface $item, PurchaseContext $context)
    {
        if (!$item->isDiscount()) {
            return;
        }

        if ($item instanceof OrderItem) {

            if ($item->getProcessorName() == PinpointSaleDiscountProcessor::class) {

                // 注文上のタイムセール値引額
                $orderDiscount = $item->getPriceIncTax();

                // 最新のまとめ買い値引き額
                $productClass = $item->getProductClass();
                /** @var PinpointSaleItem $pinpointSaleItem */
                $pinpointSaleItem = $this->pinpointSaleService->getPinpointSaleItem($productClass);
                if ($pinpointSaleItem) {
                    $nowDiscountIncTax = $pinpointSaleItem->getDiscountPriceIncTax();
                } else {
                    $nowDiscountIncTax = $productClass->getPrice02IncTax();
                }

                if (abs($orderDiscount) != abs($nowDiscountIncTax)) {
                    // 値引き額が変更されている
                    $discountTitle = $this->configService->getKeyString(ConfigSetting::SETTING_KEY_DISCOUNT_NAME);

                    $this->throwInvalidItemExceptionEx('pinpoint_sale.front.discount_change',
                        $item->getProductClass(), $discountTitle);
                }
            }
        }
    }

    /**
     * @param $errorCode
     * @param ProductClass|null $ProductClass
     * @param $discountName
     * @param bool $warning
     * @throws InvalidItemException
     */
    protected function throwInvalidItemExceptionEx($errorCode, ProductClass $ProductClass = null, $discountName = [], $warning = false)
    {
        if ($ProductClass) {
            $productName = $ProductClass->getProduct()->getName();
            if ($ProductClass->hasClassCategory1()) {
                $productName .= ' - ' . $ProductClass->getClassCategory1()->getName();
            }
            if ($ProductClass->hasClassCategory2()) {
                $productName .= ' - ' . $ProductClass->getClassCategory2()->getName();
            }

            throw new InvalidItemException(trans($errorCode, ['%product%' => $productName, '%saleName%' => $discountName]), null, $warning);
        }
        throw new InvalidItemException(trans($errorCode), null, $warning);
    }
}
