<?php
/*
* Plugin Name : uc_slide
*/

namespace Plugin\uc_slide\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * uc_slideConfig
 *
 * @ORM\Table(name="plg_uc_slide")
 * @ORM\Entity(repositoryClass="Plugin\uc_slide\Repository\uc_slideConfigRepository")
 */
class uc_slideConfig extends AbstractEntity
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
     * @ORM\Column(name="block_type", type="integer", nullable=true)
     */
    private $block_type;

    /**
     * @var int
     *
     * @ORM\Column(name="slide_type", type="integer", nullable=true)
     */
    private $slide_type;


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
     * Set block_type.
     *
     * @param int $block_type
     *
     * @return uc_slideConfig
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
     * Set slide_type.
     *
     * @param int $slide_type
     *
     * @return uc_slideConfig
     */
    public function setSlideType($slide_type)
    {
        $this->slide_type = $slide_type;

        return $this;
    }

    /**
     * Get slide_type.
     *
     * @return int
     */
    public function getSlideType()
    {
        return $this->slide_type;
    }
}
