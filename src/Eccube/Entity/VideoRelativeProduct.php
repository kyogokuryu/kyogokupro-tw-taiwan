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

if (!class_exists('\Eccube\Entity\VideoRelativeProduct')) {
    /**
     * VideoRelativeProduct
     *
     * @ORM\Table(name="plg_video_relative_products")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\Entity(repositoryClass="Eccube\Repository\VideoRelativeProductRepository")
     */
    class VideoRelativeProduct extends \Eccube\Entity\AbstractEntity
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
         * @var \Eccube\Entity\Product
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Product", inversedBy="videoRelativeProducts", cascade={"persist"})
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="product_id", referencedColumnName="id")
         * })
         */
        protected $product;

        /**
         * @return \Doctrine\Common\Collections\ArrayCollection|Product
         */
        public function getProduct()
        {
            return $this->product;
        }

        /**
         * @param \Doctrine\Common\Collections\ArrayCollection|Product $product
         */
        public function setProduct($product)
        {
            $this->product = $product;

            return $this;
        }

        /**
         * @var \Eccube\Entity\Video
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Video", inversedBy="videoRelativeProducts", cascade={"persist"})
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
         * @var
         */

        /**
         * Constructor
         */
        public function __construct()
        {
            $this->product = new \Doctrine\Common\Collections\ArrayCollection();
            $this->video = new \Doctrine\Common\Collections\ArrayCollection();
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

        public function setVideoProduct($video, $product)
        {
            $this->setVideo($video);
            $this->setProduct($product);

            return $this;
        }
    }
}