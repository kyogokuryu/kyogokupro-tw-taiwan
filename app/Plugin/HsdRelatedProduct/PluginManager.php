<?php

namespace Plugin\HsdRelatedProduct;

use Eccube\Plugin\AbstractPluginManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Eccube\Entity\Master\DeviceType;
use Eccube\Entity\Layout;
use Eccube\Repository\BlockPositionRepository;
use Eccube\Entity\BlockPosition;
use Eccube\Repository\Master\DeviceTypeRepository;
use Eccube\Repository\BlockRepository;
use Eccube\Repository\LayoutRepository;

class PluginManager extends AbstractPluginManager
{

    const BLOCKNAME = "この商品をみた人はこんな商品もみています";
    const BLOCKFILENAME = "hsd_related_product";
    const JSFILENAME = "hsd_swiper.min";
    const CSSFILENAME = "hsd_swiper.min";
    private $block;
    private $js;
    private $css;

    public function __construct()
    {
        $this->block = sprintf("%s/Resource/template/default/Block/%s.twig", __DIR__, self::BLOCKFILENAME);
        $this->js = sprintf("%s/Resource/template/default/js/%s.js", __DIR__, self::JSFILENAME);
        $this->css = sprintf("%s/Resource/template/default/js/%s.css", __DIR__, self::CSSFILENAME);
    }

    public function uninstall(array $meta, ContainerInterface $container)
    {
        $this->removeBlock($container);
    }

    public function enable(array $meta, ContainerInterface $container)
    {
        $this->copyBlock($container);

        // ブロックのデータがすでに作成されている場合は新しいブロックを作らない
        $Block = $container->get(BlockRepository::class)->findOneBy(['file_name' => self::BLOCKFILENAME]);
        if (is_null($Block)) {
            $this->createDataBlock($container);
        }
    }

    public function disable(array $meta, ContainerInterface $container)
    {
        // プラグインの無効時にブロックポジションを削除
        $em = $container->get('doctrine.orm.entity_manager');
        // block_id を取得し、ブロックポジションから削除
        $stmt = $em->getConnection()->prepare('select id from dtb_block where file_name = :file_name');
        $stmt->execute(array('file_name' => self::BLOCKFILENAME));
        $rs_block_id = $stmt->fetchAll();
        if( count($rs_block_id) > 0 && !empty($rs_block_id[0]['id']) ){
            $em->getConnection()->beginTransaction();
            try{
                $stmt_b = $em->getConnection()->prepare('delete from dtb_block_position where block_id = :block_id and section = :section and layout_id = :layout_id');
                $stmt_b->execute(array('block_id' => $rs_block_id[0]['id'], 'section' => Layout::TARGET_ID_MAIN_BOTTOM, 'layout_id' => Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE));
                $em->getConnection()->commit();
            }catch (\Exception $e) {
                $em->getConnection()->rollback();
                throw $e;
            }
        }
    }

    /**
     * ブロックファイルなどをブロックディレクトリにコピーしてDBに登録
     *
     * @param $app
     * @throws \Exception
     */
    private function copyBlock($container)
    {
        $this->container = $container;
        $templateDir = $container->getParameter('eccube_theme_front_dir');
        $htmlDir = $container->getParameter('eccube_html_front_dir') . '/assets';

        $file = new Filesystem();

        if (!$file->exists($templateDir.'/Block/'.self::BLOCKFILENAME.'.twig')) {
            $file->copy($this->block, $templateDir.'/Block/'. self::BLOCKFILENAME . '.twig');
        }

        // js, cssのコピー
        $file->copy($this->css, $htmlDir . '/css/' . self::CSSFILENAME . '.css');
        $file->copy($this->js, $htmlDir . '/js/' . self::JSFILENAME . '.js');

        $em = $container->get('doctrine.orm.entity_manager');
        $em->getConnection()->beginTransaction();
        try {
            $Block = $this->registerBlock();
            $this->registerBlockPosition($Block);
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * ブロックを削除
     *
     * @param $app
     * @throws \Exception
     */
    private function removeBlock($container)
    {
        $templateDir = $container->getParameter('eccube_theme_front_dir');
        $htmlDir = $container->getParameter('eccube_html_front_dir') . 'assets';

        // ブロックファイルを削除
        $file = new Filesystem();
        $file->remove($templateDir.'/Block/'. self::BLOCKFILENAME . '.twig');

        // js, css を削除
        $file->remove($htmlDir . '/css/' . self::CSSFILENAME . '.css');
        $file->remove($htmlDir . '/js/' . self::JSFILENAME . '.js');

        // Blockの取得(file_nameはアプリケーションの仕組み上必ずユニーク)
        /** @var \Eccube\Entity\Block $Block */
        $Block = $container->get(BlockRepository::class)->findOneBy(array('file_name' => self::BLOCKFILENAME));
        if ($Block)
        {
            $em = $container->get('doctrine.orm.entity_manager');
            $em->getConnection()->beginTransaction();
            try {
                // BlockPositionの削除
                $blockPositions = $Block->getBlockPositions();
                /** @var \Eccube\Entity\BlockPosition $BlockPosition */
                foreach ($blockPositions as $BlockPosition)
                {
                    $Block->removeBlockPosition($BlockPosition);
                    $em->remove($BlockPosition);
                }
                // Blockの削除
                $em->remove($Block);
                $em->flush();
                $em->getConnection()->commit();
            } catch (\Exception $e) {
                $em->getConnection()->rollback();
                throw $e;
            }
        }
        //Cache::clear($app, false);
    }

    /**
     * ブロックの登録
     *
     * @return \Eccube\Entity\Block
     */
    private function registerBlock()
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $Block = $this->container->get(BlockRepository::class)->findOneBy(array('file_name' => self::BLOCKFILENAME));
        if( $Block ){
            // ブロックが既に登録されている
            return $Block;
        }else{
            /** @var \Eccube\Repository\Master\DeviceTypeRepository $deviceTypeRepository */
            $DeviceType = $this->container->get(DeviceTypeRepository::class)->find(DeviceType::DEVICE_TYPE_PC);
            /** @var \Eccube\Entity\Block $Block */
            $Block = $this->container->get(BlockRepository::class)->newBlock($DeviceType);

            $Block->setName(self::BLOCKNAME);
            $Block->setFileName(self::BLOCKFILENAME);
            $Block->setUseController(true);
            $Block->setDeletable(false);
            $em->persist($Block);
            $em->flush($Block);

            return $Block;
        }
    }

    /**
     * BlockPositionの登録
     *
     * @param $Block
     */
    private function registerBlockPosition($Block)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $blockPos = $this->container->get(BlockPositionRepository::class)->findOneBy(
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

        $PageLayout = $this->container->get(LayoutRepository::class)->find(Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);
        $BlockPosition->setLayout($PageLayout);
        $BlockPosition->setLayoutId($PageLayout->getId());
        $BlockPosition->setSection(Layout::TARGET_ID_MAIN_BOTTOM);
        $BlockPosition->setBlock($Block);
        $BlockPosition->setBlockId($Block->getId());
        $em->persist($BlockPosition);
        $em->flush($BlockPosition);
    }

}

?>
