<?php

namespace Customize\Controller;


use Customize\Repository\RankingRepository;
use Doctrine\ORM\EntityManager;
use Eccube\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Knp\Component\Pager\Paginator;
use Eccube\Repository\ProductRepository;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;

class RankingController extends AbstractController
{
    /**
     * @var RankingRepository
     */
    protected $rankingRepository;

    protected $productRepository;

    protected $entityManager;

    protected $packages;

    public function __construct(
        RankingRepository $rankingRepository,
        ProductRepository $productRepository,
        EntityManager $entityManager,
        Packages $packages
    ) {
        $this->rankingRepository = $rankingRepository;
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
        $this->packages = $packages;
    }

    /**
     * 
     * @Route("/ranking", name="ranking", methods={"GET"})
     * 
     * @Template("Ranking/detail.twig")
     */
    public function ranking(Request $request)
    {
        $categories = $this->rankingRepository->getRankingProductByCategories();
        $ReviewAveList = [];
        $ReviewCntList = [];
        foreach ($categories as &$categoryData) {
            if (!empty($categoryData['products'])) {
                foreach ($categoryData['products'] as &$productData) {

                    if (is_array($productData) && isset($productData['product_id'])) {
                        $product = $this->entityManager
                            ->getRepository(\Eccube\Entity\Product::class)
                            ->find($productData['product_id']);
                    } else {
                        $product = $productData;
                    }

                    if ($product) {
                        $rate = $this->entityManager
                            ->getRepository('Plugin\ProductReview4\Entity\ProductReview')
                            ->getAvgAll($product);

                        $ReviewAveList[$product->getId()] = round($rate['recommend_avg']);
                        $ReviewCntList[$product->getId()] = intval($rate['review_count']);
                    }
                }
            }
        }
        return [
            'categories' => $categories,
            'ReviewAveList' => $ReviewAveList,
            'ReviewCntList' => $ReviewCntList,
        ]; 
    }

}
