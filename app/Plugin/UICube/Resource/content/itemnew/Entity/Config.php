<?php
/*
* Plugin Name : [code]
*/

namespace Plugin\[code]\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * [code]Config
 *
 * @ORM\Table(name="plg_[code_lower]")
 * @ORM\Entity(repositoryClass="Plugin\[code]\Repository\[code]ConfigRepository")
 */
class [code]Config extends AbstractEntity
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
     * @ORM\Column(name="display_order", type="integer", nullable=true)
     */
    private $display_order;
    /**
     * @var int
     *
     * @ORM\Column(name="block_type", type="integer", nullable=true)
     */
    private $block_type;
    /**
     * @var int
     *
     * @ORM\Column(name="display_num", type="integer", nullable=true)
     */
    private $display_num;
    /**
     * @var int
     *
     * @ORM\Column(name="category_id", type="integer", nullable=true)
     */
    private $category_id;
    /**
     * @var int
     *
     * @ORM\Column(name="tag_id", type="integer", nullable=true)
     */
    private $tag_id;

    /**
   * @var string
   *
   * @ORM\Column(name="title", type="text", nullable=true)
   */
    private $title;
    /**
     * @var int
     *
     * @ORM\Column(name="item_name", type="integer", nullable=true)
     */
    private $item_name;
    /**
     * @var int
     *
     * @ORM\Column(name="item_price", type="integer", nullable=true)
     */
    private $item_price;
    /**
     * @var int
     *
     * @ORM\Column(name="item_description", type="integer", nullable=true)
     */
    private $item_description;

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
     * Set title.
     *
     * @param string $title
     *
     * @return [code]Config
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set display_order.
     *
     * @param int $display_order
     *
     * @return [code]Config
     */
    public function setDisplayOrder($display_order)
    {
        $this->display_order = $display_order;

        return $this;
    }

    /**
     * Get display_order.
     *
     * @return int
     */
    public function getDisplayOrder()
    {
        return $this->display_order;
    }

    /**
     * Set block_type.
     *
     * @param int $block_type
     *
     * @return [code]Config
     */
    public function setBlockType($block_type)
    {
        $this->block_type = $block_type;

        return $this;
    }

    /**
     * Get block_type.
     *
     * @return int
     */
    public function getBlockType()
    {
        return $this->block_type;
    }

    /**
     * Set display_num.
     *
     * @param int $display_num
     *
     * @return [code]Config
     */
    public function setDisplayNum($display_num)
    {
        $this->display_num = $display_num;

        return $this;
    }

    /**
     * Get display_num.
     *
     * @return int
     */
    public function getDisplayNum()
    {
        return $this->display_num;
    }

    /**
     * Set category_id.
     *
     * @param int $category_id
     *
     * @return [code]Config
     */
    public function setCategoryId($category_id)
    {
        $this->category_id = $category_id;

        return $this;
    }

    /**
     * Get category_id.
     *
     * @return int
     */
    public function getCategoryId()
    {
        return $this->category_id;
    }

    /**
     * Set tag_id.
     *
     * @param int $tag_id
     *
     * @return [code]Config
     */
    public function setTagId($tag_id)
    {
        $this->tag_id = $tag_id;

        return $this;
    }

    /**
     * Get tag_id.
     *
     * @return int
     */
    public function getTagId()
    {
        return $this->tag_id;
    }

    /**
     * Set item_name.
     *
     * @param int $item_name
     *
     * @return [code]Config
     */
    public function setItemName($item_name)
    {
        $this->item_name = $item_name;

        return $this;
    }

    /**
     * Get item_name.
     *
     * @return int
     */
    public function getItemName()
    {
        return $this->item_name;
    }

    /**
     * Set item_price.
     *
     * @param int $item_price
     *
     * @return [code]Config
     */
    public function setItemPrice($item_price)
    {
        $this->item_price = $item_price;

        return $this;
    }

    /**
     * Get item_price.
     *
     * @return int
     */
    public function getItemPrice()
    {
        return $this->item_price;
    }

    /**
     * Set item_description.
     *
     * @param int $item_description
     *
     * @return [code]Config
     */
    public function setItemDescription($item_description)
    {
        $this->item_description = $item_description;

        return $this;
    }

    /**
     * Get item_description.
     *
     * @return int
     */
    public function getItemDescription()
    {
        return $this->item_description;
    }
}
