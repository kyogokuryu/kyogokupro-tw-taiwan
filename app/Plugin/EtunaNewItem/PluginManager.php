<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) Takashi Otaki All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\EtunaNewItem;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Application;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\EtunaNewItem\Entity\EtunaNewItemConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Eccube\Entity\Block;
use Eccube\Entity\Master\DeviceType;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class PluginManager.
 */
class PluginManager extends AbstractPluginManager
{
    public function install(array $meta, ContainerInterface $container)
    {
        $fs = new Filesystem();

        $dir = sprintf('%s/app/template/%s/Block',
            $container->getParameter('kernel.project_dir'),
            $container->getParameter('eccube.theme'));

        if(!file_exists($dir)) {
            $fs->mkdir($dir, 0777);
        }

        try {

            $plgDir = sprintf('%s/app/Plugin/EtunaNewItem/etuna_new_item.twig',
                $container->getParameter('kernel.project_dir'));

            $fs->copy($plgDir, $dir. '/etuna_new_item.twig', true);

        } catch (\Exception $e) {
            return false;
        }
    }

    public function enable(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');

        // プラグイン設定を追加
        $Config = $this->createConfig($em);

        // ブロックを追加
        if (!$Config->getBlockId()) {
            $Block = $this->createBlock($em);

            $Config->setBlockId($Block);
            $em->flush($Config);
        }
    }

    public function uninstall(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');

        $Config = $em->find(EtunaNewItemConfig::class, 1);
        $Block = $Config->getBlockId();

        $Config->setBlockId(null);
        $em->flush($Config);

        $em->remove($Block);
        $em->flush($Block);

        $fs = new Filesystem();

        $dir = sprintf('%s/app/template/%s/Block',
            $container->getParameter('kernel.project_dir'),
            $container->getParameter('eccube.theme'));

        try {

            $fs->remove($dir.'/etuna_new_item.twig');

        } catch (\Exception $e) {
            return false;
        }
    }

    protected function createConfig(EntityManagerInterface $em)
    {
        $Config = $em->find(EtunaNewItemConfig::class, 1);
        if ($Config) {
            return $Config;
        }
        $Config = new EtunaNewItemConfig();
        $Config->setNewitemSort(0);
        $Config->setNewitemCount(10);
        $Config->setNewitemTitle('新着商品');
        $Config->setNewitemDispTitle(1);
        $Config->setNewitemDispPrice(1);
        $Config->setNewitemDispDescriptionDetail(0);
        $Config->setNewitemDispCode(0);
        $Config->setNewitemDispCat(0);

        $em->persist($Config);
        $em->flush($Config);

        return $Config;
    }

    protected function createBlock(EntityManagerInterface $em)
    {
        $DeviceType = $em->find(DeviceType::class, DeviceType::DEVICE_TYPE_PC);

        $result = $em->createQueryBuilder('ct')
            ->select('COALESCE(MAX(ct.id), 0) AS id')
            ->from(Block::class, 'ct')
            ->getQuery()
            ->getSingleResult();

        $result['id']++;

        $Block = new Block();
        $Block
            ->setId($result['id'])
            ->setName('新着商品')
            ->setDeviceType($DeviceType)
            ->setFileName('etuna_new_item')
            ->setUseController(1)
            ->setDeletable(0);
        $em->persist($Block);
        $em->flush($Block);

        return $Block;
    }
}
