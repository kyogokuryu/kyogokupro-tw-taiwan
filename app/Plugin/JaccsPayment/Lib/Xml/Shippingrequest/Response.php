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

namespace Plugin\JaccsPayment\Lib\Xml\Shippingrequest;

use Plugin\JaccsPayment\Lib\Xml\Errors;
use Plugin\JaccsPayment\Lib\Xml\TransactionInfo;
use Plugin\JaccsPayment\Lib\Xml\XmlBasic;

/**
 * 与信審査結果取得API受信
 *
 * @author ouyou
 */
class Response extends XmlBasic
{
    public function __construct($xml = null)
    {
        //返信情報を解析する
        if ($xml) {
            $dom = new \DOMDocument();
            $dom->loadXML($xml);
            $tags = $dom->getElementsByTagName('result');

            $this->setResult($dom->getElementsByTagName('result')->item(0)->nodeValue);

            $tags = $dom->getElementsByTagName('transactionInfo');
            foreach ($tags as $tag) {
                $this->setTransactionInfo(new TransactionInfo());
                $this->getTransactionInfo()->setTransactionId($tag->getElementsByTagName('transactionId')->item(0)->nodeValue);
                break;
            }

            $this->setErrorXml($dom->getElementsByTagName('errors'));

            unset($dom);
        }

        parent::__construct();
    }

    /**
     * 処理結果コード
     *
     * @var string
     */
    protected $result;

    /**
     * 取引情報
     *
     * @var TransactionInfo
     */
    protected $transactionInfo;

    /**
     * エラー情報項目
     *
     * @var Errors
     */
    protected $errors;

    /**
     * 処理結果コード
     *
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * 処理結果コード
     *
     * @param string $result
     *
     * @return $this
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * 取引情報
     *
     * @return TransactionInfo
     */
    public function getTransactionInfo()
    {
        return $this->transactionInfo;
    }

    /**
     * 取引情報
     *
     * @param TransactionInfo $transactionInfo
     *
     * @return $this;
     */
    public function setTransactionInfo(TransactionInfo $transactionInfo)
    {
        $this->transactionInfo = $transactionInfo;

        return $this;
    }

    /**
     * エラー情報項目
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * エラー情報項目
     *
     * @param Errors $errors
     *
     * @return Response
     */
    public function setErrors(Errors $errors)
    {
        $this->errors = $errors;

        return $this;
    }
}
