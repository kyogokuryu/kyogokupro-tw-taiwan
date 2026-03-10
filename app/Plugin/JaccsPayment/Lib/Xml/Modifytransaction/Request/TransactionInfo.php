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

namespace Plugin\JaccsPayment\Lib\Xml\Modifytransaction\Request;

/**
 * 与信審査結果取得API受信
 *
 * @author ouyou
 */
class TransactionInfo extends \Plugin\JaccsPayment\Lib\Xml\TransactionInfo
{
    /**
     * 更新種別フラグ
     *
     * @var int
     */
    protected $updateTypeFlag;

    /**
     * @return int
     */
    public function getUpdateTypeFlag()
    {
        return $this->updateTypeFlag;
    }

    /**
     * @param $updateTypeFlag
     *
     * @return $this
     */
    public function setUpdateTypeFlag($updateTypeFlag)
    {
        $this->updateTypeFlag = $updateTypeFlag;

        return $this;
    }
}
