<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/07/24
 */

namespace Plugin\KokokaraSelect\Controller\Admin\Product;


use Eccube\Controller\Admin\AbstractCsvImportController;
use Eccube\Entity\ProductClass;
use Plugin\KokokaraSelect\Service\KsCsvService;
use Plugin\KokokaraSelect\Service\MultiCSVService\Annotation\MultiCsv;
use Plugin\KokokaraSelect\Service\MultiCSVService\Controller\CsvInfoInterface;
use Plugin\KokokaraSelect\Service\MultiCSVService\Controller\CsvMultiControllerInterface;
use Plugin\KokokaraSelect\Service\MultiCSVService\Controller\CsvMultiControllerTrait;
use Plugin\KokokaraSelect\Service\MultiCSVService\Entity\CsvInfo;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class KokokaraSelectCSVController
 * @package Plugin\KokokaraSelect\Controller\Admin\Product
 *
 * @MultiCsv(
 *     title="kokokara_select.csv.title",
 *     subTitle="kokokara_select.csv.root.title",
 *     menus={"product", "admin_kokokara_select_root", "admin_kokokara_select"},
 *     upMessage=""
 * )
 */
class KokokaraSelectCSVController extends AbstractCsvImportController implements CsvInfoInterface, CsvMultiControllerInterface
{

    use CsvMultiControllerTrait;

    use KokokaraSelectCommonCSVTrait;

    /** @var KsCsvService */
    protected $ksCsvService;

    public function __construct(
        KsCsvService $ksCsvService
    )
    {
        $this->ksCsvService = $ksCsvService;
        $this->ksCsvService->setDirectSelect(false);
    }

    /**
     * メイン画面
     *
     * @Route("/%eccube_admin_route%/product/kokokara_select_csv", name="admin_kokokara_select_csv")
     * @Template("@KokokaraSelect/admin/csv.twig")
     *
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function index(Request $request)
    {
        return $this->indexResponse($request);
    }

    /**
     * 雛形ダウンロード
     *
     * @Route("/%eccube_admin_route%/product/kokokara_select_csv_template", name="admin_kokokara_select_csv_template")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function templateDownload(Request $request)
    {
        return $this->templateResponse($request);
    }

    /**
     * 未使用
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|void
     */
    public function templateDataDownload(Request $request)
    {
        // 未使用
    }

    /**
     * @return CsvInfo
     */
    public function getCsvInfo()
    {
        $csvInfo = new CsvInfo(ProductClass::class);

        // KsProduct共通部
        $this->setKsProductCommon($csvInfo);

        // 選択商品価格表示
        $csvInfo
            ->createHeader('選択商品価格表示フラグ')
            ->targetON()
            ->setDescription('0:非表示 1:表示を指定します。親商品IDの先頭レコードの値が設定されます。')
            ->setCheckFunction([$this->ksCsvService, 'checkPriceView'])
            ->setUpdateFunction([$this->ksCsvService, 'updatePriceView']);

        $this->setKsGroupCommon($csvInfo);

        // 選択数量
        $csvInfo
            ->createHeader('選択数量')
            ->requiredON()
            ->targetON()
            ->setDescription('グループで選択させる商品点数。グループの先頭レコードの値が設定されます。')
            ->setCheckFunction([$this->ksCsvService, 'checkQuantity'])
            ->setUpdateFunction([$this->ksCsvService, 'updateSkip']);

        // アイテム部
        $this->setKsItemCommon($csvInfo);

        return $csvInfo;
    }

    public function getTemplateFileName()
    {
        return "kokokara_select";
    }
}
