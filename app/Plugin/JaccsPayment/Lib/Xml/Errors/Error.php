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

namespace Plugin\JaccsPayment\Lib\Xml\Errors;

use Plugin\JaccsPayment\Lib\Xml\XmlBasic;

class Error extends XmlBasic
{
    /**
     * エラー発生箇所
     *
     * @var int
     */
    protected $errorPoint;

    /**
     * エラーコード
     *
     * @var string
     */
    protected $errorCode;

    /**
     * エラーメッセージ
     *
     * @var string
     */
    protected $errorMessage;

    /**
     * エラー発生箇所
     *
     * @return number
     */
    public function getErrorPoint()
    {
        return $this->errorPoint;
    }

    /**
     * エラー発生箇所
     *
     * @param number $errorPoint
     *
     * @return $this
     */
    public function setErrorPoint($errorPoint)
    {
        $this->errorPoint = $errorPoint;

        return $this;
    }

    /**
     * エラーコード
     *
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * エラーコード
     *
     * @param string $errorCode
     *
     * @return $this
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    /**
     * エラーメッセージ
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * エラーメッセージ
     *
     * @param string $errorMessage
     *
     * @return $this
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }
}
