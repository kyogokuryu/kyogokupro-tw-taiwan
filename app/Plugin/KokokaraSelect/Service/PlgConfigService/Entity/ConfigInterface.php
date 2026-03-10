<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/05/03
 */

namespace Plugin\KokokaraSelect\Service\PlgConfigService\Entity;

interface ConfigInterface
{
    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

    /**
     * Set configKey.
     *
     * @param string $configKey
     *
     * @return $this
     */
    public function setConfigKey($configKey);

    /**
     * Get configKey.
     *
     * @return string
     */
    public function getConfigKey();

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name);

    /**
     * Set configType.
     *
     * @param int $configType
     *
     * @return $this
     */
    public function setConfigType($configType);

    /**
     * Get configType.
     *
     * @return int
     */
    public function getConfigType();

    /**
     * Set textValue.
     *
     * @param string|null $textValue
     *
     * @return $this
     */
    public function setTextValue($textValue = null);

    /**
     * Get textValue.
     *
     * @return string|null
     */
    public function getTextValue();

    /**
     * Set intValue.
     *
     * @param int|null $intValue
     *
     * @return $this
     */
    public function setIntValue($intValue = null);

    /**
     * Get intValue.
     *
     * @return int|null
     */
    public function getIntValue();

    /**
     * Set boolValue.
     *
     * @param bool $boolValue
     *
     * @return $this
     */
    public function setBoolValue($boolValue);

    /**
     * Get boolValue.
     *
     * @return bool
     */
    public function getBoolValue();

    /**
     * @return bool
     */
    public function isBoolValue();

    /**
     * @return int
     */
    public function getGroupId();

    /**
     * @param int $groupId
     * @return $this
     */
    public function setGroupId(int $groupId);

    /**
     * @param int $sortNo
     * @return mixed
     */
    public function setSortNo(int $sortNo);

    /**
     * @param ConfigOptionInterface $ConfigOption
     * @return mixed
     */
    public function addConfigOptions(ConfigOptionInterface $ConfigOption);

    /**
     * @param ConfigOptionInterface $configOption
     * @return bool
     */
    public function removeConfigOption(ConfigOptionInterface $configOption);

    /**
     * @return array
     */
    public function getConfigOptions();
}
