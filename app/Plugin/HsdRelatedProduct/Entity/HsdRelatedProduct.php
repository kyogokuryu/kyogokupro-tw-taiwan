<?php

namespace Plugin\HsdRelatedProduct\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Config
 *
 * @ORM\Table(name="plg_hsd_related_product")
 * @ORM\Entity(repositoryClass="Plugin\HsdRelatedProduct\Repository\HsdRelatedProductRepository")
 */
class HsdRelatedProduct
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
     * @ORM\Column(name="from_id", type="integer", options={"unsigned":true})
     */
    private $from_id;

    /**
     * @var string
     *
     * @ORM\Column(name="to_id", type="integer", options={"unsigned":true})
     */
    private $to_id;

    /**
     * @var string
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updated_at;

    public function getId()
    {
        return $this->id;
    }
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getFromId()
    {
        return $this->from_id;
    }
    public function setFromId($num)
    {
        $this->from_id = $num;

        return $this;
    }

    public function getToId()
    {
        return $this->to_id;
    }
    public function setToId($str)
    {
        $this->to_id = $str;

        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }
    public function setTitle($str)
    {
        $this->title = $str;

        return $this;
    }

    public function getUpdatedAt()
    {
        return $this->updated_at;
    }
    public function setUpdatedAt($str)
    {
        $this->updated_at = $str;

        return $this;
    }

}
