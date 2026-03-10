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
     * @ORM\Column(name="display_title", type="integer", nullable=true)
     */
    private $display_title;

    /**
     * @var int
     *
     * @ORM\Column(name="display_description", type="integer", nullable=true)
     */
    private $display_description;

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
     * Set display_title.
     *
     * @param int $display_title
     *
     * @return [code]Config
     */
    public function setDisplayTitle($display_title)
    {
        $this->display_title = $display_title;

        return $this;
    }

    /**
     * Get display_title.
     *
     * @return int
     */
    public function getDisplayTitle()
    {
        return $this->display_title;
    }

    /**
     * Set display_description.
     *
     * @param int $display_description
     *
     * @return [code]Config
     */
    public function setDisplayDescription($display_description)
    {
        $this->display_description = $display_description;

        return $this;
    }

    /**
     * Get display_description.
     *
     * @return int
     */
    public function getDisplayDescription()
    {
        return $this->display_description;
    }
}
