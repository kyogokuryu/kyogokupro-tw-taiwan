<?php

namespace Customize\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Customize\Entity\ShopEventPointLog')) {
    /**
     * ShopEventPointLog
     *
     * @ORM\Table(name="dtb_shop_event_point_log")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Customize\Repository\ShopEventPointLogRepository")
     */
    class ShopEventPointLog extends \Eccube\Entity\AbstractEntity
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
         * @var integer
         *
         * @ORM\Column(name="customer_id", type="integer", options={"unsigned":true})
         */
        private $customer_id;

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
         * @var integer
         *
         * @ORM\Column(name="price", type="integer", options={"default":0}, nullable=true)
         */
        private $price;

        /**
         * @var integer
         *
         * @ORM\Column(name="point", type="integer", options={"default":0}, nullable=true)
         */
        private $point;

        /**
         * @var integer|null
         *
         * @ORM\Column(name="status", type="integer", options={"default":0},  nullable=true)
         */
        private $status;

        /**
         * @var integer|null
         *
         * @ORM\Column(name="ptype", type="integer", options={"default":0},  nullable=true)
         */
        private $ptype;


        /**
         * @var string
         *
         * @ORM\Column(name="memo", type="text", nullable=true)
         */
        private $memo;


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



        public function getPoint(){
            return $this->point;
        }


        public function getPrice(){
            return $this->price;
        }

        public function getStatus(){
            return $this->status;
        }

        public function getPtype(){
            return $this->ptype;
        }

        public function getMemo(){
            return $this->memo;
        }


        public function getCustomerId(){
            return $this->customer_id;
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


        public function setPoint($val){
            $this->point = $val;
        }


        public function setPrice($val){
            $this->price = $val;
        }

        public function setStatus($val){
            $this->status = $val;
        }

        public function setPtype($val){
            $this->ptype = $val;
        }


        public function setCustomerId($val){
            $this->customer_id = $val;
        }


        public function setMemo($val){
            $this->memo = $val;
        }


        /**
         * Set create_date.
         *
         * @param \DateTime $createDate
         *
         * @return Faq
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
         * @return EventPointLog
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