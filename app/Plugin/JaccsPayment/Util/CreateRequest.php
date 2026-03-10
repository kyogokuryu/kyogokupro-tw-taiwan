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

namespace Plugin\JaccsPayment\Util;

use Eccube\Entity\Order;
use Plugin\JaccsPayment\Entity\Config;
use Plugin\JaccsPayment\Entity\History;
use Plugin\JaccsPayment\Lib\Inc;
use Plugin\JaccsPayment\Lib\Xml\Customer;
use Plugin\JaccsPayment\Lib\Xml\Details;
use Plugin\JaccsPayment\Lib\Xml\LinkInfo;
use Plugin\JaccsPayment\Lib\Xml\Ship;
use Plugin\JaccsPayment\Lib\Xml\Transaction;
use Plugin\JaccsPayment\Lib\Xml\TransactionInfo;
use Plugin\JaccsPayment\Lib\Xml\XmlBasic;
use Plugin\JaccsPayment\Lib\Xml\Getauthori;
use Plugin\JaccsPayment\Lib\Xml\Modifytransaction;
use Plugin\JaccsPayment\Lib\Xml\Shippingrequest;

class CreateRequest
{
    /**
     * @param Order $order
     * @param XmlBasic $xmlBasic
     * @param null $transaction_id
     *
     * @return History
     */
    public function CreateEntityHistory(Order $order, XmlBasic $xmlBasic, $transaction_id = null)
    {
        $history = new History();
        $history->setOrder($order);
        $history->setItem(serialize($xmlBasic));
        $history->setCreateDate(new \DateTime('now'));
        $history->setType(get_class($xmlBasic));
        if ($transaction_id) {
            $history->setTransactionId($transaction_id);
        } else {
            if (method_exists($xmlBasic, 'getTransactionInfo') &&
                method_exists($xmlBasic->getTransactionInfo(), 'getTransactionId')) {
                $history->setTransactionId($xmlBasic->getTransactionInfo()->getTransactionId());
            }
        }
        $order->addHistory($history);

        return $history;
    }

    /**
     * @param Config $config
     * @param Order $order
     * @param $isAdmin
     *
     * @return Transaction\Request
     */
    public function CreateTransaction(Config $config, Order $order, $fraudbuster, $isAdmin)
    {
        $jaccsRequest = new Transaction\Request();
        $jaccsRequest->setBrowserInfo(new Transaction\Request\BrowserInfo());
        $jaccsRequest->setCustomer(new Customer());
        $jaccsRequest->setDetails(new Details());
        $jaccsRequest->setLinkInfo(new LinkInfo());
        $jaccsRequest->setShip(new Ship());

        $this->setShip($order, $jaccsRequest->getShip());
        $this->setLinkInfo($config, $jaccsRequest->getLinkInfo());
        $this->setDetails($order, $jaccsRequest->getDetails());
        $this->setCustomer($config, $order, $jaccsRequest->getCustomer());
        $this->setBrowserInfo($jaccsRequest->getBrowserInfo(), $fraudbuster, $isAdmin);

        return $jaccsRequest;
    }

    public function CreateModifytransaction(Config $config, Order $order, $transactionId)
    {
        $jaccsRequest = new Modifytransaction\Request();
        $jaccsRequest->setLinkInfo(new LinkInfo());
        $jaccsRequest->setTransactionInfo(new Modifytransaction\Request\TransactionInfo());
        $jaccsRequest->setCustomer(new Modifytransaction\Request\Customer());
        $jaccsRequest->setShip(new Modifytransaction\Request\Ship());
        $jaccsRequest->setDetails(new Modifytransaction\Request\Details());

        $this->setShip($order, $jaccsRequest->getShip());
        $this->setLinkInfo($config, $jaccsRequest->getLinkInfo());
        $this->setDetails($order, $jaccsRequest->getDetails());
        $this->setCustomer($config, $order, $jaccsRequest->getCustomer());
        $jaccsRequest->getTransactionInfo()
            ->setTransactionId($transactionId)
            ->setUpdateTypeFlag(2);

        return $jaccsRequest;
    }

