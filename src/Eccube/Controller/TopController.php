<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Controller;

use Eccube\Entity\Product;
use Eccube\Repository\ProductRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class TopController extends AbstractController
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @Route("/", name="homepage")
     * @Template("index.twig")
     */
    public function index(Request $request)
    {
        $categoryId = $request->get('category_id');
        $qb = $this->entityManager->getRepository('Eccube\Entity\Product')->createQueryBuilder('p')
            ->Where('p.Status = 1');
        $qb
            ->leftJoin('Plugin\\ProductReview4\\Entity\\ProductReview', 'pr', \Doctrine\ORM\Query\Expr\Join::WITH, 'p.id = pr.Product')
            ->innerJoin('p.ProductCategories', 'pc')
            ->groupBy('p.id')
            ->orderBy('p.id', 'DESC');

        if (!empty($categoryId)) {
            $qb->andWhere('pc.Category = :category_id')
                ->setParameter('category_id', $categoryId);
        }
        
        $name = $request->get('name');
        if (!empty($name)) {
            $qb->andWhere('p.name LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }

        $ALLProducts = $qb->getQuery()->getResult();
        $Products = $qb->getQuery()->getResult();
        $ProductReviews = $this->entityManager->getRepository('Plugin\ProductReview4\Entity\ProductReview')->findBy(['Status' => 1, 'Product' => $ALLProducts], ['id' => 'DESC'], 3);

        $ReviewAveList = array();
        $ReviewCntList = array();
        foreach ($Products as $product) {
            $rate = $this->entityManager->getRepository('Plugin\ProductReview4\Entity\ProductReview')->getAvgAll($product);
            $ReviewAveList[$product->getId()] = round($rate['recommend_avg']);
            $ReviewCntList[$product->getId()] = intval($rate['review_count']);
        }

        usort($Products, function ($a, $b) use ($ReviewCntList) {
            $aCount = $ReviewCntList[$a->getId()] ?? 0;
            $bCount = $ReviewCntList[$b->getId()] ?? 0;
            return $bCount <=> $aCount;
        });

        $Categories = $this->entityManager->getRepository('Eccube\Entity\Category')->findBy([], ['sort_no' => 'ASC']);

        return [
            'Products' => $Products,
            'ProductReviews' => $ProductReviews,
            'ReviewAveList' => $ReviewAveList,
            'ReviewCntList' => $ReviewCntList,
            'Categories' => $Categories,
            'CurrentCategory' => $categoryId
        ];
    }
}
