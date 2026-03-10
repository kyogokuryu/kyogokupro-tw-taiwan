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

use Plugin\JaccsPayment\Lib\Xml\Errors\Error;

/**
 * エラー情報
 *
 * @author ouyou
 */
class Errors extends XmlBasic
{
    /**
     * エラー情報
     *
     * @var array
     */
    protected $errors;

    /**
     * エラー情報
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * エラー情報
     *
     * @param array $errors
     *
     * @return $this
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * @param Error $error
     *
     * @return $this
     */
    public function addError(Error $error)
    {
        $this->errors[] = $error;

        return $this;
    }

    public function deCodeXmlErrors(\DOMElement $tag)
    {
        $tag = $tag->getElementsByTagName('error');
        foreach ($tag as $key => $ta) {
            $error = new Error();
            $error->setErrorCode($ta->getElementsByTagName('errorCode')->item(0)->nodeValue);
            $error->setErrorPoint($ta->getElementsByTagName('errorPoint')->item(0)->nodeValue);
            $error->setErrorMessage($ta->getElementsByTagName('errorMessage')->item(0)->nodeValue);
            $this->addError($error);
        }
    }
}
