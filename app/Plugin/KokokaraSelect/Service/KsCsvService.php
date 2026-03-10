<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/07/24
 */

namespace Plugin\KokokaraSelect\Service;


use Eccube\Common\EccubeConfig;
use Eccube\Entity\ClassCategory;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Plugin\KokokaraSelect\Entity\KsProduct;
use Plugin\KokokaraSelect\Entity\KsProductOption;
use Plugin\KokokaraSelect\Entity\KsSelectItem;
use Plugin\KokokaraSelect\Entity\KsSelectItemGroup;
use Plugin\KokokaraSelect\Entity\KsSelectItemStock;
use Plugin\KokokaraSelect\Service\MultiCSVService\CsvEntityManagerService;
use Plugin\KokokaraSelect\Service\MultiCSVService\Entity\CsvCheckFunctionArgs;
use Plugin\KokokaraSelect\Service\MultiCSVService\Entity\CsvCheckResult;
use Plugin\KokokaraSelect\Service\MultiCSVService\Entity\CsvUpdateFunctionArgs;
use Plugin\KokokaraSelect\Service\MultiCSVService\MultiCsvService;

class KsCsvService
{

    /** @var MultiCsvService */
    protected $multiCSVService;

    /** @var CsvEntityManagerService */
    protected $csvEntityManagerService;

    /** @var KsService */
    protected $ksService;

    /** @var KsOrderService */
    protected $ksOrderService;

    /** @var KsSelectItemService */
    protected $ksSelectItemService;

    /** @var array */
    protected $ksProductFlg;

    /** @var array */
    protected $groupSettingFlg;

    /** @var EccubeConfig */
    protected $eccubeConfig;

    /** @var KsCsvHelper */
    protected $ksCsvHelper;

    protected $directSelect = false;

    public function __construct(
        MultiCsvService $multiCSVService,
        CsvEntityManagerService $csvEntityManagerService,
        KsService $ksService,
        KsOrderService $ksOrderService,
        KsSelectItemService $ksSelectItemService,
        EccubeConfig $eccubeConfig,
        KsCsvHelper $ksCsvHelper
    )
    {
        $this->multiCSVService = $multiCSVService;
        $this->csvEntityManagerService = $csvEntityManagerService;
        $this->ksService = $ksService;
        $this->ksOrderService = $ksOrderService;
        $this->ksSelectItemService = $ksSelectItemService;
        $this->eccubeConfig = $eccubeConfig;
        $this->ksCsvHelper = $ksCsvHelper;

        $this->ksProductFlg = [];
        $this->groupSettingFlg = [];
    }

    /**
     * 固定セット商品設定用
     *
     * @param boolean $directSelect true:固定商品
     * @return $this
     */
    public function setDirectSelect(bool $directSelect)
    {
        $this->directSelect = $directSelect;
        return $this;
    }

    /**
     * 商品IDチェック
     *
     * @param CsvCheckFunctionArgs $args
     * @return CsvCheckResult
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function checkProductId(CsvCheckFunctionArgs $args)
    {
        $result = new CsvCheckResult();

        // 商品ID
        $value = $args->getColData();

        // カラム名
        $colName = $args->getColName();

        // 商品IDチェック
        if (!$this->multiCSVService->isNumber($value)) {
            $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $args->getLine(), '%name%' => $colName]);
            $result->setMessage($message);

            return $result;
        }

        // 存在チェック
        /** @var Product $product */
        $product = $this->csvEntityManagerService->findEntity(Product::class, $value);

