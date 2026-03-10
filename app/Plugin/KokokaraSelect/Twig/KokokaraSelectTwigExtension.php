<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/16
 */

namespace Plugin\KokokaraSelect\Twig;


use Doctrine\Common\Annotations\Annotation\Required;
use Eccube\Entity\CartItem;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Repository\ProductClassRepository;
use Plugin\KokokaraSelect\Entity\KsCartSelectItem;
use Plugin\KokokaraSelect\Entity\KsCartSelectItemGroup;
use Plugin\KokokaraSelect\Entity\KsOrderItemEx;
use Plugin\KokokaraSelect\Entity\KsSelectItem;
use Plugin\KokokaraSelect\Entity\KsSelectItemGroup;
use Plugin\KokokaraSelect\Repository\KsSelectItemGroupRepository;
use Plugin\KokokaraSelect\Service\ConfigHelperTrait;
use Plugin\KokokaraSelect\Service\KsCartHelper;
use Plugin\KokokaraSelect\Service\KsSelectItemGroupService;
use Plugin\KokokaraSelect\Service\KsSelectItemService;
use Plugin\KokokaraSelect\Service\KsService;
use Plugin\KokokaraSelect\Service\PluginLinkService;
use Plugin\KokokaraSelect\Service\VersionHelperTrait;
use Plugin\TagEx2\Twig\Extension\TagExTwigExtension;
use Symfony\Component\Asset\Packages;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class KokokaraSelectTwigExtension extends AbstractExtension
{

    use ConfigHelperTrait;

    use VersionHelperTrait;

    /** @var Packages */
    private $packages;

    /** @var ProductClassRepository */
    private $productClassRepository;

    /** @var KsService */
    protected $ksService;

    /** @var KsCartHelper */
    protected $ksCartHelper;

    /** @var KsSelectItemService */
    protected $ksSelectItemService;

    /** @var PluginLinkService */
    protected $pluginLinkService;

    /** @var TagExTwigExtension */
    protected $tagExTwigExtension;

    /** @var KsSelectItemGroupService */
    protected $ksSelectItemGroupService;

    /** @var KsSelectItemGroupRepository */
    protected $ksSelectItemGroupRepository;

    public function __construct(
        Packages $packages,
        ProductClassRepository $productClassRepository,
        KsService $ksService,
        KsCartHelper $ksCartHelper,
        KsSelectItemService $ksSelectItemService,
        PluginLinkService $pluginLinkService,
        KsSelectItemGroupService $ksSelectItemGroupService,
        KsSelectItemGroupRepository $ksSelectItemGroupRepository
    )
    {
        $this->packages = $packages;
        $this->productClassRepository = $productClassRepository;
        $this->ksService = $ksService;
        $this->ksCartHelper = $ksCartHelper;
        $this->ksSelectItemService = $ksSelectItemService;
        $this->pluginLinkService = $pluginLinkService;
        $this->ksSelectItemGroupService = $ksSelectItemGroupService;
        $this->ksSelectItemGroupRepository = $ksSelectItemGroupRepository;
    }

    /**
     * @Required     *
     * @param TagExTwigExtension|null $tagExTwigExtension
     */
    public function setTagExTwigExtension(?TagExTwigExtension $tagExTwigExtension)
    {
        $this->tagExTwigExtension = $tagExTwigExtension;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('get_kokokara_select_class_info_as_json', [$this, 'getClassInfoAsJson']),
            new TwigFunction('get_kokokara_select_product', [$this, 'getProductClass']),
            new TwigFunction('get_kokokara_select_product_name', [$this, 'getProductClassName']),
            new TwigFunction('is_kokokara_select_product', [$this, 'isKsProduct']),
            new TwigFunction('is_kokokara_select_item', [$this, 'isKsSelectItem']),
            new TwigFunction('is_kokokara_select_cart_group_quantity', [$this, 'isKsCartSelectItemGroupQuantity']),
            new TwigFunction('is_kokokara_select_cart_item_stock', [$this, 'isKsCartSelectItemStock']),
            new TwigFunction('is_kokokara_select_item_stock_zero', [$this, 'isKsSelectItemStockZero']),
            new TwigFunction('get_kokokara_select_order_item_view', [$this, 'getKsOrderItemView']),
            new TwigFunction('get_ks_tag_ex_category_id', [$this, 'getKsTagExCategoryId']),
            new TwigFunction('get_kokokara_select_name', [$this, 'getKokokaraSelectViewName']),
            new TwigFunction('get_kokokara_select_ks_product_info', [$this, 'getKsKsProductInfo']),
            new TwigFunction('get_kokokara_select_view_group_name', [$this, 'getKsViewGroupName']),
            new TwigFunction('is_kokokara_select_view_item', [$this, 'isKsViewItem']),
            new TwigFunction('is_kokokara_select_direct_select', [$this, 'isDirectSelect']),
            new TwigFunction('is_kokokara_select_parent_direct_select', [$this, 'isParentDirectSelect']),
            new TwigFunction('get_kokokara_select_direct_select_name', [$this, 'getKsDirectSelectName']),
            new TwigFunction('get_kokokara_select_marge_items', [$this, 'getKsMargeItems']),
            new TwigFunction('is_normal_kokokara_select_order', [$this, 'isNormalKsOrder']),
            new TwigFunction('is_direct_kokokara_select_order', [$this, 'isDirectSelectKsOrder']),
            new TwigFunction('get_kokokara_select_direct_select_item_view', [$this, 'getKsDirectSelectItemView']),
            new TwigFunction('is_kokokara_select_multiple_version', [$this, 'isKsMultipleVersion']),
            new TwigFunction('is_kokokara_select_version405', [$this, 'isTwigVersion405']),
        ];
    }

    /**
     * ProductClass取得
     *
     * @param $productClassId
     * @return ProductClass
     */
    public function getProductClass($productClassId)
    {
        /** @var ProductClass $ProductClass */
        $ProductClass = $this->productClassRepository->find($productClassId);

        return $ProductClass;
    }

    /**
     * 選択商品表示商品名
     *
     * @param ProductClass $productClass
     * @return string
     */
    public function getProductClassName(ProductClass $productClass)
    {
        return $this->ksSelectItemService->getKsProductClassName($productClass);
    }

    /**
     * [管理]商品選択部分情報取得
     *
     * @param Product $Product
     * @return false|string json
     */
    public function getClassInfoAsJson(Product $Product)
    {
        $Product->_calc();
        $class_categories = [
            '__unselected' => [
                '__unselected' => [
                    'name' => trans('common.select'),
                    'product_class_id' => '',
                ],
            ],
        ];
        foreach ($Product->getProductClasses() as $ProductClass) {
            /** @var ProductClass $ProductClass */
            if (!$ProductClass->isVisible()) {
                continue;
            }
            /* @var $ProductClass ProductClass */
            $ClassCategory1 = $ProductClass->getClassCategory1();
            $ClassCategory2 = $ProductClass->getClassCategory2();
            if ($ClassCategory2 && !$ClassCategory2->isVisible()) {
                continue;
            }
            $class_category_id1 = $ClassCategory1 ? (string)$ClassCategory1->getId() : '__unselected2';
            $class_category_id2 = $ClassCategory2 ? (string)$ClassCategory2->getId() : '';
            $class_category_name2 = $ClassCategory2 ? $ClassCategory2->getName() . ($ProductClass->getStockFind() ? '' : trans('front.product.out_of_stock_label')) : '';

            $product_class_name = $this->getProductClassName($ProductClass);

            if ($Product->getMainFileName()) {
                $fileName = $this->packages->getUrl($Product->getMainFileName(), 'save_image');
            } else {
                $fileName = "";
            }

            $class_categories[$class_category_id1]['#'] = [
                'classcategory_id2' => '',
                'name' => trans('common.select'),
                'product_class_id' => '',
            ];
            $class_categories[$class_category_id1]['#' . $class_category_id2] = [
                'classcategory_id2' => $class_category_id2,
                'name' => $class_category_name2,
                'view_name' => $product_class_name,
                'status_name' => $Product->getStatus()->getName(),
                'stock_name' => ($ProductClass->isStockUnlimited() ? trans('admin.product.stock_unlimited__short') : $ProductClass->getStock()),
                'price01' => $ProductClass->getPrice01() === null ? '' : number_format($ProductClass->getPrice01()),
                'price02' => number_format($ProductClass->getPrice02()),
                'price01_inc_tax' => $ProductClass->getPrice01() === null ? '' : number_format($ProductClass->getPrice01IncTax()),
                'price02_inc_tax' => number_format($ProductClass->getPrice02IncTax()),
                'product_class_id' => (string)$ProductClass->getId(),
                'product_code' => $ProductClass->getCode() === null ? '' : $ProductClass->getCode(),
                'sale_type' => (string)$ProductClass->getSaleType()->getId(),
                'product_image' => $fileName,
            ];
        }

        return json_encode($class_categories);
    }

    /**
     * 選択商品判定
     *
     * @param OrderItem
     * @param bool $directMode
     * @return bool
     */
    public function isKsProduct($OrderItem, $directMode = true)
    {
        return $this->ksService->isKsProduct($OrderItem, $directMode);
    }

    /**
     * 選択商品構成要素判定
     *
     * @param OrderItem $OrderItem
     * @return bool
     */
    public function isKsSelectItem(OrderItem $OrderItem)
    {
        if ($OrderItem->getKsOrderItemEx()) {
            return true;
        }
        return false;
    }

    /**
     * グループの数量が適切かチェック
     *
     * @param Product $product
     * @param KsCartSelectItemGroup $ksCartSelectItemGroup
     * @return bool
     */
    public function isKsCartSelectItemGroupQuantity(Product $product, KsCartSelectItemGroup $ksCartSelectItemGroup)
    {
        return $this->ksCartHelper->validKsCartSelectItemGroupQuantity($product, $ksCartSelectItemGroup);
    }

    /**
     * 選択中商品の在庫数チェック
     *
     * @param KsCartSelectItem $ksCartSelectItem
     * @return bool true:在庫問題なし, false:在庫問題あり
     */
    public function isKsCartSelectItemStock(KsCartSelectItem $ksCartSelectItem)
    {
        $result = $this->ksCartHelper->checkKsCartSelectItemStockSingle($ksCartSelectItem);

        if ($result['result']) {
            return true;
        }

        return false;
    }

    /**
     * 選択商品の在庫切れチェック
     *
     * @param KsSelectItem $ksSelectItem
     * @return bool true:在庫切れ, false:在庫あり
     */
    public function isKsSelectItemStockZero(KsSelectItem $ksSelectItem)
    {
        if ($this->ksSelectItemService->isStockUnlimited($ksSelectItem)) {
            return false;
        }

        $stock = $this->ksSelectItemService->getStock($ksSelectItem);
        if ($stock == 0) {
            return true;
        }

        return false;
    }

    /**
     * 選択商品内訳表示取得
     *
     * @param OrderItem $orderItem
     * @param bool $directSelect
     * @return array
     */
    public function getKsOrderItemView(OrderItem $orderItem, $directSelect = false)
    {

        $result = [];

        /** @var KsOrderItemEx $ksOrderItemChild */
        foreach ($orderItem->getKsOrderItemChildren() as $ksOrderItemChild) {

            $quantity = $ksOrderItemChild->getOrderItem()->getQuantity();

            if ($directSelect) {
                $ksSelectItem = $ksOrderItemChild->getProductName();
                $id = $ksOrderItemChild->getKsSelectItemId();

                $result = $this->ksMargeItem($result, $id, $ksSelectItem, $quantity);

            } else {

                $groupId = $ksOrderItemChild->getGroupId();
                $result[$groupId][] = [
                    'name' => $ksOrderItemChild->getProductName(),
                    'quantity' => $quantity,
                ];
            }


        }

        return $result;
    }

    /**
     * 選択商品の呼称返却
     *
     * @return mixed
     */
    public function getKokokaraSelectViewName()
    {
        return $this->getKokokaraSelectName();
    }

    /**
     * 自動選択商品呼称返却
     *
     * @return string
     */
    public function getKsDirectSelectName()
    {
        return $this->getKokokaraSelectDirectSelectName();
    }

    /**
     * TagEx2連携
     *
     * @param Product $product
     * @return string
     */
    public function getKsTagExCategoryId(Product $product)
    {
        if ($this->pluginLinkService->isActivePlugin('TagEx2')) {
            if ($this->tagExTwigExtension) {
                if (method_exists($this->tagExTwigExtension, "getTagExCategoryId")) {
                    return $this->tagExTwigExtension->getTagExCategoryId($product);
                }
            }
        }

        return "";
    }

    public function getKsKsProductInfo(OrderItem $orderItem)
    {
        $result = [];

        $product = $orderItem->getProduct();
        if ($this->ksService->isKsProduct($product)) {
            $ksProduct = $product->getKsProduct();

            /** @var KsSelectItemGroup $ksSelectItemGroup */
            foreach ($ksProduct->getKsSelectItemGroups() as $key => $ksSelectItemGroup) {

                $groupName = $this->ksSelectItemGroupService->getViewGroupName($ksSelectItemGroup, ($key + 1));

                $result[] = [
                    'name' => $groupName,
                    'quantity' => $ksSelectItemGroup->getQuantity(),
                ];
            }
        }
        return $result;
    }

    /**
     * 表示用グループ名取得
     *
     * @param OrderItem $orderItem
     * @return string|null
     */
    public function getKsViewGroupName(OrderItem $orderItem)
    {
        $ksOrderItemEx = $orderItem->getKsOrderItemEx();
        if ($ksOrderItemEx) {
            $targetKsSelectItemGroup = $ksOrderItemEx->getKsSelectItemGroup();

            if (!$targetKsSelectItemGroup) {
                $ksSelectItemGroupId = $ksOrderItemEx->getKsSelectItemGroupId();

                if ($ksSelectItemGroupId) {
                    $targetKsSelectItemGroup = $this->ksSelectItemGroupRepository->find($ksSelectItemGroupId);
                }
            }

            if ($targetKsSelectItemGroup) {

                $parentProduct = $ksOrderItemEx->getParentOrderItem()->getProduct();

                $parentKsProduct = $parentProduct->getKsProduct();
                $index = 1;

                /** @var KsSelectItemGroup $ksSelectItemGroup */
                foreach ($parentKsProduct->getKsSelectItemGroups() as $ksSelectItemGroup) {
                    if ($ksSelectItemGroup->getId() == $targetKsSelectItemGroup->getId()) {
                        break;
                    }
                    $index++;
                }

                return '[' . $this->ksSelectItemGroupService->getViewGroupName($ksSelectItemGroup, $index) . ']';
            }
        }
        return "[グループ不明]";
    }

    /**
     * 選択商品の公開状態チェック
     *
     * @param KsSelectItem $ksSelectItem
     * @return bool
     */
    public function isKsViewItem(KsSelectItem $ksSelectItem)
    {
        /** @var Product $product */
        $product = $ksSelectItem->getProductClass()->getProduct();

        if ($product->getStatus()->getId() == ProductStatus::DISPLAY_SHOW) {
            return true;
        }

        return false;
    }

    /**
     * 固定セット商品判定
     *
     * @param ProductClass $productClass
     * @return bool true 固定セット商品
     */
    public function isDirectSelect(ProductClass $productClass)
    {
        return $this->ksService->isDirectSelectKsProduct($productClass);
    }

    /**
     * 親商品が固定セット商品か判定
     *
     * @param OrderItem $orderItem
     * @return bool
     */
    public function isParentDirectSelect(OrderItem $orderItem)
    {

        if (!$orderItem->isKsSelectItem()) {
            // 構成品チェック
            false;
        }

        $parentOrderItem = $orderItem->getKsOrderItemEx()->getParentOrderItem();
        $parentProductClass = $parentOrderItem->getProductClass();

        return $this->isDirectSelect($parentProductClass);
    }

    /**
     * 選択商品を集計した情報を返却
     *
     * @param CartItem $cartItem
     * @return array
     */
    public function getKsMargeItems(CartItem $cartItem)
    {

        $result = [];

        if (!$cartItem->isProduct()) {
            return $result;
        }

        /** @var KsCartSelectItemGroup $ksCartSelectItemGroup */
        foreach ($cartItem->getKsCartSelectItemGroups() as $ksCartSelectItemGroup) {
            /** @var KsCartSelectItem $ksCartSelectItem */
            foreach ($ksCartSelectItemGroup->getKsCartSelectItems() as $ksCartSelectItem) {

                $ksSelectItem = $ksCartSelectItem->getKsSelectItem();
                $result = $this->ksMargeItem(
                    $result, $ksSelectItem->getId(), $ksSelectItem, $ksCartSelectItem->getQuantity());
            }
        }

        return $result;
    }

    /**
     * 自動選択商品数量マージ
     *
     * @param $result
     * @param $id
     * @param KsSelectItem|string $ksSelectItem
     * @param $quantity
     * @return
     */
    private function ksMargeItem($result, $id, $ksSelectItem, $quantity)
    {

        if (!isset($result[$id])) {

            if ($ksSelectItem instanceof KsSelectItem) {
                $viewProductName = $ksSelectItem->getProductClass()->getProduct()->getName();
            } else {
                $viewProductName = $ksSelectItem;
            }

            $result[$id] = [
                'viewProductName' => $viewProductName,
                'quantity' => (int)$quantity
            ];
        } else {
            $result[$id]['quantity'] += (int)$quantity;
        }

        return $result;
    }

    /**
     * 通常の選択商品が受注に含まれるかチェック
     *
     * @param Order $order
     * @return bool
     */
    public function isNormalKsOrder(Order $order)
    {
        return $this->ksService->isKsOrder($order, KsService::IS_KS_ORDER_NORMAL);
    }

    /**
     * 自動選択のセット商品が受注に含まれるかチェック
     *
     * @param Order $order
     * @return bool
     */
    public function isDirectSelectKsOrder(Order $order)
    {
        return $this->ksService->isKsOrder($order, KsService::IS_KS_ORDER_DIRECT);
    }

    /**
     * 固定セット商品の内訳情報
     *
     * @param Product $product
     * @return array
     */
    public function getKsDirectSelectItemView(Product $product)
    {
        $result = [];

        $ksProduct = $product->getKsProduct();
        /** @var KsSelectItemGroup $ksSelectItemGroup */
        foreach ($ksProduct->getKsSelectItemGroups() as $ksSelectItemGroup) {
            /** @var KsSelectItem $ksSelectItem */
            foreach ($ksSelectItemGroup->getKsSelectItems() as $ksSelectItem) {

                $targetProduct = $ksSelectItem->getProductClass()->getProduct();
                if ($targetProduct->getStatus()->getId() != ProductStatus::DISPLAY_SHOW) {
                    continue;
                }

                $result = $this->ksMargeItem(
                    $result, $ksSelectItem->getId(), $ksSelectItem, $ksSelectItem->getQuantity());
            }
        }

        return $result;
    }

    /**
     * 固定セット商品 複数配送利用可能バージョンかチェック
     *
     * @return bool
     */
    public function isKsMultipleVersion()
    {
        return $this->isMultipleVersion();
    }

    /**
     * Version 4.0.5以降はtrueを返却
     *
     * @return mixed
     */
    public function isTwigVersion405()
    {
        return $this->isVersion405();
    }
}
