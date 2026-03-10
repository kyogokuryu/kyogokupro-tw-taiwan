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
     * @var string
     *
     * @ORM\Column(name="title", type="text", nullable=true)
     */
    private $title;
    
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
}
