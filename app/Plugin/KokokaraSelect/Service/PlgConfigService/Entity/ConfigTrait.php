<?php


namespace Plugin\KokokaraSelect\Service\PlgConfigService\Entity;

use Doctrine\ORM\Mapping as ORM;

trait ConfigTrait
{

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="smallint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="config_key", type="string", length=255, nullable=false)
     */
    private $configKey;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="config_type", type="integer", nullable=false)
     */
    private $configType;

    /**
     * @var string|null
     *
     * @ORM\Column(name="text_value", type="string", length=255, nullable=true)
     */
    private $textValue;

    /**
     * @var integer
     *
     * @ORM\Column(name="int_value", type="integer", nullable=true)
     */
    private $intValue;

    /**
     * @var bool
     *
     * @ORM\Column(name="bool_value", type="boolean", options={"default":false})
     */
    private $boolValue;

    /**
     * @var integer
     *
     * @ORM\Column(name="group_id", type="integer", nullable=true)
     */
    private $groupId;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_no", type="integer", options={"unsigned":true, "default":1})
     */
    private $sortNo;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\KokokaraSelect\Entity\PlgConfigOption", mappedBy="PlgConfig", cascade={"persist", "remove"})
     */
    private $ConfigOptions;

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
     * Set configKey.
     *
     * @param string $configKey
     *
     * @return $this
     */
    public function setConfigKey($configKey)
    {
        $this->configKey = $configKey;

        return $this;
    }

    /**
     * Get configKey.
     *
     * @return string
     */
    public function getConfigKey()
    {
        return $this->configKey;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set configType.
     *
     * @param int $configType
     *
     * @return $this
     */
    public function setConfigType($configType)
    {
        $this->configType = $configType;

        return $this;
    }

    /**
     * Get configType.
     *
     * @return int
     */
    public function getConfigType()
    {
        return $this->configType;
    }

    /**
     * Set textValue.
     *
     * @param string|null $textValue
     *
     * @return $this
     */
    public function setTextValue($textValue = null)
    {
        $this->textValue = $textValue;

        return $this;
    }

    /**
     * Get textValue.
     *
     * @return string|null
     */
    public function getTextValue()
    {
        return $this->textValue;
    }

    /**
     * Set intValue.
     *
     * @param int|null $intValue
     *
     * @return $this
     */
    public function setIntValue($intValue = null)
    {
        $this->intValue = $intValue;

        return $this;
    }

    /**
     * Get intValue.
     *
     * @return int|null
     */
    public function getIntValue()
    {
        return $this->intValue;
    }

    /**
     * Set boolValue.
     *
     * @param bool $boolValue
     *
     * @return $this
     */
    public function setBoolValue($boolValue)
    {
        $this->boolValue = $boolValue;

        return $this;
    }

    /**
     * Get boolValue.
     *
     * @return bool
     */
    public function getBoolValue()
    {
        return $this->boolValue;
    }

    /**
     * @return bool
     */
    public function isBoolValue()
    {
        return $this->boolValue;
    }

    /**
     * @return int
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @param int $groupId
     * @return $this
     */
    public function setGroupId(int $groupId)
    {
        $this->groupId = $groupId;
        return $this;
    }

    /**
     * @return int
     */
    public function getSortNo()
    {
        return $this->sortNo;
    }

    /**
     * @param int $sortNo
     * @return $this
     */
    public function setSortNo(int $sortNo)
    {
        $this->sortNo = $sortNo;
        return $this;
    }

    /**
     * @param $ConfigOption
     * @return $this
     */
    public function addConfigOptions(ConfigOptionInterface $ConfigOption)
    {
        $this->ConfigOptions[] = $ConfigOption;

        return $this;
    }

    /**
     * @param ConfigOptionInterface $configOption
     * @return bool
     */
    public function removeConfigOption(ConfigOptionInterface $configOption)
    {
        return $this->ConfigOptions->removeElement($configOption);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection $ConfigOptions
     */
    public function getConfigOptions()
    {
        return $this->ConfigOptions;
    }

}
