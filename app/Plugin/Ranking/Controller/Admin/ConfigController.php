<?php

namespace Plugin\Ranking\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\Ranking\Form\Type\Admin\ConfigType;
use Plugin\Ranking\Form\Type\Admin\FrameType;
use Plugin\Ranking\Repository\ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Eccube\Form\Type\Admin\SearchProductType;

use Eccube\Repository\CategoryRepository;
use Eccube\Repository\ProductRepository;
use Knp\Component\Pager\PaginatorInterface;

class ConfigController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var PaginatorInterface
     */
    protected $paginator;

    /**
     * ConfigController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        ConfigRepository $configRepository,
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        PaginatorInterface $paginator
    ){
        $this->configRepository = $configRepository;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->paginator = $paginator;
    }

    /**
     * @Route("/%eccube_admin_route%/ranking/config", name="ranking_admin_config")
     * @Template("@Ranking/admin/config.twig")
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->get();
        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);


        // 検索結果の保持
        $builder = $this->formFactory
        ->createBuilder(SearchProductType::class);
        $searchForm = $builder->getForm();
        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);
        }



        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);
            $this->addSuccess('登録しました。', 'admin');

            return $this->redirectToRoute('ranking_admin_config');
        }

        return [
            'form' => $form->createView(),
            'searchForm' => $searchForm->createView()
        ];
    }

    /**
     * search product modal.
     *
     * @param Request $request
     * @param int $page_no
     *
     * @return \Symfony\Component\HttpFoundation\Response|array
     *
     * @Route("/%eccube_admin_route%/ranking/search/product", name="admin_ranking_product_search")
     * @Route("/%eccube_admin_route%/ranking/search/product/page/{page_no}", name="admin_ranking_product_search_product_page", requirements={"page_no":"\d+"})
     *
     * @Template("@Ranking/admin/modal_result.twig")
     */
    public function searchProduct(Request $request, $page_no = null)
    {
        if (!$request->isXmlHttpRequest()) {
            return null;
        }

        $pageCount = $this->eccubeConfig['eccube_default_page_count'];
        $session = $this->session;
        if ('POST' === $request->getMethod()) {
            log_info('get search data with parameters ', [
                'id' => $request->get('id'),
                'category_id' => $request->get('category_id'),
            ]);
            $page_no = 1;
            $searchData = ['id' => $request->get('id')];
            if ($categoryId = $request->get('category_id')) {
                $searchData['category_id'] = $categoryId;
            }
            $session->set('eccube.plugin.ranking.product.search', $searchData);
            $session->set('eccube.plugin.ranking.product.search.page_no', $page_no);
        } else {
            $searchData = (array) $session->get('eccube.plugin.ranking.product.search');
            if (is_null($page_no)) {
                $page_no = intval($session->get('eccube.plugin.ranking.product.search.page_no'));
            } else {
                $session->set('eccube.plugin.ranking.product.search.page_no', $page_no);
            }
        }

        if (!empty($searchData['category_id'])) {
            $searchData['category_id'] = $this->categoryRepository->find($searchData['category_id']);
        }

        $qb = $this->productRepository->getQueryBuilderBySearchDataForAdmin($searchData);

        /** @var \Knp\Component\Pager\Pagination\SlidingPagination $pagination */
        $pagination = $this->paginator->paginate(
            $qb,
            $page_no,
            $pageCount,
            ['wrap-queries' => true]
        );

        return [
            'pagination' => $pagination,
        ];
    }
}
