<?php
/*
 * Copyright(c) 2020 YAMATO.CO.LTD
 */
namespace Plugin\SameCategoryProduct;

use Eccube\Entity\Block;
use Eccube\Entity\BlockPosition;
use Eccube\Entity\Master\DeviceType;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\BlockRepository;
use Eccube\Repository\Master\DeviceTypeRepository;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * プラグインマネージャ<br>
 *
 * 必要なblockファイルのコピーと、レコードをDBに登録する
 *
 * @author Masaki Okada
 */
class PluginManager extends AbstractPluginManager
{

    /** @var string コピー元ブロックファイル */
    private $originBlock;

    /** @var string ブロック名 */
    private $blockName = '同カテゴリ商品';

    /** @var string ブロックファイル名 */
    private $blockFileName = 'same_category_product';

    /**
     * PluginManager constructor.
     */
    public function __construct()
    {
        // コピー元ブロックファイル
        $this->originBlock = __DIR__ . '/Resource/template/Block/' . $this->blockFileName . '.twig';
    }

    /**
     * プラグイン　有効化
     *
     * @param null|array $meta
     * @param ContainerInterface $container
     * @throws Exception
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        $this->copyBlock($container);
        $Block = $container->get(BlockRepository::class)->findOneBy([
            'file_name' => $this->blockFileName
        ]);
        if (is_null($Block)) {
            // pagelayoutの作成
            $this->createDataBlock($container);
        }
    }

    /**
     * プラグイン　更新
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function update(array $meta, ContainerInterface $container)
    {
        $this->copyBlock($container);
    }

    /**
     * プラグイン　無効化
     *
     * @param array $meta
     * @param ContainerInterface $container
     * @throws Exception
     */
    public function disable(array $meta, ContainerInterface $container)
    {
        $this->removeDataBlock($container);
    }

    /**
     * プラグイン　アンインストール
     *
     * @param array $meta
     * @param ContainerInterface $container
     * @throws Exception
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        // ブロックの削除
        $this->removeDataBlock($container);
        $this->removeBlock($container);
    }

    /**
     * ブロック作成
     *
     * @param ContainerInterface $container
     * @throws Exception
     */
    protected function createDataBlock(ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $DeviceType = $container->get(DeviceTypeRepository::class)->find(DeviceType::DEVICE_TYPE_PC);
        try {
            /** @var Block $Block */
            $Block = $container->get(BlockRepository::class)->newBlock($DeviceType);
            $Block->setFileName($this->blockFileName)
                ->setName($this->blockName)
                ->setUseController(true)
                ->setDeletable(false);
            $em->persist($Block);
            $em->flush();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * dtb_block, dtb_block_position のレコード削除処理
     *
     * @param ContainerInterface $container
     * @throws Exception
     */
    private function removeDataBlock(ContainerInterface $container)
    {
        // Blockの取得(file_nameはアプリケーションの仕組み上必ずユニーク)
        /** @var Block $Block */
        $Block = $container->get(BlockRepository::class)->findOneBy([
            'file_name' => $this->blockFileName
        ]);

        if (! $Block) {
            return;
        }

        $em = $container->get('doctrine.orm.entity_manager');
        try {
            // BlockPositionの削除
            $blockPositions = $Block->getBlockPositions();
            /** @var BlockPosition $BlockPosition */
            foreach ($blockPositions as $BlockPosition) {
                $Block->removeBlockPosition($BlockPosition);
                $em->remove($BlockPosition);
            }

            // Blockの削除
            $em->remove($Block);
            $em->flush();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * ブロックファイルをコピー
     *
     * @param ContainerInterface $container
     */
    private function copyBlock(ContainerInterface $container)
    {
        $file = new Filesystem();

        $distFile = $this->getDistBlockFilePath($container);

        if (! $file->exists($distFile)) {
            $file->copy($this->originBlock, $distFile);
        }
    }

    /**
     * ブロックファイルを削除
     *
     * @param ContainerInterface $container
     */
    private function removeBlock(ContainerInterface $container)
    {
        $file = new Filesystem();
        $file->remove($this->getDistBlockFilePath($container));
    }

    /**
     * コピー先ファイルパスを返却
     *
     * @param ContainerInterface $container
     * @return string コピー先のファイルパス
     */
    private function getDistBlockFilePath(ContainerInterface $container)
    {
        $templateDir = $container->getParameter('eccube_theme_front_dir');
        return $templateDir . '/Block/' . $this->blockFileName . '.twig';
    }
}
