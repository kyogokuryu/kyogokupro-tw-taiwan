<?php

namespace Customize\Entity;

// use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Customize\Entity\AccessLog')) {
    /**
     * PointLog
     *
     * @ORM\Table(name="dtb_access_log")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Customize\Repository\AccessLogRepository")
     */
    class AccessLog extends \Eccube\Entity\AbstractEntity
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
         * @ORM\Column(name="customer_id", type="integer", nullable=true)
         */
        private $customer_id;
        
        /**
         * @var string
         *
         * @ORM\Column(name="path", type="text", nullable=true)
         */
        private $path;

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

        public function setCustomerId($customer_id){
            $this->customer_id = $customer_id;
            return $this;
        }

        public function getCustomerId(){
            return $this->customer_id;
        }

        public function setPath($path){
            $this->path = $path;
            return $this;
        }

        public function getPath(){
            return $this->path;
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