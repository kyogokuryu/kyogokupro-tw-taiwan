<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/17
 */

namespace Plugin\KokokaraSelect\Service\MultiCSVService;


use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\QueryBuilder;
use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\Admin\CsvImportType;
use Eccube\Service\CsvImportService;
use Eccube\Util\CacheUtil;
use Plugin\KokokaraSelect\Service\MultiCSVService\Annotation\MultiCsv;
use Plugin\KokokaraSelect\Service\MultiCSVService\Controller\CsvInfoInterface;
use Plugin\KokokaraSelect\Service\MultiCSVService\Controller\CsvMultiControllerInterface;
use Plugin\KokokaraSelect\Service\MultiCSVService\Entity\CsvCheckFunctionArgs;
use Plugin\KokokaraSelect\Service\MultiCSVService\Entity\CsvCheckResult;
use Plugin\KokokaraSelect\Service\MultiCSVService\Entity\CsvInfo;
use Plugin\KokokaraSelect\Service\MultiCSVService\Entity\CsvInfoHeader;
use Plugin\KokokaraSelect\Service\MultiCSVService\Entity\CsvUpdateFunctionArgs;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

/**
 * @version 2.7.0
 *
 * Class CsvMultiHelper
 * @package Plugin\KokokaraSelect\Service\MultiCSVService
 */
class CsvMultiHelper
{
    /** @var CsvInfoInterface */
    protected $csvInfoInterface;

    /** @var CsvInfo */
    protected $csvInfoObj;

    /** @var FormFactoryInterface */
    protected $formFactory;

    protected $errors = [];

    /** @var CacheUtil */
    protected $cacheUtil;

    /** @var Session */
    protected $session;

    /** @var EccubeConfig */
    protected $eccubeConfig;

    /** @var CsvEntityManagerService */
    protected $csvEntityManagerService;

    /** @var CsvExportService */
    protected $csvExportService;

    /** @var Environment */
    protected $twig;

    /** @var Reader */
    protected $reader;

    public function __construct(
        CacheUtil $cacheUtil,
        CsvEntityManagerService $csvEntityManagerService,
        CsvExportService $csvExportService,
        Environment $twig,
        Reader $reader
    )
    {
        $this->cacheUtil = $cacheUtil;
        $this->csvEntityManagerService = $csvEntityManagerService;
        $this->csvExportService = $csvExportService;

        $this->twig = $twig;
        $this->reader = $reader;
    }

    /**
     * コントローラ初期処理
     *
     * @param $classObj
     * @throws \ReflectionException
     */
    public function initializeController($classObj)
    {

        $refObj = new \ReflectionObject($classObj);

        /** @var MultiCsv $multiCsv */
        $multiCsv = $this->reader->getClassAnnotation($refObj, 'Plugin\KokokaraSelect\Service\MultiCSVService\Annotation\MultiCsv');

        if ($multiCsv) {
            // 表示情報設定
            $this->twig->addGlobal('csv_page_title', trans($multiCsv->getTitle()));
            $this->twig->addGlobal('csv_page_sub_title', trans($multiCsv->getSubTitle()));
            $this->twig->addGlobal('csv_menus', $multiCsv->getMenus());
            $this->twig->addGlobal('csv_up_message', trans($multiCsv->getUpMessage()));

            // ダウンロード用URL設定
            if ($classObj instanceof CsvMultiControllerInterface) {

                $method = $refObj->getMethod('templateDownload');
                /** @var Route $templateDownloadAnnotation */
                $templateDownloadAnnotation = $this->reader->getMethodAnnotation($method, 'Symfony\Component\Routing\Annotation\Route');

                if ($templateDownloadAnnotation) {
                    $tmpUrl = $templateDownloadAnnotation->getName();
                } else {
                    $tmpUrl = "";
                }
                $this->twig->addGlobal('csv_template_url', $tmpUrl);

                $method = $refObj->getMethod('templateDataDownload');
                /** @var Route $templateDataDownloadAnnotation */
                $templateDataDownloadAnnotation = $this->reader->getMethodAnnotation($method, 'Symfony\Component\Routing\Annotation\Route');

                if ($templateDataDownloadAnnotation) {
                    $tmpDataUrl = $templateDataDownloadAnnotation->getName();
                } else {
                    $tmpDataUrl = "";
                }
                $this->twig->addGlobal('csv_template_data_url', $tmpDataUrl);

            } else {
                $this->twig->addGlobal('csv_template_url', "");
                $this->twig->addGlobal('csv_template_data_url', "");
            }
        } else {
            // 表示情報設定
            $this->twig->addGlobal('csv_page_title', "汎用CSV");
            $this->twig->addGlobal('csv_page_sub_title', "");
            $this->twig->addGlobal('csv_menus', []);
            $this->twig->addGlobal('csv_up_message', "");
            $this->twig->addGlobal('csv_template_url', "");
            $this->twig->addGlobal('csv_template_data_url', "");
        }
    }

