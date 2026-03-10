<?php

namespace Plugin\ECCUBE4LineIntegration\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * LineIntegration
 *
 * @ORM\Table(name="plg_line_integration")
 * @ORM\Entity(repositoryClass="Plugin\ECCUBE4LineIntegration\Repository\LineIntegrationRepository")
 */
class LineIntegration extends AbstractEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="customer_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $customer_id;

    /**
     * @var string
     *
     * @ORM\Column(name="line_user_id", type="text")
     */
    private $line_user_id;

    /**
     * @var int
     *
     * @ORM\Column(name="line_notification_flg", type="smallint", options={"unsigned":true})
     */
    private $line_notification_flg;

    /**
     * @var int
     *
     * @ORM\Column(name="del_flg", type="smallint")
     */
    private $del_flg;

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
     * @var \Eccube\Entity\Customer
     *
     */
    private $Customer;

    public function getCustomerId()
    {
        return $this->customer_id;
    }

    public function setCustomerId($customer_id)
    {
        $this->customer_id = $customer_id;
        return $this;
    }

    public function getLineUserId()
    {
        return $this->line_user_id;
    }

    public function setLineUserId($line_user_id)
    {
        $this->line_user_id = $line_user_id;
        return $this;
    }

    public function getLineNotificationFlg()
    {
        return $this->line_notification_flg;
    }

    public function setLineNotificationFlg($line_notification_flg)
    {
        $this->line_notification_flg = $line_notification_flg;

        return $this;
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

    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    public function getUpdateDate()
    {
        return $this->update_date;
    }

    public function setCustomer(\Eccube\Entity\Customer $customer = null)
    {
        $this->Customer = $customer;
        return $this;
    }

    public function getCustomer()
    {
        return $this->Customer;
    }
}