        if (!$product) {
            $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $args->getLine(), '%name%' => $colName]);
            $result->setMessage($message);

            return $result;
        }

        // 選択商品親チェック
        if ($product->hasProductClass()) {
            $message = trans('kokokara_select.csv.error.has_product_class', ['%line%' => $args->getLine(), '%name%' => $colName]);
            $result->setMessage($message);

            return $result;
        }

        // 選択商品判定
        if ($this->ksSelectItemService->isSetting($product)) {
            $message = trans('kokokara_select.csv.error.ks_product', ['%line%' => $args->getLine(), '%name%' => $colName]);
            $result->setMessage($message);

            return $result;
        }

        // 販売済みチェック
        if (!$this->ksService->isKsProduct($product)
            && $this->ksOrderService->isBuyIngProduct($product)) {
            $message = trans('kokokara_select.csv.error.buying', ['%line%' => $args->getLine(), '%name%' => $colName]);
            $result->setMessage($message);

            return $result;
        }

        $result->setSuccess(true);
        return $result;
    }

    /**
     * 商品説明チェック
     *
     * @param CsvCheckFunctionArgs $args
     * @return CsvCheckResult
     */
    public function checkDescription(CsvCheckFunctionArgs $args)
    {
        $result = new CsvCheckResult();

        $value = $args->getColData();

        if (!empty($value)) {
            $max = $this->eccubeConfig['eccube_ltext_len'];
            $message = $this->ksCsvHelper->checkMaxLength($args, $max);
            if (!empty($message)) {
                $result->setMessage($message);

                return $result;
            }
        }

        $result->setSuccess(true);
        return $result;
    }

    /**
     * 選択商品価格表示フラグチェック
     *
     * @param CsvCheckFunctionArgs $args
     * @return CsvCheckResult
     */
    public function checkPriceView(CsvCheckFunctionArgs $args)
    {
        $result = new CsvCheckResult();

        // 選択商品価格表示フラグ
        $value = $args->getColData();

        // カラム名
        $colName = $args->getColName();

        if (!$this->multiCSVService->isBlank($value)) {

            // 数値チェック
            if (!$this->multiCSVService->isNumber($value)) {
                $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $args->getLine(), '%name%' => $colName]);
                $result->setMessage($message);

                return $result;
            }
        }

        $result->setSuccess(true);
        return $result;
    }

    /**
     * グループ番号チェック
     *
     * @param CsvCheckFunctionArgs $args
     * @return CsvCheckResult
     */
    public function checkGroupNo(CsvCheckFunctionArgs $args)
    {
        $result = new CsvCheckResult();

        // グループ番号
        $value = $args->getColData();

        // カラム名
        $colName = $args->getColName();

        // グループ番号チェック
        if (!$this->multiCSVService->isNumber($value)) {
            $message = trans('kokokara_select.csv.error.number', ['%line%' => $args->getLine(), '%name%' => $colName]);
            $result->setMessage($message);

            return $result;
        }

        if ($value <= 0) {
            $message = trans('kokokara_select.csv.error.number', ['%line%' => $args->getLine(), '%name%' => $colName]);
            $result->setMessage($message);

            return $result;
        }

        $result->setSuccess(true);
        return $result;
    }

    /**
     * グループ名チェック
     *
     * @param CsvCheckFunctionArgs $args
     * @return CsvCheckResult
     */
    public function checkGroupName(CsvCheckFunctionArgs $args)
    {
        $result = new CsvCheckResult();

        $value = $args->getColData();

        if (!empty($value)) {
            $max = $this->eccubeConfig['eccube_stext_len'];
            $message = $this->ksCsvHelper->checkMaxLength($args, $max);
            if (!empty($message)) {
                $result->setMessage($message);

                return $result;
            }
        }

        $result->setSuccess(true);
        return $result;
    }

    /**
     * グループ説明チェック
     *
     * @param CsvCheckFunctionArgs $args
     * @return CsvCheckResult
     */
    public function checkGroupDescription(CsvCheckFunctionArgs $args)
    {
        $result = new CsvCheckResult();

        $value = $args->getColData();

        if (!empty($value)) {
            $max = $this->eccubeConfig['eccube_ltext_len'];
            $message = $this->ksCsvHelper->checkMaxLength($args, $max);
            if (!empty($message)) {
                $result->setMessage($message);

                return $result;
            }
        }

        $result->setSuccess(true);
        return $result;
    }

    /**
     * 数量チェック
     *
     * @param CsvCheckFunctionArgs $args
     * @return CsvCheckResult
     */
    public function checkQuantity(CsvCheckFunctionArgs $args)
    {
        $result = new CsvCheckResult();

        // 選択数量
        $value = $args->getColData();

        // カラム名
        $colName = $args->getColName();

        $key = $this->ksCsvHelper->getGroupSettingKey($args->getRowData());

        // グループの先頭
        if (!isset($this->groupSettingFlg[$key])
            || $this->groupSettingFlg[$key] == $args->getLine()) {

            if ($this->multiCSVService->isBlank($value)) {
                $message = trans('kokokara_select.csv.error.number', ['%line%' => $args->getLine(), '%name%' => $colName]);
                $result->setMessage($message);

                return $result;
            }

            if (!$this->multiCSVService->isNumber($value)) {
                $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $args->getLine(), '%name%' => $colName]);
                $result->setMessage($message);

                return $result;
            }

            if ($value <= 0) {
                $message = trans('kokokara_select.csv.error.number', ['%line%' => $args->getLine(), '%name%' => $colName]);
                $result->setMessage($message);

                return $result;
            }
        }

        $result->setSuccess(true);
        return $result;
    }

    /**
     * 投入数量チェック
     *
     * @param CsvCheckFunctionArgs $args
     * @return CsvCheckResult
     */
    public function checkDirectSelectQuantity(CsvCheckFunctionArgs $args)
    {
        $result = new CsvCheckResult();

        $colName = $args->getColName();

        // 投入数量
        $value = $args->getColData();

        if (!$this->multiCSVService->isNumber($value)) {
            $message = trans('kokokara_select.csv.error.number', ['%line%' => $args->getLine(), '%name%' => $colName]);
            $result->setMessage($message);

            return $result;
        }

        if ($value <= 0) {
            $message = trans('kokokara_select.csv.error.number', ['%line%' => $args->getLine(), '%name%' => $colName]);
            $result->setMessage($message);

            return $result;
        }

        $result->setSuccess(true);
        return $result;
    }

    /**
     * 選択商品チェック
     *
     * @param CsvCheckFunctionArgs $args
     * @return CsvCheckResult
     */
    public function checkSelectProductId(CsvCheckFunctionArgs $args)
    {
        $result = new CsvCheckResult();

        // 行
        $line = $args->getLine();

        // 選択商品ID
        $value = $args->getColData();

        $rowData = $args->getRowData();

        // 商品規格ID1
        $classCategoryId1 = $rowData['規格分類1(ID)'];

        // 商品規格ID2
        $classCategoryId2 = $rowData['規格分類2(ID)'];

        $productClass = $this->ksCsvHelper->getProductClass($value, $classCategoryId1, $classCategoryId2);

        if (!$productClass) {
            // 選択商品存在なし
            $message = trans('kokokara_select.csv.error.select.product.none', ['%line%' => $line, '%name%' => $args->getColName()]);
            $result->setMessage($message);
            return $result;
        }

        // 選択商品親でないかチェック
        if ($this->ksService->isKsProduct($productClass)) {
            $message = trans('kokokara_select.csv.error.select.ng.product', ['%line%' => $args->getLine(), '%name%' => '商品情報']);
            $result->setMessage($message);
            return $result;
        }

        $result->setSuccess(true);
        return $result;
    }

    /**
     * 規格分離IDチェック
     *
     * @param CsvCheckFunctionArgs $args
     * @return CsvCheckResult
     */
    public function checkClassCategory(CsvCheckFunctionArgs $args)
    {
        $result = new CsvCheckResult();

        // 行
        $line = $args->getLine();

        // 規格分類IDチェック
        $value = $args->getColData();

        if ($this->multiCSVService->isBlank($value)) {
            // 未設定はOK
            $result->setSuccess(true);
            return $result;
        } else {

            // 数値チェック
            if (!$this->multiCSVService->isNumber($value)) {
                $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => '規格分類ID']);
                $result->setMessage($message);

                return $result;
            }

            // 存在チェック
            $classCategory = $this->csvEntityManagerService->findEntity(ClassCategory::class, $value);

            if (!$classCategory) {
                $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $args->getLine(), '%name%' => '商品情報']);
                $result->setMessage($message);

                return $result;
            }
        }

        $result->setSuccess(true);

        return $result;
    }

    /**
     * 割当在庫数チェック
     *
     * @param CsvCheckFunctionArgs $args
     * @return CsvCheckResult
     */
    public function checkStock(CsvCheckFunctionArgs $args)
    {
        $result = new CsvCheckResult();

        $line = $args->getLine();

        // 在庫数
        $value = $args->getColData();

        $colName = $args->getColName();

        if ($this->multiCSVService->isBlank($value)) {
            // 未設定はOKとする
            // 在庫無制限フラグとの連動は在庫無制限側でチェック
            $result->setSuccess(true);
            return $result;
        }

        // 数値チェック
        if (!$this->multiCSVService->isNumber($value)) {
            $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $colName]);
            $result->setMessage($message);

            return $result;
        }

        // 元在庫数との比較
        $rowData = $args->getRowData();

        // 商品ID
        $productId = $rowData['選択商品ID'];

        // 商品規格ID1
        $classCategoryId1 = $rowData['規格分類1(ID)'];

        // 商品規格ID2
        $classCategoryId2 = $rowData['規格分類2(ID)'];

        $targetProductClass = $this->ksCsvHelper->getProductClass($productId, $classCategoryId1, $classCategoryId2);

        if (!$targetProductClass->isStockUnlimited()) {
            if ($targetProductClass->getStock() < $value) {
                // 在庫オーバー
                $message = trans('kokokara_select.csv.error.stock', ['%line%' => $line, '%name%' => $colName]);
                $result->setMessage($message);

                return $result;
            }
        }

        $result->setSuccess(true);

        return $result;
    }

    /**
     * 割当在庫無制限チェック
     *
     * @param CsvCheckFunctionArgs $args
     * @return CsvCheckResult
     */
    public function checkStockUnlimited(CsvCheckFunctionArgs $args)
    {
        $result = new CsvCheckResult();

        $line = $args->getLine();

        // 在庫数無制限フラグ
        $value = $args->getColData();

        $colName = $args->getColName();

        // 数値チェックメッセージ
        $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $colName]);

        if ($this->multiCSVService->isBlank($value)) {
            $result->setMessage($message);
            return $result;
        }

        if ($this->multiCSVService->isNumber($value)
            && $value >= 0) {

            if ($value == "0") {
                // 在庫数が指定されているか
                $rowData = $args->getRowData();
                $stock = $rowData['割当在庫数'];

                if ($this->multiCSVService->isBlank($stock)) {
                    // 在庫数未設定NG
                    $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => '割当在庫数']);
                    $result->setMessage($message);
                    return $result;
                }
            }

            $result->setSuccess(true);

        } else {
            // 数値が設定されていない
            $result->setMessage($message);
        }

        $result->setSuccess(true);
        return $result;
    }

    /**
     * 選択商品専用フラグ
     *
     * @param CsvCheckFunctionArgs $args
     * @return CsvCheckResult
     */
    public function checkSelectOnly(CsvCheckFunctionArgs $args)
    {
        $result = new CsvCheckResult();

        $line = $args->getLine();

        // 選択商品フラグ
        $value = $args->getColData();

        $colName = $args->getColName();

        if (!$this->multiCSVService->isBlank($value)
            && !$this->multiCSVService->isNumber($value)) {

            $message = trans('admin.common.csv_invalid_greater_than_zero', ['%line%' => $line, '%name%' => $colName]);
            $result->setMessage($message);
            return $result;
        }

        $result->setSuccess(true);
        return $result;
    }

    /**
     * 更新対象のEntity取得
     *
     * @param CsvUpdateFunctionArgs $args
     */
    public function updateProductId(CsvUpdateFunctionArgs $args)
    {
        $rowData = $args->getRowData();

        // 商品ID
        $productId = $rowData['親商品ID'];
        $productClass = $this->ksCsvHelper->getProductClass($productId);

        /** @var Product $product */
        $product = $productClass->getProduct();

        if (!$this->ksService->isKsProduct($product)) {
            $ksProduct = new KsProduct();
            $ksProduct
                ->setQuantity(0)
                ->setProduct($product)
                ->setDirectSelect($this->directSelect);
            $this->csvEntityManagerService->update($ksProduct);

            $product
                ->setKsProduct($ksProduct);
        } else {

            if (!isset($this->ksProductFlg[$productId])) {
                // SortNo調整
                $sortNo = 1;

                $ksProduct = $product->getKsProduct();
                $ksProduct->setDirectSelect($this->directSelect);

                /** @var KsSelectItemGroup $ksSelectItemGroup */
                foreach ($ksProduct->getKsSelectItemGroups() as $ksSelectItemGroup) {
                    $ksSelectItemGroup->setSortNo($sortNo);
                    $sortNo += 1;
                }

                $this->csvEntityManagerService->update($ksProduct);
            }
        }

        if (!isset($this->ksProductFlg[$productId])) {
            $this->ksProductFlg[$productId] = $args->getLine();
        }

        $args->setEntity($productClass);
    }

    /**
     * 商品説明設定
     *
     * @param CsvUpdateFunctionArgs $args
     */
    public function updateDescription(CsvUpdateFunctionArgs $args)
    {
        $rowData = $args->getRowData();
        $value = $args->getColData();

        $productId = $rowData['親商品ID'];

        if (!isset($this->ksProductFlg[$productId])) {
            return;
        }

        if ($this->ksProductFlg[$productId] == $args->getLine()) {
            // 値設定
            /** @var ProductClass $productClass */
            $productClass = $args->getEntity();

            $ksProduct = $productClass->getProduct()->getKsProduct();
            $ksProduct->setDescription($value);

            $this->csvEntityManagerService->update($ksProduct);
        }
    }

    /**
     * 選択商品価格表示フラグ
     *
     * @param CsvUpdateFunctionArgs $args
     */
    public function updatePriceView(CsvUpdateFunctionArgs $args)
    {
        $rowData = $args->getRowData();
        $value = $args->getColData();

        $productId = $rowData['親商品ID'];

        if (!isset($this->ksProductFlg[$productId])) {
            return;
        }

        if ($this->ksProductFlg[$productId] == $args->getLine()) {
            // 値設定
            /** @var ProductClass $productClass */
            $productClass = $args->getEntity();

            $ksProduct = $productClass->getProduct()->getKsProduct();

            if ($value) {
                if ($value == 1) {
                    $ksProduct->setPriceView(true);
                } else {
                    $ksProduct->setPriceView(false);
                }
            } else {
                $ksProduct->setPriceView(false);
            }

            $this->csvEntityManagerService->update($ksProduct);
        }
    }

    /**
     * グループ生成
     *
     * @param CsvUpdateFunctionArgs $args
     */
    public function updateGroupNo(CsvUpdateFunctionArgs $args)
    {
        $rowData = $args->getRowData();
        $value = $args->getColData();

        /** @var ProductClass $productClass */
        $productClass = $args->getEntity();
        $ksProduct = $productClass->getProduct()->getKsProduct();

        $ksSelectItemGroup = $ksProduct->getKsSelectItemGroupBySortNo($value);

        if (!$ksSelectItemGroup) {
            // グループなし新規
            $ksSelectItemGroup = new KsSelectItemGroup();
            $ksSelectItemGroup
                ->setKsProduct($ksProduct)
                ->setSortNo($value);

            $ksProduct->addKsSelectItemGroup($ksSelectItemGroup);

            $args->setEntity($productClass);
        }
    }

    /**
     * グループ情報更新
     *
     * @param CsvUpdateFunctionArgs $args
     */
    public function updateGroupInfo(CsvUpdateFunctionArgs $args)
    {
        $rowData = $args->getRowData();
        $value = $args->getColData();

        $groupNo = $rowData['グループ番号'];

        $groupDescription = $rowData['グループ説明'];

        if (isset($rowData['選択数量'])) {
            $groupQuantity = $rowData['選択数量'];
        } else {
            // 固定セット商品
            $groupQuantity = 0;
        }

        /** @var ProductClass $productClass */
        $productClass = $args->getEntity();

        $key = $this->ksCsvHelper->getGroupSettingKey($rowData);

        if (isset($this->groupSettingFlg[$key])) {
            return;
        }

        $ksProduct = $productClass->getProduct()->getKsProduct();

        $ksSelectItemGroup = $ksProduct->getKsSelectItemGroupBySortNo($groupNo);

        if ($ksSelectItemGroup) {
            $ksSelectItemGroup
                ->setGroupName($value)
                ->setDescription($groupDescription)
                ->setQuantity($groupQuantity);

            // 削除
            $this->ksSelectItemService
                ->deleteRelationKsCartItem($ksSelectItemGroup->getKsSelectItems());

            // 商品情報クリア
            /** @var KsSelectItem $ksSelectItem */
            foreach ($ksSelectItemGroup->getKsSelectItems() as $ksSelectItem) {

                $this->csvEntityManagerService->remove($ksSelectItem);
                $ksSelectItemGroup->removeKsSelectItem($ksSelectItem);
            }

            $this->csvEntityManagerService->update($ksSelectItemGroup);
        }

        if (empty($this->groupSettingFlg)) {
            $ksProduct->setQuantity(0);
        }

        // 合計数量加算
        $ksProduct->setQuantity(($ksProduct->getQuantity() + $groupQuantity));

        $this->groupSettingFlg[$key] = $args->getLine();
    }


    /**
     * 割当在庫数更新
     *
     * @param CsvUpdateFunctionArgs $args
     */
    public function updateStockUnlimited(CsvUpdateFunctionArgs $args)
    {
        $rowData = $args->getRowData();
        $value = $args->getColData();

        // KsSelectItem取得
        $productClass = $this->ksCsvHelper->getProductClass($rowData['親商品ID']);
        $ksSelectItem = $this->getKsSelectItem($productClass, $rowData);

        // 更新対象
        if ($value == 0) {
            // 在庫数取得
            $stock = $rowData['割当在庫数'];

            // 在庫設定
            $ksSelectItem->setStock($stock);
            $ksSelectItem->setStockUnlimited(false);

            $ksSelectItemStock = $ksSelectItem->setKsSelectItemStock();
            $ksSelectItemStock->setStock($stock);

        } else {
            // 在庫無制限

            // 在庫設定
            $ksSelectItem
                ->setStock(null)
                ->setStockUnlimited(true);

            // 在庫テーブルへの設定
            /** @var KsSelectItemStock $ksSelectItemStock */
            $ksSelectItemStock = $ksSelectItem->setKsSelectItemStock();
            $ksSelectItemStock->setStock(null);
        }

        $this->csvEntityManagerService->update($ksSelectItem);

        $args->setEntity($productClass);
    }

    private function getKsSelectItem(ProductClass $productClass, $rowData)
    {
        $ksProduct = $productClass->getProduct()->getKsProduct();

        $ksSelectItemGroup = $ksProduct->getKsSelectItemGroupBySortNo($rowData['グループ番号']);

        // 商品ID
        $productId = $rowData['選択商品ID'];

        // 商品規格ID1
        $classCategoryId1 = $rowData['規格分類1(ID)'];

        // 商品規格ID2
        $classCategoryId2 = $rowData['規格分類2(ID)'];

        $targetProductClass = $this->ksCsvHelper->getProductClass($productId, $classCategoryId1, $classCategoryId2);
        $ksSelectItem = $this->ksCsvHelper->getKsSelectItem($ksSelectItemGroup, $targetProductClass->getId());

        if (!$ksSelectItem) {
            $ksSelectItem = new KsSelectItem();
            $ksSelectItem
                ->setProductClass($targetProductClass)
                ->setKsSelectItemGroup($ksSelectItemGroup)
                ->setSortNo(($ksSelectItemGroup->getKsSelectItems()->count() + 1));

            $ksSelectItemGroup->addKsSelectItem($ksSelectItem);
        }

        return $ksSelectItem;
    }

    /**
     * 投入数量更新
     *
     * @param CsvUpdateFunctionArgs $args
     */
    public function updateDirectSelectQuantity(CsvUpdateFunctionArgs $args)
    {
        $rowData = $args->getRowData();
        $value = $args->getColData();

        // KsSelectItem取得
        $productClass = $this->ksCsvHelper->getProductClass($rowData['親商品ID']);
        $ksSelectItem = $this->getKsSelectItem($productClass, $rowData);

        // 投入数量設定
        $ksSelectItem->setQuantity($value);
        $this->csvEntityManagerService->update($ksSelectItem);

        // グループ数量更新
        $ksSelectItemGroup = $ksSelectItem->getKsSelectItemGroup();
        $quantity = $ksSelectItemGroup->getQuantity();
        $ksSelectItemGroup->setQuantity($quantity + $value);
        $this->csvEntityManagerService->update($ksSelectItemGroup);

        // 選択商品計
        $ksProduct = $ksSelectItemGroup->getKsProduct();
        $ksProduct->setQuantity($ksProduct->getQuantity() + $value);

        $args->setEntity($productClass);
    }

    /**
     * 選択専用商品更新
     *
     * @param CsvUpdateFunctionArgs $args
     */
    public function updateSelectOnly(CsvUpdateFunctionArgs $args)
    {

        $rowData = $args->getRowData();
        $value = $args->getColData();

        // 商品ID
        $productId = $rowData['選択商品ID'];

        // 商品規格ID1
        $classCategoryId1 = $rowData['規格分類1(ID)'];

        // 商品規格ID2
        $classCategoryId2 = $rowData['規格分類2(ID)'];

        $targetProductClass = $this->ksCsvHelper->getProductClass($productId, $classCategoryId1, $classCategoryId2);

        if ($targetProductClass) {

            $product = $targetProductClass->getProduct();
            $productOption = $product->getKsProductOption();
            if (!$productOption) {
                $productOption = new KsProductOption();
                $productOption
                    ->setProduct($product);
            }

            if ($value) {
                $productOption->setSelectOnly(true);
            } else {
                $productOption->setSelectOnly(false);
            }

            $this->csvEntityManagerService->update($productOption);

            $product->setKsProductOption($productOption);
        }
    }

    /**
     * 設定処理Skip
     *
     * @param CsvUpdateFunctionArgs $args
     */
    public function updateSkip(CsvUpdateFunctionArgs $args)
    {
        return;
    }

    /**
     * CSV登録処理後更新処理
     */
    public function afterUpdate()
    {
        $checkData = [];
        foreach ($this->groupSettingFlg as $key => $item) {
            $data = explode('-', $key);
            $checkData[$data[0]][$data[1]] = true;
        }

        foreach ($checkData as $productId => $item) {

            /** @var Product $product */
            $product = $this->csvEntityManagerService->findEntity(Product::class, $productId);

            $ksProduct = $product->getKsProduct();

            $index = 1;
            /** @var KsSelectItemGroup $ksSelectItemGroup */
            foreach ($ksProduct->getKsSelectItemGroups() as $ksSelectItemGroup) {

                $sortNo = $ksSelectItemGroup->getSortNo();
                if (!isset($item[$sortNo])) {
                    // 削除
                    $this->ksSelectItemService
                        ->deleteRelationKsCartItem($ksSelectItemGroup->getKsSelectItems());

                    $this->csvEntityManagerService->remove($ksSelectItemGroup);
                } else {
                    // ソート振り直し
                    $ksSelectItemGroup->setSortNo($index);
                    $this->csvEntityManagerService->update($ksSelectItemGroup);
                    $index += 1;
                }
            }
        }
    }
}
