<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 *
 */

namespace Plugin\KokokaraSelect\Service\MultiCSVService\Entity;


use Doctrine\Common\Inflector\Inflector;

class CsvInfo
{

    private $table;

    private $headers;

    private $baseTableAccessMethod;

    /** @var callable */
    private $afterFunction;

    public function __construct($table = "")
    {
        $this->table = $table;
        $this->afterFunction = null;
    }

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param mixed $table
     * @return $this
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBaseTableAccessMethod()
    {
        return $this->baseTableAccessMethod;
    }

    /**
     * @param mixed $baseTableAccessMethod
     * @return CsvInfo
     */
    public function setBaseTableAccessMethod($baseTableAccessMethod)
    {
        $this->baseTableAccessMethod = $baseTableAccessMethod;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param $index
     * @return CsvInfoHeader
     */
    public function getHeader($index)
    {
        return $this->headers[$index];
    }

    /**
     * カラムID情報取得
     *
     * @return array
     */
    public function getColumnIds()
    {
        $result = [];

        /** @var CsvInfoHeader $header */
        foreach ($this->headers as $header) {

            $result[] = $header->getId();
        }

        return $result;
    }

    /**
     * ヘッダ名称返却
     *
     * @return array
     */
    public function getTemplateHeader()
    {
        $result = [];

        /** @var CsvInfoHeader $header */
        foreach ($this->headers as $header) {

            $result[] = $header->getName();
        }

        return $result;
    }

    /**
     * @param CsvInfoHeader $csvInfoHeader
     * @return CsvInfo
     */
    public function addHeader(CsvInfoHeader $csvInfoHeader)
    {
        $this->headers[] = $csvInfoHeader;
        return $this;
    }

    /**
     * 取込CSV情報作成
     *
     * @param $name
     * @return CsvInfoHeader
     */
    public function createHeader($name)
    {
        $csvInfoHeader = new CsvInfoHeader();
        $this->headers[] = $csvInfoHeader;

        $csvInfoHeader->setName($name);

        return $csvInfoHeader;
    }

    /**
     * ヘッダー数返却
     *
     * @return int
     */
    public function headerSize()
    {
        return count($this->headers);
    }

    public function getToArray()
    {
        $table = $this->getTable();
        $headersData = $this->getHeadersToArray();

        return [
            'table' => $table,
            'headers' => $headersData
        ];
    }

    public function getHeadersToArray()
    {
        $headersData = [];

        foreach ($this->headers as $header) {

            $refClass = new \ReflectionClass($header);

            $headerParams = [];

            /** @var \ReflectionProperty $item */
            foreach ($refClass->getProperties() as $item) {
                $property = $item->getName();

                $method = 'get' . Inflector::classify($property);
                if (!method_exists($header, $method)) {
                    $method = 'is' . Inflector::classify($property);
                }
                $value = $header->{$method}();

                $headerParams = array_merge($headerParams, [
                    $property => $value
                ]);
            }

            $headersData = array_merge($headersData, [
                $headerParams['name'] => $headerParams
            ]);
        }

        return $headersData;
    }

    /**
     * 削除カラムの名称取得
     *
     * @return bool
     */
    public function getDeleteColName()
    {
        $deleteColName = null;

        /** @var CsvInfoHeader $header */
        foreach ($this->headers as $header) {
            if ($header->isDelete()) {
                $deleteColName = $header->getName();
                break;
            }
        }

        return $deleteColName;
    }

    /**
     * @return bool
     */
    public function isAfterFunction()
    {
        return (empty($this->afterFunction) ? false : true);
    }

    /**
     * @return callable
     */
    public function getAfterFunction()
    {
        return $this->afterFunction;
    }

    /**
     * @param callable $afterFunction
     * @return CsvInfo
     */
    public function setAfterFunction(callable $afterFunction)
    {
        $this->afterFunction = $afterFunction;
        return $this;
    }

}
