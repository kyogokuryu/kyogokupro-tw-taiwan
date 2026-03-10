<?php

namespace Plugin\ECCUBE4LineIntegration\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * LineIntegrationHistory
 *
 * @ORM\Table(name="plg_line_integration_history")
 * @ORM\Entity(repositoryClass="Plugin\ECCUBE4LineIntegration\Repository\LineIntegrationHistoryRepository")
 */
class LineIntegrationHistory extends AbstractEntity
{
    /**
     * @var int id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private $message;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="send_date", type="datetimetz", nullable=true)
     */
    private $send_date;

    /**
     * @var int
     *
     * @ORM\Column(name="send_count", type="integer", nullable=true)
     */
    private $send_count;

    /**
     * @var string
     *
     * @ORM\Column(name="send_image", type="text", nullable=true)
     */
    private $send_image;

    /**
     * @var int
     *
     * @ORM\Column(name="del_flg", type="smallint", options={"default":0})
     */
    private $del_flg;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    public function setSendCount($sendCount)
    {
        $this->send_count = $sendCount;

        return $this;
    }

    public function getSendCount()
    {
        return $this->send_count;
    }

    public function setSendDate($send_date)
    {
        $this->send_date = $send_date;

        return $this;
    }

    public function getSendDate()
    {
        return $this->send_date;
    }

    public function setSendImage($send_image)
    {
        $this->send_image = $send_image;

        return $this;
    }

    public function getSendImage()
    {
        return $this->send_image;
    }
    public function setDelFlg($delFlg)
    {
        $this->del_flg = $delFlg;

        return $this;
    }

    public function getDelFlg()
    {
        return $this->del_flg;
    }

    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    public function getCreateDate()
    {
        return $this->create_date;
    }
}
