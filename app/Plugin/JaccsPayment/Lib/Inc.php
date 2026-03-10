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

namespace Plugin\JaccsPayment\Lib;

class Inc
{
    const baseuri = 'https://www.manage.atodene.jp/api/';
    //const baseuri = 'https://devwb01.manage.atodene.jp/api/';

    const transactionUri = 'transaction.do';
    const getauthoriUri = 'getauthori.do';
    const shippingrequestUri = 'shippingrequest.do';
    const modifytransactionUri = 'modifytransaction.do';
    const getinvoicedataUri = 'getinvoicedata.do';
    const reinvoiceUri = 'reinvoice.do';

    const fraudbuster_js_url = 'https://fraud-buster.appspot.com/js/fraudbuster.js';

    const http_timeout = 30;

    //const JACCS_LINK_ID = 'eccubeid01';
    const JACCS_LINK_ID = 'original00';
}
