<?php


namespace Plugin\KokokaraSelect\Controller\Admin\Product;


use Plugin\KokokaraSelect\Service\KsCsvService;
use Plugin\KokokaraSelect\Service\MultiCSVService\Entity\CsvInfo;

/**
 * Trait KokokaraSelectCommonCSVTrait
 * @package Plugin\KokokaraSelect\Controller\Admin\Product
 *
 * @property KsCsvService $ksCsvService;
 */
trait KokokaraSelectCommonCSVTrait
{

    public function setKsProductCommon(CsvInfo $csvInfo)
    {
        // ID
        $csvInfo
            ->createHeader('親商品ID')
            ->setId('Product.id')
            ->requiredON()
            ->targetON()
            ->setDescription('セット商品の商品IDを設定します。')
            ->setCheckFunction([$this->ksCsvService, 'checkProductId'])
            ->setUpdateFunction([$this->ksCsvService, 'updateProductId']);

        // 名称
        $csvInfo
            ->createHeader('親商品名')
            ->setId('Product.name')
            ->setDescription('参考情報。CSVを分かりやすくするための項目です。');

        // 商品説明
        $csvInfo
            ->createHeader('商品説明')
            ->targetON()
            ->setDescription('選択商品の商品説明を設定します。親商品IDの先頭レコードの値が設定されます。')
            ->setCheckFunction([$this->ksCsvService, 'checkDescription'])
            ->setUpdateFunction([$this->ksCsvService, 'updateDescription']);

    }

    public function setKsGroupCommon(CsvInfo $csvInfo)
    {
        // グループ番号
        $csvInfo
            ->createHeader('グループ番号')
            ->setDescription('選択グループの番号。1からの連番を設定します。この値が同じ選択商品が同じグループに属します。')
            ->targetON()
            ->setCheckFunction([$this->ksCsvService, 'checkGroupNo'])
            ->setUpdateFunction([$this->ksCsvService, 'updateGroupNo']);

        // 表示グループ名称
        $csvInfo
            ->createHeader('グループ名称')
            ->targetON()
            ->setDescription('選択グループの名称。グループの先頭レコードの値が設定されます。')
            ->setCheckFunction([$this->ksCsvService, 'checkGroupName'])
            ->setUpdateFunction([$this->ksCsvService, 'updateGroupInfo']);

        // グループ説明
        $csvInfo
            ->createHeader('グループ説明')
            ->targetON()
            ->setDescription('選択グループの説明。グループの先頭レコードの値が設定されます。')
            ->setCheckFunction([$this->ksCsvService, 'checkGroupDescription'])
            ->setUpdateFunction([$this->ksCsvService, 'updateSkip']);

    }


    public function setKsItemCommon(CsvInfo $csvInfo)
    {
        // 選択商品 商品ID
        $csvInfo
            ->createHeader('選択商品ID')
            ->requiredON()
            ->targetON()
            ->setDescription('選択商品の商品IDを設定します。')
            ->setCheckFunction([$this->ksCsvService, 'checkSelectProductId'])
            ->setUpdateFunction([$this->ksCsvService, 'updateSkip']);

        // 規格分類1ID
        $csvInfo
            ->createHeader('規格分類1(ID)')
            ->setId('ClassCategory1.id')
            ->setDescription('')
            ->setCheckFunction([$this->ksCsvService, 'checkClassCategory']);

        // 規格分類2ID
        $csvInfo
            ->createHeader('規格分類2(ID)')
            ->setId('ClassCategory2.id')
            ->setDescription('')
            ->setCheckFunction([$this->ksCsvService, 'checkClassCategory']);

        // 割当在庫数
        $csvInfo
            ->createHeader('割当在庫数')
            ->targetON()
            ->setDescription('割当在庫数無制限フラグが0の場合、0以上の数値を設定してください。')
            ->setCheckFunction([$this->ksCsvService, 'checkStock'])
            ->setUpdateFunction([$this->ksCsvService, 'updateSkip']);

        // 割当在庫数無制限フラグ
        $csvInfo
            ->createHeader('割当在庫数無制限フラグ')
            ->targetON()
            ->requiredON()
            ->setDescription('0:制限 1:全在庫数を指定します。')
            ->setCheckFunction([$this->ksCsvService, 'checkStockUnlimited'])
            ->setUpdateFunction([$this->ksCsvService, 'updateStockUnlimited']);

        // 選択商品専用フラグ
        $csvInfo
            ->createHeader('選択商品専用フラグ')
            ->targetON()
            ->setDescription('0:通常商品 1:選択専用商品を指定します。')
            ->setCheckFunction([$this->ksCsvService, 'checkSelectOnly'])
            ->setUpdateFunction([$this->ksCsvService, 'updateSelectOnly']);

        // 選択商品商品名
        $csvInfo
            ->createHeader('選択商品商品名')
            ->setDescription('参考情報。CSVを分かりやすくするための項目です。');

        // 規格分類1名
        $csvInfo
            ->createHeader('規格1名称')
            ->setId('ClassCategory1.name')
            ->setDescription('参考情報。CSVを分かりやすくするための項目です。');

        // 規格分類2名
        $csvInfo
            ->createHeader('規格2名称')
            ->setId('ClassCategory2.name')
            ->setDescription('参考情報。CSVを分かりやすくするための項目です。');

        // 完了前処理
        $csvInfo
            ->setAfterFunction([$this->ksCsvService, 'afterUpdate']);
    }
}
