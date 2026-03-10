<?php

namespace Plugin\Collection\Service\Twig;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Repository\ProductRepository;
use Plugin\Collection\Entity\Collection;

class GetProductsWithCollection extends \Twig_Extension
{
    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var Constructor
     */
    public function __construct(ProductRepository $productRepository,
                                EccubeConfig $eccubeConfig)
    {
        $this->productRepository = $productRepository;
        $this->eccubeConfig = $eccubeConfig;
    }

    // --------------------------------------------------------------------------------

    /**
     * @param Collection $collection
     * @return array
     */
    public function getFunction(Collection $Collection)
    {
        return $this->productRepository->createQueryBuilder('p')
            ->leftJoin('p.CollectionProducts', 'cp')
            ->leftJoin('p.ProductClasses', 'pc')
            ->where('p.Status = :Disp')
            ->andWhere('pc.stock_unlimited = :StockUnlimited OR pc.stock > 0')
            ->andWhere('cp.Collection = :Collection')
            ->setParameter('Disp', ProductStatus::DISPLAY_SHOW)
            ->setParameter('StockUnlimited', true)
            ->setParameter('Collection', $Collection)
            ->orderBy('cp.sort_no', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // --------------------------------------------------------------------------------

    /**
     * Twigでの呼び出し名の登録
     *
     * @return array|\Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('getProductsWithCollection', [$this, 'getFunction']),
        ];
    }

    /**
     * Twig拡張ファイルに必須
     *
     * @return string
     */
    public function getName()
    {
        return get_class($this);
    }
}
