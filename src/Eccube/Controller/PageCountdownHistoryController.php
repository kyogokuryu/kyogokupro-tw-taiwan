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

use Eccube\Repository\PageCountdownHistoryRepository;
use Eccube\Repository\PageCountdownRepository;
use Eccube\Service\PageCountdownService;
use Knp\Component\Pager\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use http\Env\Response;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use simpleDI\LoadedTestWithDependencyInjectionCest;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class PageCountdownHistoryController extends AbstractController
{
    protected $pageCountdownRepository;
    protected $pageCountdownHistoryRepository;
    public function __construct(
        PageCountdownRepository $pageCountdownRepository,
        PageCountdownHistoryRepository $pageCountdownHistoryRepository
    ) {
        $this->pageCountdownRepository = $pageCountdownRepository;
        $this->pageCountdownHistoryRepository = $pageCountdownHistoryRepository;
    }

    /**
     * @Route("/mypage/countdown/history", name="mypage_countdown_history", methods={"GET"})
     * @Template("Mypage/countdown_history.twig")
     */
    public function getHistoryReward(Paginator $paginator, Request $request, $page_no = 1, $limit = 20)
    {
        $data = $request->query->all();
        $lastDategroup = $data['last-date'] ?? 1;
        $customer = $this->getUser();
        $history = $this->pageCountdownHistoryRepository->getHistoryReward($customer, $lastDategroup);

        $pagination = $paginator->paginate(
            $history,
            $data['pageno'] ?? $page_no,
            $data['limit'] ?? $limit
        );

        return [
            'pagination' => $pagination,
            'lastDateGroup' => (int)$lastDategroup,
        ];
    }
}
