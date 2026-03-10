<?php
/**
 * This file is part of BundleSale4
 *
 * Copyright(c) Akira Kurozumi <info@a-zumi.net>
 *
 * https://a-zumi.net
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\BundleSale4\Controller\Admin;

use Eccube\Controller\AbstractController;
use Eccube\Repository\CategoryRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Repository\ProductClassRepository;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Eccube\Util\StringUtil;
use Eccube\Entity\Master\ProductStatus;

class BundleItemController extends AbstractController
{
    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var ProductClassRepository
     */
    protected $productClassRepository;

    /**
     * @var PaginatorInterface
     */
    protected $paginator;

    /**
     * BundleItemController constructor.
     *
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository $productRepository
     * @param ProductClassRepository $productClassRepository
     * @param PaginatorInterface $paginator
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        ProductClassRepository $productClassRepository,
        PaginatorInterface $paginator
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->productClassRepository = $productClassRepository;
        $this->paginator = $paginator;
    }

    /**
     * search product modal.
     *
     * @param Request $request
     * @param int $page_no
     *
     * @return \Symfony\Component\HttpFoundation\Response|array
     *
     * @Route("/%eccube_admin_route%/bundle_product/search/product", name="admin_bundle_item_search")
     * @Route("/%eccube_admin_route%/bundle_product/search/product/page/{page_no}", name="admin_bundle_item_search_product_page", requirements={"page_no":"\d+"})
     *
     * @Template("@BundleSale4/admin/modal_result.twig")
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
            $session->set('eccube.plugin.bundle_product.product.search', $searchData);
            $session->set('eccube.plugin.bundle_product.product.search.page_no', $page_no);
        } else {
            $searchData = (array) $session->get('eccube.plugin.bundle_product.product.search');
            if (is_null($page_no)) {
                $page_no = intval($session->get('eccube.plugin.bundle_product.product.search.page_no'));
            } else {
                $session->set('eccube.plugin.bundle_product.product.search.page_no', $page_no);
            }
        }

        if (!empty($searchData['category_id'])) {
            $searchData['category_id'] = $this->categoryRepository->find($searchData['category_id']);
        }

        $qb = $this->productClassRepository->createQueryBuilder("pc")
            ->addSelect('pc')
            ->leftJoin('pc.Product', 'p')
            ->where('pc.visible = :pc_visible')
            ->andWhere('p.Status = :Status')
            ->setParameter('pc_visible', true)
            ->setParameter('Status', ProductStatus::DISPLAY_SHOW);

        // id
        if (isset($searchData['id']) && StringUtil::isNotBlank($searchData['id'])) {
            $id = preg_match('/^\d{0,10}$/', $searchData['id']) ? $searchData['id'] : null;
            $qb
                ->andWhere('p.id = :id OR p.name LIKE :likeid OR pc.code LIKE :likeid')
                ->setParameter('id', $id)
                ->setParameter('likeid', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $searchData['id']).'%');
        }

        // category
        if (!empty($searchData['category_id']) && $searchData['category_id']) {
            $Categories = $searchData['category_id']->getSelfAndDescendants();
            if ($Categories) {
                $qb
                    ->innerJoin('p.ProductCategories', 'pct')
                    ->innerJoin('pct.Category', 'c')
                    ->andWhere($qb->expr()->in('pct.Category', ':Categories'))
                    ->setParameter('Categories', $Categories);
            }
        }

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
