<?php

namespace Plugin\Paidy4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * Config
 *
 * @ORM\Table(name="plg_paidy_config")
 * @ORM\Entity(repositoryClass="Plugin\Paidy4\Repository\ConfigRepository")
 */
class Config extends AbstractEntity
{

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="public_key", type="string", length=255, nullable=true)
     */
    private $public_key;

    /**
     * @var string
     *
     * @ORM\Column(name="secret_key", type="string", length=255, nullable=true)
     */
    private $secret_key;

    /**
     * @var string
     *
     * @ORM\Column(name="logo_url", type="string", length=255, nullable=true)
     */
    private $logo_url;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_method", type="string", length=255, nullable=true)
     */
    private $payment_method;

    /**
     * @var string
     *
     * @ORM\Column(name="store_name", type="string", length=255, nullable=true)
     */
    private $store_name;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_title", type="string", length=255, nullable=true)
     */
    private $mail_title;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_description_text", type="text", nullable=true)
     */
    private $mail_description_text;

    /**
     * @var integer
     *
     * @ORM\Column(name="charge_type", type="integer")
     */
    private $charge_type;

    /**
     * @var string
     *
     * @ORM\Column(name="webhook_request_ip", type="string", length=255, nullable=true)
     */
    private $webhook_request_ip;

    /**
     * @var boolean
     *
     * @ORM\Column(name="description_disp", type="boolean", options={"default":false})
     */
    private $description_disp = false;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description_text", type="text", nullable=true)
     */
    private $description_text;

    /**
     * @var boolean
     *
     * @ORM\Column(name="link_disp", type="boolean", options={"default":false})
     */
    private $link_disp = false;


    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicKey()
    {
        return $this->public_key;
    }

    /**
     * {@inheritdoc}
     */
    public function setPublicKey($public_key)
    {
        $this->public_key = $public_key;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecretKey()
    {
        return $this->secret_key;
    }

    /**
     * {@inheritdoc}
     */
    public function setSecretKey($secret_key)
    {
        $this->secret_key = $secret_key;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogoUrl()
    {
        return $this->logo_url;
    }

    /**
     * {@inheritdoc}
     */
    public function setLogoUrl($logo_url)
    {
        $this->logo_url = $logo_url;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentMethod()
    {
        return $this->payment_method;
    }

    /**
     * {@inheritdoc}
     */
    public function setPaymentMethod($payment_method)
    {
        $this->payment_method = $payment_method;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getStoreName()
    {
        return $this->store_name;
    }

    /**
     * {@inheritdoc}
     */
    public function setStoreName($store_name)
    {
        $this->store_name = $store_name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMailTitle()
    {
        return $this->mail_title;
    }

    /**
     * {@inheritdoc}
     */
    public function setMailTitle($mail_title)
    {
        $this->mail_title = $mail_title;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMailDescriptionText()
    {
        return $this->mail_description_text;
    }

    /**
     * {@inheritdoc}
     */
    public function setMailDescriptionText($mail_description_text)
    {
        $this->mail_description_text = $mail_description_text;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getChargeType()
    {
        return $this->charge_type;
    }

    /**
     * {@inheritdoc}
     */
    public function setChargeType($charge_type)
    {
        $this->charge_type = $charge_type;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getWebhookRequestIp()
    {
        return $this->webhook_request_ip;
    }

    /**
     * {@inheritdoc}
     */
    public function setWebhookRequestIp($webhook_request_ip)
    {
        $this->webhook_request_ip = $webhook_request_ip;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescriptionDisp()
    {
        return $this->description_disp;
    }

    /**
     * {@inheritdoc}
     */
    public function setDescriptionDisp($description_disp)
    {
        $this->description_disp = $description_disp;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescriptionText()
    {
        return $this->description_text;
    }

    /**
     * {@inheritdoc}
     */
    public function setDescriptionText($description_text)
    {
        $this->description_text = $description_text;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLinkDisp()
    {
        return $this->link_disp;
    }

    /**
     * {@inheritdoc}
     */
    public function setLinkDisp($link_disp)
    {
        $this->link_disp = $link_disp;

        return $this;
    }
}
