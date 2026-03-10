<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/06/13
 */

namespace Plugin\KokokaraSelect\Service\PlgConfigService\Entity;


interface ConfigOptionInterface
{

    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

    /**
     * Set configId.
     *
     * @param ConfigInterface $PlgConfig
     * @return $this
     */
    public function setPlgConfig(ConfigInterface $PlgConfig);

    /**
     * Get configId.
     */
    public function getPlgConfig();

    /**
     * Set value.
     *
     * @param int|null $value
     *
     * @return $this
     */
    public function setValue($value = null);

    /**
     * Get value.
     *
     * @return int|null
     */
    public function getValue();
}
