<?php

namespace Customize\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Customize\Entity\MLog')) {
    /**
     * Mlog
     *
     * @ORM\Table(name="dtb_mlog")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Customize\Repository\MLogRepository")
     */
    class MLog extends \Eccube\Entity\AbstractEntity
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
         * @ORM\Column(name="memo", type="text", nullable=true)
         */
        private $memo;

        /**
         * @var string
         *
         * @ORM\Column(name="comm", type="text", nullable=true)
         */
        private $comm;

        /**
         * @var string
         *
         * @ORM\Column(name="comm_manager", type="text", nullable=true)
         */
        private $comm_manager;

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
         * @var integer|null
         *
         * @ORM\Column(name="is_ceo", type="integer", nullable=true)
         */
        private $is_ceo = 0;

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


        public function getC_time(){
            return $this->c_time;
        }

        public function getTodayMsg(){
            return $this->today_msg;
        }

        public function getNextMsg(){
            return $this->next_msg;
        }


        public function getMemo(){
            return $this->memo;
        }


        public function getComm(){
            return $this->comm;
        }

        public function getCommManager(){
            return $this->comm_manager;
        }

        public function getIsCeo(): ?int
        {
            return $this->is_ceo;
        }


        public function setC_staff($val){
            $this->c_staff = $val;
        }


        public function setC_cate($val){
            $this->c_cate = $val;
        }


        public function setC_time($val){
            $this->c_time = $val;
        }

        public function setC_date($val){
            $this->c_date = $val;
        }


        public function setTodayMsg($val){
            $this->today_msg = $val;
        }

        public function setNextMsg($val){
            $this->next_msg = $val;
        }


        public function setMemo($val){
            $this->memo = $val;
        }


        public function setComm($val){
            $this->comm = $val;
        }

        public function setCommManager($val){
            $this->comm_manager = $val;
        }

        public function setIsCeo($val)
        {
            $this->is_ceo = $val;
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
         * @return Mlog
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