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

if (!class_exists('\Eccube\Entity\VideoCategory')) {
    /**
     * VideoCategory
     *
     * @ORM\Table(name="plg_video_categories")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Eccube\Repository\VideoCategoryRepository")
     * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
     */
    class VideoCategory extends \Eccube\Entity\AbstractEntity
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
         * @var string
         *
         * @ORM\Column(name="name", type="string", length=255)
         */
        protected $name;

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

        /**
         * @var \Eccube\Entity\Video
         *
         * @ORM\OneToMany(targetEntity="Eccube\Entity\Video", mappedBy="videoCategory")
         */
        protected $videos;

        /**
         * @return \Doctrine\Common\Collections\ArrayCollection|Video
         */
        public function getVideos()
        {
            return $this->videos;
        }

        /**
         * @param \Doctrine\Common\Collections\ArrayCollection|Video $videos
         */
        public function setVideos($videos)
        {
            $this->videos = $videos;

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
            $this->videos = new \Doctrine\Common\Collections\ArrayCollection();
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

        public function getCountVideo()
        {
            return $this->videos->count();
        }
    }
}
