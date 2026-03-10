<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ProductReview4;

use Eccube\Entity\Product;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\Master\ProductStatusRepository;
use Plugin\ProductReview4\Entity\ProductReviewStatus;
use Plugin\ProductReview4\Repository\ProductReviewConfigRepository;
use Plugin\ProductReview4\Repository\ProductReviewRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductReviewEvent implements EventSubscriberInterface
{
    /**
     * @var ProductReviewConfigRepository
     */
    protected $productReviewConfigRepository;

    /**
     * @var ProductReviewRepository
     */
    protected $productReviewRepository;

    /**
     * ProductReview constructor.
     *
     * @param ProductReviewConfigRepository $productReviewConfigRepository
     * @param ProductStatusRepository $productStatusRepository
     * @param ProductReviewRepository $productReviewRepository
     */
    public function __construct(
        ProductReviewConfigRepository $productReviewConfigRepository,
        ProductStatusRepository $productStatusRepository,
        ProductReviewRepository $productReviewRepository
    ) {
        $this->productReviewConfigRepository = $productReviewConfigRepository;
        $this->productStatusRepository = $productStatusRepository;
        $this->productReviewRepository = $productReviewRepository;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'KokokaraSelect/Resource/template/default/Product/list.twig' => 'detail',
            'Product/detail.twig' => 'detail',
            '@KokokaraSelect/default/Product/list.twig' => 'detail',
        ];
    }

    /**
     * @param TemplateEvent $event
     */
    public function detail(TemplateEvent $event)
    {
        $event->addSnippet('@ProductReview4/default/review.twig');

        $Config = $this->productReviewConfigRepository->get();

        /** @var Product $Product */
        $Product = $event->getParameter('Product');

        // Page指定
        if (!isset($_GET['page'])) {
            $_GET['page'] = 1;
        }

        $ProductReviews = $this->productReviewRepository->findBy(['Status' => ProductReviewStatus::SHOW, 'Product' => $Product], ['id' => 'DESC'], $Config->getReviewMax(), (($_GET['page']-1)*$Config->getReviewMax()));

        $qb = $this->productReviewRepository->createQueryBuilder('r');
        //$qb->select('a')->from('Plugin\ProductReview4\Entity\ProductReview','a');
        $qb->where('r.Product = :product_id')->setParameter('product_id', $Product->getId());
        $qb->andWhere('r.Status = 1');
        $qb->andwhere('r.pic1 is not null');
        $qb->orderBy('r.id','desc');
        $qb->setMaxResults(4);
        $PicProductReviews = $qb->getQuery()->getResult();
        //var_dump($PicProductReviews);

        $rate = $this->productReviewRepository->getAvgAll($Product);
        $avg = round($rate['recommend_avg']);
        $count = intval($rate['review_count']);

        $parameters = $event->getParameters();
        $parameters['ProductReviews'] = $ProductReviews;
        $parameters['ProductReviewAvg'] = $avg;
        $parameters['ProductReviewCount'] = $count;
        $parameters['ProductReviewPage'] = $_GET['page'];
        $parameters['ProductReviewPageCount'] = ceil($count / $Config->getReviewMax());
        $parameters["PicProductReviews"] = $PicProductReviews;
        $event->setParameters($parameters);
    }
}
