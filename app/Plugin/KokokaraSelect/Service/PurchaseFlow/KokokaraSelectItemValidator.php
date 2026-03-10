<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/23
 */

namespace Plugin\KokokaraSelect\Service\PurchaseFlow;


use Eccube\Annotation\CartFlow;
use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\CartItem;
use Eccube\Entity\ItemInterface;
use Eccube\Service\PurchaseFlow\ItemValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\KokokaraSelect\Entity\KsCartSelectItemGroup;
use Plugin\KokokaraSelect\Service\ConfigHelperTrait;
use Plugin\KokokaraSelect\Service\KsCartHelper;
use Plugin\KokokaraSelect\Service\KsService;
use Plugin\KokokaraSelect\Service\KsValidatorTrait;

/**
 * @CartFlow
 * @ShoppingFlow
 *
 * Class KokokaraSelectItemValidator
 * @package Plugin\KokokaraSelect\Service\PurchaseFlow
 */
class KokokaraSelectItemValidator extends ItemValidator
{

    use ConfigHelperTrait;

    use KsValidatorTrait;

    /** @var KsService */
    protected $ksService;

    /** @var KsCartHelper */
    protected $ksCartHelper;

    public function __construct(
        KsService $ksService,
        KsCartHelper $ksCartHelper
    )
    {
        $this->ksService = $ksService;
        $this->ksCartHelper = $ksCartHelper;
    }

    /**
     * 選択商品のチェック
     *
     * @param ItemInterface $item
     * @param PurchaseContext $context
     * @throws \Eccube\Service\PurchaseFlow\InvalidItemException
     */
    protected function validate(ItemInterface $item, PurchaseContext $context)
    {

        if (!$item->isProduct()) {
            return;
        }

        if (!$item instanceof CartItem) {
            return;
        }

        $activeKsCartKey = $this->ksService->getActiveKsCartKey();
        $isAddCartRoute = $this->ksCartHelper->isSelectItemAddCartRoute();

        $productClass = $item->getProductClass();
        $product = $productClass->getProduct();

        // 選択商品判定
        if ($this->ksService->isKsProduct($item)) {

            // 適切に選択されているか確認
            if ($item->getKsCartSelectItemGroups()->count() == 0) {

                // カート追加時は、追加グループのみチェック
                if ($isAddCartRoute) {
                    if (empty($activeKsCartKey)) {
                        // 選択数量が適切でない
                        $this->throwKsInvalidItemException('kokokara_select.cart.item.validate.quantity',
                            ['%kokokara_select%' => $this->getKokokaraSelectName()], $productClass);
                    }
                } else {
                    $item->setQuantity(0);
                    $this->throwKsInvalidItemException('kokokara_select.cart.item.validate.quantity',
                        ['%kokokara_select%' => $this->getKokokaraSelectName()], $productClass);
                }

            } else {

                /** @var KsCartSelectItemGroup $ksCartSelectItemGroup */
                foreach ($item->getKsCartSelectItemGroups() as $ksCartSelectItemGroup) {

                    // カート追加時は、追加グループのみチェック
                    if ($isAddCartRoute) {
                        if ($activeKsCartKey
                            && $ksCartSelectItemGroup->getKsCartKey() != $activeKsCartKey) {
                            continue;
                        }
                    }

                    if (!$this->ksCartHelper->validKsCartSelectItemGroupProduct($product, $ksCartSelectItemGroup)) {
                        // 選択不可商品
                        if (!$this->ksService->isKsProduct($item, true)) {
                            $item->setQuantity(0);
                        }
                        $this->throwInvalidItemException('kokokara_select.cart.item.validate.item');
                    }

                    if (!$this->ksCartHelper->validKsCartSelectItemGroupQuantity($product, $ksCartSelectItemGroup)) {
                        if (!$this->ksService->isKsProduct($item, true)) {
                            $item->setQuantity(0);
                            $this->throwKsInvalidItemException('kokokara_select.cart.direct_item.validate.quantity',
                                ['%kokokara_select%' => $this->getKokokaraSelectDirectSelectName()], $productClass);
                        } else {
                            // 選択数量が適切でない
                            $this->throwKsInvalidItemException('kokokara_select.cart.item.validate.quantity',
                                ['%kokokara_select%' => $this->getKokokaraSelectName()], $productClass);
                        }
                    }
                }
            }


        } else {
            $item->clearKsCartSelectItemGroup();
        }

    }
}
