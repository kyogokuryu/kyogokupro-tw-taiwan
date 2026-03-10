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

use Eccube\Entity\VideoPointSetting;
use Eccube\Entity\VideoWatchPoint;
use Eccube\Repository\VideoCategoryRepository;
use Eccube\Repository\VideoPointSettingRepository;
use Eccube\Repository\VideoRepository;
use Eccube\Repository\VideoWatchPointRepository;
use http\Env\Response;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Knp\Component\Pager\Paginator;
use simpleDI\LoadedTestWithDependencyInjectionCest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;


class VideoWatchPointController extends AbstractController
{
    protected $videoRepository;

    protected $videoWatchPointRepository;

    protected $videoPointSettingRepository;

    protected $videoCategoryRepository;

    public function __construct
    (
        VideoRepository $videoRepository,
        VideoWatchPointRepository $videoWatchPointRepository,
        VideoPointSettingRepository $videoPointSettingRepository,
        VideoCategoryRepository $videoCategoryRepository
    )
    {
        $this->videoRepository = $videoRepository;
        $this->videoWatchPointRepository = $videoWatchPointRepository;
        $this->videoPointSettingRepository = $videoPointSettingRepository;
        $this->videoCategoryRepository = $videoCategoryRepository;
    }

    /**
     * .
     *
     * @Route("/mypage/point/history", name="mypage_point_history", methods={"GET"})
     * @Template("Mypage/point_history.twig")
     */
    public function getPointHistory(Paginator $paginator, $page_no = 1, $limit = 20, Request $request)
    {
        $filter = $_GET;
        $customer = $this->getUser();
        $pointHistory = $this->videoWatchPointRepository->getVideoHistoryByFilter($customer, $filter);
        $videoCategories = $this->videoCategoryRepository->getAll();

        $pagination = $paginator->paginate(
            $pointHistory,
            $filter['pageno'] ?? $page_no,
            $limit
        );

        return [
            'pagination' => $pagination,
            'videoCategories' => $videoCategories,
        ];
    }

}