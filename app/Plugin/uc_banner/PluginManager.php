<?php
/*
* Plugin Name : uc_banner
*/

namespace Plugin\uc_banner;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Application;
use Eccube\Entity\Block;
use Eccube\Entity\BlockPosition;
use Eccube\Entity\Layout;
use Eccube\Entity\Master\DeviceType;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\BlockPositionRepository;
use Eccube\Repository\BlockRepository;
use Eccube\Repository\LayoutRepository;
use Eccube\Repository\Master\DeviceTypeRepository;
use Plugin\uc_banner\Entity\uc_bannerConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;


/**
 * Class PluginManager.
 */
class PluginManager extends AbstractPluginManager
{
    /**
     * @var string コピー元ブロックファイル
     */
    private $originBlock;

    /**
     * @var string ブロック名
     */
    private $blockName = 'バナー';

    /**
     * @var string ブロックファイル名
     */
    private $blockFileName = 'uc_banner_block';

    /**
     * PluginManager constructor.
     */
    public function __construct()
    {
        // コピー元ブロックファイル
        $this->originBlock = __DIR__.'/Resource/template/Block/'.$this->blockFileName.'_0.twig';
    }

    /**
     * @param null|array $meta
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function enable(array $meta = null, ContainerInterface $container)
    {
        // プラグイン設定を追加
        $em = $container->get('doctrine.orm.entity_manager');
        $Config = $this->createConfig($em);

        // ブロックのコピー
        $this->copyBlock($container);
        // ファイル名を検索
        $Block = $container->get(BlockRepository::class)->findOneBy(['file_name' => $this->blockFileName]);
        // ファイル名がなければ
        if (is_null($Block)) {
            // pagelayoutの作成
            $this->createDataBlock($container);
        }
    }

    /**
     * @param array|null $meta
     * @param ContainerInterface $container
     */
    public function disable(array $meta = null, ContainerInterface $container)
    {
        $this->removeDataBlock($container);
    }

    /**
     * @param null $meta
     * @param Application|null $app
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        // ブロックの削除
        $this->removeDataBlock($container);
        $this->removeBlock($container);
    }

    /**
     * @param array|null $meta
     * @param ContainerInterface $container
     */
    public function update(array $meta = null, ContainerInterface $container)
    {
        $this->copyBlock($container);
    }

    /**
     * 設定画面を登録.
     *
     * @param ContainerInterface $container
     */
    protected function createConfig(EntityManagerInterface $em)
    {
        $Config = $em->find(uc_bannerConfig::class, 1);
        if ($Config) {
            return $Config;
        }

        $Config = new uc_bannerConfig();
        $Config->setTitle('バナー');
        $Config->setDisplayTitle(0);
        $Config->setDisplayDescription(1);
        $em->persist($Config);
        $em->flush($Config);

        return $Config;
    }


    /**
     * ブロックを登録.
     *
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    private function createDataBlock(ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $DeviceType = $container->get(DeviceTypeRepository::class)->find(DeviceType::DEVICE_TYPE_PC);

        try {
            /** @var Block $Block */
            $Block = $container->get(BlockRepository::class)->newBlock($DeviceType);

            // Blockの登録
            $Block->setName($this->blockName)
                ->setFileName($this->blockFileName)
                ->setUseController(false)
                ->setDeletable(false);
            $em->persist($Block);
            $em->flush($Block);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * ブロックを削除.
     *
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    private function removeDataBlock(ContainerInterface $container)
    {
        // Blockの取得(file_nameはアプリケーションの仕組み上必ずユニーク)
        /** @var \Eccube\Entity\Block $Block */
        $Block = $container->get(BlockRepository::class)->findOneBy(['file_name' => $this->blockFileName]);

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
            $em->flush();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Copy block template.
     *
     * @param ContainerInterface $container
     */
    private function copyBlock(ContainerInterface $container)
    {
        $templateDir = $container->getParameter('eccube_theme_front_dir');
        // ファイルコピー
        $file = new Filesystem();

        if (!$file->exists($templateDir.'/Block/'.$this->blockFileName.'.twig')) {
            // ブロックファイルをコピー
            $file->copy($this->originBlock, $templateDir.'/Block/'.$this->blockFileName.'.twig');
        }
    }

    /**
     * Remove block template.
     *
     * @param ContainerInterface $container
     */
    private function removeBlock(ContainerInterface $container)
    {
        $templateDir = $container->getParameter('eccube_theme_front_dir');
        $file = new Filesystem();
        $file->remove($templateDir.'/Block/'.$this->blockFileName.'.twig');
    }
}
