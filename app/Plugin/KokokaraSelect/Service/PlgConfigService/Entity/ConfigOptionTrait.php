<?php


namespace Plugin\KokokaraSelect\Service\PlgConfigService\Entity;

use Doctrine\ORM\Mapping as ORM;
use Plugin\KokokaraSelect\Entity\PlgConfig;

trait ConfigOptionTrait
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int|null
     *
     * @ORM\Column(name="value", type="smallint", nullable=true)
     */
    private $value;

    /**
     * @var PlgConfig
     *
     * @ORM\ManyToOne(targetEntity="Plugin\KokokaraSelect\Entity\PlgConfig", inversedBy="PlgConfigOptions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="config_id", referencedColumnName="id")
     * })
     */
    private $PlgConfig;

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
     * @return ConfigInterface PlgConfig
     */
    public function getPlgConfig()
    {
        return $this->PlgConfig;
    }

    /**
     * @param ConfigInterface $PlgConfig
     * @return ConfigOptionTrait
     */
    public function setPlgConfig(ConfigInterface $PlgConfig)
    {
        $this->PlgConfig = $PlgConfig;
        return $this;
    }

    /**
     * Set value.
     *
     * @param int|null $value
     *
     * @return $this
     */
    public function setValue($value = null)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value.
     *
     * @return int|null
     */
    public function getValue()
    {
        return $this->value;
    }
}
