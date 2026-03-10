<?php

namespace Customize\Controller\Admin\Faq;

use Customize\Entity\Faq;
use Customize\Form\Admin\FaqType;
use Customize\Form\Admin\SearchFaqType;
use Customize\Repository\FaqRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Util\CacheUtil;
use Eccube\Util\FormUtil;
use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Q&Aページ用コントローラ
 */
class FaqController extends AbstractController
{
    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var FaqRepository
     */
    protected $faqRepository;

    const FAQ_PAGE_SEARCH_SESSION_KEY = 'eccube.admin.faq.search';
    const FAQ_PAGE_COUNT_SESSION_KEY = 'eccube.admin.faq.search.page_count';
    const FAQ_PAGE_NO_SESSION_KEY = 'eccube.admin.faq.search.page_no';

    public function __construct(
        PageMaxRepository $pageMaxRepository,
        FaqRepository $faqRepository
    )
    {
        $this->pageMaxRepository = $pageMaxRepository;
        $this->faqRepository = $faqRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/faq", name="admin_faq")
     * @Route("/%eccube_admin_route%/faq/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_faq_page")
     * @Template("@admin/Faq/index.twig")
     */
    public function index(Request $request, $page_no = null, Paginator $paginator)
    {
        $builder = $this->formFactory->createBuilder(SearchFaqType::class);
        $searchForm = $builder->getForm();

        $pageMaxis = $this->pageMaxRepository->findAll();
        $pageCount = $this->session->get(self::FAQ_PAGE_COUNT_SESSION_KEY, $this->eccubeConfig['eccube_default_page_count']);
        $pageCountParam = $request->get('page_count');
        if ($pageCountParam && is_numeric($pageCountParam)) {
            foreach ($pageMaxis as $pageMax) {
                if ($pageCountParam == $pageMax->getName()) {
                    $pageCount = $pageMax->getName();
                    $this->session->set(self::FAQ_PAGE_COUNT_SESSION_KEY, $pageCount);
                    break;
                }
            }
        }

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);

            if ($searchForm->isValid()) {
                /**
                 * 検索が実行された場合は, セッションに検索条件を保存する.
                 * ページ番号は最初のページ番号に初期化する.
                 */
                $page_no = 1;
                $searchData = $searchForm->getData();

                // 検索条件, ページ番号をセッションに保持.
                $this->session->set(self::FAQ_PAGE_SEARCH_SESSION_KEY, FormUtil::getViewData($searchForm));
                $this->session->set(self::FAQ_PAGE_NO_SESSION_KEY, $page_no);
            } else {
                // 検索エラーの際は, 詳細検索枠を開いてエラー表示する.
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $page_no,
                    'page_count' => $pageCount,
                    'has_errors' => true,
                ];
            }
        } else {
            if (null !== $page_no || $request->get('resume')) {
                /*
                 * ページ送りの場合または、他画面から戻ってきた場合は, セッションから検索条件を復旧する.
                 */
                if ($page_no) {
                    // ページ送りで遷移した場合.
                    $this->session->set(self::FAQ_PAGE_NO_SESSION_KEY, (int) $page_no);
                } else {
                    // 他画面から遷移した場合.
                    $page_no = $this->session->get(self::FAQ_PAGE_NO_SESSION_KEY, 1);
                }
                $viewData = $this->session->get(self::FAQ_PAGE_SEARCH_SESSION_KEY, []);
                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
            } else {
                /**
                 * 初期表示の場合.
                 */
                $page_no = 1;
                // submit default value
                $viewData = FormUtil::getViewData($searchForm);
                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);

                // セッション中の検索条件, ページ番号を初期化.
                $this->session->set(self::FAQ_PAGE_SEARCH_SESSION_KEY, $viewData);
                $this->session->set(self::FAQ_PAGE_NO_SESSION_KEY, $page_no);
            }
        }

        $qb = $this->faqRepository->getQueryBuilderBySearchDataForAdmin($searchData);
        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $pageCount
        );

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $pageCount,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/faq/new", name="admin_faq_new")
     * @Route("/%eccube_admin_route%/faq/{id}/edit", requirements={"id" = "\d+"}, name="admin_faq_edit")
     * @Template("@admin/Faq/edit.twig")
     */
    public function edit(Request $request, $id = null)
    {
        $Faq = new Faq();
        if ($id) {
            $Faq = $this->faqRepository->find($id);
            if (is_null($Faq) || !$Faq instanceof Faq) {
                throw new NotFoundHttpException();
            }
        }

        $builder = $this->formFactory->createBuilder(FaqType::class, $Faq);

        $form = $builder->getForm();

        if ($Faq->getFaqCategory()) {
            $form['FaqCategory']->setData($Faq->getFaqCategory());
        }

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                log_info('Q&A登録開始', [$id]);

                $FaqCategory = $form->get('FaqCategory')->getData();
                $Faq->setFaqCategory($FaqCategory);

                $this->entityManager->persist($Faq);
                $this->entityManager->flush();
                log_info('Q&A登録完了', [$id]);
                $this->addSuccess('admin.common.save_complete', 'admin');

                return $this->redirectToRoute('admin_faq_edit', ['id' => $Faq->getId()]);
            }
        }

        return [
            'Faq' => $Faq,
            'form' => $form->createView(),
            'id' => $id,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/faq/{id}/delete", requirements={"id" = "\d+"}, name="admin_faq_delete", methods={"DELETE"})
     */
    public function delete(Request $request, $id = null, CacheUtil $cacheUtil)
    {
        $this->isTokenValid();
        $session = $request->getSession();
        $page_no = intval($session->get(self::FAQ_PAGE_NO_SESSION_KEY));
        $page_no = $page_no ? $page_no : Constant::ENABLED;
        $message = null;
        $success = false;

        if (is_null($id)) {
            log_info('Q&A削除エラー', [$id]);
            $message = trans('admin.common.delete_error');
            return $this->createDeleteResponse($request, $success, $message, $page_no);
        }

        $Faq = $this->faqRepository->find($id);
        if (!$Faq || !$Faq instanceof Faq) {
            $message = trans('admin.common.delete_error_already_deleted');
            return $this->createDeleteResponse($request, $success, $message, $page_no);
        }

        log_info('Q&A削除開始', [$id]);

        try {
            $this->faqRepository->delete($Faq);
            $this->entityManager->flush();

            log_info('Q&A削除完了', [$id]);

            $success = true;
            $message = trans('admin.common.delete_complete');

            $cacheUtil->clearDoctrineCache();
        } catch (ForeignKeyConstraintViolationException $e) {
            log_info('Q&A削除エラー', [$id]);
            $message = trans('admin.common.delete_error_foreign_key', ['%name%' => $Faq->getQuestion()]);
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
        $rUrl = $this->generateUrl('admin_faq_page', ['page_no' => $page_no]);
        return $this->redirect($rUrl);
    }
}