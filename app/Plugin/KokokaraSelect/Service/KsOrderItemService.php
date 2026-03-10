<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/27
 */

namespace Plugin\KokokaraSelect\Service;


use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\OrderItem;
use Eccube\Repository\Master\OrderItemTypeRepository;
use Plugin\KokokaraSelect\Entity\KokokaraSelect\KsOrderItemRow;
use Plugin\KokokaraSelect\Entity\KokokaraSelect\KsOrderItemRows;
use Plugin\KokokaraSelect\Entity\KsOrderItemTypeEx;
use Plugin\KokokaraSelect\Entity\KsProduct;
use Plugin\KokokaraSelect\Entity\KsSelectItemGroup;
use Plugin\KokokaraSelect\Repository\KsOrderItemTypeExRepository;
use Plugin\KokokaraSelect\Service\PurchaseFlow\KsOrderItemHelper;

class KsOrderItemService
{

    use ConfigHelperTrait;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var KsService */
    protected $ksService;

    /** @var KsProductService */
    protected $ksProductService;

    /** @var KsSelectItemGroupService */
    protected $ksSelectItemGroupService;

    /** @var KsSelectItemService */
    protected $ksSelectItemService;

    /** @var KsOrderItemHelper */
    protected $ksOrderItemHelper;

    public function __construct(
        EntityManagerInterface $entityManager,
        KsService $ksService,
        KsProductService $ksProductService,
        KsSelectItemGroupService $ksSelectItemGroupService,
        KsSelectItemService $ksSelectItemService,
        KsOrderItemHelper $ksOrderItemHelper
    )
    {
        $this->entityManager = $entityManager;
        $this->ksService = $ksService;
        $this->ksProductService = $ksProductService;
        $this->ksSelectItemGroupService = $ksSelectItemGroupService;
        $this->ksSelectItemService = $ksSelectItemService;
        $this->ksOrderItemHelper = $ksOrderItemHelper;
    }

    /**
     * 選択商品用のOrderItemType取得
     *
     * @return OrderItemType
     */
    public function getKsOrderItemType()
    {
        /** @var KsOrderItemTypeExRepository $ksOrderItemExRepository */
        $ksOrderItemExRepository = $this->entityManager->getRepository(KsOrderItemTypeEx::class);

        /** @var OrderItemTypeRepository $orderItemTypeRepository */
        $orderItemTypeRepository = $this->entityManager->getRepository(OrderItemType::class);

        // 選択商品用のOrderItemType取得
        $ksOrderItemTypeEx = $ksOrderItemExRepository->get();

        return $orderItemTypeRepository->find($ksOrderItemTypeEx->getOrderItemTypeId());
    }

    /**
     * 商品用のOrderItemType取得
     *
     * @return OrderItemType
     */
    public function getProductOrderItemType()
    {
        /** @var OrderItemTypeRepository $orderItemTypeRepository */
        $orderItemTypeRepository = $this->entityManager->getRepository(OrderItemType::class);

        return $orderItemTypeRepository->find(OrderItemType::PRODUCT);
    }

