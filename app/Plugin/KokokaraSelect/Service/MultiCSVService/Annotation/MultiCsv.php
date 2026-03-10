<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/20
 */

namespace Plugin\KokokaraSelect\Service\MultiCSVService\Annotation;


/**
 * @Annotation
 * @Target("CLASS")
 */
final class MultiCsv
{
    private $title;
    private $subTitle;
    private $menus;
    private $upMessage;

    public function __construct($data)
    {
        foreach ($data as $key => $value) {
            $method = 'set' . str_replace('_', '', $key);
            if (!method_exists($this, $method)) {
                throw new \BadMethodCallException(sprintf('Unknown property "%s" on annotation "%s".', $key, \get_class($this)));
            }
            $this->$method($value);
        }
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title): void
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getSubTitle()
    {
        return $this->subTitle;
    }

    /**
     * @param mixed $subTitle
     */
    public function setSubTitle($subTitle): void
    {
        $this->subTitle = $subTitle;
    }

    /**
     * @return mixed
     */
    public function getMenus()
    {
        return $this->menus;
    }

    /**
     * @param mixed $menus
     */
    public function setMenus($menus): void
    {
        $this->menus = $menus;
    }

    /**
     * @return mixed
     */
    public function getUpMessage()
    {
        return $this->upMessage;
    }

    /**
     * @param mixed $upMessage
     */
    public function setUpMessage($upMessage): void
    {
        $this->upMessage = $upMessage;
    }
}
