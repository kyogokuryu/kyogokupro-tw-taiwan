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

namespace Plugin\JaccsPayment\Lib\Xml\Getauthori;

use Plugin\JaccsPayment\Lib\Xml\LinkInfo;
use Plugin\JaccsPayment\Lib\Xml\TransactionInfo;
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
     * @return $tjos
     */
    public function setTransactionInfo(TransactionInfo $transactionInfo)
    {
        $this->transactionInfo = $transactionInfo;

        return $this;
    }
}
