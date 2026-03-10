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

namespace Plugin\JaccsPayment\Lib\Xml\Shippingrequest\Request;

/**
 * 与信審査結果取得API受信
 *
 * @author ouyou
 */
class TransactionInfo extends \Plugin\JaccsPayment\Lib\Xml\TransactionInfo
{
    /**
     * 出荷報告種別
     *
     * @var string
     */
    protected $deliveryType;

    /**
     * 運送会社コード
     *
     * @var string
     */
    protected $deliveryCompanyCode;

    /**
     * 配送伝票番号
     *
     * @var string
     */
    protected $deliverySlipNo;

    /**
     * 請求書発行日
     *
     * @var string
     */
    protected $invoiceDate;

    /**
     * @return string
     */
    public function getDeliveryType()
    {
        return $this->deliveryType;
    }

    /**
     * @param $deliveryType
     *
     * @return $this
     */
    public function setDeliveryType($deliveryType)
    {
        $this->deliveryType = $deliveryType;

        return $this;
    }

    /**
     * @return string
     */
    public function getDeliveryCompanyCode()
    {
        return $this->deliveryCompanyCode;
    }

    /**
     * @param $deliveryCompanyCode
     *
     * @return $this
     */
    public function setDeliveryCompanyCode($deliveryCompanyCode)
    {
        $this->deliveryCompanyCode = $deliveryCompanyCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getDeliverySlipNo()
    {
        return $this->deliverySlipNo;
    }

    /**
     * @param $deliverySlipNo
     *
     * @return $this
     */
    public function setDeliverySlipNo($deliverySlipNo)
    {
        $this->deliverySlipNo = $deliverySlipNo;

        return $this;
    }

    /**
     * @return string
     */
    public function getInvoiceDate()
    {
        return $this->invoiceDate;
    }

    /**
     * @param $invoiceDate
     *
     * @return $this
     */
    public function setInvoiceDate($invoiceDate)
    {
        $this->invoiceDate = $invoiceDate;

        return $this;
    }
}
