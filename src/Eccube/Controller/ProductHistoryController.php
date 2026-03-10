<?php

namespace Eccube\Controller;

use Eccube\Entity\Product;
use Eccube\Repository\ProductRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
class ProductHistoryController extends AbstractController
{

    /**
     * @Route("/products/history", name="product_history")
     * @Template("product_history.twig")
     */
    public function index(Request $request)
    {
        $idsParam = $request->query->get('ids', '');
        if (!$idsParam) {
            return [
                'Products' => [],
            ];
        }
        $ids = $idsParam ? explode(',', $idsParam) : [];

        $qb = $this->getDoctrine()->getRepository(Product::class)->createQueryBuilder('p');
        $qb->where('p.Status = 1')
           ->andWhere('p.id IN (:ids)')
           ->setParameter('ids', $ids)
           ->leftJoin('Plugin\\ProductReview4\\Entity\\ProductReview', 'pr', \Doctrine\ORM\Query\Expr\Join::WITH, 'p.id = pr.Product')
           ->innerJoin('p.ProductCategories', 'pc')
           ->groupBy('p.id')
           ->orderBy('p.id', 'DESC');

        $Products = $qb->getQuery()->getResult();

        $ReviewAveList = [];
        $ReviewCntList = [];

        foreach ($Products as $product) {
            $rate = $this->getDoctrine()
                ->getRepository('Plugin\ProductReview4\Entity\ProductReview')
                ->getAvgAll($product);

            $ReviewAveList[$product->getId()] = round($rate['recommend_avg']);
            $ReviewCntList[$product->getId()] = intval($rate['review_count']);
        }

        
        return [
            'Products' => $Products,
            'ReviewAveList' => $ReviewAveList,
            'ReviewCntList' => $ReviewCntList
        ];
    }
}