    public function CreateCancel(Config $config, $transactionId)
    {
        $jaccsRequest = new Modifytransaction\Request();
        $jaccsRequest->setLinkInfo(new LinkInfo());
        $jaccsRequest->setTransactionInfo(new Modifytransaction\Request\TransactionInfo());

        $this->setLinkInfo($config, $jaccsRequest->getLinkInfo());
        $jaccsRequest->getTransactionInfo()
            ->setUpdateTypeFlag(1)
            ->setTransactionId($transactionId);

        return $jaccsRequest;
    }

    public function CreateShippingRequest(Config $config, $transactionId,
                                          $deliverySlipNo, $deliveryCompanyCode, $invoiceDate)
    {
        $jaccsRequest = new Shippingrequest\Request();
        $jaccsRequest->setLinkInfo(new LinkInfo());
        $jaccsRequest->setTransactionInfo(new Shippingrequest\Request\TransactionInfo());

        $jaccsRequest->getTransactionInfo()->setTransactionId($transactionId);
        $jaccsRequest->getTransactionInfo()->setDeliveryCompanyCode($deliveryCompanyCode);
        $jaccsRequest->getTransactionInfo()->setDeliverySlipNo($deliverySlipNo);
        $jaccsRequest->getTransactionInfo()->setInvoiceDate($invoiceDate);
        $jaccsRequest->getTransactionInfo()->setDeliveryType(1);

        $this->setLinkInfo($config, $jaccsRequest->getLinkInfo());

        return $jaccsRequest;
    }

    public function CreateUpdateShippingRequest(Config $config, $transactionId,
                                                $deliverySlipNo, $deliveryCompanyCode, $invoiceDate)
    {
        $jaccsRequest = $this->CreateShippingRequest($config, $transactionId, $deliverySlipNo, $deliveryCompanyCode, $invoiceDate);
        $jaccsRequest->getTransactionInfo()->setDeliveryType(2);
        return $jaccsRequest;
    }

    public function CreateShippingRequestCancel(Config $config, $transactionId)
    {
        $jaccsRequest = new Shippingrequest\Request();
        $jaccsRequest->setLinkInfo(new LinkInfo());
        $jaccsRequest->setTransactionInfo(new Shippingrequest\Request\TransactionInfo());

        $jaccsRequest->getTransactionInfo()->setTransactionId($transactionId);
        $jaccsRequest->getTransactionInfo()->setDeliveryType(3);
        $jaccsRequest->getTransactionInfo()->setDeliveryCompanyCode("");
        $jaccsRequest->getTransactionInfo()->setDeliverySlipNo("");
        $jaccsRequest->getTransactionInfo()->setInvoiceDate("");

        $this->setLinkInfo($config, $jaccsRequest->getLinkInfo());

        return $jaccsRequest;
    }

    protected function setLinkInfo(Config $config, LinkInfo $linkInfo)
    {
        $linkInfo->setLinkId(Inc::JACCS_LINK_ID)
            ->setLinkPassword($config->getLinkPassword())
            ->setShopCode($config->getShopCode());
    }

