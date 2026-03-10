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
use mysql_xdevapi\Exception;
use simpleDI\LoadedTestWithDependencyInjectionCest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;


class VideoController extends AbstractController
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
     * @Route("/videos", name="video_list")
     * @Template("Video/list.twig")
     */
    public function index(Paginator $paginator, $page_no = 1, $limit = 20)
    {
        $filter = $_GET;
        $customer = $this->getUser();
        $videoCategoryList = $this->videoCategoryRepository->getAll();

        $videos = $this->videoRepository->getVideoByFilter($customer, $filter);

        $watchedVideos = $this->videoWatchPointRepository->getVideoIdWatchedByUser($customer);

        $videoIds = array_map(function ($item) {
            return $item['id'];
        }, $watchedVideos);

        /** @var SlidingPagination $pagination */
        $pagination = $paginator->paginate(
            $videos,
            $filter['pageno'] ?? $page_no,
            $limit
        );

        return [
            'pagination' => $pagination,
            'videoCategories' => $videoCategoryList,
            'watchedVideoIds' => $videoIds
        ];
    }

    /**
     * @Route("/videos/{id}", name="video_detail", methods={"GET"}, requirements={"id" = "\d+"})
     * @Template("Video/detail.twig")
     */
    public function detail($id)
    {
        if ($this->isGranted('ROLE_USER')) {
            $video = $this->videoRepository->detail($id);
            $customer = $this->getUser();
            $permission = true;
            $countUserWatchVideo = $this->videoWatchPointRepository->countUserWatchVideo($customer, $video);

            if ($countUserWatchVideo > 0) {
                $permission = false;
            }

            return [
                'video' => $video,
                'customer' => $customer,
                'permission' => $permission,
            ];

        } else {
            $this->setLoginTargetPath($this->generateUrl('video_detail', ['id' => $id]));

            return $this->redirectToRoute('mypage_login');
        }
    }

    /**
     * @Route("/video/point/new", name="video_new_point", methods={"POST"})
     *
     */
    public function createPointToUser(Request $request, SerializerInterface $serializer)
    {
        try {
            $customer = $this->getUser();
            $data = $request->request->all();

            if (!$data['video_id']) {
                throw new Exception('none of video_id');
            }

            $video = $this->videoRepository->findOneBy([
                'id' => $data['video_id']
            ]);
            $countUserWatchVideo = $this->videoWatchPointRepository->countUserWatchVideo($customer, $video);

            if ($countUserWatchVideo < 1) {
                $this->entityManager->beginTransaction();
                $videoWatchPoint = $this->videoWatchPointRepository->savePoint($video, $customer);
                $json = $serializer->serialize($videoWatchPoint->getVideoPointSetting()->getPoint(), 'json', ['groups' => ['normal']]);
                $this->entityManager->commit();
                return $this->json($json);
            } else {
                return $this->json(['permission' => false]);
            }

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

    }
}