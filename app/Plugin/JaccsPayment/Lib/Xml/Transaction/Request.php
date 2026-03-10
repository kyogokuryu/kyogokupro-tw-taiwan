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

namespace Plugin\JaccsPayment\Lib\Xml\Transaction;

use Plugin\JaccsPayment\Lib\Xml\Customer;
use Plugin\JaccsPayment\Lib\Xml\Details;
use Plugin\JaccsPayment\Lib\Xml\LinkInfo;
use Plugin\JaccsPayment\Lib\Xml\Ship;
use Plugin\JaccsPayment\Lib\Xml\Transaction\Request\BrowserInfo;
use Plugin\JaccsPayment\Lib\Xml\XmlBasic;

/**
 * 取引登録API送信用
 *
 * @author ouyou
 */
class Request extends XmlBasic
{
    /**
     * @var LinkInfo
     */
    protected $linkInfo;

    /**
     * @var BrowserInfo
     */
    protected $browserInfo;

    /**
     * @var Customer
     */
    protected $customer;

    /**
     * @var Ship
     */
    protected $ship;

    /**
     * @var Details
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
     * ブラウザ関連情報
     *
     * @return BrowserInfo
     */
    public function getBrowserInfo()
    {
        return $this->browserInfo;
    }

    /**
     * ブラウザ関連情報
     *
     * @param BrowserInfo $browserInfo
     *
     * @return $this
     */
    public function setBrowserInfo(BrowserInfo $browserInfo)
    {
        $this->browserInfo = $browserInfo;

        return $this;
    }

    /**
     * 購入者情報
     *
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * 購入者情報
     *
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
     * 配送先情報
     *
     * @return Ship
     */
    public function getShip()
    {
        return $this->ship;
    }

    /**
     * 配送先情報
     *
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
     * 明細詳細項目
     *
     * @return Details
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * 明細詳細項目
     *
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