    protected function setBrowserInfo(Transaction\Request\BrowserInfo $browserInfo, $fraudbuster, $isAdmin)
    {
        if ($isAdmin) {
            $browserInfo->setHttpHeader('')
                ->setDeviceInfo('');
        } else {
            $attr = ['HTTP_ACCEPT', 'HTTP_CHARSET', 'HTTP_ACCEPT_ENCODING', 'HTTP_ACCEPT_LANGUAGE', 'HTTP_CLIENT_IP', 'HTTP_CONNECTION', 'HTTP_DNT', 'HTTP_X_DO_NOT_TRACK',
                'HTTP_HOST', 'HTTP_REFERER', 'HTTP_USER_AGENT', 'HTTP_KEEP_ALIVE', 'HTTP_UA_CPU', 'HTTP_VIA', 'HTTP_X_FORWARDED_FOR', ];

            $all = array_diff_key($_SERVER, $attr);
            $str = '';
            if (count($all)) {
                foreach ($all as $key => $val) {
                    if (strlen($str)) {
                        $str .= '::';
                    }
                    $str .= "{$key}--{$val}";
                }
            }

            $httpHeader = self::getServer('HTTP_ACCEPT').';:'
                .self::getServer('HTTP_CHARSET')
                .';:'.self::getServer('HTTP_ACCEPT_ENCODING')
                .';:'.self::getServer('HTTP_ACCEPT_LANGUAGE')
                .';:'.self::getServer('HTTP_CLIENT_IP')
                .';:'.self::getServer('HTTP_CONNECTION')
                .';:'.(strlen(self::getServer('HTTP_DNT')) ? self::getServer('HTTP_DNT') : self::getServer('HTTP_X_DO_NOT_TRACK'))
                .';:'.self::getServer('HTTP_HOST')
                .';:'.self::getServer('HTTP_REFERER')
                .';:'.self::getServer('HTTP_USER_AGENT')
                .';:'.self::getServer('HTTP_KEEP_ALIVE')
                .';:'.self::getServer('HTTP_UA_CPU')
                .';:'.self::getServer('HTTP_VIA')
                .';:'.self::getServer('HTTP_X_FORWARDED_FOR')
                .';:'.$str
                .';:'.self::getClientIp()
                .';:'.self::getPhoneId().';:';

            $browserInfo->setHttpHeader(self::utf8Substr($httpHeader, 1000))
                ->setDeviceInfo(self::utf8Substr($fraudbuster, 1000));
        }
    }

    protected static function getServer($key)
    {
        return array_key_exists($key, $_SERVER) ? $_SERVER[$key] : '';
    }

