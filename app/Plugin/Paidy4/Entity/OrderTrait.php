<?php

namespace Plugin\Paidy4\Entity;

use Eccube\Annotation\EntityExtension;
use Doctrine\ORM\Mapping as ORM;

/**
 * @EntityExtension("Eccube\Entity\Order")
 */
trait OrderTrait
{
    /**
     * @var string
     *
     * @ORM\Column(name="paidy_order_id", type="string", length=255, nullable=true)
     */
    private $paidy_order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="paidy_capture_id", type="string", length=255, nullable=true)
     */
    private $paidy_capture_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="paidy_payment_total", type="integer", nullable=true)
     */
    private $paidy_payment_total;

    /**
     * @var integer
     *
     * @ORM\Column(name="paidy_capture_total", type="integer", nullable=true)
     */
    private $paidy_capture_total;

    /**
     * @var integer
     *
     * @ORM\Column(name="paidy_refund_total", type="integer", nullable=true)
     */
    private $paidy_refund_total;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="paidy_expire_date", type="datetimetz", nullable=true)
     */
    private $paidy_expire_date;

    /**
     * @var string
     *
     * @ORM\Column(name="paidy_status", type="string", length=255, nullable=true)
     */
    private $paidy_status;

    /**
     * {@inheritdoc}
     */
    public function setPaidyOrderId($paidy_order_id)
    {
        $this->paidy_order_id = $paidy_order_id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaidyOrderId()
    {
        return $this->paidy_order_id;
    }

    /**
     * {@inheritdoc}
     */
    public function setPaidyCaptureId($paidy_capture_id)
    {
        $this->paidy_capture_id = $paidy_capture_id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaidyCaptureId()
    {
        return $this->paidy_capture_id;
    }

    /**
     * {@inheritdoc}
     */
    public function setPaidyPaymentTotal($paidy_payment_total)
    {
        $this->paidy_payment_total = $paidy_payment_total;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaidyPaymentTotal()
    {
        return $this->paidy_payment_total;
    }

    /**
     * {@inheritdoc}
     */
    public function setPaidyCaptureTotal($paidy_capture_total)
    {
        $this->paidy_capture_total = $paidy_capture_total;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaidyCaptureTotal()
    {
        return $this->paidy_capture_total;
    }

    /**
     * {@inheritdoc}
     */
    public function setPaidyRefundTotal($paidy_refund_total)
    {
        $this->paidy_refund_total = $paidy_refund_total;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaidyRefundTotal()
    {
        return $this->paidy_refund_total;
    }

    /**
     * {@inheritdoc}
     */
    public function setPaidyExpireDate($paidy_expire_date)
    {
        $this->paidy_expire_date = $paidy_expire_date;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaidyExpireDate()
    {
        return $this->paidy_expire_date;
    }

    /**
     * {@inheritdoc}
     */
    public function setPaidyStatus($paidy_status)
    {
        $this->paidy_status = $paidy_status;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaidyStatus()
    {
        return $this->paidy_status;
    }
}
