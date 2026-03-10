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

namespace Plugin\JaccsPayment\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Config
 *
 * @ORM\Table(name="plg_jaccs_payment_config")
 * @ORM\Entity(repositoryClass="Plugin\JaccsPayment\Repository\ConfigRepository")
 */
class Config extends \Eccube\Entity\AbstractEntity
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
     * 加盟店コード
     *
     * @var string
     *
     * @ORM\Column(name="shop_code", type="string", length=1024, nullable=true)
     */
    private $shop_code;

    /**
     * 連携パスワード
     *
     * @var string
     *
     * @ORM\Column(name="link_password", type="string", length=1024, nullable=true)
     */
    private $link_password;

    /**
     * 請求書送付方法
     *
     * @var int
     *
     * @ORM\Column(name="service", type="integer", nullable=true)
     */
    private $service;

    /**
     * 通信エラーメール送信
     *
     * @var boolean
     *
     * @ORM\Column(name="is_error_mail", type="boolean", options={"default":false})
     */
    private $is_error_mail;

    /**
     * 通信条件不満メール送信
     *
     * @var boolean
     *
     * @ORM\Column(name="is_condition_mail", type="boolean", options={"default":false})
     */
    private $is_condition_mail;

    /**
     * メール
     *
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=1024, nullable=true)
     */
    private $email;

    /**
     * バッチタイプ
     *
     * @var batch_type
     *
     * @ORM\Column(name="batch_type", type="integer", nullable=true)
     */
    private $batch_type;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getShopCode()
    {
        return $this->shop_code;
    }

    /**
     * @param string $shop_code
     *
     * @return Config
     */
    public function setShopCode($shop_code)
    {
        $this->shop_code = $shop_code;

        return $this;
    }

    /**
     * @return string
     */
    public function getLinkPassword()
    {
        return $this->link_password;
    }

    /**
     * @param string $link_password
     *
     * @return Config
     */
    public function setLinkPassword($link_password)
    {
        $this->link_password = $link_password;

        return $this;
    }

    /**
     * @return int
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param int $service
     *
     * @return Config
     */
    public function setService($service)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsErrorMail()
    {
        return $this->is_error_mail;
    }

    /**
     * @param bool $is_error_mail
     *
     * @return Config
     */
    public function setIsErrorMail($is_error_mail)
    {
        $this->is_error_mail = $is_error_mail;

        return $this;
    }

    /**
     * @return bool
     */
    public function isErrorMail()
    {
        return $this->is_error_mail && strlen($this->email) > 0;
    }

    /**
     * @return bool
     */
    public function getIsConditionMail()
    {
        return $this->is_condition_mail;
    }

    /**
     * @param bool $is_condition_mail
     *
     * @return Config
     */
    public function setIsConditionMail($is_condition_mail)
    {
        $this->is_condition_mail = $is_condition_mail;

        return $this;
    }

    /**
     * @return bool
     */
    public function isConditionMail()
    {
        return $this->is_condition_mail && strlen($this->email) > 0;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return Config
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return batch_type
     */
    public function getBatchType()
    {
        return $this->batch_type;
    }

    /**
     * @param batch_type $batch_type
     *
     * @return Config
     */
    public function setBatchType($batch_type)
    {
        $this->batch_type = $batch_type;

        return $this;
    }
}
