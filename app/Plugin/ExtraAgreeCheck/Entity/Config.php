<?php

namespace Plugin\ExtraAgreeCheck\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Config
 *
 * @ORM\Table(name="plg_extra_agree_check_config")
 * @ORM\Entity(repositoryClass="Plugin\ExtraAgreeCheck\Repository\ConfigRepository")
 *
 * @package Plugin\ExtraAgreeCheck\Entity
 */
class Config extends \Eccube\Entity\AbstractEntity
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
     * @var boolean
     *
     * @ORM\Column(name="nonmember_add_check", type="boolean", options={"default":false})
     */
    private $nonmember_add_check;

    /**
     * @var string
     *
     * @ORM\Column(name="nonmember_check_label", type="string", nullable=true)
     */
    private $nonmember_check_label;

    /**
     * @var boolean
     *
     * @ORM\Column(name="contact_add_check", type="boolean", options={"default":false})
     */
    private $contact_add_check;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_check_label", type="string", nullable=true)
     */
    private $contact_check_label;

    /**
     * @var boolean
     *
     * @ORM\Column(name="auto_insert", type="boolean", options={"default":false})
     */
    private $auto_insert;

    /**
     * Set nigiwai_block config id.
     *
     * @param int $id
     *
     * @return Config
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * @param boolean $nonmember_add_check
     *
     * @return Config
     */
    public function setNonmemberAddCheck($nonmember_add_check): Config
    {
        $this->nonmember_add_check = $nonmember_add_check;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getNonmemberAddCheck()
    {
        return $this->nonmember_add_check;
    }

    /**
     * @param string|null $nonmember_check_label
     *
     * @return Config
     */
    public function setNonmemberCheckLabel($nonmember_check_label): Config
    {
        $this->nonmember_check_label = $nonmember_check_label;

        return $this;
    }

    /**
     * @return string
     */
    public function getNonmemberCheckLabel()
    {
        return $this->nonmember_check_label;
    }

    /**
     * @param boolean $contact_add_check
     *
     * @return Config
     */
    public function setContactAddCheck($contact_add_check): Config
    {
        $this->contact_add_check = $contact_add_check;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getContactAddCheck()
    {
        return $this->contact_add_check;
    }

    /**
     * @param string|null $contact_check_label
     *
     * @return Config
     */
    public function setContactCheckLabel($contact_check_label): Config
    {
        $this->contact_check_label = $contact_check_label;

        return $this;
    }

    /**
     * @return string
     */
    public function getContactCheckLabel()
    {
        return $this->contact_check_label;
    }

    /**
     * @param boolean $auto_insert
     *
     * @return Config
     */
    public function setAutoInsert($auto_insert): Config
    {
        $this->auto_insert = $auto_insert;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getAutoInsert()
    {
        return $this->auto_insert;
    }
}
