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

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Customize\Entity\SalonafResult')) {
    /**
     * SalonafResult
     *
     * @ORM\Table(name="dtb_salonaf_result")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Customize\Repository\SalonafResultRepository")
     */
    class SalonafResult extends \Eccube\Entity\AbstractEntity
    {
        /**
         * @var integer
         *
         * @ORM\Column(name="id", type="integer", options={"unsigned":true})
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        private $id;

        /**
         * @var \Eccube\Entity\Customer
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
         * })
         */
        private $Customer;

        /**
         * @var \DateTime
         *
         * @ORM\Column(name="month", type="datetimetz")
         */
        private $month;

        /**
         * @var integer
         *
         * @ORM\Column(name="paid_flg", type="integer", options={"unsigned":true,"default":0})
         */
        private $paid_flg = 0;

        /**
         * @var string
         *
         * @ORM\Column(name="sales", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
         */
        private $sales = 0;

        /**
         * @var string
         *
         * @ORM\Column(name="reward", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
         */
        private $reward = 0;

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
         * Get id.
         *
         * @return int
         */
        public function getId()
        {
            return $this->id;
        }

        /**
         * Set customer.
         *
         * @param \Eccube\Entity\Customer|null $customer
         *
         * @return SalonafResult
         */
        public function setCustomer(\Eccube\Entity\Customer $customer = null)
        {
            $this->Customer = $customer;

            return $this;
        }

        /**
         * Get customer.
         *
         * @return \Eccube\Entity\Customer|null
         */
        public function getCustomer()
        {
            return $this->Customer;
        }

        /**
         * Set month.
         *
         * @param \DateTime $month
         *
         * @return SalonafResult
         */
        public function setMonth($month)
        {
            $this->month = $month;

            return $this;
        }

        /**
         * Get month.
         *
         * @return \DateTime
         */
        public function getMonth()
        {
            return $this->month;
        }

        /**
         * Set paid_flg.
         *
         * @param integer $paid_flg
         *
         * @return SalonafResult
         */
        public function setPaidFlg($paidFlg)
        {
            $this->paid_flg = $paidFlg;

            return $this;
        }

        /**
         * Get paid_flg.
         *
         * @return integer
         */
        public function getPaidFlg()
        {
            return $this->paid_flg;
        }

        /**
         * Set sales.
         *
         * @param string $sales
         *
         * @return SalonafResult
         */
        public function setSales($sales)
        {
            $this->sales = $sales;

            return $this;
        }

        /**
         * Get sales.
         *
         * @return string
         */
        public function getSales()
        {
            return $this->sales;
        }

        /**
         * Set reward.
         *
         * @param string $reward
         *
         * @return SalonafResult
         */
        public function setReward($reward)
        {
            $this->reward = $reward;

            return $this;
        }

        /**
         * Get reward.
         *
         * @return string
         */
        public function getReward()
        {
            return $this->reward;
        }

        /**
         * Set create_date.
         *
         * @param \DateTime $createDate
         *
         * @return SalonafResult
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
         * @return CouponOrder
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
}