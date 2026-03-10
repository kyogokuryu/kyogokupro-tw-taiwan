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

namespace Eccube\Controller\Admin\Content;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Category;
use Eccube\Entity\News;
use Eccube\Entity\Product;
use Eccube\Entity\Video;
use Eccube\Entity\VideoCategory;
use Eccube\Entity\VideoRelativeProduct;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Admin\NewsType;
use Eccube\Form\Type\Admin\VideoType;
use Eccube\Repository\NewsRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Repository\VideoPointSettingRepository;
use Eccube\Repository\VideoRelativeProductRepository;
use Eccube\Repository\VideoRepository;
use Eccube\Repository\VideoWatchPointRepository;
use Eccube\Util\CacheUtil;
use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\DateTime;
use function Couchbase\defaultDecoder;

class VideoController extends AbstractController
{
    /**
     * @var VideoRepository
     */
    protected $videoRepository;

    protected $videoRelativeProductRepository;

    protected $productRepository;

    protected $videoPointSettingRepository;

    protected $videoWatchPointRepository;
    /**
     * VideoController constructor.
     *
     * @param VideoRepository $videoRepository
     * @param VideoRelativeProductRepository $videoRelativeProductRepository
     * @param ProductRepository $productRepository
     * @param VideoPointSettingRepository $videoPointSettingRepository
     * @param VideoWatchPointRepository $videoWatchPointRepository
     */
    public function __construct
    (
        VideoRepository $videoRepository,
        VideoRelativeProductRepository $videoRelativeProductRepository,
        ProductRepository $productRepository,
        VideoPointSettingRepository $videoPointSettingRepository,
        VideoWatchPointRepository $videoWatchPointRepository
    )
    {
        $this->videoRepository = $videoRepository;
        $this->videoRelativeProductRepository = $videoRelativeProductRepository;
        $this->productRepository = $productRepository;
        $this->videoPointSettingRepository = $videoPointSettingRepository;
        $this->videoWatchPointRepository = $videoWatchPointRepository;
    }

    /**
     * 新着情報一覧を表示する。
     *
     * @Route("/%eccube_admin_route%/content/video", name="admin_content_video")
     * @Route("/%eccube_admin_route%/content/video/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_content_video_page")
     * @Template("@admin/Content/video.twig")
     *
     * @param Request $request
     * @param int $page_no
     * @param Paginator $paginator
     *
     * @return array
     */
    public function index(Request $request, $page_no = 1, Paginator $paginator)
    {
        $qb = $this->videoRepository->createQueryBuilder('v');

        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $this->eccubeConfig->get('eccube_default_page_count')
        );

        return [
            'pagination' => $pagination,
        ];
    }

    /**
     * 新着情報を登録・編集する。
     *
     * @Route("/%eccube_admin_route%/content/video/new", name="admin_content_video_new")
     * @Route("/%eccube_admin_route%/content/video/{id}/edit", requirements={"id" = "\d+"}, name="admin_content_video_edit")
     * @Template("@admin/Content/video_edit.twig")
     *
     * @param Request $request
     * @param null $id
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function edit(Request $request, $id = null, CacheUtil $cacheUtil, ManagerRegistry $managerRegistry)
    {
        if ($id) {
            $video = $this->videoRepository->find($id);

            if (!$video) {
                throw new NotFoundHttpException();
            }

        } else {
            $video = new \Eccube\Entity\Video();
            $video->setCreatedAt(new \DateTime());
        }

        $builder = $this->formFactory
            ->createBuilder(VideoType::class, $video);

        $form = $builder->getForm();
        $videoRelativeProducts = $video->getVideoRelativeProducts();
        $products = [];
        foreach ($videoRelativeProducts as $videoRelativeProduct) {
            $products[] = $videoRelativeProduct->getProduct();
        }
        $videoPS = $video->getVideoPointSetting();
        $form->get('product')->setData($products);
        $form->get('second')->setData($videoPS ? $videoPS->getSecond() : 1);
        $form->get('point')->setData($videoPS ? $videoPS->getPoint() : 0);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $request->request->all();
            $second = $form->get('second')->getData();
            $point = $form->get('point')->getData();
            $videoProducts = $form->get('product')->getData();
            try {
                $this->entityManager->beginTransaction();
                $video->setUpdatedAt(new \DateTime());

                foreach ($videoRelativeProducts as $videoRelativeProduct) {
                    $this->videoRelativeProductRepository->removeVideoRelativeProduct($videoRelativeProduct, $video);
                }

                $this->entityManager->persist($video);
                $category = $managerRegistry->getRepository(VideoCategory::class)->find($data['video_category_id']);
                $video->setVideoCategory($category);
                $videoPoint = $this->videoPointSettingRepository->isExistVideoPointSetting($video, $second, $point);

                if (!$videoPoint) {
                    $videoPointSetting = $this->videoPointSettingRepository->createVideoPointSetting($video, $second, $point);
                } else {
                    $videoPointSetting = $videoPoint;
                }

                $video->addVideoPointSettings($videoPointSetting);
                $video->setVideoPointSetting($videoPointSetting);

                foreach ($videoProducts as $videoProduct) {
                    $videoRelativeProduct = $this->videoRelativeProductRepository->createVideoRelativeProduct($video, $videoProduct);
                    $this->entityManager->persist($videoRelativeProduct);
                    $video->addVideoRelativeProducts($videoRelativeProduct);
                }

                $this->entityManager->persist($video);
                $this->entityManager->flush();
                $this->entityManager->commit();
            } catch (\Exception $e) {
                $this->entityManager->rollBack();
                throw $e;
            }

            $this->addSuccess('admin.common.save_complete', 'admin');
            $cacheUtil->clearDoctrineCache();
            return $this->redirectToRoute('admin_content_video');
        }

        return [
            'form' => $form->createView(),
            'video' => $video,
        ];
    }

    /**
     * 指定した新着情報を削除する。
     *
     * @Route("/%eccube_admin_route%/content/video/{id}/delete", requirements={"id" = "\d+"}, name="admin_content_video_delete", methods={"DELETE"})
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete(Request $request, Video $video, CacheUtil $cacheUtil)
    {
        $this->isTokenValid();
        try {
            $this->videoWatchPointRepository->removeVideo($video);
            $this->entityManager->remove($video);
            $this->entityManager->flush();
            $this->addSuccess('admin.common.delete_complete', 'admin');
            // キャッシュの削除
            $cacheUtil->clearDoctrineCache();
        } catch (\Exception $e) {
            $message = trans('admin.common.delete_error_foreign_key', ['%name%' => $video->getName()]);
            $this->addError($message, 'admin');
        }

        return $this->redirectToRoute('admin_content_video');
    }
}
