<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\JaccsPayment\Lib\Xml\Getauthori\Request\TransactionInfo;

use Plugin\JaccsPayment\Lib\Xml\XmlBasic;

class ManualAuthorireasons extends XmlBasic
{
    /**
     * 目視審査結果理由
     *
     * @var array
     */
    protected $manualAuthorireason;

    /**
     * 目視審査結果理由
     *
     * @return array
     */
    public function getManualAuthorireason()
    {
        return $this->manualAuthorireason;
    }

    /**
     * 目視審査結果理由
     *
     * @param array $manualAuthorireason
     *
     * @return $this
     */
    public function setManualAuthorireason($manualAuthorireason)
    {
        $this->manualAuthorireason = $manualAuthorireason;

        return $this;
    }

    /**
     * 目視審査結果理由
     *
     * @param string $manualAuthorireason
     *
     * @return $this
     */
    public function addManualAuthorireason($manualAuthorireason)
    {
        $this->manualAuthorireason[] = $manualAuthorireason;

        return $this;
    }
}
