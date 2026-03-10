<?php

namespace Plugin\Collection\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Product;

/**
 * @ORM\Table(name="plg_collection_product")
 * @ORM\Entity(repositoryClass="Plugin\Collection\Repository\CollectionProductRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 */
class CollectionProduct
{
    /**
     * temporary number of sort_no to remove
     * 
     * @var int
     */
    public const INVALID_SORT_NO = -1;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Collection", inversedBy="CollectionProducts", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="collection_id", referencedColumnName="id")
     * })
     */
    private $Collection;

    /**
     * @ORM\ManyToOne(targetEntity="\Eccube\Entity\Product")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     * })
     */
    private $Product;

    /**
     * @ORM\Column(type="integer")
     */
    private $sort_no;

    /**
     * CollectionProduct constructor.
     */
    public function __construct() {
        //
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCollection(): ?Collection
    {
        return $this->Collection;
    }

    public function setCollection(Collection $Collection): self
    {
        $this->Collection = $Collection;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->Product;
    }

    public function setProduct(Product $Product): self
    {
        $this->Product = $Product;

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
}
