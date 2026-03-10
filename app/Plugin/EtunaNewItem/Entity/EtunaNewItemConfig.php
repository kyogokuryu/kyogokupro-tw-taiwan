<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) Takashi Otaki All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\EtunaNewItem\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * EtunaNewItemConfig
 *
 * @ORM\Table(name="plg_etuna_new_item_config")
 * @ORM\Entity(repositoryClass="Plugin\EtunaNewItem\Repository\EtunaNewItemConfigRepository")
 */
class EtunaNewItemConfig extends AbstractEntity
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
     * @ORM\Column(name="newitem_sort", type="smallint", nullable=false, options={"unsigned":true})
     */
    private $newitem_sort;

    /**
     * @var int
     *
     * @ORM\Column(name="newitem_count", type="smallint", nullable=false, options={"unsigned":true})
     */
    private $newitem_count;

    /**
     * @var string
     *
     * @ORM\Column(name="newitem_title", type="text", nullable=true)
     */
    private $newitem_title;

    /**
     * @var int
     *
     * @ORM\Column(name="newitem_disp_title", type="smallint", nullable=false, options={"unsigned":true})
     */
    private $newitem_disp_title;

    /**
     * @var int
     *
     * @ORM\Column(name="newitem_disp_price", type="smallint", nullable=false, options={"unsigned":true})
     */
    private $newitem_disp_price;

    /**
     * @var int
     *
     * @ORM\Column(name="newitem_disp_description_detail", type="smallint", nullable=false, options={"unsigned":true})
     */
    private $newitem_disp_description_detail;

    /**
     * @var int
     *
     * @ORM\Column(name="newitem_disp_code", type="smallint", nullable=false, options={"unsigned":true})
     */
    private $newitem_disp_code;

    /**
     * @var int
     *
     * @ORM\Column(name="newitem_disp_cat", type="smallint", nullable=false, options={"unsigned":true})
     */
    private $newitem_disp_cat;

    /**
     * @var \Eccube\Entity\Block
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Block")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="block_id", nullable=true, referencedColumnName="id")
     * })
     */
    private $block_id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getNewitemSort()
    {
        return $this->newitem_sort;
    }

    /**
     * @param int $newitem_sort
     */
    public function setNewitemSort($newitem_sort)
    {
        $this->newitem_sort = $newitem_sort;
    }

    /**
     * @return int
     */
    public function getNewitemCount()
    {
        return $this->newitem_count;
    }

    /**
     * @param int $newitem_count
     */
    public function setNewitemCount($newitem_count)
    {
        $this->newitem_count = $newitem_count;
    }

    /**
     * @return string
     */
    public function getNewitemTitle()
    {
        return $this->newitem_title;
    }

    /**
     * @param string $newitem_title
     */
    public function setNewitemTitle($newitem_title)
    {
        $this->newitem_title = $newitem_title;
    }

    /**
     * @return int
     */
    public function getNewitemDispTitle()
    {
        return $this->newitem_disp_title;
    }

    /**
     * @param int $newitem_disp_title
     */
    public function setNewitemDispTitle($newitem_disp_title)
    {
        $this->newitem_disp_title = $newitem_disp_title;
    }

    /**
     * @return int
     */
    public function getNewitemDispPrice()
    {
        return $this->newitem_disp_price;
    }

    /**
     * @param int $newitem_disp_price
     */
    public function setNewitemDispPrice($newitem_disp_price)
    {
        $this->newitem_disp_price = $newitem_disp_price;
    }

    /**
     * @return int
     */
    public function getNewitemDispDescriptionDetail()
    {
        return $this->newitem_disp_description_detail;
    }

    /**
     * @param int $newitem_disp_description_detail
     */
    public function setNewitemDispDescriptionDetail($newitem_disp_description_detail)
    {
        $this->newitem_disp_description_detail = $newitem_disp_description_detail;
    }

    /**
     * @return int
     */
    public function getNewitemDispCode()
    {
        return $this->newitem_disp_code;
    }

    /**
     * @param int $newitem_disp_code
     */
    public function setNewitemDispCode($newitem_disp_code)
    {
        $this->newitem_disp_code = $newitem_disp_code;
    }

    /**
     * @return int
     */
    public function getNewitemDispCat()
    {
        return $this->newitem_disp_cat;
    }

    /**
     * @param int $newitem_disp_cat
     */
    public function setNewitemDispCat($newitem_disp_cat)
    {
        $this->newitem_disp_cat = $newitem_disp_cat;
    }

    /**
     * @return \Eccube\Entity\Block
     */
    public function getBlockId()
    {
        return $this->block_id;
    }

    /**
     * @param \Eccube\Entity\Block $block_id
     */
    public function setBlockId($block_id)
    {
        $this->block_id = $block_id;
    }
}
