<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/08/30
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
 * Class KokokaraSelectDirectCSVController
 * @package Plugin\KokokaraSelect\Controller\Admin\Product
 *
 * @MultiCsv(
 *     title="kokokara_select.csv.direct.title",
 *     subTitle="kokokara_select.csv.root.title",
 *     menus={"product", "admin_kokokara_select_root", "admin_kokokara_select_direct"},
 *     upMessage=""
 * )
 */
class KokokaraSelectDirectCSVController extends AbstractCsvImportController implements CsvInfoInterface, CsvMultiControllerInterface
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
        $this->ksCsvService->setDirectSelect(true);
    }

    /**
     * メイン画面
     *
     * @Route("/%eccube_admin_route%/product/admin_kokokara_select_direct_csv", name="admin_kokokara_select_direct_csv")
     * @Template("@KokokaraSelect/admin/csv.twig")
     *
     * @param Request $request
     * @return array|void
     * @throws \Exception
     */
    public function index(Request $request)
    {
        return $this->indexResponse($request);
    }

    /**
     * 雛形ダウンロード
     *
     * @Route("/%eccube_admin_route%/product/admin_kokokara_select_direct_csv_template", name="admin_kokokara_select_direct_csv_template")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function templateDownload(Request $request)
    {
        return $this->templateResponse($request);
    }

    public function templateDataDownload(Request $request)
    {
        // 未使用
    }

    public function getCsvInfo()
    {
        $csvInfo = new CsvInfo(ProductClass::class);

        // KsProduct共通部
        $this->setKsProductCommon($csvInfo);

        // グループ部
        $this->setKsGroupCommon($csvInfo);

        // アイテム部
        // 選択商品 投入数量
        $csvInfo
            ->createHeader('投入数量')
            ->targetON()
            ->requiredON()
            ->setDescription('固定セット販売でのセット商品内訳の数量を指定します。')
            ->setCheckFunction([$this->ksCsvService, 'checkDirectSelectQuantity'])
            ->setUpdateFunction([$this->ksCsvService, 'updateDirectSelectQuantity']);

        // アイテム部
        $this->setKsItemCommon($csvInfo);

        return $csvInfo;
    }

    public function getTemplateFileName()
    {
        return "kokokara_select_direct";
    }
}
