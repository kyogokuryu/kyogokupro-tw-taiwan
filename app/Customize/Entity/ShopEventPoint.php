<?php

namespace Customize\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Customize\Entity\EventPoint')) {
    /**
     * ShopEventPointLog
     *
     * @ORM\Table(name="dtb_shop_event_point")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Customize\Repository\ShopEventPointLogRepository")
     */
    class ShopEventPoint extends \Eccube\Entity\AbstractEntity
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
         * @ORM\Column(name="e_pass", type="string")
         */
        private $e_pass;


        /**
         * @var integer
         *
         * @ORM\Column(name="e_sdate", type="date", nullable=true)
         */
        private $e_sdate;

        /**
         * @var integer
         *
         * @ORM\Column(name="e_edate", type="date", nullable=true)
         */
        private $e_edate;


        /**
         * @var string
         *
         * @ORM\Column(name="memo", type="text", nullable=true)
         */
        private $memo;
        private $memo_obj;


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

        public function getE_pass(){
            return $this->e_pass;
        }

        public function getE_sdate(){
            return $this->e_sdate;
        }

        public function getE_edate(){
            return $this->e_edate;
        }


        public function getMemo(){
            return $this->memo;
        }



        public function setE_pass($val){
            $this->e_pass = $val;
        }


        public function setE_sdate($val){
            $this->e_sdate = $val;
        }

        public function setE_edate($val){
            $this->e_edate = $val;
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
         * @return ShopEventPointLog
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



        private function parse_memo_obj(){
            if($this->memo_obj){
                return $this->memo_obj;
            }

            if($this->memo){
                $this->memo_obj = (array)json_decode($this->memo);
                if(is_array($this->memo_obj)){
                    return $this->memo_obj;
                }
            }
            return [];
        }


        public function getPrice1(){
            $memo = $this->parse_memo_obj();
            if(isset($memo[0]) && isset($memo[0]->price)){ return $memo[0]->price; }
        }
        public function getPrice2(){
            $memo = $this->parse_memo_obj();
            if(isset($memo[1]) && isset($memo[1]->price)){ return $memo[1]->price; }
        }
        public function getPrice3(){
            $memo = $this->parse_memo_obj();
            if(isset($memo[2]) && isset($memo[2]->price)){ return $memo[2]->price; }
        }
        public function getPrice4(){
            $memo = $this->parse_memo_obj();
            if(isset($memo[3]) && isset($memo[3]->price)){ return $memo[3]->price; }
        }
        public function getPrice5(){
            $memo = $this->parse_memo_obj();
            if(isset($memo[4]) && isset($memo[4]->price)){ return $memo[4]->price; }
        }


        public function getValue1(){
            $memo = $this->parse_memo_obj();
            if(isset($memo[0]) && isset($memo[0]->value)){ return $memo[0]->value; }
        }
        public function getValue2(){
            $memo = $this->parse_memo_obj();
            if(isset($memo[1]) && isset($memo[1]->value)){ return $memo[1]->value; }
        }
        public function getValue3(){
            $memo = $this->parse_memo_obj();
            if(isset($memo[2]) && isset($memo[2]->value)){ return $memo[2]->value; }
        }
        public function getValue4(){
            $memo = $this->parse_memo_obj();
            if(isset($memo[3]) && isset($memo[3]->value)){ return $memo[3]->value; }
        }
        public function getValue5(){
            $memo = $this->parse_memo_obj();
            if(isset($memo[4]) && isset($memo[4]->value)){ return $memo[4]->value; }
        }

        public function getPointValue($v){
            $memo = $this->parse_memo_obj();

            $point = 0;
            if($this->getPrice1() != null && $this->getPrice1() <= $v){ $point = $this->getValue1(); }
            if($this->getPrice2() != null && $this->getPrice2() <= $v){ $point = $this->getValue2(); }
            if($this->getPrice3() != null && $this->getPrice3() <= $v){ $point = $this->getValue3(); }
            if($this->getPrice4() != null && $this->getPrice4() <= $v){ $point = $this->getValue4(); }
            if($this->getPrice5() != null && $this->getPrice5() <= $v){ $point = $this->getValue5(); }
            return $point;
        }
    }
}