    /**
     * @param EccubeConfig $eccubeConfig
     * @required
     */
    public function setEccubeConfig(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * @param SessionInterface $session
     * @required
     */
    public function setSession(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @param FormFactoryInterface $formFactory
     * @required
     */
    public function setFormFactory(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * CSV読込用フォーム生成
     *
     * @return FormInterface
     */
    public function createCsvForm()
    {
        return $this->formFactory->createBuilder(CsvImportType::class)->getForm();
    }

    /**
     * 取込CSV情報の読込
     *
     * @param CsvInfoInterface $csvInfo
     * @return CsvInfo
     */
    public function loadCsvInfo(CsvInfoInterface $csvInfo)
    {
        $this->csvInfoInterface = $csvInfo;
        $this->csvInfoObj = $csvInfo->getCsvInfo();

        return $this->csvInfoObj;
    }

    /**
     * ヘッダ情報取得
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->csvInfoObj->getHeadersToArray();
    }

    /**
     * ヘッダより Key = true のKeyリストを取得
     *
     * @return array
     */
    public function getKeyColumns()
    {
        $keyColumns = array_keys(array_filter($this->getHeaders(), function ($value) {
            return (isset($value['key']) && $value['key'] ? true : false);
        }));

        return $keyColumns;
    }

    public function renderWithError($form, $headers, $rollback = true)
    {
        if ($this->hasErrors()) {
            if ($rollback) {
                $this->csvEntityManagerService->rollback();
            }
        }

        $this->removeUploadedFile();

        return [
            'form' => $form->createView(),
            'headers' => $headers,
            'errors' => $this->errors,
        ];
    }

    /**
     * 登録、更新時のエラー画面表示
     * @param $message
     */
    protected function addErrors($message)
    {
        $this->errors[] = $message;
    }

    /**
     * @return array
     */
    protected function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return boolean
     */
    protected function hasErrors()
    {
        return count($this->getErrors()) > 0;
    }

    /**
     * アップロードされたCSVファイルの削除
     */
    protected function removeUploadedFile()
    {
        if (!empty($this->csvFileName)) {
            try {
                $fs = new Filesystem();
                $fs->remove($this->eccubeConfig['eccube_csv_temp_realdir'] . '/' . $this->csvFileName);
            } catch (\Exception $e) {
                // エラーが発生しても無視する
            }
        }
    }

    /**
     *
     * @param $form
     * @return mixed
     */
    public function getCsvFile($form)
    {
        return $form['import_file']->getData();
    }

    /**
     * CSV取込処理
     *
     * @param $form
     * @param $data
     * @return null
     * @throws \Exception
     */
    public function importCsv($form, $data)
    {
        log_info('CSV登録開始');

        // 共通チェック
        $ret = $this->commonCheck($form, $this->getHeaders(), $data);
        if (!$ret['success']) {
            return $ret['response'];
        }

        // 個別処理
        $ret = $this->csvUpdate($form, $this->csvInfoObj, $data, $this->cacheUtil);
        if (!$ret['success']) {
            return $ret['response'];
        }

        log_info('CSV登録完了');

        return null;
    }

    /**
     * @param $form
     * @param $headers
     * @param CsvImportService $data
     * @return array
     */
    private function commonCheck($form, $headers, CsvImportService $data)
    {

        log_info("共通チェック開始");

        $ret = [
            'success' => true,
            'response' => null
        ];

        if ($data === false) {
            log_info("CSVのフォーマット不一致(データ無し)");
            $this->addErrors(trans('admin.common.csv_invalid_format'));

            return [
                'success' => false,
                'response' => $this->renderWithError($form, $headers, false)
            ];
        }
        $getId = function ($item) {
            return $item['id'];
        };
        $requireHeader = array_keys(array_map($getId, array_filter($headers, function ($value) {
            return (isset($value['required']) ? $value['required'] : false);
        })));

        $columnHeaders = $data->getColumnHeaders();

        if (count(array_diff($requireHeader, $columnHeaders)) > 0) {
            log_info("CSVのフォーマット不一致", [
                '規定' => $requireHeader,
                'データ' => $columnHeaders,
            ]);
            $this->addErrors(trans('admin.common.csv_invalid_format'));

            return [
                'success' => false,
                'response' => $this->renderWithError($form, $headers, false)
            ];
        }

        $size = count($data);

        if ($size < 1) {
            log_info("読み込みファイルにデータ無し");
            $this->addErrors(trans('admin.common.csv_invalid_no_data'));

            return [
                'success' => false,
                'response' => $this->renderWithError($form, $headers, false)
            ];
        }

        log_info("共通チェック終了");

        return $ret;
    }

    /**
     * @param $form
     * @param CsvInfo $csvInfo
     * @param CsvImportService $data
     * @param CacheUtil $cacheUtil
     * @return array
     * @throws \Exception
     */
    private function csvUpdate($form, $csvInfo, CsvImportService $data, CacheUtil $cacheUtil)
    {

        log_info("CSV更新処理開始");

        $getId = function ($item) {
            return $item['id'];
        };

        $headers = $csvInfo->getHeadersToArray();
        $entityClassName = $csvInfo->getTable();

        $headerSize = $csvInfo->headerSize();
        $this->csvEntityManagerService->beginTransaction();

        foreach ($data as $row) {

            // 新規・更新判定
            $keyColumns = $this->getKeyColumns();

            if (count($keyColumns) === 1) {
                $id = $row[$keyColumns[0]];

                if (!empty($id)) {
                    $entity = $this->csvEntityManagerService->findEntity($entityClassName, $id);
                } else {
                    $entity = new $entityClassName();
                }
            } elseif (count($keyColumns) > 1) {

                $args = [];
                foreach ($keyColumns as $keyColumn) {
                    $key = $headers[$keyColumn]['id'];
                    $targetKeys = explode('.', $key);
                    $targetKey = $targetKeys[0];

                    $args[$targetKey] = $row[$keyColumn];
                }
                $entity = $this->csvEntityManagerService->findOneEntity($entityClassName, $args);

            } else {
                $entity = new $entityClassName();
            }

            $line = $data->key() + 1;

            // ヘッダ件数チェック
            if ($headerSize != count($row)) {
                log_info("ヘッダ件数チェックNG", [
                    'line' => $line,
                    '件数規定' => $headerSize,
                    'データ' => count($row),
                ]);
                $message = trans('admin.common.csv_invalid_format_line', ['%line%' => $line]);
                $this->addErrors($message);

                return [
                    'success' => false,
                    'response' => $this->renderWithError($form, $headers)
                ];
            }

            $delete = false;
            $deleteColName = $this->csvInfoObj->getDeleteColName();
            if ($deleteColName) {
                $colData = $row[$deleteColName];
                if ($colData == 1) {
                    $delete = true;
                }
            }

            if (!$delete) {

                // 各種処理
                /** @var CsvInfoHeader $header */
                foreach ($this->csvInfoObj->getHeaders() as $header) {

                    $colName = $header->getName();

                    if (isset($row[$colName])) {
                        $colData = $row[$colName];
                    } else {
                        $colData = null;
                    }

                    $targetName = $header->getId();

                    // チェック
                    if ($header->isRequired()) {
                        // 必須チェック
                        if ($colData == '') {
                            log_info("必須チェックNG", ['line' => $line, 'name' => $colName]);
                            $message = trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $colName]);
                            $this->addErrors($message);
                            break;
                        }
                    }

                    // 専用チェック
                    if ($header->isCheckFunction()) {

                        $args = new CsvCheckFunctionArgs();
                        $args
                            ->setColData($colData)
                            ->setRowData($row)
                            ->setLine($line)
                            ->setColName($colName);

                        /** @var CsvCheckResult $result */
                        $result = call_user_func_array($header->getCheckFunction(), [$args]);

                        if (!$result->isSuccess()) {
                            log_info("個別チェックNG", ['line' => $line, 'name' => $colName]);
                            $this->addErrors($result->getMessage());
                            break;
                        }
                    }

                    // 更新対象カラムにデータセット
                    if (!$header->isKey() && $header->isTarget()) {
                        if (!$header->isUpdateFunction()) {

                            // 通常の値
                            $targetName = Inflector::classify($targetName);
                            $method = 'set' . $targetName;
                            $entity->{$method}($colData);

                        } else {

                            $args = new CsvUpdateFunctionArgs();
                            $args
                                ->setEntity($entity)
                                ->setColData($colData)
                                ->setRowData($row)
                                ->setDelete(false)
                                ->setLine($line);

                            call_user_func_array($header->getUpdateFunction(), [$args]);
                            $entity = $args->getEntity();
                            $delete = $args->getDelete();
                        }
                    }
                }
            }

            if ($this->hasErrors()) {
                return [
                    'success' => false,
                    'response' => $this->renderWithError($form, $headers)
                ];
            }

            if ($delete) {
                // 削除
                if ($entity) {
                    $this->csvEntityManagerService->remove($entity);
                }
            } else {
                // 更新
                if ($entity) {
                    $this->csvEntityManagerService->update($entity);
                }
            }

        }

        // 完了前処理
        if($this->csvInfoObj->isAfterFunction()) {
            call_user_func_array($this->csvInfoObj->getAfterFunction(), []);
        }

        $this->csvEntityManagerService->commit();

        $message = 'admin.common.csv_upload_complete';
        $this->session->getFlashBag()->add('eccube.admin.success', $message);

        $cacheUtil->clearDoctrineCache();

        log_info("CSV更新処理終了");

        return [
            'success' => true,
            'response' => null
        ];
    }

    /**
     * 雛形ファイル用
     *
     * @return array
     */
    public function getTemplateHeader()
    {
        $headers = $this->csvInfoObj->getTemplateHeader();
        return $headers;
    }

    /**
     * データダウンロード用Query
     *
     * @param QueryBuilder $qb
     */
    public function setExportQuery(QueryBuilder $qb)
    {
        $this->csvExportService->setDownloadQuery($qb);
    }

}
