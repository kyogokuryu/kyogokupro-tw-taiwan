<?php

namespace Plugin\HsdRelatedProduct\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Config
 *
 * @ORM\Table(name="plg_hsd_related_product_config")
 * @ORM\Entity(repositoryClass="Plugin\HsdRelatedProduct\Repository\ConfigRepository")
 */
class Config
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
     * @ORM\Column(name="max_num", type="integer", options={"unsigned":true})
     */
    private $max_num;

    /**
     * @var string
     *
     * @ORM\Column(name="max_row_num", type="integer", options={"unsigned":true})
     */
    private $max_row_num;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=128)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="show_price", type="string", length=16)
     */
    private $show_price;

    /**
     * @var string
     *
     * @ORM\Column(name="show_type", type="string", length=16)
     */
    private $show_type;

    /**
     * @var string
     *
     * @ORM\Column(name="pagination", type="string", length=16)
     */
    private $pagination;

    /**
     * @var string
     *
     * @ORM\Column(name="navbuttons", type="string", length=16)
     */
    private $navbuttons;

    /**
     * @var string
     *
     * @ORM\Column(name="showloop", type="string", length=16)
     */
    private $showloop;

    public function getId()
    {
        return $this->id;
    }
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getMaxNum()
    {
        return $this->max_num;
    }
    public function setMaxNum($num)
    {
        $this->max_num = $num;

        return $this;
    }

    public function getMaxRowNum()
    {
        return $this->max_row_num;
    }
    public function setMaxRowNum($num)
    {
        $this->max_row_num = $num;

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

    public function getShowPrice()
    {
        return $this->show_price;
    }
    public function setShowPrice($str)
    {
        $this->show_price = $str;

        return $this;
    }

    public function getShowType()
    {
        return $this->show_type;
    }
    public function setShowType($str)
    {
        $this->show_type = $str;

        return $this;
    }

    public function getPagination()
    {
        return $this->pagination;
    }
    public function setPagination($str)
    {
        $this->pagination = $str;

        return $this;
    }

    public function getNavbuttons()
    {
        return $this->navbuttons;
    }
    public function setNavbuttons($str)
    {
        $this->navbuttons = $str;

        return $this;
    }

    public function getShowloop()
    {
        return $this->showloop;
    }
    public function setShowloop($str)
    {
        $this->showloop = $str;

        return $this;
    }

}