    /**
     * 文字列の長さを取る
     *
     * @param string $str
     *
     * @return number
     */
    public static function strlength($str)
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($str);
        }

        return strlen($str);
    }

    /**
     * @param $str
     * @param $len
     *
     * @return bool|string
     */
    public static function utf8Substr($str, $len)
    {
        if (self::strlength($str) <= $len) {
            return $str;
        }
        if (function_exists('mb_substr')) {
            return mb_substr($str, 0, $len);
        }

        for ($i = 0; $i < $len; $i++) {
            $temp_str = substr($str, 0, 1);
            if (ord($temp_str) > 127) {
                $i++;
                if ($i < $len) {
                    $new_str[] = substr($str, 0, 3);
                    $str = substr($str, 3);
                }
            } else {
                $new_str[] = substr($str, 0, 1);
                $str = substr($str, 1);
            }
        }

        return join($new_str);
    }

    /**
     * @param Config $config
     * @param Order $order
     * @param Customer $customer
     */
    protected function setCustomer(Config $config, Order $order, Customer $customer)
    {
        $customer->setShopOrderId($order->getId())
            ->setShopOrderDate($order->getCreateDate()->format('Y/m/d'))
            ->setName($order->getName01().$order->getName02())
            ->setKanaName($order->getKana01().$order->getKana02())
            ->setZip($order->getPostalCode())
            ->setAddress($order->getPref()->getName().$order->getAddr01().$order->getAddr02())
            ->setCompanyName($order->getCompanyName())
            ->setTel($order->getPhoneNumber())
            ->setEmail($order->getEmail())
            ->setBilledAmount((float) $order->getPaymentTotal())
            ->setService($config->getService());
    }

    /**
     * @param Order $order
     * @param Ship $ship
     */
    protected function setShip(Order $order, Ship $ship)
    {
        $shipping = $order->getShippings();
        if (count($shipping)) {
            $shipping = $shipping[0];
            $ship->setShipName($shipping->getName01().$shipping->getName02())
                ->setShipKananame($shipping->getKana01().$shipping->getKana02())
                ->setShipZip($shipping->getPostalCode())
                ->setShipAddress($shipping->getPref()->getName().$shipping->getAddr01().$shipping->getAddr02())
                ->setShipCompanyName($shipping->getCompanyName())
                ->setShipTel($shipping->getPhoneNumber());
        }
    }

    /**
     * @param Order $order
     * @param Details $details
     */
    protected function setDetails(Order $order, Details $details)
    {
        $i = 0;

        /** @var $item \Eccube\Entity\OrderItem */
        foreach ($order->getOrderItems() as $item) {
            if (is_null($item->getProduct())) {
                continue;
            }

            $i++;
            $detail = new Details\Detail();

            $goods = $item->getProductName();

            if (strlen($item->getClassCategoryName1())) {
                $goods .= ' '.$item->getClassCategoryName1();
            }

            if (strlen($item->getClassCategoryName2())) {
                $goods .= ' '.$item->getClassCategoryName2();
            }

            $pic = (float) $item->getPriceIncTax();

            $detail->setGoods($goods)
                ->setGoodsAmount($item->getQuantity())
                ->setGoodsPrice($pic)
                ->setExpand2($i)
                ->setExpand3('')
                ->setExpand4('');

            $details->addDetail($detail);
        }
        
        if ($order->getDeliveryFeeTotal() > 0) {
            $detail = new Details\Detail();
            $detail->setGoods('送料')
                ->setGoodsPrice((float) $order->getDeliveryFeeTotal())
                ->setGoodsAmount(1)
                ->setExpand2(++$i)
                ->setExpand3('')
                ->setExpand4('');
            $details->addDetail($detail);
        }

        if ($order->getCharge() > 0) {
            $detail = new Details\Detail();
            $detail->setGoods('後払い（アトディーネ）手数料')
                ->setGoodsAmount(1)
                ->setGoodsPrice((float) $order->getCharge())
                ->setExpand2(++$i)
                ->setExpand3('')
                ->setExpand4('');
            $details->addDetail($detail);
        }

        // 値引き(割引:課税)
        if ($order->getTaxableDiscount() < 0) {
            $detail = new Details\Detail();
            $detail->setGoods(trans('admin.order.discount'))
                ->setGoodsAmount(1)
                ->setGoodsPrice((float) $order->getTaxableDiscount())
                ->setExpand2(++$i)
                ->setExpand3('')
                ->setExpand4('');
            $details->addDetail($detail);
        }

        // 割引:不課税および利用ポイント
        foreach($order->getTaxFreeDiscountItems() as $Item) {
            $detail = new Details\Detail();
            $detail->setGoods($Item->getProductName())
                ->setGoodsAmount(1)
                ->setGoodsPrice((float) $Item->getTotalPrice())
                ->setExpand2(++$i)
                ->setExpand3('')
                ->setExpand4('');
            $details->addDetail($detail);
        }
    }

    /**
     * @return bool
     */
    public static function getClientIp()
    {
        $ip = false;
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) {
                array_unshift($ips, $ip);
                $ip = false;
            }
            for ($i = 0; $i < count($ips); $i++) {
                if (!preg_match("/^(10|172\.16|192\.168)\./", $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }

        return $ip ? $ip : $_SERVER['REMOTE_ADDR'];
    }

    /**
     * @return string
     */
    public static function getPhoneId()
    {
        $uid = self::getServer('HTTP_X_DCMGUID');
        if ($uid == '') {
            $uid = self::getServer('HTTP_X_UP_SUBNO');
        }
        if ($uid == '') {
            $uid = self::getServer('HTTP_X_JPHONE_UID');
        }
        if ($uid == '') {
            $uid = self::getServer('REMOTE_ADDR');
        }

        return $uid;
    }

    /**
     * @param Config $config
     * @param $transactionId
     *
     * @return Getauthori\Request
     */
    public function CreateGetauthori(Config $config, $transactionId)
    {
        $jaccsRequest = new Getauthori\Request();
        $jaccsRequest->setLinkInfo(new LinkInfo());
        $jaccsRequest->setTransactionInfo(new TransactionInfo());
        $jaccsRequest->getTransactionInfo()->setTransactionId($transactionId);

        $this->setLinkInfo($config, $jaccsRequest->getLinkInfo());

        return $jaccsRequest;
    }
}
