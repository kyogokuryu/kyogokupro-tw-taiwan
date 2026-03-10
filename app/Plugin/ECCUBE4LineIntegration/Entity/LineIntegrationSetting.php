<?php

namespace Plugin\ECCUBE4LineIntegration\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * LineIntegrationSetting
 *
 * @ORM\Table(name="plg_line_integration_setting")
 * @ORM\Entity(repositoryClass="Plugin\ECCUBE4LineIntegration\Repository\LineIntegrationSettingRepository")
 */
class LineIntegrationSetting extends AbstractEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="line_access_token", type="text", nullable=true)
     */
    private $line_access_token;
    /**
     * @var string
     *
     * @ORM\Column(name="line_channel_id", type="text", nullable=true)
     */
    private $line_channel_id;

    /**
     * @var string
     *
     * @ORM\Column(name="line_channel_secret", type="text", nullable=true)
     */
    private $line_channel_secret;

    /**
     * @var bool
     *
     * @ORM\Column(name="cart_notify_is_enabled", type="boolean", nullable=true)
     */
    private $cart_notify_is_enabled;

    /**
     * @var int
     *
     * @ORM\Column(name="cart_notify_past_day_to_notify", type="smallint", options={"unsigned":true}, nullable=true)
     */
    private $cart_notify_past_day_to_notify;

    /**
     * @var int
     *
     * @ORM\Column(name="cart_notify_max_cart_item_count", type="smallint", options={"unsigned":true}, nullable=true)
     */
    private $cart_notify_max_cart_item_count;

    /**
     * @var string
     *
     * @ORM\Column(name="cart_notify_base_url", type="text", nullable=true)
     */
    private $cart_notify_base_url;

    /**
     * @var string
     *
     * @ORM\Column(name="line_add_cancel_redirect_url", type="text", nullable=true)
     */
    private $line_add_cancel_redirect_url;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getLineAccessToken()
    {
        return $this->line_access_token;
    }

    public function setLineAccessToken($line_access_token)
    {
        $this->line_access_token = $line_access_token;
        return $this;
    }

    public function getLineChannelId()
    {
        return $this->line_channel_id;
    }

    public function setLineChannelId($line_channel_id)
    {
        $this->line_channel_id = $line_channel_id;
        return $this;
    }

    public function getLineChannelSecret()
    {
        return $this->line_channel_secret;
    }

    public function setLineChannelSecret($line_channel_secret)
    {
        $this->line_channel_secret = $line_channel_secret;
        return $this;
    }

    public function getCartNotifyIsEnabled()
    {
        return $this->cart_notify_is_enabled;
    }

    public function setCartNotifyIsEnabled($cart_notify_is_enabled)
    {
        $this->cart_notify_is_enabled = $cart_notify_is_enabled;
        return $this;
    }

    public function getCartNotifyPastDayToNotify()
    {
        return $this->cart_notify_past_day_to_notify;
    }

    public function setCartNotifyPastDayToNotify($cart_notify_past_day_to_notify)
    {
        $this->cart_notify_past_day_to_notify = $cart_notify_past_day_to_notify;
        return $this;
    }

    public function getCartNotifyMaxCartItemCount()
    {
        return $this->cart_notify_max_cart_item_count;
    }

    public function setCartNotifyMaxCartItemCount($cart_notify_max_cart_item_count)
    {
        $this->cart_notify_max_cart_item_count = $cart_notify_max_cart_item_count;
        return $this;
    }

    public function getCartNotifyBaseUrl()
    {
        return $this->cart_notify_base_url;
    }

    public function setCartNotifyBaseUrl($cart_notify_base_url)
    {
        $this->cart_notify_base_url = $cart_notify_base_url;
        return $this;
    }

    public function getLineAddCancelRedirectUrl()
    {
        return $this->line_add_cancel_redirect_url;
    }

    public function setLineAddCancelRedirectUrl($line_add_cancel_redirect_url)
    {
        $this->line_add_cancel_redirect_url = $line_add_cancel_redirect_url;
        return $this;
    }
}
