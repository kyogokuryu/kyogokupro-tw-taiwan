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
 * 配送先情報
 *
 * @author ouyou
 */
class Ship extends XmlBasic
{
    /**
     * 氏名
     *
     * @var string
     */
    protected $shipName;

    /**
     * 氏名
     *
     * @var stirng
     */
    protected $shipKananame;

    /**
     * 郵便番号
     *
     * @var stirng
     */
    protected $shipZip;

    /**
     * 住所
     *
     * @var stirng
     */
    protected $shipAddress;

    /**
     * 会社名
     *
     * @var stirng
     */
    protected $shipCompanyName;

    /**
     * 部署名
     *
     * @var stirng
     */
    protected $shipSectionName;

    /**
     * 電話番号
     *
     * @var stirng
     */
    protected $shipTel;

    /**
     * 氏名
     *
     * @return string
     */
    public function getShipName()
    {
        return $this->shipName;
    }

    /**
     * 氏名
     *
     * @param stirng $shipName
     *
     * @return $this
     */
    public function setShipName($shipName)
    {
        $this->shipName = $shipName;

        return $this;
    }

    /**
     * 氏名
     *
     * @return stirng
     */
    public function getShipKananame()
    {
        return $this->shipKananame;
    }

    /**
     * 氏名
     *
     * @param stirng $shipKananame
     *
     * @return $this
     */
    public function setShipKananame($shipKananame)
    {
        $this->shipKananame = $shipKananame;

        return $this;
    }

    /**
     * 郵便番号
     *
     * @return stirng
     */
    public function getShipZip()
    {
        return $this->shipZip;
    }

    /**
     * 郵便番号
     *
     * @param stirng $shipZip
     *
     * @return $this
     */
    public function setShipZip($shipZip)
    {
        $this->shipZip = $shipZip;

        return $this;
    }

    /**
     * 住所
     *
     * @return stirng
     */
    public function getShipAddress()
    {
        return $this->shipAddress;
    }

    /**
     * 住所
     *
     * @param stirng $shipAddress
     *
     * @return $this
     */
    public function setShipAddress($shipAddress)
    {
        $this->shipAddress = $shipAddress;

        return $this;
    }

    /**
     * 会社名
     *
     * @return stirng
     */
    public function getShipCompanyName()
    {
        return $this->shipCompanyName;
    }

    /**
     * 会社名
     *
     * @param stirng $shipCompanyName
     *
     * @return $this
     */
    public function setShipCompanyName($shipCompanyName)
    {
        $this->shipCompanyName = $shipCompanyName;

        return $this;
    }

    /**
     * 部署名
     *
     * @return stirng
     */
    public function getShipSectionName()
    {
        return $this->shipSectionName;
    }

    /**
     * 部署名
     *
     * @param stirng $shipSectionName
     *
     * @return $this
     */
    public function setShipSectionName($shipSectionName)
    {
        $this->shipSectionName = $shipSectionName;

        return $this;
    }

    /**
     * 電話番号
     *
     * @return stirng
     */
    public function getShipTel()
    {
        return $this->shipTel;
    }

    /**
     * 電話番号
     *
     * @param stirng $shipTel
     *
     * @return $this
     */
    public function setShipTel($shipTel)
    {
        $this->shipTel = $shipTel;

        return $this;
    }
}
