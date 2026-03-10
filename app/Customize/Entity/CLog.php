<?php

namespace Customize\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Customize\Entity\CLog')) {
    /**
     * CLog
     *
     * @ORM\Table(name="dtb_clog")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Customize\Repository\CLogRepository")
     */
    class CLog extends \Eccube\Entity\AbstractEntity
    {
        const CLOG_CATEGORIES = [
            "請選擇" => "",
            "KG" => 0,
            "JOBVR" => 1,
            "KG（樂天）" => 2,
            "KG（新沙龍列表）" => 3,
            "眉Lab" => 4,
        ];        
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
         * @ORM\Column(name="order_id", type="integer", nullable=true, options={"unsigned":true})
         */
        private $order_id;

        /**
         * @var \Eccube\Entity\Order
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Order")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="order_id", referencedColumnName="id")
         * })
         */
        private $Order;


        /**
         * @var integer
         *
         * @ORM\Column(name="c_staff", type="string")
         */
        private $c_staff;


        /**
         * @var integer
         *
         * @ORM\Column(name="c_cate", type="integer", options={"default":0}, nullable=true)
         */
        private $c_cate;

        /**
         * @var integer
         *
         * @ORM\Column(name="c_status", type="integer", options={"default":0}, nullable=true)
         */
        private $c_status;

        /**
         * @var integer|null
         *
         * @ORM\Column(name="c_time", type="integer", options={"default":0},  nullable=true)
         */
        private $c_time;


        /**
         * @var integer
         *
         * @ORM\Column(name="c_date", type="date", nullable=true)
         */
        private $c_date;

        /**
         * @var integer
         *
         * @ORM\Column(name="today_msg", type="text", nullable=true)
         */
        private $today_msg;

        /**
         * @var string
         *
         * @ORM\Column(name="next_msg", type="text", nullable=true)
         */
        private $next_msg;


        /**
         * @var string
         *
         * @ORM\Column(name="c_needs", type="text", nullable=true)
         */
        private $c_needs;

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


        public function getC_date(){
            return $this->c_date;
        }

        public function getC_staff(){
            return $this->c_staff;
        }


        public function getC_cate(){
            return $this->c_cate;
        }

        public function getC_status(){
            return $this->c_status;
        }

        public function getC_time(){
            return $this->c_time;
        }

        public function getTodayMsg(){
            return $this->today_msg;
        }

        public function getNextMsg(){
            return $this->next_msg;
        }

        public function getC_needs(){
            return $this->c_needs;
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


        /**
         * Set order.
         *
         * @param \Eccube\Entity\Order|null $order
         *
         * @return SalonafResult
         */
        public function setOrder(\Eccube\Entity\Order $order = null)
        {
            $this->Order = $order;

            return $this;
        }

        /**
         * Get order.
         *
         * @return \Eccube\Entity\Order|null
         */
        public function getOrder()
        {
            return $this->Order;
        }



        public function setC_staff($val){
            $this->c_staff = $val;
        }


        public function setC_cate($val){
            $this->c_cate = $val;
        }

        public function setC_status($val){
            $this->c_status = $val;
        }

        public function setC_time($val){
            $this->c_time = $val;
        }

        public function setC_date($val){
            $this->c_date = $val;
        }

        public function setCustomerId($val){
            $this->customer_id = $val;
        }

        public function setTodayMsg($val){
            $this->today_msg = $val;
        }

        public function setNextMsg($val){
            $this->next_msg = $val;
        }

        public function setC_needs($val){
            $this->c_needs = $val;
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
         * @return CLog
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