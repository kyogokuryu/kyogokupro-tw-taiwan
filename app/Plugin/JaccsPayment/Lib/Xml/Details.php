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

use Plugin\JaccsPayment\Lib\Xml\Details\Detail;

/**
 * 商品詳細情報
 *
 * @author ouyou
 */
class Details extends XmlBasic
{
    /**
     * 明細詳細情報
     *
     * @var array
     */
    protected $details;

    /**
     * 明細詳細情報
     *
     * @return array
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * 明細詳細情報
     *
     * @param Detail $details
     *
     * @return $this
     */
    public function setDetails(Detail $details)
    {
        $this->details = $details;

        return $this;
    }

    public function addDetail(Detail $detail)
    {
        $this->details[] = $detail;

        return $this;
    }
}
