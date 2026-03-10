<?php
/*
* Plugin Name : [code]
*/

namespace Plugin\[code]\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * [code]Data
 *
 * @ORM\Table(name="plg_[code_lower]_data")
 * @ORM\Entity(repositoryClass="Plugin\[code]\Repository\[code]DataRepository")
 */
class [code]Data extends AbstractEntity
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
     * @return [code]Data
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
     * @return [code]Data
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
     * @return [code]Data
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
