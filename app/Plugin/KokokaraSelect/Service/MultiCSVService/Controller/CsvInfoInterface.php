<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/17
 */

namespace Plugin\KokokaraSelect\Service\MultiCSVService\Controller;


use Plugin\KokokaraSelect\Service\MultiCSVService\Entity\CsvInfo;

interface CsvInfoInterface
{
    /**
     * CSV 情報
     *
     * @return CsvInfo
     */
    public function getCsvInfo();

    /**
     * テンプレート名
     *
     * @return string
     */
    public function getTemplateFileName();
}
