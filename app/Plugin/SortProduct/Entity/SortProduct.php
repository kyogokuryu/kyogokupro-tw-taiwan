<?php

namespace Plugin\SortProduct\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SortProduct
 *
 * @ORM\Table(name="plg_sort_product")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\SortProduct\Repository\SortProductRepository")
 */
class SortProduct extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="product_id", type="integer", nullable=false)
     */
    private $product_id;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_no", type="integer", nullable=true)
     */
    private $sort_no;

    public function getId()
    {
        return $this->id;
    }

    public function setProductId($product_id)
    {
        $this->product_id = $product_id;
        return $this;
    }

    public function getProductId()
    {
        return $this->product_id;
    }

    public function setSortNo($sort_no)
    {
        $this->sort_no = $sort_no;
        return $this;
    }

    public function getSortNo()
    {
        return $this->sort_no;
    }
}
