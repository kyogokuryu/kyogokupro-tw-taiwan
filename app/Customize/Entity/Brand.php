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

namespace Customize\Entity;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Product;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

if (!class_exists('\Customize\Entity\Brand')) {
    /**
     * Brand
     *
     * @ORM\Table(name="dtb_brands", uniqueConstraints={@ORM\UniqueConstraint(name="name", columns={"name"})})
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Customize\Repository\BrandRepository")
     */
    class Brand extends \Eccube\Entity\AbstractEntity
    {
        /**
         * @ORM\Id
         * @ORM\GeneratedValue
         * @ORM\Column(type="integer")
         */
        protected $id;

        /**
         * @ORM\Column(name="name",type="string", length=255)
         */
        protected $name;

        /**
         * @ORM\Column(name="description",type="text")
         */
        protected $description;

        /**
         * @ORM\Column(name="is_hidden",type="integer")
         */
        private $is_hidden = 0;

        /**
         * @var int
         *
         * @ORM\Column(name="sort_no", type="integer", options={"unsigned":true})
         */
        protected $sort_no;

        /**
         * @ORM\Column(name="image",type="string", length=255)
         */
        protected $image;

        /**
         * @var \Doctrine\Common\Collections\Collection
         *
         * @ORM\OneToMany(targetEntity="Eccube\Entity\Product", mappedBy="brand", cascade={"persist"})
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="id", referencedColumnName="brand_id")
         * })
         */
        protected $products;

        /**
         * @var \DateTime
         *
         * @ORM\Column(name="created_at", type="datetimetz")
         */
        private $created_at;

        /**
         * @var \DateTime
         *
         * @ORM\Column(name="updated_at", type="datetimetz")
         */
        private $updated_at;

        public function __construct()
        {
            $this->products = new \Doctrine\Common\Collections\ArrayCollection();
        }

        public static function loadValidatorMetadata(ClassMetadata $metadata)
        {
            $metadata->addConstraint(new UniqueEntity([
                'fields' => 'name',
                'message' => 'form_error.brand_already_exists',
            ]));
        }

        public function getId(): ?int
        {
            return $this->id;
        }

        public function setId(int $id): void
        {
            $this->id = $id;
        }

        public function getName(): ?string
        {
            return $this->name;
        }

        public function setName(string $name): self
        {
            $this->name = $name;

            return $this;
        }

        public function getEnName()
        {
            return $this->name;
        }

        public function setEnName(string $name)
        {
            $this->name = $name;

            return $this;
        }

        public function getIsHidden(): ?bool
        {
            return $this->is_hidden;
        }

        public function setIsHidden(bool $is_hidden): self
        {
            $this->is_hidden = $is_hidden;

            return $this;
        }

        /**
         * Get description.
         *
         * @return string
         */
        public function getDescription(): ?string
        {
            return $this->description;
        }

        /**
         * Set description.
         *
         * @param string $description
         *
         * @return $this
         */
        public function setDescription(string $description): self
        {
            $this->description = $description;

            return $this;
        }

        /**
         * Get image.
         *
         * @return string
         */
        public function getImage(): ?string
        {
            return $this->image;
        }

        /**
         * Set image.
         *
         * @param string $image
         *
         * @return $this
         */
        public function setImage(string $image): self
        {
            $this->image = $image;

            return $this;
        }

        /**
         * Set sort_no.
         *
         * @param int $sort_no
         *
         * @return $this
         */
        public function setSortNo($sort_no)
        {
            $this->sort_no = $sort_no;

            return $this;
        }

        /**
         * Get sort_no.
         *
         * @return int
         */
        public function getSortNo()
        {
            return $this->sort_no;
        }

        /**
         * Set createdAt.
         *
         * @param \DateTime $createdAt
         *
         * @return Brand
         */
        public function setCreatedAt($createdAt)
        {
            $this->created_at = $createdAt;

            return $this;
        }

        /**
         * Get createdAt.
         *
         * @return \DateTime
         */
        public function getCreatedAt()
        {
            return $this->created_at;
        }

        /**
         * Set updatedAt.
         *
         * @param \DateTime $updatedAt
         *
         * @return Brand
         */
        public function setUpdatedAt($updatedAt)
        {
            $this->updated_at = $updatedAt;

            return $this;
        }

        /**
         * Get updatedAt.
         *
         * @return \DateTime
         */
        public function getUpdatedAt()
        {
            return $this->updated_at;
        }

        public function removeProducts(Product $product)
        {
            return $this->products->removeElement($product);
        }

        public function addProducts(Product $product) 
        {
            $this->products[] = $product;

            return $this;
        }

        public function getProducts() 
        {
            return $this->products;
        }
    }
}
