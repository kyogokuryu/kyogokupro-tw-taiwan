<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) Takashi Otaki All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\EtunaNewItem\Controller;

use Plugin\EtunaNewItem\Repository\EtunaNewItemConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Eccube\Repository\ProductRepository;

/**
 * Class EtunaNewItemController front.
 */
class EtunaNewItemController extends \Eccube\Controller\AbstractController
{

    /**
     * @Route("/block/etuna_new_item", name="block_etuna_new_item")
     * @Template("Block/etuna_new_item.twig")
     *
     * @param Request $request
     * @param EtunaNewItemConfigRepository $configRepository
     * @param ProductRepository $productRepository
     */
    public function index(Request $request, EtunaNewItemConfigRepository $configRepository, ProductRepository $productRepository)
    {
        $Config = $configRepository->get();

        $qb = $productRepository->createQueryBuilder('p')
            ->where('p.Status = 1')
            ->setMaxResults($Config->getNewitemCount());

        if ($Config->getNewitemSort() == 0) {
            $qb = $qb
                ->addOrderBy('p.create_date', 'DESC')
                ->addOrderBy('p.id', 'DESC');
        }  else {
            $qb = $qb
                ->addOrderBy('p.update_date', 'DESC');
        }

        $NewItem = $qb->getQuery()->getResult();

        return [
            'Config' => $Config,
            'NewItem' => $NewItem,
        ];
    }
}
