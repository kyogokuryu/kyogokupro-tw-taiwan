<?php

namespace Customize\Controller\Admin\Faq;

use Customize\Entity\FaqCategory;
use Customize\Form\Admin\FaqCategoryType;
use Customize\Repository\FaqCategoryRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Util\CacheUtil;
use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Q&Aカテゴリページ用コントローラ
 */
class FaqCategoryController extends AbstractController
{
    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var FaqCategoryRepository
     */
    protected $faqCategoryRepository;

    const FAQ_CATEGORY_PAGE_COUNT_SESSION_KEY = 'eccube.admin.faq.category.search.page_count';
    const FAQ_CATEGORY_PAGE_NO_SESSION_KEY = 'eccube.admin.faq.category.search.page_no';

    public function __construct(
        PageMaxRepository $pageMaxRepository,
        FaqCategoryRepository $faqCategoryRepository
    )
    {
        $this->pageMaxRepository = $pageMaxRepository;
        $this->faqCategoryRepository = $faqCategoryRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/faq/category", name="admin_faq_category")
     * @Route("/%eccube_admin_route%/faq/category/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_faq_category_page")
     * @Template("@admin/Faq/category_index.twig")
     */
    public function index(Request $request, $page_no = null, Paginator $paginator)
    {
        $session = $this->session;

        $pageMaxis = $this->pageMaxRepository->findAll();
        $pageCount = $session->get(self::FAQ_CATEGORY_PAGE_COUNT_SESSION_KEY, $this->eccubeConfig['eccube_default_page_count']);
        $pageCountParam = $request->get('page_count');
        if ($pageCountParam && is_numeric($pageCountParam)) {
            foreach ($pageMaxis as $pageMax) {
                if ($pageCountParam == $pageMax->getName()) {
                    $pageCount = $pageMax->getName();
                    $session->set(self::FAQ_CATEGORY_PAGE_COUNT_SESSION_KEY, $pageCount);
                    break;
                }
            }
        }

        if (null !== $page_no || $request->get('resume')) {
            if ($page_no) {
                $session->set(self::FAQ_CATEGORY_PAGE_NO_SESSION_KEY, (int) $page_no);
            } else {
                $page_no = $session->get(self::FAQ_CATEGORY_PAGE_NO_SESSION_KEY, 1);
            }
        } else {
            $page_no = 1;
            $session->set(self::FAQ_CATEGORY_PAGE_NO_SESSION_KEY, $page_no);
        }

        $qb = $this->faqCategoryRepository->getQueryBuilder();
        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $pageCount
        );

        return [
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $pageCount,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/faq/category/new", name="admin_faq_category_new")
     * @Route("/%eccube_admin_route%/faq/category/{id}/edit", requirements={"id" = "\d+"}, name="admin_faq_category_edit")
     * @Template("@admin/Faq/category_edit.twig")
     */
    public function edit(Request $request, $id = null)
    {
        $FaqCategory = new FaqCategory();
        if ($id) {
            $FaqCategory = $this->faqCategoryRepository->find($id);
            if (is_null($FaqCategory) || !$FaqCategory instanceof FaqCategory) {
                throw new NotFoundHttpException();
            }
        }

        $builder = $this->formFactory->createBuilder(FaqCategoryType::class, $FaqCategory);

        $form = $builder->getForm();

        // ファイルの登録
        $images = [];
        if ($FaqCategory->getIconName()) {
            $images[] = $FaqCategory->getIconName();
        }
        $form['images']->setData($images);

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                log_info('Q&Aカテゴリ登録開始', [$id]);

                // 画像の登録
                $add_images = $form->get('add_images')->getData();
                foreach ($add_images as $add_image) {
                    $FaqCategory->setIconName($add_image);

                    // 移動
                    $file = new File($this->eccubeConfig['eccube_temp_image_dir'].'/'.$add_image);
                    $file->move($this->eccubeConfig['eccube_save_image_dir']);
                }

                // 画像の削除
                $delete_images = $form->get('delete_images')->getData();
                foreach ($delete_images as $delete_image) {
                    if ($FaqCategory->getIconName() === $delete_image) {
                        $FaqCategory->setIconName(null);
                    }
                    // 削除
                    $fs = new Filesystem();
                    $fs->remove($this->eccubeConfig['eccube_save_image_dir'].'/'.$delete_image);
                }

                $this->entityManager->persist($FaqCategory);
                $this->entityManager->flush();
                log_info('Q&Aカテゴリ登録完了', [$id]);
                $this->addSuccess('admin.common.save_complete', 'admin');

                return $this->redirectToRoute('admin_faq_category_edit', ['id' => $FaqCategory->getId()]);
            }
        }

        return [
            'FaqCategory' => $FaqCategory,
            'form' => $form->createView(),
            'id' => $id,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/faq/category/{id}/delete", requirements={"id" = "\d+"}, name="admin_faq_category_delete", methods={"DELETE"})
     */
    public function delete(Request $request, $id = null, CacheUtil $cacheUtil)
    {
        $this->isTokenValid();
        $session = $request->getSession();
        $page_no = intval($session->get(self::FAQ_CATEGORY_PAGE_NO_SESSION_KEY));
        $page_no = $page_no ? $page_no : Constant::ENABLED;
        $message = null;
        $success = false;

        if (is_null($id)) {
            log_info('Q&Aカテゴリ削除エラー', [$id]);
            $message = trans('admin.common.delete_error');
            return $this->createDeleteResponse($request, $success, $message, $page_no);
        }

        $FaqCategory = $this->faqCategoryRepository->find($id);
        if (!$FaqCategory || !$FaqCategory instanceof FaqCategory) {
            $message = trans('admin.common.delete_error_already_deleted');
            return $this->createDeleteResponse($request, $success, $message, $page_no);
        }

        log_info('Q&Aカテゴリ削除開始', [$id]);

        try {
            $this->faqCategoryRepository->delete($FaqCategory);
            $this->entityManager->flush();

            log_info('Q&Aカテゴリ削除完了', [$id]);

            $success = true;
            $message = trans('admin.common.delete_complete');

            $cacheUtil->clearDoctrineCache();
        } catch (ForeignKeyConstraintViolationException $e) {
            log_info('Q&Aカテゴリ削除エラー', [$id]);
            $message = trans('admin.common.delete_error_foreign_key', ['%name%' => $FaqCategory->getName()]);
        }

        return $this->createDeleteResponse($request, $success, $message, $page_no);
    }

    private function createDeleteResponse(Request $request, $success, $message, $page_no)
    {
        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => $success, 'message' => $message]);
        }

