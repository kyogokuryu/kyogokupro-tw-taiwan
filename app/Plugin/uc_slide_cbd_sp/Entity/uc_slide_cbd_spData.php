<?php
/*
* Plugin Name : uc_slide_cbd_sp
*/

namespace Plugin\uc_slide_cbd_sp\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * uc_slide_cbd_spData
 *
 * @ORM\Table(name="plg_uc_slide_cbd_sp_data")
 * @ORM\Entity(repositoryClass="Plugin\uc_slide_cbd_sp\Repository\uc_slide_cbd_spDataRepository")
 */
class uc_slide_cbd_spData extends AbstractEntity
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
     * @ORM\Column(name="img_url", type="text", nullable=true)
     */
    private $img_url;

    /**
     * @var string
     *
     * @ORM\Column(name="img_alt", type="text", nullable=true)
     */
    private $img_alt;

    /**
     * @var string
     *
     * @ORM\Column(name="link_url", type="text", nullable=true)
     */
    private $link_url;


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
     * Set img_url.
     *
     * @param string $img_url
     *
     * @return uc_slide_cbd_spData
     */
    public function setImgUrl($img_url)
    {
        $this->img_url = $img_url;

        return $this;
    }

    /**
     * Get img_url.
     *
     * @return string
     */
    public function getImgUrl()
    {
        return $this->img_url;
    }

    /**
     * Set img_alt.
     *
     * @param string $img_alt
     *
     * @return uc_slide_cbd_spData
     */
    public function setImgAlt($img_alt)
    {
        $this->img_alt = $img_alt;

        return $this;
    }

    /**
     * Get img_alt.
     *
     * @return string
     */
    public function getImgAlt()
    {
        return $this->img_alt;
    }

    /**
     * Set link_url.
     *
     * @param string $link_url
     *
     * @return uc_slide_cbd_spData
     */
    public function setLinkUrl($link_url)
    {
        $this->link_url = $link_url;

        return $this;
    }

    /**
     * Get link_url.
     *
     * @return string
     */
    public function getLinkUrl()
    {
        return $this->link_url;
    }
}
