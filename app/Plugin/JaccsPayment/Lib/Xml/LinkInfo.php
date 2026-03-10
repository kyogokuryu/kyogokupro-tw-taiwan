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
 * 連携情報
 *
 * @author ouyou
 */
class LinkInfo extends XmlBasic
{
    /**
     * @var string
     */
    protected $shopCode;

    /**
     * @var string
     */
    protected $linkId;

    /**
     * @var string
     */
    protected $linkPassword;

    /**
     * 加盟店コード
     *
     * @return string
     */
    public function getShopCode()
    {
        return $this->shopCode;
    }

    /**
     * 加盟店コード
     *
     * @param string $shopCode
     *
     * @return $this
     */
    public function setShopCode($shopCode)
    {
        $this->shopCode = $shopCode;

        return $this;
    }

    /**
     * 接続先特定ID
     *
     * @return string
     */
    public function getLinkId()
    {
        return $this->linkId;
    }

    /**
     * 接続先特定ID
     *
     * @param string $linkId
     *
     * @return $this
     */
    public function setLinkId($linkId)
    {
        $this->linkId = $linkId;

        return $this;
    }

    /**
     * 連携パスワード
     *
     * @return string
     */
    public function getLinkPassword()
    {
        return $this->linkPassword;
    }

    /**
     * 連携パスワード
     *
     * @param string $linkPassword
     *
     * @return $this
     */
    public function setLinkPassword($linkPassword)
    {
        $this->linkPassword = $linkPassword;

        return $this;
    }
}
