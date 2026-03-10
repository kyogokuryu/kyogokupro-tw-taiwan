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

namespace Plugin\JaccsPayment\Lib\Xml\Getauthori\Request;

use Plugin\JaccsPayment\Lib\Xml\Getauthori\Request\TransactionInfo\ManualAuthorireasons;

/**
 * 与信審査結果取得API受信
 *
 * @author ouyou
 */
class TransactionInfo extends \Plugin\JaccsPayment\Lib\Xml\TransactionInfo
{
    /**
     * 自動審査結果
     *
     * @var string
     */
    protected $autoAuthoriresult;

    /**
     * 目視審査結果
     *
     * @var string
     */
    protected $manualAuthoriresult;

    /**
     * 目視審査結果理由項目
     *
     * @var ManualAuthorireasons
     */
    protected $manualAuthorireasons;

    /**
     * 自動審査結果
     *
     * @return string
     */
    public function getAutoAuthoriresult()
    {
        return $this->autoAuthoriresult;
    }

    /**
     * 自動審査結果
     *
     * @param string $autoAuthoriresult
     *
     * @return $this
     */
    public function setAutoAuthoriresult($autoAuthoriresult)
    {
        $this->autoAuthoriresult = $autoAuthoriresult;

        return $this;
    }

    /**
     * 目視審査結果
     *
     * @return string
     */
    public function getManualAuthoriresult()
    {
        return $this->manualAuthoriresult;
    }

    /**
     * 目視審査結果
     *
     * @param string $manualAuthoriresult
     *
     * @return $this
     */
    public function setManualAuthoriresult($manualAuthoriresult)
    {
        $this->manualAuthoriresult = $manualAuthoriresult;

        return $this;
    }

    /**
     * 目視審査結果理由項目
     *
     * @return ManualAuthorireasons
     */
    public function getManualAuthorireasons()
    {
        return $this->manualAuthorireasons;
    }

    /**
     * 目視審査結果理由項目
     *
     * @param ManualAuthorireasons $manualAuthorireasons
     *
     * @return $this
     */
    public function setManualAuthorireasons(ManualAuthorireasons $manualAuthorireasons)
    {
        $this->manualAuthorireasons = $manualAuthorireasons;

        return $this;
    }
}
