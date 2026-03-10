<?php

namespace Plugin\JaccsPayment\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ShippingRequest
 *
 * @ORM\Table(name="plg_jaccs_shipping_request")
 * @ORM\Entity(repositoryClass="Plugin\JaccsPayment\Repository\ShippingRequestRepository")
 */
class ShippingRequest extends \Eccube\Entity\AbstractEntity
{
    const SEND_REQUEST = true;

    const NOT_SEND_REQUEST = false;

    static public $DeliverCompanyCode = array(
        '11' => '佐川急便',
        '12' => 'ヤマト運輸',
        '15' => '郵便書留',
        '16' => 'ゆうパック',
        '28' => '翌朝10時便',
        '27' => 'エコ配',
        '18' => '福山通運',
        '14' => '西濃運輸',
        '13' => '日本通運',
        '26' => 'トールエクスプレス',
        '30' => 'セイノーエクスプレス',
        '21' => '名鉄運輸',
        '23' => '信州名鉄運輸',
        '20' => '新潟運輸',
        '29' => 'トナミ運輸',
        '31' => '大川配送サービス',
        '32' => 'プラスサービス',
    );

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Eccube\Entity\Order
     *
     * @ORM\ManyToOne(targetEntity="\Eccube\Entity\Order", inversedBy="JaccsShippingRequests")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     * })
     */
    private $Order;

    /**
     * @var string
     *
     * @ORM\Column(name="transaction_id", type="string", length=1024, nullable=true)
     */
    private $transaction_id;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_company_code", type="string", length=255, nullable=true)
     */
    private $delivery_company_code;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_slip_no", type="string", length=255, nullable=true)
     */
    private $delivery_slip_no;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="invoice_date", type="datetimetz", nullable=true)
     */
    private $invoice_date;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_send_request", type="boolean", options={"unsigned":true,"default":false})
     */
    private $is_send_request;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Eccube\Entity\Order
     */
    public function getOrder()
    {
        return $this->Order;
    }

    /**
     * @param \Eccube\Entity\Order $Order
     *
     * @return ShippingRequest
     */
    public function setOrder(\Eccube\Entity\Order $Order)
    {
        $this->Order = $Order;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    /**
     * @param string $transaction_id
     *
     * @return ShippingRequest
     */
    public function setTransactionId($transaction_id)
    {
        $this->transaction_id = $transaction_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getDeliveryCompanyCode()
    {
        return $this->delivery_company_code;
    }

    /**
     * @param string $deliveryCompanyCode
     * @return ShippingRequest
     */
    public function setDeliveryCompanyCode(string $deliveryCompanyCode)
    {
        $this->delivery_company_code = $deliveryCompanyCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getDeliverySlipNo()
    {
        return $this->delivery_slip_no;
    }

    /**
     * @param string $deliverySlipNo
     * @return ShippingRequest
     */
    public function setDeliverySlipNo(string $deliverySlipNo)
    {
        $this->delivery_slip_no = $deliverySlipNo;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getInvoiceDate()
    {
        return $this->invoice_date;
    }

    /**
     * @param \DateTime $invoiceDate
     * @return ShippingRequest
     */
    public function setInvoiceDate($invoiceDate)
    {
        $this->invoice_date = $invoiceDate;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSendRequest()
    {
        return $this->is_send_request;
    }

    /**
     * @param bool $isSendRequest
     * @return ShippingRequest
     */
    public function setIsSendRequest(bool $isSendRequest)
    {
        $this->is_send_request = $isSendRequest;
        return $this;
    }
}