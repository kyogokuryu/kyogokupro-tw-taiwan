<?php

namespace Plugin\ExtraAgreeCheck;

use Eccube\Plugin\AbstractPluginManager;
use Plugin\ExtraAgreeCheck\Entity\Config;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginManager extends AbstractPluginManager
{
    public function enable(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');

        // プラグイン設定を追加
        /** @var Config $Config */
        $Config = $em->find(Config::class, 1);
        if ($Config) {
            return $Config;
        }
        $Config = new Config();

        $Config->setNonmemberAddCheck(false);
        $Config->setNonmemberCheckLabel('');
        $Config->setContactAddCheck(false);
        $Config->setContactCheckLabel('');
        $Config->setAutoInsert(false);

        $em->persist($Config);
        $em->flush($Config);
    }

    public function uninstall(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');

        // プラグイン設定を削除
        /** @var Config $Config */
        $Config = $em->find(Config::class, 1);
        if ($Config) {
            $Config->setNonmemberAddCheck(false);
            $Config->setNonmemberCheckLabel('');
            $Config->setContactAddCheck(false);
            $Config->setContactCheckLabel('');
            $Config->setAutoInsert(false);
            $em->flush($Config);
        }
    }
}
