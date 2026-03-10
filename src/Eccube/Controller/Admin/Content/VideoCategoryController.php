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
use Eccube\Entity\News;
use Eccube\Entity\VideoCategory;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Admin\NewsType;
use Eccube\Form\Type\Admin\VideoCategoryType;
use Eccube\Form\Type\Admin\VideoType;
use Eccube\Repository\NewsRepository;
use Eccube\Repository\VideoCategoryRepository;
use Eccube\Repository\VideoRepository;
use Eccube\Util\CacheUtil;
use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class VideoCategoryController extends AbstractController
{
    /**
     * @var VideoCategoryRepository
     */
    protected $videoCategoryRepository;

    /**
     * VideoCategoryRepository constructor.
     *
     * @param VideoCategoryRepository $videoCategoryRepository
     */
    public function __construct(VideoCategoryRepository $videoCategoryRepository)
    {
        $this->videoCategoryRepository = $videoCategoryRepository;
    }

    /**
     * 新着情報一覧を表示する。
     *
     * @Route("/%eccube_admin_route%/content/video/category", name="admin_content_video_category")
     * @Route("/%eccube_admin_route%/content/video/category/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_content_video_category_page")
     * @Template("@admin/Content/video_category.twig")
     *
     * @param Request $request
     * @param int $page_no
     * @param Paginator $paginator
     *
     * @return array
     */
    public function index(Request $request, $page_no = 1, Paginator $paginator)
    {
        $qb = $this->videoCategoryRepository->getAll();

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
     * @Route("/%eccube_admin_route%/content/video/category/new", name="admin_content_video_category_new")
     * @Route("/%eccube_admin_route%/content/video/category/{id}/edit", requirements={"id" = "\d+"}, name="admin_content_video_category_edit")
     * @Template("@admin/Content/video_category_edit.twig")
     *
     * @param Request $request
     * @param null $id
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function edit(Request $request, $id = null, CacheUtil $cacheUtil)
    {
        if ($id) {
            $videoCategory = $this->videoCategoryRepository->find($id);

            if (!$videoCategory) {
                throw new NotFoundHttpException();
            }

        } else {
            $videoCategory = new \Eccube\Entity\VideoCategory();
            $videoCategory->setCreatedAt(new \DateTime());
        }

        $builder = $this->formFactory->createBuilder(VideoCategoryType::class, $videoCategory);
        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $videoCategory->setUpdatedAt(new \DateTime());
            $this->videoCategoryRepository->save($videoCategory);
            $this->addSuccess('admin.common.save_complete', 'admin');
            $cacheUtil->clearDoctrineCache();
            return $this->redirectToRoute('admin_content_video_category');
        }

        return [
            'form' => $form->createView(),
            'video_category' => $videoCategory,
        ];
    }

    /**
     * 指定した新着情報を削除する。
     *
     * @Route("/%eccube_admin_route%/content/video/category/{id}/delete", requirements={"id" = "\d+"}, name="admin_content_video_category_delete", methods={"DELETE"})
     * @param Request $request
     * @param VideoCategory $videoCategory
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete(Request $request, VideoCategory $videoCategory, CacheUtil $cacheUtil)
    {
        $this->isTokenValid();

        try {
            $result = $this->videoCategoryRepository->delete($videoCategory);

            if ($result){
                $this->addSuccess('admin.common.delete_complete', 'admin');
                // キャッシュの削除
                $cacheUtil->clearDoctrineCache();
            } else {
                $this->addError('admin.common.delete_error_relationship', 'admin');
            }

        } catch (\Exception $e) {
            $message = trans('admin.common.delete_error_foreign_key', ['%name%' => $videoCategory->getName()]);
            $this->addError($message, 'admin');
        }

        return $this->redirectToRoute('admin_content_video_category');
    }
}
