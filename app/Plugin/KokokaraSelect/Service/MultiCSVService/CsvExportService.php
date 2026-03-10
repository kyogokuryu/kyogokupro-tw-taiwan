<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 *
 */

namespace Plugin\KokokaraSelect\Service\MultiCSVService;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Eccube\Common\EccubeConfig;
use Plugin\KokokaraSelect\Service\MultiCSVService\Entity\CsvExportConvertFunctionArgs;
use Plugin\KokokaraSelect\Service\MultiCSVService\Entity\CsvInfo;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    /**
     * @var resource
     */
    protected $fp;

    /**
     * @var boolean
     */
    protected $closed = false;

    /**
     * @var \Closure
     */
    protected $convertEncodingCallBack;

    /**
     * @var EntityManagerInterface
     * @deprecated
     */
    protected $entityManager;

    /** @var CsvEntityManagerService */
    protected $csvEntityManagerService;

    /** @var QueryBuilder */
    protected $queryBuilder;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    public function __construct(
        EccubeConfig $eccubeConfig,
        CsvEntityManagerService $csvEntityManagerService
    )
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->csvEntityManagerService = $csvEntityManagerService;
    }

    /**
     * 文字エンコーディングの変換を行うコールバック関数を返す.
     *
     * @return \Closure
     */
    private function getConvertEncodingCallback()
    {
        $config = $this->eccubeConfig;

        return function ($value) use ($config) {
            return mb_convert_encoding(
                (string)$value, $config['eccube_csv_export_encoding'], 'UTF-8'
            );
        };
    }


    public function fopen()
    {
        if (is_null($this->fp) || $this->closed) {
            $this->fp = fopen('php://output', 'w');
        }
    }

    /**
     * @param $row
     */
    public function fputcsv($row)
    {
        if (is_null($this->convertEncodingCallBack)) {
            $this->convertEncodingCallBack = $this->getConvertEncodingCallback();
        }

        fputcsv($this->fp, array_map($this->convertEncodingCallBack, $row), $this->eccubeConfig['eccube_csv_export_separator']);
    }

    /**
     *
     */
    private function fclose()
    {
        if (!$this->closed) {
            fclose($this->fp);
            $this->closed = true;
        }
    }

    /**
     * ヘッダ出力
     *
     * @param $headers
     */
    public function exportHeader($headers)
    {
        $row = [];
        foreach ($headers as $header) {
            $row[] = $header;
        }

        $this->fopen();
        $this->fputcsv($row);
        $this->fclose();
    }

    /**
     * ダウンロード用QueryBuilder設定
     *
     * @param QueryBuilder $qb
     */
    public function setDownloadQuery(QueryBuilder $qb)
    {
        $this->queryBuilder = $qb;
    }

    /**
     * CSVエクスポート
     *
     * @param CsvInfo $csvInfo
     * @param string $fileNameBase
     * @return StreamedResponse
     * @throws \Exception
     */
    public function exportCSV(CsvInfo $csvInfo, $fileNameBase)
    {
        log_info("CSV出力開始");

        // タイムアウト無効化
        set_time_limit(0);

        // ログ出力無効化
        $this->csvEntityManagerService->logStop();

        $response = new StreamedResponse();
        $response->setCallback(function () use ($csvInfo) {

            // ヘッダ出力
            $headers = $csvInfo->getTemplateHeader();
            $this->exportHeader($headers);

            $entityClassName = $csvInfo->getTable();

            // 出力カラム情報
            $csvColumns = $csvInfo->getColumnIds();

            $this->exportData($entityClassName, function ($baseEntity, CsvExportService $csvExportService) use ($csvColumns, $csvInfo) {

                if (!empty($csvInfo->getTable())
                    && get_class($baseEntity) != $csvInfo->getTable()) {

                    $method = "get" . $csvInfo->getBaseTableAccessMethod();
                    $targetEntity = $baseEntity->{$method}();

                } else {
                    $targetEntity[] = $baseEntity;
                }

                foreach ($targetEntity as $entity) {

                    $ExportCsvRow = new \Eccube\Entity\ExportCsvRow();

                    // CSV出力項目と合致するデータ取得
                    foreach ($csvColumns as $key => $csvColumn) {

                        $csvHeaderInfo = $csvInfo->getHeader($key);

                        if ($csvHeaderInfo->isExportConvertFunction()) {
                            // 出力時変換処理
                            $args = new CsvExportConvertFunctionArgs();
                            $args
                                ->setCsvColumn($csvColumn)
                                ->setEntity($entity);

                            $result = call_user_func_array($csvHeaderInfo->getExportConvertFunction(), [$args]);
                            $ExportCsvRow->setData($result);

                        } else {
                            $ExportCsvRow->setData($this->getEntityData($csvColumn, $entity));
                        }

                        $ExportCsvRow->pushData();
                    }

                    // 出力.
                    $csvExportService->fputcsv($ExportCsvRow->getRow());

                }
            });
        });

        $now = new \DateTime();
        $filename = $fileNameBase . '_' . $now->format('YmdHis') . '.csv';
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);
        $response->send();

        log_info("CSV出力終了");

        return $response;
    }

    /**
     * CSVデータ出力
     *
     * @param $entityClassName
     * @param \Closure $closure
     */
    private function exportData($entityClassName, \Closure $closure)
    {

        if (is_null($this->queryBuilder)) {
            $qb = $this->csvEntityManagerService->findAllQueryBuilder($entityClassName);
        } else {
            $qb = $this->queryBuilder;
        }

        if (is_null($qb) || !$this->csvEntityManagerService->hasEntityManager()) {
            throw new \LogicException('query builder not set.');
        }

        $this->fopen();

        $query = $qb->getQuery();
        foreach ($query->getResult() as $iterableResult) {
            $closure($iterableResult, $this);
            $this->csvEntityManagerService->detach($iterableResult);
            $query->free();
            flush();
        }

        $this->fclose();
    }

    /**
     * @param $entity
     * @param $params
     * @param int $index
     * @return string|null
     */
    private function getEntityValue($entity, $params, $index = 0)
    {

        $column = $params[$index];

        // カラム名がエンティティに存在するかどうかをチェック.
        if (!$entity->offsetExists($column)) {
            return null;
        }

        // データを取得.
        $data = $entity->offsetGet($column);

        // one to one の場合は, dtb_csv.reference_field_name, 合致する結果を取得する.
        if ($data instanceof \Eccube\Entity\AbstractEntity) {

            $index++;
            return $this->getEntityValue($data, $params, $index);

        } elseif ($data instanceof \Doctrine\Common\Collections\Collection) {

            $array = [];
            $index++;
            foreach ($data as $datum) {
                $array[] = $this->getEntityValue($datum, $params, $index);
            }

            return implode($this->eccubeConfig['eccube_csv_export_multidata_separator'], $array);

        } elseif ($data instanceof \DateTime) {
            // datetimeの場合は文字列に変換する.
            return $data->format($this->eccubeConfig['eccube_csv_export_date_format']);

        } else {
            // スカラ値の場合はそのまま.
            return $data;
        }

    }

    /**
     * Entityから指定したカラム情報取得
     *
     * @param $csvColumn
     * @param $entity
     * @return string|null
     */
    public function getEntityData($csvColumn, $entity)
    {

        // . で分割
        $params = explode('.', $csvColumn);

        $data = $this->getEntityValue($entity, $params, 0);

        if (empty($data)) {
            return null;
        }

        return $data;
    }

}
