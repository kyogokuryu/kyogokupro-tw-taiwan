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

namespace Plugin\JaccsPayment\Lib\Xml\Transaction\Request;

use Plugin\JaccsPayment\Lib\Xml\XmlBasic;

/**
 * ブラウザ関連情報
 *
 * @author ouyou
 */
class BrowserInfo extends XmlBasic
{
    /**
     * @var string
     */
    protected $httpHeader;

    /**
     * @var string
     */
    protected $deviceInfo;

    /**
     * HTTPヘッダー情報
     *
     * @return string
     */
    public function getHttpHeader()
    {
        return $this->httpHeader;
    }

    /**
     * @param $httpHeader
     *
     * @return $this
     */
    public function setHttpHeader($httpHeader)
    {
        $this->httpHeader = $httpHeader;

        return $this;
    }

    /**
     * デバイス情報
     *
     * @return string
     */
    public function getDeviceInfo()
    {
        return $this->deviceInfo;
    }

    /**
     * @param $deviceInfo
     *
     * @return $this
     */
    public function setDeviceInfo($deviceInfo)
    {
        $this->deviceInfo = $deviceInfo;

        return $this;
    }
}
