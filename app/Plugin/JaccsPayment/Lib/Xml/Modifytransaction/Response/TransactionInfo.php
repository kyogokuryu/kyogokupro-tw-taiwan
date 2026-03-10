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

namespace Plugin\JaccsPayment\Lib\Xml\Modifytransaction\Response;

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
     * @return string
     */
    public function getAutoAuthoriresult()
    {
        return $this->autoAuthoriresult;
    }

    /**
     * @param $autoAuthoriresult
     *
     * @return $this
     */
    public function setAutoAuthoriresult($autoAuthoriresult)
    {
        $this->autoAuthoriresult = $autoAuthoriresult;

        return $this;
    }
}
