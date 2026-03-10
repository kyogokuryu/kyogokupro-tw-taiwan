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

namespace Plugin\JaccsPayment\Lib\Xml\Transaction\Response;

class TransactionInfo extends \Plugin\JaccsPayment\Lib\Xml\TransactionInfo
{
    /**
     * ご購入店受注番号
     *
     * @var stirng
     */
    protected $shopOrderId;

    /**
     *　審査結果
     *
     * @var string
     */
    protected $autoAuthoriresult;

    /**
     * ご購入店受注番号
     *
     * @return stirng
     */
    public function getShopOrderId()
    {
        return $this->shopOrderId;
    }

    /**
     * お問い合わせ番号
     *
     * @param string $shopOrderId
     *
     * @return $this
     */
    public function setShopOrderId($shopOrderId)
    {
        $this->shopOrderId = $shopOrderId;

        return $this;
    }

    /**
     * 審査結果
     *
     * @return string
     */
    public function getAutoAuthoriresult()
    {
        return $this->autoAuthoriresult;
    }

    /**
     * 審査結果
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
}
