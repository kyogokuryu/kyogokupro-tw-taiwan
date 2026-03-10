<?php
/*
 * Copyright(c) 2020 YAMATO.CO.LTD
 */
namespace Plugin\SameCategoryProduct\Repository;

use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Eccube\Common\EccubeConfig;
use Eccube\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NativeQuery;
use Eccube\Entity\Product;
use Plugin\SameCategoryProduct\Constant\SCPConstants;

/**
 * 同カテゴリ商品リポジトリ
 *
 * @author Masaki Okada
 */
class SameCategoryProductRepository extends CategoryRepository
{

    /**
     * CustomCategoryRepository constructor.
     *
     * @param RegistryInterface $registry
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(RegistryInterface $registry, EccubeConfig $eccubeConfig)
    {
        parent::__construct($registry, $eccubeConfig);
    }

    /**
     * 同じカテゴリの商品を返却する。<br/>
     * 複数のカテゴリに所属する場合、一番若いIDのカテゴリの商品を順に表示する
     *
     * @param int $product_id
     *            商品ID
     * @return Product 商品リスト
     */
    public function getSameCategoryProducts($product_id)
    {
        // SQLファイル取得
        $sql = @file_get_contents(SCPConstants::GET_SAME_CATEGORY_PRODUCTS);
        if ($sql == false) {
            return null;
        }
        
        // エンティティマネージャー取得
        /* @var EntityManagerager */
        $em = $this->getEntityManager();
        $rsm = new ResultSetMappingBuilder($em);
        $rsm->addRootEntityFromClassMetadata('\\Eccube\\Entity\\Product', 'c4');
        $query = $em->createNativeQuery($sql, $rsm)->setParameter('product_id', $product_id);
        $products = $query->getResult();

        return $products;
    }
}
