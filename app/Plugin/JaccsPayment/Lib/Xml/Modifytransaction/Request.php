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

namespace Plugin\JaccsPayment\Lib\Xml\Modifytransaction;

use Plugin\JaccsPayment\Lib\Xml\Details;
use Plugin\JaccsPayment\Lib\Xml\LinkInfo;
use Plugin\JaccsPayment\Lib\Xml\Modifytransaction\Request\Customer;
use Plugin\JaccsPayment\Lib\Xml\Modifytransaction\Request\Ship;
use Plugin\JaccsPayment\Lib\Xml\Modifytransaction\Request\TransactionInfo;
use Plugin\JaccsPayment\Lib\Xml\XmlBasic;

/**
 * 与信審査結果取得API送信
 *
 * @author ouyou
 */
class Request extends XmlBasic
{
    /**
     * 連携情報
     *
     * @var LinkInfo
     */
    protected $linkInfo;

    /**
     * 取引情報
     *
     * @var TransactionInfo
     */
    protected $transactionInfo;

    /**
     * 購入者情報
     *
     * @var Customer
     */
    protected $customer;

    /**
     * 配送先情報
     *
     * @var Ship
     */
    protected $ship;

    /**
     * 明細詳細情報項目
     *
     * @var array
     */
    protected $details;

    /**
     * 連携情報
     *
     * @return LinkInfo
     */
    public function getLinkInfo()
    {
        return $this->linkInfo;
    }

    /**
     * 連携情報
     *
     * @param LinkInfo $linkInfo
     *
     * @return $this
     */
    public function setLinkInfo(LinkInfo $linkInfo)
    {
        $this->linkInfo = $linkInfo;

        return $this;
    }

    /**
     * @return TransactionInfo
     */
    public function getTransactionInfo()
    {
        return $this->transactionInfo;
    }

    /**
     * @param TransactionInfo $transactionInfo
     *
     * @return $this
     */
    public function setTransactionInfo(TransactionInfo $transactionInfo)
    {
        $this->transactionInfo = $transactionInfo;

        return $this;
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Customer $customer
     *
     * @return $this
     */
    public function setCustomer(Customer $customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return Ship
     */
    public function getShip()
    {
        return $this->ship;
    }

    /**
     * @param Ship $ship
     *
     * @return $this
     */
    public function setShip(Ship $ship)
    {
        $this->ship = $ship;

        return $this;
    }

    /**
     * @return array
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param Details $details
     *
     * @return $this
     */
    public function setDetails(Details $details)
    {
        $this->details = $details;

        return $this;
    }
}
