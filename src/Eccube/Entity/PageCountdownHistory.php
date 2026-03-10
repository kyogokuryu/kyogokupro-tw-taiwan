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

namespace Eccube\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Eccube\Entity\CountDown')) {
    /**
     * Video
     *
     * @ORM\Table(name="plg_page_countdown_reward")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\Entity(repositoryClass="Eccube\Repository\CountDownRepository")
     */
    class PageCountdownHistory extends \Eccube\Entity\AbstractEntity
    {
        /**
         * @return string
         */
        public function __toString()
        {
            return '';
        }

        /**
         * @var int
         *
         * @ORM\Column(name="id", type="integer", options={"unsigned":true})
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        public $id;

        /**
         * @var \DateTime
         *
         * @ORM\Column(name="created_at", type="datetime")
         */
        protected $created_at;

        /**
         * @var \DateTime
         *
         * @ORM\Column(name="updated_at", type="datetime")
         */
        protected $updated_at;

        /**
         * @var
         */

        /**
         * Constructor
         */
        public function __construct()
        {
        }

        /**
         * Set id.
         *
         * @param int $id
         *
         * @return $this
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
         * @var \integer
         *
         * @ORM\Column(name="point", type="integer")
         */
        protected $point;

        /**
         * @return mixed
         */
        public function getPoint()
        {
            return $this->point;
        }

        /**
         * @param mixed $point
         */
        public function setPoint($point)
        {
            $this->point = $point;

            return $this;
        }

        public function getSecond()
        {
            return $this->second;
        }

        public function setSecond($second)
        {
            $this->second = $second;

            return $this;
        }

        /**
         * @var \integer
         *
         * @ORM\Column(name="second", type="integer")
         */
        protected $second;


        /**
         * @var \PageCountdown
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\PageCountdown", inversedBy="PageCountdownHistory")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="page_countdown_id", referencedColumnName="id")
         * })
         */
        protected $countdown;

        /**
         * @return mixed
         */
        public function getCountdown()
        {
            return $this->countdown;
        }

        /**
         * @param mixed $countdown
         */
        public function setCountdown($countdown)
        {
            $this->countdown = $countdown;

            return $this;
        }

        /**
         * Set id.
         *
         * @param int $id
         *
         * @return $this
         */

        /**
         * @var \Eccube\Entity\Customer
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer", inversedBy="PageCountdownHistory")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
         * })
         */
        protected $customer;

        /**
         * @return \Doctrine\Common\Collections\ArrayCollection|Customer
         */
        public function getCustomer()
        {
            return $this->customer;
        }

        /**
         * @param \Doctrine\Common\Collections\ArrayCollection|Customer $customer
         */
        public function setCustomer($customer)
        {
            $this->customer = $customer;

            return $this;
        }

        public function setCreatedAt(\DateTime $created_at)
        {
            $this->created_at = $created_at;

            return $this;
        }

        /**
         * Get created_at.
         *
         * @return \DateTime
         */
        public function getCreatedAt()
        {
            return $this->created_at;
        }


        /**
         * Set updated_at.
         *
         * @param \DateTime $updated_at
         *
         * @return $this
         */
        public function setUpdatedAt(\DateTime $updated_at)
        {
            $this->updated_at = $updated_at;

            return $this;
        }

        /**
         * Get updated_at.
         *
         * @return \DateTime
         */
        public function getUpdatedAt()
        {
            return $this->updated_at;
        }
    }
}
