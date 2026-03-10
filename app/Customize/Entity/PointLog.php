<?php

namespace Customize\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Customize\Entity\PointLog')) {
    /**
     * PointLog
     *
     * @ORM\Table(name="dtb_point_log")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Customize\Repository\PointLogRepository")
     */
    class PointLog extends \Eccube\Entity\AbstractEntity
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
         * @ORM\Column(name="customer_id", type="integer")
         */
        private $customer_id;

        /**
         * @var integer
         *
         * @ORM\Column(name="point1", type="integer")
         */
        private $point1;

        /**
         * @var integer
         *
         * @ORM\Column(name="point2", type="integer")
         */
        private $point2;

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


        public function getPoint1(){
            return $this->point1;
        }

        public function getPoint2(){
            return $this->point2;
        }


        public function getMemo(){
            return $this->memo;
        }

        public function getMemoStr(){

            if(preg_match('/add_login_point/', $this->memo)){
                return "ログインポイント";
            }
            if(preg_match('/PointProcessor.php/', $this->memo)){
                return "ポイント利用";
            }
            if(preg_match('/PointHelper.php/', $this->memo)){
                return "ポイント付与";
            }
            if(preg_match('/PaymentNotificationController.php/', $this->memo)){
                return "ファミリーライト送料還元";
            }
            if(preg_match('/reward/', $this->memo)){
                return "ページ閲覧ポイント";
            }
            if(preg_match('/AutoGift/', $this->memo)){
                return "ギフト";
            }
            //VideoController.
            if(preg_match('/VideoController/', $this->memo)){
                return "動画視聴";
            }
            
            if(preg_match('/ShopEventPointController/', $this->memo)){
                return "店頭付与";
            }
            //CustomizeShopEventPointController
            if(preg_match('/CustomizeShopEventPointController/', $this->memo)){
                return "店頭付与";
            }
            if(preg_match('/PageCountdownService/', $this->memo)){
                return "カウントダウン";
            }

            return ""; //"<!--" . $this->memo . "-->";
        }

        public function getCustomerId(){
            return $this->customer_id;
        }


        public function setPoint1($val){
            $this->point1 = $val;
        }

        public function setPoint2($val){
            $this->point2 = $val;
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
         * @return PointLog
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