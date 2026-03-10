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
 * 購入者情報
 *
 * @author ouyou
 */
class Customer extends XmlBasic
{
    /**
     * ご購入店注文番号
     *
     * @var string
     */
    protected $shopOrderId;

    /**
     * 日付け
     *
     * @var string
     */
    protected $shopOrderDate;

    /**
     * 氏名
     *
     * @var string
     */
    protected $name;

    /**
     * 氏名
     *
     * @var string
     */
    protected $kanaName;

    /**
     * 郵便番号
     *
     * @var string
     */
    protected $zip;

    /**
     * 住所
     *
     * @var string
     */
    protected $address;

    /**
     * 会社名
     *
     * @var string
     */
    protected $companyName;

    /**
     * 部署名
     *
     * @var string
     */
    protected $sectionName;

    /**
     * 電話番号
     *
     * @var string
     */
    protected $tel;

    /**
     * 電話番号
     *
     * @var string
     */
    protected $email;

    /**
     * 顧客請求金額(税込み)
     *
     * @var int
     */
    protected $billedAmount;

    protected $expand1;

    /**
     * 請求書送り付き方法
     *
     * @var int
     */
    protected $service;

    /**
     * ご購入店注文番号
     *
     * @return string
     */
    public function getShopOrderId()
    {
        return $this->shopOrderId;
    }

    /**
     * ご購入店注文番号
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
     * 日付け
     *
     * @return string
     */
    public function getShopOrderDate()
    {
        return $this->shopOrderDate;
    }

    /**
     * 日付け
     *
     * @param string $shopOrderDate
     *
     * @return $this
     */
    public function setShopOrderDate($shopOrderDate)
    {
        $this->shopOrderDate = $shopOrderDate;

        return $this;
    }

    /**
     * 氏名
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 氏名
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * 氏名
     *
     * @return string
     */
    public function getKanaName()
    {
        return $this->kanaName;
    }

    /**
     * 氏名
     *
     * @param string $kanaName
     *
     * @return $this
     */
    public function setKanaName($kanaName)
    {
        $this->kanaName = $kanaName;

        return $this;
    }

    /**
     * 郵便番号
     *
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * 郵便番号
     *
     * @param string $zip
     *
     * @return $this
     */
    public function setZip($zip)
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * 住所
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * 住所
     *
     * @param string $address
     *
     * @return $this
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * 会社名
     *
     * @return string
     */
    public function getCompanyName()
    {
        return $this->companyName;
    }

    /**
     * 会社名
     *
     * @param string $companyName
     *
     * @return $this
     */
    public function setCompanyName($companyName)
    {
        $this->companyName = $companyName;

        return $this;
    }

    /**
     * 部署名
     *
     * @return string
     */
    public function getSectionName()
    {
        return $this->sectionName;
    }

    /**
     * 部署名
     *
     * @param string $sectionName
     *
     * @return $this
     */
    public function setSectionName($sectionName)
    {
        $this->sectionName = $sectionName;

        return $this;
    }

    /**
     * 電話番号
     *
     * @return string
     */
    public function getTel()
    {
        return $this->tel;
    }

    /**
     * 電話番号
     *
     * @param string $tel
     *
     * @return $this
     */
    public function setTel($tel)
    {
        $this->tel = $tel;

        return $this;
    }

    /**
     * 電話番号
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * 電話番号
     *
     * @param string $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * 顧客請求金額(税込み)
     *
     * @return number
     */
    public function getBilledAmount()
    {
        return $this->billedAmount;
    }

    /**
     * 顧客請求金額(税込み)
     *
     * @param number $billedAmount
     *
     * @return $this
     */
    public function setBilledAmount($billedAmount)
    {
        $this->billedAmount = $billedAmount;

        return $this;
    }

    public function getExpand1()
    {
        return $this->expand1;
    }

    public function setExpand1($expand1)
    {
        $this->expand1 = $expand1;

        return $this;
    }

    /**
     * 請求書送り付き方法
     *
     * @return number
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * 請求書送り付き方法
     *
     * @param number $service
     *
     * @return $this
     */
    public function setService($service)
    {
        $this->service = $service;

        return $this;
    }
}
