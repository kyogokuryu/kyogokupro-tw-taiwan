<?php
/**
 * Created by SYSTEM_KD
 * Date: 2019-08-18
 */

namespace Plugin\PinpointSale\Service\PurchaseFlow\Processor;


use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Entity\Master\TaxType;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Service\PurchaseFlow\DiscountProcessor;
use Eccube\Service\PurchaseFlow\ProcessResult;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\TaxRuleService;
use Plugin\PinpointSale\Config\ConfigSetting;
use Plugin\PinpointSale\Service\PinpointSaleService;
use Plugin\PinpointSale\Service\PlgConfigService\ConfigService;

/**
 * ShoppingFlow
 *
 * Class PinpointSaleDiscountProcessor
 * @package Plugin\PinpointSale\Service\PurchaseFlow\Processor
 */
class PinpointSaleDiscountProcessor implements DiscountProcessor
{

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var PinpointSaleService */
    protected $pinpointSaleService;

    /** @var TaxRuleRepository */
    protected $taxRuleRepository;

    /** @var TaxRuleService */
    protected $taxRuleService;

    /** @var ConfigService */
    protected $configService;

    /**
     * PinpointSaleDiscountProcessor constructor.
     * @param PinpointSaleService $pinpointSaleService
     * @param EntityManagerInterface $entityManager
     * @param TaxRuleRepository $taxRuleRepository
     * @param TaxRuleService $taxRuleService
     * @param ConfigService $configService
     */
    public function __construct(
        PinpointSaleService $pinpointSaleService,
        EntityManagerInterface $entityManager,
        TaxRuleRepository $taxRuleRepository,
        TaxRuleService $taxRuleService,
        ConfigService $configService
    )
    {
        $this->pinpointSaleService = $pinpointSaleService;
        $this->entityManager = $entityManager;
        $this->taxRuleRepository = $taxRuleRepository;
        $this->taxRuleService = $taxRuleService;
        $this->configService = $configService;
    }

    /**
     * 値引き明細の削除処理を実装します.
     *
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext $context
     */
    public function removeDiscountItem(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        // 値引きレコードクリア
        if ($itemHolder instanceof Order) {

            /** @var OrderItem $orderItem */
            foreach ($itemHolder->getItems() as $orderItem) {

                if ($orderItem->getProcessorName() == PinpointSaleDiscountProcessor::class) {
                    $itemHolder->removeOrderItem($orderItem);
                    $this->entityManager->remove($orderItem);
                }
            }
        }
    }

    /**
     * 値引き明細の追加処理を実装します.
     *
     * かならず合計金額等のチェックを行い, 超える場合は利用できる金額まで丸めるか、もしくは明細の追加処理をスキップしてください.
     * 正常に追加できない場合は, ProcessResult::warnを返却してください.
     *
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext $context
     *
     * @return ProcessResult|null
     * @throws \Exception
     */
    public function addDiscountItem(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {

        $DiscountType = $this->entityManager
            ->find(OrderItemType::class, OrderItemType::DISCOUNT);

        $TaxExcluded = $this->entityManager
            ->find(TaxDisplayType::class, TaxDisplayType::EXCLUDED);

        $TaxIncluded = $this->entityManager
            ->find(TaxDisplayType::class, TaxDisplayType::INCLUDED);

        $totalDiscount = 0;

        // 割引レコード用の設定取得
        $settingTaxTyp = $this->configService->getKeyInteger(ConfigSetting::SETTING_KEY_DISCOUNT_TAX);
        $discountTaxType = $this->entityManager->find(TaxType::class, $settingTaxTyp);

        // 値引名称
        $discountTitle = $this->configService->getKeyString(ConfigSetting::SETTING_KEY_DISCOUNT_NAME);

        if ($itemHolder instanceof Order) {

            /** @var Shipping $shipping */
            foreach ($itemHolder->getShippings() as $shipping) {
                /** @var OrderItem $orderItem */
                foreach ($shipping->getOrderItems() as $orderItem) {

                    if (!$orderItem->isProduct()) continue;

                    $productClass = $orderItem->getProductClass();

                    // タイムセール情報取得
                    $pinpointSaleItem =
                        $this->pinpointSaleService->getPinpointSaleItem($productClass);

                    if ($pinpointSaleItem) {

                        // 値引きレコード設定

                        // 税抜き価格取得
                        $discount = $pinpointSaleItem->getDiscountPrice();

                        if ($discountTaxType->getId() == TaxType::TAXATION) {
                            // 税抜き価格取得
                            $discount = $pinpointSaleItem->getDiscountPrice();
                        } else {
                            // 税込み価格取得
                            $discount = $pinpointSaleItem->getDiscountPriceIncTax();
                        }

                        if ($discount <= 0) {
                            // マイナスの場合（値上がりする場合）は値引きレコードを作成しない
                            continue;
                        }

                        $quantity = $orderItem->getQuantity();

                        if ($itemHolder->getSubtotal() <= ($totalDiscount + $discount * $quantity)) {
                            // MG状態
                            // 値引き額調整
                            $discount = $itemHolder->getSubtotal() - $totalDiscount;
                            $quantity = 1;
                        }

                        $totalDiscount += $discount;
                        $setDiscount = -1 * $discount;

                        $discountName = trans('pinpoint_sale.admin.discount_title', [
                            '%saleName%' => $discountTitle,
                            '%product_name%' => $productClass->getProduct()->getName()
                        ]);

                        $newOrderItem = new OrderItem();
                        $newOrderItem->setProductName($discountName)
                            ->setOrderItemType($DiscountType)
                            ->setPrice($setDiscount)
                            ->setQuantity($quantity)
                            ->setTaxType($discountTaxType)
                            ->setProductClass($productClass)
                            ->setShipping($shipping)
                            ->setOrder($itemHolder)
                            ->setProcessorName(PinpointSaleDiscountProcessor::class);

                        if ($orderItem->getTaxRuleId()) {
                            $TaxRule = $this->taxRuleRepository->find($orderItem->getTaxRuleId());
                        } else {
                            $TaxRule = $this->taxRuleRepository->getByRule($orderItem->getProduct(), $productClass);
                        }

                        // 税区分: 非課税, 不課税
                        if ($discountTaxType->getId() != TaxType::TAXATION) {
                            $newOrderItem->setTax(0);
                            $newOrderItem->setTaxRate(0);
                            $newOrderItem->setRoundingType(null);
                            $newOrderItem->setTaxRuleId(null);
                            $newOrderItem->setTaxDisplayType($TaxIncluded);

                            // 税込表示の場合は, priceが税込金額のため割り戻す.
                            $tax = $this->taxRuleService->calcTaxIncluded(
                                abs($setDiscount), $TaxRule->getTaxRate(), $TaxRule->getRoundingType()->getId(),
                                $TaxRule->getTaxAdjust());

                        } else {

                            $tax = $this->taxRuleService->calcTax(
                                abs($setDiscount), $TaxRule->getTaxRate(), $TaxRule->getRoundingType()->getId(),
                                $TaxRule->getTaxAdjust());

                            $newOrderItem->setTaxRate($TaxRule->getTaxRate());
                            $newOrderItem->setRoundingType($TaxRule->getRoundingType());
                            $newOrderItem->setTaxRuleId($TaxRule->getId());
                            $newOrderItem->setTaxDisplayType($TaxExcluded);
                        }

                        if ($setDiscount < 0) {
                            $tax = -1 * $tax;
                        }

                        $newOrderItem->setTax($tax);

                        $itemHolder->addItem($newOrderItem);
                    }
                }
            }
        }

        return null;
    }
}
