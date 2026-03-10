<?php

namespace Plugin\Ranking;

use Eccube\Entity\Block;
use Eccube\Entity\BlockPosition;
use Eccube\Entity\Master\DeviceType;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\BlockRepository;
use Eccube\Repository\Master\DeviceTypeRepository;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Plugin\Ranking\Entity\Config;

class PluginManager extends AbstractPluginManager
{

	/**
	 * @var string コピー元ブロックファイル
	 */
	private $originBlock;

	/**
	 * @var string ランキング
	 */
	private $blockName = 'ランキング';

	/**ranking
	 * @var string ブロックファイル名
	 */
	private $blockFileName = 'ranking';

	/**
	 * PluginManager constructor.
	 */
	public function __construct()
	{
		// コピー元ブロックファイル
		$this->originBlock = __DIR__.'/Resource/template/Block/'.$this->blockFileName.'.twig';
	}

	/**
	 * @param null|array $meta
	 * @param ContainerInterface $container
	 *
	 * @throws Exception
	 */
	public function enable(array $meta, ContainerInterface $container)
	{
		$this->copyBlock($container);
		$Block = $container->get(BlockRepository::class)->findOneBy(['file_name' => $this->blockFileName]);
		if (is_null($Block)) {
			// pagelayoutの作成
			$this->createDataBlock($container);
		}

		// 設定情報を挿入
		$this->createConfig($container);
	}

    /**
     * 設定情報挿入
     * @param ContainerInterface $container
     */
    private function createConfig(ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $Config = $em->find(Config::class, 1);
        if($Config) return; // すでにINSERT済みの場合以降の処理をスキップして終了

        $Config = new Config();
		$Config
			->setTargetPeriod(1)
			->setSliderAutoPlay(1)
			->setSliderDesign(1)
			->setframe1Type(0)
			->setframe1Value(1)
			->setframe2Type(0)
			->setframe2Value(2)
			->setframe3Type(0)
			->setframe3Value(3)
			->setframe4Type(0)
			->setframe4Value(4)
			->setframe5Type(0)
			->setframe5Value(5)
			->setframe6Type(0)
			->setframe6Value(6)
			->setframe7Type(0)
			->setframe7Value(7)
			->setframe8Type(0)
			->setframe8Value(8)
			->setframe9Type(0)
			->setframe9Value(9)
			->setframe10Type(0)
			->setframe10Value(10)
		;

        $em->persist($Config);
        $em->flush($Config);
    }

	/**
	 * @param array|null $meta
	 * @param ContainerInterface $container
	 */
	public function update(array $meta, ContainerInterface $container)
	{
		$this->copyBlock($container);
	}

	/**
	 * @param array|null $meta
	 * @param ContainerInterface $container
	 * @throws Exception
	 */
	public function disable(array $meta, ContainerInterface $container)
	{
		$this->removeDataBlock($container);
	}

	/**
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
	 * ブロックを削除.
	 *
	 * @param ContainerInterface $container
	 *
	 * @throws Exception
	 */
	private function removeDataBlock(ContainerInterface $container)
	{
		// Blockの取得(file_nameはアプリケーションの仕組み上必ずユニーク)
		/** @var Block $Block */
		$Block = $container->get(BlockRepository::class)->findOneBy(['file_name' => $this->blockFileName]);

		if (!$Block) {
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
	 * Copy block template.
	 *
	 * @param ContainerInterface $container
	 */
	private function copyBlock(ContainerInterface $container)
	{
		$templateDir = $container->getParameter('eccube_theme_front_dir');
		// ファイルコピー
		$file = new Filesystem();

		// 既に存在する場合バックアップを保持
		if ($file->exists($templateDir.'/Block/'.$this->blockFileName.'.twig')) {
			$file->copy($templateDir.'/Block/'.$this->blockFileName.'.twig', $templateDir.'/Block/'.$this->blockFileName.'.twig.bak');
		}
		// if (!$file->exists($templateDir.'/Block/'.$this->blockFileName.'.twig')) {
			// ブロックファイルをコピー
			$file->copy($this->originBlock, $templateDir.'/Block/'.$this->blockFileName.'.twig');
		// }
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
