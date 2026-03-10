<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 *
 */

namespace Plugin\KokokaraSelect\Service\MultiCSVService\Entity;


class CsvUpdateFunctionArgs
{

    // 対象Entity
    private $entity;

    // 対象データ
    private $colData;

    // 対象行
    private $rowData;

    // 削除フラグ
    private $delete;

    // 対象行
    private $line;

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param mixed $entity
     * @return CsvUpdateFunctionArgs
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getColData()
    {
        return $this->colData;
    }

    /**
     * @param mixed $colData
     * @return CsvUpdateFunctionArgs
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
     * @return CsvUpdateFunctionArgs
     */
    public function setRowData($rowData)
    {
        $this->rowData = $rowData;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDelete()
    {
        return $this->delete;
    }

    /**
     * @param mixed $delete
     * @return CsvUpdateFunctionArgs
     */
    public function setDelete($delete)
    {
        $this->delete = $delete;
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
     * @return CsvUpdateFunctionArgs
     */
    public function setLine($line)
    {
        $this->line = $line;
        return $this;
    }

}
