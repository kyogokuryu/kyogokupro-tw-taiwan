<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/20
 */

namespace Plugin\KokokaraSelect\Service\MultiCSVService;


use Doctrine\ORM\QueryBuilder;
use Eccube\Form\Type\Admin\SearchCustomerType;
use Eccube\Form\Type\Admin\SearchOrderType;
use Eccube\Form\Type\Admin\SearchProductType;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Util\FormUtil;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

class CsvExportHelper
{

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /** @var ProductRepository */
    protected $productRepository;

    /** @var OrderRepository */
    protected $orderRepository;

    /** @var CustomerRepository */
    protected $customerRepository;

    public function __construct(
        FormFactoryInterface $formFactory,
        ProductRepository $productRepository,
        OrderRepository $orderRepository,
        CustomerRepository $customerRepository
    )
    {
        $this->formFactory = $formFactory;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
    }

    /**
     * データ取得QueryBuilderとして商品検索を設定
     *
     * @param Request $request
     * @return QueryBuilder
     */
    public function getProductAdminSearchQueryBuilder(Request $request)
    {
        $session = $request->getSession();
        $builder = $this->formFactory
            ->createBuilder(SearchProductType::class);
        $searchForm = $builder->getForm();

        $viewData = $session->get('eccube.admin.product.search', []);
        $searchData = FormUtil::submitAndGetData($searchForm, $viewData);

        // 商品データのクエリビルダを構築.
        $qb = $this->productRepository
            ->getQueryBuilderBySearchDataForAdmin($searchData);

        return $qb;
    }

    /**
     * データ取得QueryBuilderとして受注検索を設定
     *
     * @param Request $request
     * @return QueryBuilder
     */
    public function getOrderAdminSearchQueryBuilder(Request $request)
    {
        $session = $request->getSession();
        $builder = $this->formFactory
            ->createBuilder(SearchOrderType::class);
        $searchForm = $builder->getForm();

        $viewData = $session->get('eccube.admin.order.search', []);
        $searchData = FormUtil::submitAndGetData($searchForm, $viewData);

        // 受注データのクエリビルダを構築.
        $qb = $this->orderRepository
            ->getQueryBuilderBySearchDataForAdmin($searchData);

        return $qb;
    }

    /**
     * データ取得QueryBuilderとして顧客検索を設定
     *
     * @param Request $request
     * @return QueryBuilder
     */
    public function getCustomerAdminSearchQueryBuilder(Request $request)
    {
        $session = $request->getSession();
        $builder = $this->formFactory
            ->createBuilder(SearchCustomerType::class);
        $searchForm = $builder->getForm();

        $viewData = $session->get('eccube.admin.customer.search', []);
        $searchData = FormUtil::submitAndGetData($searchForm, $viewData);

        // 会員データのクエリビルダを構築.
        $qb = $this->customerRepository
            ->getQueryBuilderBySearchData($searchData);

        return $qb;
    }
}
