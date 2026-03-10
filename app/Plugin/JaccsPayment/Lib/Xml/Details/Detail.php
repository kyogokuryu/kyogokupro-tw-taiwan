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

namespace Plugin\JaccsPayment\Lib\Xml\Details;

use Plugin\JaccsPayment\Lib\Xml\XmlBasic;

/**
 * 明細詳細情報
 *
 * @author ouyou
 */
class Detail extends XmlBasic
{
    /**
     * 商品名
     *
     * @var string
     */
    protected $goods;

    /**
     * 単価(税込み)
     *
     * @var int
     */
    protected $goodsPrice;

    /**
     * 数量
     *
     * @var int
     */
    protected $goodsAmount;

    /**
     * @var string
     */
    protected $expand2;

    /**
     * @var string
     */
    protected $expand3;

    /**
     * @var string
     */
    protected $expand4;

    /**
     * 商品名
     *
     * @return string
     */
    public function getGoods()
    {
        return $this->goods;
    }

    /**
     * 商品名
     *
     * @param $goods
     *
     * @return $this
     */
    public function setGoods($goods)
    {
        $this->goods = $goods;

        return $this;
    }

    /**
     * 単価
     *
     * @return int
     */
    public function getGoodsPrice()
    {
        return $this->goodsPrice;
    }

    /**
     * 単価
     *
     * @param int $goodsPrice
     *
     * @return $this
     */
    public function setGoodsPrice($goodsPrice)
    {
        $this->goodsPrice = $goodsPrice;

        return $this;
    }

    /**
     * 数量
     *
     * @return number
     */
    public function getGoodsAmount()
    {
        return $this->goodsAmount;
    }

    /**
     * 数量
     *
     * @param number $goodsAmount
     *
     * @return $this
     */
    public function setGoodsAmount($goodsAmount)
    {
        $this->goodsAmount = $goodsAmount;

        return $this;
    }

    public function getExpand2()
    {
        return $this->expand2;
    }

    public function setExpand2($expand2)
    {
        $this->expand2 = $expand2;

        return $this;
    }

    public function getExpand3()
    {
        return $this->expand3;
    }

    public function setExpand3($expand3)
    {
        $this->expand3 = $expand3;

        return $this;
    }

    public function getExpand4()
    {
        return $this->expand4;
    }

    public function setExpand4($expand4)
    {
        $this->expand4 = $expand4;

        return $this;
    }
}
