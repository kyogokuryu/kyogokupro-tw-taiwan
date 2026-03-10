<?php

namespace Plugin\Collection\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
use Eccube\Annotation as Eccube;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="plg_collection")
 * @ORM\Entity(repositoryClass="Plugin\Collection\Repository\CollectionRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 */
class Collection
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $collection_code;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column (type="string", length=4000, nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $display_from;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $display_to;

    /**
     * @ORM\Column(type="boolean", options={"default":false})
     */
    private $visible;

    /**
     * @ORM\Column(type="integer")
     */
    private $sort_no;

    /**
     * @ORM\Column(type="boolean", options={"default":false})
     */
    private $deleted;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $create_date;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $update_date;

    /**
     * @var string
     *
     * @ORM\Column(name="file_name", type="string", length=255, nullable=true)
     * @Eccube\FormAppend
     *      type="Symfony\Component\Form\Extension\Core\Type\FileType;",
     *      options={
     *          "multiple": false,
     *          "required": false,
     *          "mapped": false
     *     })
     */

    private $file_name;

    /**
     * @var \Doctrine\Common\Collections\Collection|CollectionProduct[]
     *
     * @ORM\OneToMany(targetEntity="CollectionProduct", mappedBy="Collection", cascade={"persist"})
     */
    private $CollectionProducts;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->CollectionProducts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCollectionCode(): ?string
    {
        return $this->collection_code;
    }

    public function setCollectionCode(string $collection_code): self
    {
        $this->collection_code = $collection_code;

        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDisplayFrom(): ?\DateTimeInterface
    {
        return $this->display_from;
    }

    public function setDisplayFrom(?\DateTimeInterface $display_from): self
    {
        $this->display_from = $display_from;

        return $this;
    }

    public function getDisplayTo(): ?\DateTimeInterface
    {
        return $this->display_to;
    }

    public function setDisplayTo(?\DateTimeInterface $display_to): self
    {
        $this->display_to = $display_to;

        return $this;
    }

    public function getVisible(): ?int
    {
        return $this->visible;
    }

    public function setVisible(int $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    public function getSortNo(): ?int
    {
        return $this->sort_no;
    }

    public function setSortNo(int $sort_no): self
    {
        $this->sort_no = $sort_no;

        return $this;
    }

    public function getDeleted(): ?int
    {
        return $this->deleted;
    }

    public function setDeleted(int $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getCreateDate(): ?\DateTimeInterface
    {
        return $this->create_date;
    }

    public function setCreateDate(?\DateTimeInterface $create_date): self
    {
        $this->create_date = $create_date;

        return $this;
    }

    public function getUpdateDate(): ?\DateTimeInterface
    {
        return $this->update_date;
    }

    public function setUpdateDate(?\DateTimeInterface $update_date): self
    {
        $this->update_date = $update_date;

        return $this;
    }

    /**
     * Get fileName
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->file_name;
    }
    /**
     * Set fileName
     *
     * @param string $
     *
     * @return filename
     */
    public function setFileName($fileName)
    {
        $this->file_name = $fileName;

        return $this;
    }

    /**
     * Add collectionProduct.
     *
     * @param \Plugin\Collection\Entity\CollectionProduct $CollectionProduct
     *
     * @return Order
     */
    public function addCollectionProduct(\Plugin\Collection\Entity\CollectionProduct $CollectionProduct)
    {
        $this->CollectionProducts[] = $CollectionProduct;

        return $this;
    }

    /**
     * Remove collectionProduct.
     *
     * @param \Plugin\Collection\Entity\CollectionProduct $CollectionProduct
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCollectionProduct(\Plugin\Collection\Entity\CollectionProduct $CollectionProduct)
    {
        return $this->CollectionProducts->removeElement($CollectionProduct);
    }

    /**
     * Get collectionProducts.
     *
     * @return \Doctrine\Common\Collections\Collection|\Plugin\Collection\Entity\CollectionProduct[]
     */
    public function getCollectionProducts()
    {
        // sort by sort_no
        $criteria = Criteria::create()
            ->orderBy(['sort_no' => 'DESC']);

        return $this->CollectionProducts->matching($criteria);
    }

    /**
     * Get collectionProducts by limit
     *
     * @param $limit
     * @return \Doctrine\Common\Collections\Collection|\Plugin\Collection\Entity\CollectionProduct[]
     */
    public function getCollectionProductsLimit($limit)
    {
        // sort by sort_no
        $criteria = Criteria::create()
            ->orderBy(['sort_no' => 'DESC'])
            ->setMaxResults($limit);

        return $this->CollectionProducts->matching($criteria);
    }
}
