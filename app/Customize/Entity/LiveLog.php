<?php

namespace Customize\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Customize\Entity\LiveLog')) {
    /**
     * Livelog
     *
     * @ORM\Table(name="dtb_livelog", indexes={
     *   @ORM\Index(name="dtb_livelog_c_staff_c_date_idx", columns={"c_staff","c_date"})
     *  })
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Customize\Repository\LiveLogRepository")
     * 
     */
    class LiveLog extends \Eccube\Entity\AbstractEntity
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


        /**  試聴数
         * @var integer
         *
         * @ORM\Column(name="c_cate", type="integer", options={"default":0}, nullable=true)
         */
        private $c_cate;

        /** ライブ時間
         * @var integer|null
         *
         * @ORM\Column(name="c_time", type="integer", options={"default":0},  nullable=true)
         */
        private $c_time;




        /** ライブ日
         * @var integer
         *
         * @ORM\Column(name="c_date", type="date", nullable=true)
         */
        private $c_date;



        /** ライブ開始時間
         * @var integer
         *
         * @ORM\Column(name="c_time1", type="string", nullable=true)
         */
        private $c_time1;

        
        /** ライブ終了日時
         * @var integer
         *
         * @ORM\Column(name="c_time2", type="string", nullable=true)
         */
        private $c_time2;

        /** お客様の声
         * @var integer
         *
         * @ORM\Column(name="today_msg", type="text", nullable=true)
         */
        private $today_msg;

        /**  気づき
         * @var string
         *
         * @ORM\Column(name="next_msg", type="text", nullable=true)
         */
        private $next_msg;

        /**  売上
         * @var string
         *
         * @ORM\Column(name="sell_memo", type="text", nullable=true)
         */
        private $sell_memo;

        /**  備考
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

        public function getC_time1(){
            return $this->c_time1;
        }

        public function getC_time2(){
            return $this->c_time2;
        }


        public function getC_time(){
            return $this->c_time;
        }

        // 
        public function getTodayMsg(){
            return $this->today_msg;
        }

        public function getNextMsg(){
            return $this->next_msg;
        }


        public function getSellMemo(){
            return $this->sell_memo;
        }

        public function getMemo(){
            return $this->memo;
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



        public function setC_time1($val){
            $this->c_time1 = $val;
        }
        

        public function setC_time2($val){
            $this->c_time2 = $val;
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

        public function setSellMemo($val){
            $this->sell_memo = $val;
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