<?php

namespace Plugin\KokokaraSelect\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PlgKsSelectItemStock
 *
 * @ORM\Table(name="plg_ks_select_item_stock")
 * @ORM\Entity(repositoryClass="Plugin\KokokaraSelect\Repository\KsSelectItemStockRepository")
 */
class KsSelectItemStock extends \Eccube\Entity\AbstractEntity
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
     * @var $KsSelectItem
     *
     * @ORM\OneToOne(targetEntity="Plugin\KokokaraSelect\Entity\KsSelectItem", inversedBy="KsSelectItemStock")
     * @ORM\JoinColumns({
     *    @ORM\JoinColumn(name="select_item_id", referencedColumnName="id")
     * })
     */
    private $KsSelectItem;

    /**
     * @var int|null
     *
     * @ORM\Column(name="stock", type="integer", nullable=true)
     */
    private $stock;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="create_date", type="datetimetz", nullable=true)
     */
    private $createDate;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="update_date", type="datetimetz", nullable=true)
     */
    private $updateDate;


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
     * Set selectItemId.
     *
     * @param int|null $selectItemId
     *
     * @return KsSelectItemStock
     */
    public function setSelectItemId($selectItemId = null)
    {
        $this->selectItemId = $selectItemId;

        return $this;
    }

    /**
     * Get selectItemId.
     *
     * @return int|null
     */
    public function getSelectItemId()
    {
        return $this->selectItemId;
    }

    /**
     * Set stock.
     *
     * @param int|null $stock
     *
     * @return KsSelectItemStock
     */
    public function setStock($stock = null)
    {
        $this->stock = $stock;

        return $this;
    }

    /**
     * Get stock.
     *
     * @return int|null
     */
    public function getStock()
    {
        return $this->stock;
    }

    /**
     * Set createDate.
     *
     * @param \DateTime|null $createDate
     *
     * @return KsSelectItemStock
     */
    public function setCreateDate($createDate = null)
    {
        $this->createDate = $createDate;

        return $this;
    }

    /**
     * Get createDate.
     *
     * @return \DateTime|null
     */
    public function getCreateDate()
    {
        return $this->createDate;
    }

    /**
     * Set updateDate.
     *
     * @param \DateTime|null $updateDate
     *
     * @return KsSelectItemStock
     */
    public function setUpdateDate($updateDate = null)
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    /**
     * Get updateDate.
     *
     * @return \DateTime|null
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
    }

    /**
     * Set ksSelectItem.
     *
     * @param \Plugin\KokokaraSelect\Entity\KsSelectItem|null $ksSelectItem
     *
     * @return KsSelectItemStock
     */
    public function setKsSelectItem(\Plugin\KokokaraSelect\Entity\KsSelectItem $ksSelectItem = null)
    {
        $this->KsSelectItem = $ksSelectItem;

        return $this;
    }

    /**
     * Get ksSelectItem.
     *
     * @return \Plugin\KokokaraSelect\Entity\KsSelectItem|null
     */
    public function getKsSelectItem()
    {
        return $this->KsSelectItem;
    }
}
