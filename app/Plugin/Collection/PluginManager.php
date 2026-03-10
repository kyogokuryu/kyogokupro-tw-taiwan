<?php

namespace Plugin\Collection;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\Constant;
use Eccube\Entity\Csv;
use Eccube\Entity\Layout;
use Eccube\Entity\BlockPosition;
use Eccube\Entity\Master\CsvType;
use Eccube\Entity\Master\DeviceType;
use Eccube\Entity\Page;
use Eccube\Repository\BlockPositionRepository;
use Eccube\Repository\BlockRepository;
use Eccube\Repository\LayoutRepository;
use Eccube\Repository\Master\DeviceTypeRepository;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\Mall\Entity\Shop;
use Plugin\Mall\Repository\PageRepository;
use Plugin\Mall\Repository\ShopRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class PluginManager extends AbstractPluginManager
{
    /**
     * @var array プラグインブロック
     */
    private $blocks;

    /**
     * @var string
     */
    private $pluginTemplateDir = '/Resource/customize_template';

    /**
     * @var Constructor
     */
    public function __construct()
    {
        $this->blocks = [
            [
                'file_name' => 'collection',
                'block_name' => "特集",
                'use_controller' => false
            ]
        ];
    }

    public function install(array $meta, ContainerInterface $container)
    {
    }

    public function enable(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');

        $this->copyTemplates($container);

        foreach ($this->blocks as $block) {
            $Block = $container->get(BlockRepository::class)->findOneBy(['file_name' => $block['file_name']]);
            if (is_null($Block)) {
                $this->createDataBlock($container, $block);
            }
        }

        $em->flush();
    }

    public function disable(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');

        foreach ($this->blocks as $block) {
            $this->removeDataBlock($container, $block);
        }
        $em->flush();
    }

    /**
     * Copy Plugin Templates to Eccube Template Directory
     *
     * @param ContainerInterface $container
     */
    private function copyTemplates(ContainerInterface $container)
    {
        $appTemplateDir = $container->getParameter('eccube_theme_app_dir');
        $fileList = $this->getFileList(dirname(__FILE__) . $this->pluginTemplateDir);
        $fileSystem = new Filesystem();

        foreach ($fileList as $file) {
            if (is_file($file)) {
                $customizeFile = $this->getTemplateCustomizeDir($file, $appTemplateDir);
                $fileSystem->copy($file, $customizeFile);
            }
        }
    }

    /**
     * Get Eccube Template File Directory with Plugin file Directory
     *
     * @param String $pluginFile
     * @param String $appTemplateDir
     * @return mixed|string
     */
    private function getTemplateCustomizeDir(String $pluginFile, String $appTemplateDir)
    {
        $customizeDir = str_replace(dirname(__FILE__), '', $pluginFile);
        $customizeDir = str_replace($this->pluginTemplateDir, '', $customizeDir);
        $customizeDir = $appTemplateDir . $customizeDir;
        return $customizeDir;
    }

    /**
     * Get all file paths lower than the folder
     *
     * @param String $dir
     * @return array
     */
    private function getFileList(String $dir) {
        $files = glob(rtrim($dir, '/') . '/*');
        $list = [];
        foreach ($files as $file) {
            if (is_file($file)) {
                $list[] = $file;
            }
            if (is_dir($file)) {
                $list = array_merge($list, $this->getFileList($file));
            }
        }
        return $list;
    }

    /**
     * Register plugin block
     *
     * @param ContainerInterface $container
     * @param array $block
     *
     * @throws \Exception
     */
    private function createDataBlock(ContainerInterface $container, array $block)
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $DeviceType = $container->get(DeviceTypeRepository::class)->find(DeviceType::DEVICE_TYPE_PC);
        try {
            /** @var Block $Block */
            $Block = $container->get(BlockRepository::class)->newBlock($DeviceType);
            // Blockの登録
            $Block->setName($block['block_name'])
                ->setFileName($block['file_name'])
                ->setUseController($block['use_controller'])
                ->setDeletable(false);
            $em->persist($Block);
            $em->flush($Block);
            // check exists block position
            $blockPos = $container->get(BlockPositionRepository::class)->findOneBy(['Block' => $Block]);
            if ($blockPos) {
                return;
            }
            // BlockPositionの登録
            $blockPos = $container->get(BlockPositionRepository::class)->findOneBy(
                ['section' => Layout::TARGET_ID_MAIN_BOTTOM, 'layout_id' => Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE],
                ['block_row' => 'DESC']
            );
            $BlockPosition = new BlockPosition();
            // ブロックの順序を変更
            $BlockPosition->setBlockRow(1);
            if ($blockPos) {
                $blockRow = $blockPos->getBlockRow() + 1;
                $BlockPosition->setBlockRow($blockRow);
            }
            $LayoutDefault = $container->get(LayoutRepository::class)->find(Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);
            $BlockPosition->setLayout($LayoutDefault)
                ->setLayoutId($LayoutDefault->getId())
                ->setSection(Layout::TARGET_ID_MAIN_BOTTOM)
                ->setBlock($Block)
                ->setBlockId($Block->getId());
            $em->persist($BlockPosition);
            $em->flush($BlockPosition);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Remove plugin block
     *
     * @param ContainerInterface $container
     * @param array $block
     *
     * @throws \Exception
     */
    private function removeDataBlock(ContainerInterface $container, array $block)
    {
        // Blockの取得(file_nameはアプリケーションの仕組み上必ずユニーク)
        /** @var \Eccube\Entity\Block $Block */
        $Block = $container->get(BlockRepository::class)->findOneBy(['file_name' => $block['file_name']]);
        if (!$Block) {
            return;
        }
        $em = $container->get('doctrine.orm.entity_manager');
        try {
            // BlockPositionの削除
            $blockPositions = $Block->getBlockPositions();
            /** @var \Eccube\Entity\BlockPosition $BlockPosition */
            foreach ($blockPositions as $BlockPosition) {
                $Block->removeBlockPosition($BlockPosition);
                $em->remove($BlockPosition);
            }
            // Blockの削除
            $em->remove($Block);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
