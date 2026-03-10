<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/17
 */

namespace Plugin\PinpointSale\Entity;


use Eccube\Entity\ProductClass;

class PinpointSaleItem
{
    /* 状態 */
    // タイムセール設定なし
    const NONE = 0;

    /** @var int タイムセール中 */
    const ON = 1;

    /** @var int 時間外 */
    const OFF = 2;

    /** @var int 状態 */
    private $status;

    /** @var int タイムセール中価格 */
    private $price;

    /** @var int タイムセール中税込み価格 */
    private $price_inc_tax;

    /** @var int タイムセール値引き価格 */
    private $discount_price;

    /** @var int タイムセール値引き税込み価格 */
    private $discount_price_inc_tax;

    /** @var ProductClass */
    private $ProductClass;

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return PinpointSaleItem
     */
    public function setStatus(int $status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param mixed $price
     * @return PinpointSaleItem
     */
    public function setPrice($price)
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPriceIncTax()
    {
        return $this->price_inc_tax;
    }

    /**
     * @param mixed $price_inc_tax
     * @return PinpointSaleItem
     */
    public function setPriceIncTax($price_inc_tax)
    {
        $this->price_inc_tax = $price_inc_tax;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDiscountPrice()
    {
        return $this->discount_price;
    }

    /**
     * @param mixed $discount_price
     * @return PinpointSaleItem
     */
    public function setDiscountPrice($discount_price)
    {
        $this->discount_price = $discount_price;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDiscountPriceIncTax()
    {
        return $this->discount_price_inc_tax;
    }

    /**
     * @param mixed $discount_price_inc_tax
     * @return PinpointSaleItem
     */
    public function setDiscountPriceIncTax($discount_price_inc_tax)
    {
        $this->discount_price_inc_tax = $discount_price_inc_tax;
        return $this;
    }

    /**
     * @return ProductClass
     */
    public function getProductClass()
    {
        return $this->ProductClass;
    }

    /**
     * @param ProductClass $ProductClass
     * @return PinpointSaleItem
     */
    public function setProductClass(ProductClass $ProductClass)
    {
        $this->ProductClass = $ProductClass;
        return $this;
    }

    /**
     * タイムセール該当判定
     *
     * @return bool true:タイムセール中
     */
    public function isActive()
    {
        if ($this->status == self::NONE || $this->status == self::OFF) {
            return false;
        }

        return true;
    }
}
