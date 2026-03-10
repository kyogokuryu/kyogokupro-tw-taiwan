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

if (!class_exists('\Eccube\Entity\Video')) {
    /**
     * Video
     *
     * @ORM\Table(name="plg_videos")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\Entity(repositoryClass="Eccube\Repository\VideoRepository")
     */
    class Video extends \Eccube\Entity\AbstractEntity
    {
        /**
         * @return string
         */
        public function __toString()
        {
            return (string) $this->getName();
        }

        protected $second;

        /**
         * @return mixed
         */
        public function getSecond()
        {
            return $this->second;
        }

        /**
         * @param mixed $second
         */
        public function setSecond($second)
        {
            $this->second = $second;

            return $this;
        }
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

        /**
         * @var int
         *
         * @ORM\Column(name="id", type="integer", options={"unsigned":true})
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        public $id;

        /**
         * @var string
         *
         * @ORM\Column(name="name", type="string", length=255)
         */
        protected $name;

        /**
         * @var int
         *
         * @ORM\Column(name="status", type="integer")
         */
        protected $status;

        /**
         * @var string
         *
         * @ORM\Column(name="link", type="string")
         */
        protected $link;

        /**
         * @var string
         *
         * @ORM\Column(name="description", type="string")
         */
        protected $description;

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
         * @var \Eccube\Entity\VideoCategory
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\VideoCategory", inversedBy="videos")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="video_category_id", referencedColumnName="id")
         * })
         */
        protected $videoCategory;

        public function getVideoCategory()
        {
            return $this->videoCategory;
        }

        public function setVideoCategory($videoCategory)
        {
            $this->videoCategory = $videoCategory;

            return $this;
        }

        /**
         * @var \Eccube\Entity\VideoPointSetting
         *
         * @ORM\OneToMany(targetEntity="Eccube\Entity\VideoPointSetting", mappedBy="video")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="id", referencedColumnName="video_id")
         * })
         */
        protected $videoPointSettings;

        /**
         * @return \Doctrine\Common\Collections\ArrayCollection|VideoPointSetting
         */
        public function getVideoPointSettings()
        {
            return $this->videoPointSettings;
        }

        /**
         * @param \Doctrine\Common\Collections\ArrayCollection|VideoPointSetting $videoPointSetting
         */
        public function addVideoPointSettings($videoPointSetting)
        {
            $this->videoPointSettings[] = $videoPointSetting;

            return $this;
        }

        public function removeVideoPointSettings($videoPointSetting)
        {
            $this->videoPointSettings->removeElement($videoPointSetting);

            return $this;
        }

        /**
         * @var \Eccube\Entity\VideoPointSetting
         *
         * @ORM\OneToOne(targetEntity="Eccube\Entity\VideoPointSetting", cascade={"persist"})
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="video_point_setting_id", referencedColumnName="id")
         * })
         */
        protected $videoPointSetting;

        /**
         * @return VideoPointSetting
         */
        public function getVideoPointSetting()
        {
            return $this->videoPointSetting;
        }

        /**
         * @param VideoPointSetting $videoPointSetting
         */
        public function setVideoPointSetting(VideoPointSetting $videoPointSetting)
        {
            $this->videoPointSetting = $videoPointSetting;

            return $this;
        }


        /**
         * @var \Eccube\Entity\VideoWatchPoint
         *
         * @ORM\OneToMany(targetEntity="Eccube\Entity\VideoWatchPoint", mappedBy="video", cascade={"persist"})
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="id", referencedColumnName="video_id")
         * })
         */
        public $videoWatchPoints;

        public function addVideoWatchPoints($videoWatchPoints)
        {
            $this->videoWatchPoints[] = $videoWatchPoints;

            return $this;
        }

        /**
         * @param \Doctrine\Common\Collections\ArrayCollection|VideoWatchPoint $videoWatchPoints
         */
        public function removeVideoWatchPoints($videoWatchPoints)
        {
            $this->videoWatchPoints->removeElement($videoWatchPoints);

            return $this;
        }

        public function getVideoWatchPoints()
        {
            return $this->videoWatchPoints;
        }

        /**
         * @var \Eccube\Entity\VideoRelativeProduct
         *
         * @ORM\OneToMany(targetEntity="Eccube\Entity\VideoRelativeProduct", mappedBy="video")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="id", referencedColumnName="video_id")
         * })
         */
        protected $videoRelativeProducts;

        /**
         * @return \Doctrine\Common\Collections\ArrayCollection|VideoRelativeProduct
         */
        public function getVideoRelativeProducts()
        {
            return $this->videoRelativeProducts;
        }

        /**
         * @param \Doctrine\Common\Collections\ArrayCollection|VideoRelativeProduct $videoRelativeProduct
         */
        public function addVideoRelativeProducts($videoRelativeProduct)
        {
            $this->videoRelativeProducts[] = $videoRelativeProduct;

            return $this;
        }

        public function removeVideoRelativeProducts($videoRelativeProduct)
        {
            $this->videoRelativeProducts->removeElement($videoRelativeProduct);

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
            $this->videoPointSettings = new \Doctrine\Common\Collections\ArrayCollection();
            $this->videoWatchPoints = new \Doctrine\Common\Collections\ArrayCollection();
            $this->videoRelativeProducts = new \Doctrine\Common\Collections\ArrayCollection();
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
         * Set name.
         *
         * @param string $name
         *
         * @return $this
         */
        public function setName($name)
        {
            $this->name = $name;

            return $this;
        }

        /**
         * Get name.
         *
         * @return string
         */
        public function getName()
        {
            return $this->name;
        }

        /**
         *  Get status.
         *
         * @return int
         */
        public function getStatus()
        {
            return $this->status;
        }

        /**
         *  Set status.
         *
         * @param int $status
         */
        public function setStatus(int $status)
        {
            $this->status = $status;

            return $this;
        }

        /**
         * @return string
         */
        public function getLink()
        {
            return $this->link;
        }

        /**
         * @param string $link
         */
        public function setLink(string $link)
        {
            $this->link = $link;

            return $this;
        }

        /**
         * @return string
         */
        public function getDescription()
        {
            return $this->description;
        }

        /**
         * @param string $description
         */
        public function setDescription(string $description)
        {
            $this->description = $description;

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

        public function getVideoYoutubeId()
        {
            $query_str = parse_url($this->link, PHP_URL_QUERY);
            parse_str($query_str, $result);
            $path_str = parse_url($this->link, PHP_URL_PATH);
            $path = substr($path_str, 1);
            return $result['v'] ?? $path;
        }

        public function countVideoWatchPoints()
        {
            return $this->videoWatchPoints->count();
        }

    }
}
