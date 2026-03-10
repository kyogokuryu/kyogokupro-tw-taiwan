<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/19
 */

namespace Plugin\KokokaraSelect\Service\MultiCSVService\Entity;


class CsvExportConvertFunctionArgs
{

    private $csvColumn;

    private $entity;

    /**
     * @return mixed
     */
    public function getCsvColumn()
    {
        return $this->csvColumn;
    }

    /**
     * @param mixed $csvColumn
     * @return CsvExportConvertFunctionArgs
     */
    public function setCsvColumn($csvColumn)
    {
        $this->csvColumn = $csvColumn;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param mixed $entity
     * @return CsvExportConvertFunctionArgs
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }
}
