<?php
/**
 * Copyright(c) 2021 systemkd
 * Date: 2021/1/27
 */

namespace Plugin\KokokaraSelect\Service\PurchaseFlow\Processor;


use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\KokokaraSelect\Service\KsOrderItemService;


/**
 * @ShoppingFlow
 *
 * Class TaxFixProcessor
 * @package Plugin\KokokaraSelect\Service\PurchaseFlow\Processor
 */
class TaxFixProcessor implements ItemHolderPreprocessor
{

    /** @var TaxRuleRepository */
    private $taxRuleRepository;

    /** @var KsOrderItemService */
    private $ksOrderItemService;

    public function __construct(
        TaxRuleRepository $taxRuleRepository,
        KsOrderItemService $ksOrderItemService
    )
    {
        $this->taxRuleRepository = $taxRuleRepository;
        $this->ksOrderItemService = $ksOrderItemService;
    }

    /**
     * セット商品（構成品）の税率を商品単位の税率へ変更。
     *
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext $context
     * @throws \Doctrine\ORM\NoResultException
     */
    public function process(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {

        if (!$itemHolder instanceof Order) {
            return;
        }

        $ksOrderItemType = $this->ksOrderItemService->getKsOrderItemType();

        foreach ($itemHolder->getOrderItems() as $item) {

            if ($context->isShoppingFlow() || $item->getRoundingType() === null) {

                // 選択商品の場合
                if ($item->getOrderItemType()->getId() == $ksOrderItemType->getId()) {
                    $TaxRule = $this->taxRuleRepository->getByRule($item->getProduct(), $item->getProductClass());
                    $item->setTaxRate($TaxRule->getTaxRate())
                        ->setTaxAdjust($TaxRule->getTaxAdjust())
                        ->setRoundingType($TaxRule->getRoundingType());
                }
            }
        }
    }
}
