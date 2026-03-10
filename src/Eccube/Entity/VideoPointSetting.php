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

use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Eccube\Entity\VideoPointSetting')) {
    /**
     * VideoPointSetting
     *
     * @ORM\Table(name="plg_video_point_settings")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\Entity(repositoryClass="Eccube\Repository\VideoPointSettingRepository")
     */
    class VideoPointSetting extends \Eccube\Entity\AbstractEntity
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
         * @var int
         *
         * @ORM\Column(name="second", type="integer")
         */
        protected $second;

        /**
         * @var int
         *
         * @ORM\Column(name="point", type="integer")
         */
        protected $point;

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
         * @var \Eccube\Entity\Video
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Video", inversedBy="videoPointSettings", cascade={"persist"})
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
         * @var \Eccube\Entity\VideoWatchPoint
         *
         * @ORM\OneToMany(targetEntity="Eccube\Entity\VideoWatchPoint", mappedBy="VideoPointSetting", cascade={"persist"})
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="id", referencedColumnName="video_point_setting_id")
         * })
         */
        protected $videoWatchPoints;

        /**
         * @return \Doctrine\Common\Collections\ArrayCollection|VideoWatchPoint
         */
        public function getVideoWatchPoints()
        {
            return $this->videoWatchPoints;
        }

        /**
         * @param \Doctrine\Common\Collections\ArrayCollection|VideoWatchPoint $videoWatchPoints
         */
        public function addVideoPointSettings($videoWatchPoints)
        {
            $this->videoWatchPoints = $videoWatchPoints;

            return $this;
        }

        public function removeVideoPointSettings($videoWatchPoints)
        {
            $this->videoWatchPoints->removeElement($videoWatchPoints);

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
            $this->videoWatchPoints = new \Doctrine\Common\Collections\ArrayCollection();
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

        /**
         * Set second.
         *
         * @param int $second
         *
         * @return $this
         */
        public function setSecond($second)
        {
            $this->second = $second;

            return $this;
        }

        /**
         * Get second.
         *
         * @return int
         */
        public function getSecond()
        {
            return $this->second;
        }

        /**
         * Set point.
         *
         * @param int $point
         *
         * @return $this
         */
        public function setPoint($point)
        {
            $this->point = $point;

            return $this;
        }

        /**
         * Get point.
         *
         * @return int
         */
        public function getPoint()
        {
            return $this->point;
        }

        /**
         * Set created_at.
         *
         * @param \DateTime $created_at
         *
         * @return $this
         */
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