        if ($success) {
            $this->addSuccess($message, 'admin');
        } else {
            $this->addError($message, 'admin');
        }
        $rUrl = $this->generateUrl('admin_faq_category_page', ['page_no' => $page_no]);
        return $this->redirect($rUrl);
    }

    /**
     * @Route("/%eccube_admin_route%/faq/category/image/add", name="admin_faq_category_image_add", methods={"POST"})
     */
    public function addImage(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $images = $request->files->get('admin_faq_category');

        $allowExtensions = ['gif', 'jpg', 'jpeg', 'png'];
        $files = [];
        if (count($images) === 1) {
            foreach ($images as $image) {
                //ファイルフォーマット検証
                $mimeType = $image->getMimeType();
                if (0 !== strpos($mimeType, 'image')) {
                    throw new UnsupportedMediaTypeHttpException();
                }

                // 拡張子
                $extension = $image->getClientOriginalExtension();
                if (!in_array(strtolower($extension), $allowExtensions)) {
                    throw new UnsupportedMediaTypeHttpException();
                }

                $filename = date('mdHis').uniqid('_').'.'.$extension;
                $image->move($this->eccubeConfig['eccube_temp_image_dir'], $filename);
                $files[] = $filename;
            }
        }

        $event = new EventArgs(
            [
                'images' => $images,
                'files' => $files,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_PRODUCT_ADD_IMAGE_COMPLETE, $event);
        $files = $event->getArgument('files');

        return $this->json(['files' => $files], 200);
    }
}