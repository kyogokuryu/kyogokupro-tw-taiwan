<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/07/28
 */

namespace Plugin\KokokaraSelect\Service;


use Doctrine\Common\Annotations\Annotation\Required;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\ProductClass;
use Plugin\KokokaraSelect\Entity\KsSelectItem;
use Plugin\KokokaraSelect\Entity\KsSelectItemGroup;
use Plugin\KokokaraSelect\Service\MultiCSVService\CsvEntityManagerService;
use Plugin\KokokaraSelect\Service\MultiCSVService\Entity\CsvCheckFunctionArgs;

class KsCsvHelper
{

    /** @var CsvEntityManagerService */
    protected $csvEntityManagerService;

    /** @var EccubeConfig */
    protected $eccubeConfig;

    /**
     * @Required
     * @param CsvEntityManagerService $csvEntityManagerService
     */
    public function setCsvEntityManagerService(CsvEntityManagerService $csvEntityManagerService)
    {
        $this->csvEntityManagerService = $csvEntityManagerService;
    }

    /**
     * @Required
     * @param EccubeConfig $eccubeConfig
     */
    public function setEccubeConfig(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * Groupの先頭判別用配列のキー取得
     *
     * @param $rowData
     * @return string
     */
    public function getGroupSettingKey($rowData)
    {
        $productId = $rowData['親商品ID'];
        $groupNo = $rowData['グループ番号'];

        return $productId . '-' . $groupNo;
    }

    /**
     * ProductClass取得
     *
     * @param $productId
     * @param null $classCategoryId1
     * @param null $classCategoryId2
     * @return ProductClass|object|null
     */
    public function getProductClass($productId, $classCategoryId1 = null, $classCategoryId2 = null)
    {

        $search = [
            'Product' => $productId
        ];

        if (!empty($classCategoryId1)) {
            $search += ['ClassCategory1' => $classCategoryId1];
        }

        if (!empty($classCategoryId2)) {
            $search += ['ClassCategory2' => $classCategoryId2];
        }

        return $this->csvEntityManagerService->findOneEntity(ProductClass::class, $search);
    }

    /**
     * 文字数チェック
     *
     * @param CsvCheckFunctionArgs $args
     * @param $max
     * @return string
     */
    public function checkMaxLength(CsvCheckFunctionArgs $args, $max)
    {

        // グループ名
        $value = $args->getColData();

        // カラム名
        $colName = $args->getColName();

        if (!empty($value)) {
            if (strlen($value) > $this->eccubeConfig['eccube_stext_len']) {
                return trans('kokokara_select.csv.error.group_name', [
                    '%line%' => $args->getLine(), '%name%' => $colName, '%max%' => $max
                ]);
            }
        }

        return "";
    }

    /**
     * KsSelectItemGroupから指定したProductClassIdのKsSelectItemを取り出し
     *
     * @param KsSelectItemGroup $ksSelectItemGroup
     * @param $productClassId
     * @return KsSelectItem|null
     */
    public function getKsSelectItem($ksSelectItemGroup, $productClassId)
    {
        /** @var KsSelectItem $ksSelectItem */
        foreach ($ksSelectItemGroup->getKsSelectItems() as $ksSelectItem) {
            if ($ksSelectItem->getProductClass()->getId() == $productClassId) {
                return $ksSelectItem;
            }
        }

        return null;
    }
}
