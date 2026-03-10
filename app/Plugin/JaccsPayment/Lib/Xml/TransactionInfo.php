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

namespace Plugin\JaccsPayment\Lib\Xml;

/**
 * お問い合わせ情報基本クラス
 *
 * @author ouyou
 */
class TransactionInfo extends XmlBasic
{
    /**
     * お問い合わせ番号
     *
     * @var string
     */
    protected $transactionId;

    /**
     * お問い合わせ番号
     *
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * お問い合わせ番号
     *
     * @param string $transactionId
     *
     * @return $this
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;

        return $this;
    }
}
