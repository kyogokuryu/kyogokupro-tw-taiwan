<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 *
 */

namespace Plugin\KokokaraSelect\Service\MultiCSVService\Entity;


class CsvCheckFunctionArgs
{

    // 対象データ
    private $colData;

    // レコードデータ
    private $rowData;

    // 対象行
    private $line;

    // カラム名
    private $colName;

    /**
     * @return mixed
     */
    public function getColData()
    {
        return $this->colData;
    }

    /**
     * @param mixed $colData
     * @return CsvCheckFunctionArgs
     */
    public function setColData($colData)
    {
        $this->colData = $colData;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRowData()
    {
        return $this->rowData;
    }

    /**
     * @param mixed $rowData
     * @return CsvCheckFunctionArgs
     */
    public function setRowData($rowData)
    {
        $this->rowData = $rowData;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * @param mixed $line
     * @return CsvCheckFunctionArgs
     */
    public function setLine($line)
    {
        $this->line = $line;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getColName()
    {
        return $this->colName;
    }

    /**
     * @param mixed $colName
     * @return CsvCheckFunctionArgs
     */
    public function setColName($colName)
    {
        $this->colName = $colName;
        return $this;
    }

}