    /**
     * OrderItemが選択商品の構成要素か判定
     *
     * @param OrderItem $orderItem
     * @return bool
     */
    public function isKsOrderItem(OrderItem $orderItem)
    {
        /** @var OrderItemType $orderItemType */
        $orderItemType = $this->getKsOrderItemType();
        if ($orderItem->getOrderItemType()->getId() == $orderItemType->getId()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 表示用の選択商品名取得
     *
     * @param OrderItem $orderItem
     * @param string $prefix
     * @return string
     */
    public function getViewOrderItemName(OrderItem $orderItem, $prefix = "　")
    {
        if ($orderItem->isKsSelectItem()) {

            $ksOrderItemEx = $orderItem->getKsOrderItemEx();
            $parentProductName = $ksOrderItemEx->getParentOrderItem()->getProductName();

            $productClassName = $ksOrderItemEx->getProductName();

            $parentKsProduct = $ksOrderItemEx->getParentOrderItem()->getKsProduct();

            // 動的に生成
            return $this->ksOrderItemHelper->getKsSelectOrderItemName(
                $parentKsProduct,
                $prefix,
                $parentProductName,
                $ksOrderItemEx->getGroupId(),
                $productClassName
            );

        } else {
            return $orderItem->getProductName();
        }
    }

    /**
     * @param $ksOrderItemChildren
     * @return mixed
     */
    public function getViewOrderItemDirectName($ksOrderItemChildren)
    {

        $margeData = [];

        // セット商品用に集計
        foreach ($ksOrderItemChildren as $ksOrderItemChild) {

            $quantity = $ksOrderItemChild->getOrderItem()->getQuantity();
            $name = $ksOrderItemChild->getProductName();

            $id = $ksOrderItemChild->getKsSelectItemId();

            if (isset($margeData[$id])) {
                $margeData[$id]['quantity'] += (int)$quantity;
            } else {
                $margeData[$id] = [
                    'quantity' => $quantity,
                    'name' => $name
                ];
            }
        }

        $names[] = '[' . $this->getKokokaraSelectDirectSelectName() . trans('kokokara_select.admin.pdf.direct_select.suffix') . ']';

        foreach ($margeData as $margeDatum) {
            $names[] = ' ' . $margeDatum['name'] . ' × ' . $margeDatum['quantity'];
        }

        return $names;
    }

    /**
     * 選択商品の構成要素が適切に選択されているか確認
     *
     * @param OrderItem $targetOrderItem
     * @return array
     */
    public function validOrderItem(OrderItem $targetOrderItem)
    {

        $valid = true;

        $ksSelectItemStructureErrors = [];
        $ksSelectItemGroupErrors = [];
        $ksSelectItemErrors = [];

        if ($this->ksService->isKsProduct($targetOrderItem)) {

            // 商品の構成情報取得
            $ksProduct = $targetOrderItem->getKsProduct();

            // 購入商品の構成情報取得
            $ksOrderItemRows = $targetOrderItem->getKsOrderItemRows();

            // 選択商品構成チェック
            $ksSelectItemStructureErrors = $this->validGroupStructure(
                $ksProduct,
                $ksOrderItemRows
            );

            // グループ存在/数量チェック
            $ksSelectItemGroupErrors = $this->validGroup(
                $ksProduct,
                $ksOrderItemRows
            );

            // 商品存在チェック
            $ksSelectItemErrors = $this->validItem(
                $ksProduct,
                $ksOrderItemRows
            );

            if (count($ksSelectItemStructureErrors) > 0
                || count($ksSelectItemGroupErrors) > 0
                || count($ksSelectItemErrors) > 0) {

                $valid = false;
            }

        }

        return [
            'valid' => $valid,
            'ksSelectItemStructureErrors' => $ksSelectItemStructureErrors,
            'ksSelectItemGroupErrors' => $ksSelectItemGroupErrors,
            'ksSelectItemErrors' => $ksSelectItemErrors,
        ];
    }


    /**
     * グループ通り存在するかチェック
     *
     * @param KsProduct $ksProduct
     * @param KsOrderItemRows $ksOrderItemRows
     * @return array
     */
    private function validGroupStructure(KsProduct $ksProduct, KsOrderItemRows $ksOrderItemRows)
    {
        // 選択商品の構成情報取得
        $ksSelectItemGroups = $ksProduct->getKsSelectItemGroupsAndKey();

        // 注文情報のグループ取得
        $ksOrderItemGroups = $ksOrderItemRows->getKsOrderItemGroups();

        $productClass = $ksOrderItemRows->getProductClass();

        $ksSelectItemStructureErrors = [];

        /** @var KsSelectItemGroup $ksSelectItemGroup */
        foreach ($ksSelectItemGroups as $ksSelectItemGroupId => $ksSelectItemGroup) {
            if (!isset($ksOrderItemGroups[$ksSelectItemGroupId])) {
                // 構成NG
                $ksSelectItemStructureErrors[$productClass->getId()]['NG'] = true;
            }
        }

        return $ksSelectItemStructureErrors;
    }

    /**
     * グループに存在するかチェック
     *
     * @param KsProduct $ksProduct
     * @param KsOrderItemRows $ksOrderItemRows
     * @return array
     */
    private function validGroup(KsProduct $ksProduct, KsOrderItemRows $ksOrderItemRows)
    {

        $ksSelectItemGroupErrors = [];

        // 選択商品の構成情報取得
        $ksSelectItemGroups = $ksProduct->getKsSelectItemGroupsAndKey();

        /** @var KsOrderItemRow $ksOrderItemRow */
        foreach ($ksOrderItemRows->getKsOrderItemRow() as $ksOrderItemRow) {

            $groupIndex = $ksOrderItemRow->getGroupIndex();
            $groupQuantity = $ksOrderItemRow->getKsOrderSelectItemGroupQuantity();

            foreach ($groupQuantity as $ksSelectItemGroupId => $quantity) {

                if (!isset($ksSelectItemGroups[$ksSelectItemGroupId])) {
                    // 既にグループが存在しない
                    $ksSelectItemGroupErrors[$groupIndex]['NoGroup'] = true;
                }
            }
        }

        /** @var KsSelectItemGroup $ksSelectItemGroup */
        foreach ($ksSelectItemGroups as $key => $ksSelectItemGroup) {

            /** @var KsOrderItemRow $ksOrderItemRow */
            foreach ($ksOrderItemRows->getKsOrderItemRow() as $ksOrderItemRow) {

                $groupIndex = $ksOrderItemRow->getGroupIndex();
                $groupQuantity = $ksOrderItemRow->getKsOrderSelectItemGroupQuantity();
                if (isset($groupQuantity[$key])) {
                    // 数量チェック
                    if ($groupQuantity[$key] != $ksSelectItemGroup->getQuantity()) {
                        // 数量違い
                        $ksSelectItemGroupErrors[$groupIndex]['NGQuantity'] = true;
                    }
                } else {
                    if (!isset($ksSelectItemGroupErrors[$groupIndex]['NoGroup'])) {
                        // 数量なし
                        $ksSelectItemGroupErrors[$groupIndex]['NGQuantity'] = true;
                    }
                }
            }
        }

        return $ksSelectItemGroupErrors;
    }

    /**
     * 商品が存在するかチェック
     *
     * @param KsProduct $ksProduct
     * @param KsOrderItemRows $ksOrderItemRows
     * @return array
     */
    private function validItem(KsProduct $ksProduct, KsOrderItemRows $ksOrderItemRows)
    {

        $ksSelectItemErrors = [];

        $ksSelectItems = $ksProduct->getKsSelectItemsAndKey();

        /** @var KsOrderItemRow $ksOrderItemRow */
        foreach ($ksOrderItemRows->getKsOrderItemRow() as $ksOrderItemRow) {

            $groupIndex = $ksOrderItemRow->getGroupIndex();

            foreach ($ksOrderItemRow->getKsOrderItemStructures() as $ksSelectItemId => $ksOrderItemStructures) {

                if (!isset($ksSelectItems[$ksSelectItemId])) {

                    // 選択商品が存在しない
                    $ksSelectItemErrors[$groupIndex][$ksSelectItemId]['NoItem'] = true;
                    break;
                }
            }
        }

        return $ksSelectItemErrors;
    }
}
