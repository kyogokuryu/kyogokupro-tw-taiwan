<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/03/14
 */

namespace Plugin\KokokaraSelect\Service;


use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginLinkService
{

    /** @var ContainerInterface */
    private $container;

    public function __construct(
        ContainerInterface $container
    )
    {
        $this->container = $container;
    }

    /**
     * プラグインの有効チェック
     *
     * @param string $plgCode プラグインコード
     * @return bool true:有効
     */
    public function isActivePlugin($plgCode)
    {
        $plugins = $this->container->getParameter('eccube.plugins.enabled');
        $pluginsCheck = array_flip($plugins);

        if (!empty($plugins) && isset($pluginsCheck[$plgCode])) {
            return true;
        }

        return false;
    }

}
