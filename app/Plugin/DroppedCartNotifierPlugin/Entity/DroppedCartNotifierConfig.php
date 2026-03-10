<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\DroppedCartNotifierPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * DroppedCartNotifierConfig
 *
 * @ORM\Table(name="plg_dropped_cart_notifier_config")
 * @ORM\Entity(repositoryClass="Plugin\DroppedCartNotifierPlugin\Repository\DroppedCartNotifierConfigRepository")
 */
class DroppedCartNotifierConfig extends AbstractEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="past_day_to_notify", type="smallint", options={"unsigned":true})
     */
    private $pastDayToNotify;

    /**
     * @var int
     *
     * @ORM\Column(name="max_cart_item_count", type="smallint", options={"unsigned":true})
     */
    private $maxCartItem;

    /**
     * @var int
     *
     * @ORM\Column(name="max_recommended_item", type="smallint", options={"unsigned":true})
     */
    private $maxRecommendedItem;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_subject", type="string")
     */
    private $mailSubject;

    /**
     * @var string
     *
     * @ORM\Column(name="base_url", type="string")
     */
    private $baseUrl;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_send_report_mail", type="boolean")
     */
    private $isSendReportMail;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetimetz")
     */
    private $update_date;

    /**
     * Set product_review config id.
     *
     * @param string $id
     *
     * @return DroppedCartNotifierConfig
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $pastDayToNotify
     *
     * @return DroppedCartNotifierConfig
     */
    public function setPastDayToNotify(int $pastDayToNotify)
    {
        $this->pastDayToNotify = $pastDayToNotify;

        return $this;
    }

    /**
     * @return int
     */
    public function getPastDayToNotify()
    {
        return $this->pastDayToNotify;
    }

    /**
     * @param int $maxCartItem
     *
     * @return DroppedCartNotifierConfig
     */
    public function setMaxCartItem(int $maxCartItem)
    {
        $this->maxCartItem = $maxCartItem;

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxCartItem()
    {
        return $this->maxCartItem;
    }

    /**
     * @param int $maxRecommendedItem
     *
     * @return DroppedCartNotifierConfig
     */
    public function setMaxRecommendedItem(int $maxRecommendedItem)
    {
        $this->maxRecommendedItem = $maxRecommendedItem;

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxRecommendedItem()
    {
        return $this->maxRecommendedItem;
    }

    /**
     * @param string|null $mailSubject
     *
     * @return DroppedCartNotifierConfig
     */
    public function setMailSubject($mailSubject)
    {
        $this->mailSubject = $mailSubject ?? "";    // ここでnullを空文字に

        return $this;
    }

    /**
     * @return string
     */
    public function getMailSubject()
    {
        return $this->mailSubject;
    }

    /**
     * @param string|null $baseUrl
     *
     * @return DroppedCartNotifierConfig
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl ?? "";            // ここでnullを空文字に

        return $this;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @param boolean $isSendReportMail
     *
     * @return DroppedCartNotifierConfig
     */
    public function setIsSendReportMail(bool $isSendReportMail)
    {
        $this->isSendReportMail = $isSendReportMail;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsSendReportMail()
    {
        return $this->isSendReportMail;
    }

    /**
     * Set create_date.
     *
     * @param \DateTime $createDate
     *
     * @return $this
     */
    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * Get create_date.
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set update_date.
     *
     * @param \DateTime $updateDate
     *
     * @return $this
     */
    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
     * Get update_date.
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }
}
