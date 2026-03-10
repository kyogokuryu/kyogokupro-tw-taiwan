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

if (!class_exists('\Eccube\Entity\VideoWatchPoint')) {
    /**
     * VideoWatchPoint
     *
     * @ORM\Table(name="plg_video_watch_points")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\Entity(repositoryClass="Eccube\Repository\VideoWatchPointRepository")
     */
    class VideoWatchPoint extends \Eccube\Entity\AbstractEntity
    {
        /**
         * @return string
         */
        public function __toString()
        {
            return (string) $this->getName();
        }

        /**
         * @var int
         *
         * @ORM\Column(name="id", type="integer", options={"unsigned":true})
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        protected $id;

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
         * Set id.
         *
         * @param int $id
         *
         * @return $this
         */

        /**
         * @var \Eccube\Entity\Customer
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer", inversedBy="VideoWatchPoints")
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

        /**
         * @var \Eccube\Entity\Video
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Video", inversedBy="VideoWatchPoints")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="video_id", referencedColumnName="id")
         * })
         */
        protected $video;

        /**
         * @return \Doctrine\Common\Collections\ArrayCollection|Video
         */
        public function getVideo()
        {
            return $this->video;
        }

        /**
         * @param \Doctrine\Common\Collections\ArrayCollection|Video $video
         */
        public function setVideo($video)
        {
            $this->video = $video;

            return $this;
        }

        /**
         * @var \Eccube\Entity\VideoPointSetting
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\VideoPointSetting", inversedBy="VideoWatchPoint")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="video_point_setting_id", referencedColumnName="id")
         * })
         */
        protected $videoPointSetting;

        /**
         * @return \Doctrine\Common\Collections\ArrayCollection|VideoPointSetting
         */
        public function getVideoPointSetting()
        {
            return $this->videoPointSetting;
        }

        /**
         * @param \Doctrine\Common\Collections\ArrayCollection|VideoPointSetting $videoPointSetting
         */
        public function setVideoPointSetting($videoPointSetting)
        {
            $this->videoPointSetting = $videoPointSetting;

            return $this;
        }

        /**
         * @var
         */

        /**
         * Constructor
         */
        public function __construct()
        {

        }

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