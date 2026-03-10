<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/17
 */

namespace Plugin\KokokaraSelect\Service\MultiCSVService\Controller;


use Plugin\KokokaraSelect\Service\MultiCSVService\CsvExportHelper;
use Plugin\KokokaraSelect\Service\MultiCSVService\CsvExportService;
use Plugin\KokokaraSelect\Service\MultiCSVService\CsvMultiHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CsvMultiControllerTrait
 *
 * @package Plugin\KokokaraSelect\Service\MultiCSVService\Controller
 */
trait CsvMultiControllerTrait
{

    /** @var CsvMultiHelper */
    protected $csvMultiHelper;

    /** @var CsvExportService */
    protected $csvExportService;

    /** @var CsvExportHelper */
    protected $csvExportHelper;

    /**
     * @param CsvMultiHelper $csvMultiHelper
     * @required
     * @throws \ReflectionException
     */
    public function setCsvMultiHelper(CsvMultiHelper $csvMultiHelper)
    {
        /** @var CsvMultiHelper csvMultiHelper */
        $this->csvMultiHelper = $csvMultiHelper;

        if ($this instanceof CsvInfoInterface) {
            $this->csvMultiHelper->loadCsvInfo($this);
        }

        // コントローラ初期処理
        $this->csvMultiHelper->initializeController($this);
    }

    /**
     * @param CsvExportService $csvExportService
     * @required
     */
    public function setCsvExportService(CsvExportService $csvExportService)
    {
        $this->csvExportService = $csvExportService;
    }

    /**
     * @param CsvExportHelper $csvExportHelper
     * @required
     */
    public function setCsvExportHelper(CsvExportHelper $csvExportHelper)
    {
        $this->csvExportHelper = $csvExportHelper;
    }

    /**
     * @param CsvInfoInterface $csvInfo
     */
    public function loadCsvInfo(CsvInfoInterface $csvInfo)
    {
        $this->csvMultiHelper->loadCsvInfo($csvInfo);
    }

    /**
     * メイン画面
     *
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function indexResponse(Request $request)
    {
        $form = $this->csvMultiHelper->createCsvForm();

        $headers = $this->csvMultiHelper->getHeaders();

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $formFile = $this->csvMultiHelper->getCsvFile($form);
                if (!empty($formFile)) {

                    $result = $this->csvMultiHelper->importCsv(
                        $form,
                        $this->getImportData($formFile)
                    );

                    if (!is_null($result)) {
                        return $result;
                    }
                }
            }
        }

        return $this->csvMultiHelper->renderWithError($form, $headers);
    }

    /**
     * 雛形ダウンロード
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function templateResponse(Request $request)
    {
        $headers = $this->csvMultiHelper->getTemplateHeader();
        $filename = $this->getTemplateFileName() . '.csv';

        return $this->sendTemplateResponse($request, $headers, $filename);
    }

    /**
     * 雛形＋全データダウンロード
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws \Exception
     */
    public function templateResponseFull(Request $request)
    {
        return $this->csvExportService->exportCSV(
            $this->getCsvInfo(),
            $this->getTemplateFileName()
        );
    }
}
