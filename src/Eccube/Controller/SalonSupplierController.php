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

use Carbon\Carbon;
use Eccube\Entity\Customer;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Front\CustomerLoginType;
use Eccube\Form\Type\SupplierCodeType;
use Eccube\Repository\CategoryRepository;
use Eccube\Repository\CustomerFavoriteProductRepository;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\Master\ProductListMaxRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Service\CartService;
use Exception;
use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SalonSupplierController extends AbstractController
{

    /**
     * @var CustomerRepository
     */
    private $customerRepository;
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;
    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var ProductListMaxRepository
     */
    private $productListMaxRepository;

    /**
     * @var CustomerFavoriteProductRepository
     */
    private $customerFavoriteProductRepository;

    /**
     * @var Packages
     */
    protected $packages;

    /**
     * @var CartService
     */
    private $cartService;

    public function __construct(
        CustomerRepository $customerRepository,
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        ProductListMaxRepository $productListMaxRepository,
        CustomerFavoriteProductRepository $customerFavoriteProductRepository,
        CartService $cartService,
        Packages $packages

    ) {
        $this->customerRepository = $customerRepository;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->productListMaxRepository = $productListMaxRepository;
        $this->customerFavoriteProductRepository= $customerFavoriteProductRepository;
        $this->cartService = $cartService;
        $this->packages = $packages;
    }

    /**
     * 商品一覧画面.
     *
     * @Route("/salon-suppliers", name="salon-suppliers", methods={"POST","GET"})
     * @Template("Product/salon_supplier.twig")
     */
    public function index(Request $request, Paginator $paginator)
    {
        
        if ($request->getMethod() === 'GET') {
            $request->query->set('pageno', $request->query->get('pageno', ''));
        }

        // searchForm
        $builder = $this->formFactory->createNamedBuilder('', SupplierCodeType::class);
        if ($request->getMethod() === 'GET') {
            $builder->setMethod('GET');
        }

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_INDEX_INITIALIZE, $event);

        $searchForm = $builder->getForm();

        $searchForm->handleRequest($request);
        $searchData = $searchForm->getData();

        $form_c = $this->createForm(SupplierCodeType::class);
        $form_c = $form_c->createView();

        $Customer = $this->getUser();

        if ($Customer && $Customer->getIsSupplier()) {
            $this->session->set('IS_SUPPLIER', true);
        }

        if ('POST' == $request->getMethod()) {
            $builder = $this->formFactory
                ->createBuilder(SupplierCodeType::class);
            $form = $builder->getForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                if (!$request->get('supplier')['supplier_code']) {
                    $this->addError('入力されていません');

                    return $this->redirectToRoute('salon-suppliers');
                }

                $isSupplierCode = $this->customerRepository->findOneBy(['supplier_code' => $request->get('supplier')['supplier_code']]);
                if (!$isSupplierCode) {
                    $this->addError('パスワードが間違っています');

                    return $this->redirectToRoute('salon-suppliers');
                }
                if ($Customer) {
                    $Customer = $this->customerRepository->findOneBy(['id' => $this->getUser()->getId(), 'supplier_code' => $request->get('supplier')['supplier_code']]);
                    if (!$Customer) {
                        $this->addError('パスワードが間違っています');

                        return $this->redirectToRoute('salon-suppliers');
                    }

                    $Customer->setIsSupplier(true);
                    $Customer->setEnterSupplierCodeDate(Carbon::now());
                    $this->entityManager->flush();
                }

                $this->session->set('IS_SUPPLIER', true);

                if ($this->session->get('supplier_product_id')) {

                    return $this->redirectToRoute('product_detail', ['id' => $this->session->get('supplier_product_id')]);
                }

                return $this->redirectToRoute('salon-suppliers');
            }
        }

        
        $categories = $this->categoryRepository->getListCategoryForSupplier(env('SALON_SUPPLIER_CATEGORY'));
        $arrChildCategories = [];
        foreach ($categories as $category) {
            $arrChildCategories = array_merge($arrChildCategories, $category->getSelfAndDescendants());
        }

        if (count($categories) < 1) {
            return [
                'form' => $form_c,
                'pagination' => [],
            ];
        }

        foreach ($categories as $category) {
            $searchData['category_id'] = $category;
            $productCategoriesQuery = $this->productRepository->getQueryBuilderBySearchData($searchData, 0, true);
            $query = $productCategoriesQuery->getQuery()
                ->useResultCache(true, $this->eccubeConfig['eccube_result_cache_lifetime_short']);

            $pagination = $paginator->paginate(
                $query,
                !empty($searchData['pageno']) ? $searchData['pageno'] : 1,
                !empty($searchData['disp_number']) ? $searchData['disp_number']->getId() : $this->productListMaxRepository->findOneBy([], ['sort_no' => 'ASC'])->getId()
            );

            foreach ($pagination as $Product) {
                $rate = $this->entityManager->getRepository('Plugin\ProductReview4\Entity\ProductReview')->getAvgAll($Product);
                $ReviewAveList[$Product->getId()] = round($rate['recommend_avg']);
                $ReviewCntList[$Product->getId()] = intval($rate['review_count']);
            }

            $Products = $productCategoriesQuery->getQuery()->getResult();
            $Carts = null;
            $Carts = $this->cartService->getCarts();
            return [
                'form' => $form_c,
                'arrChildCategories' => $arrChildCategories,
                'category' => $category,
                'Customer' => $Customer,
                'ProductsList' => $Products,
                'pagination' => $pagination,
                'ReviewAveList' => $ReviewAveList ?? 0,
                'ReviewCntList' => $ReviewCntList ?? 0,
                'Carts' => $Carts,
            ];
        }
    }

    /**
     * ログイン画面.
     *
     * @Route("/supplier/login", name="supplier_login")
     * @Template("Product/supplier_login.twig")
     */
    public function login(Request $request, AuthenticationUtils $utils)
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            log_info('認証済のためログイン処理をスキップ');

            return $this->redirectToRoute('salon-suppliers');
        }

        /* @var $form \Symfony\Component\Form\FormInterface */
        $builder = $this->formFactory
            ->createNamedBuilder('', CustomerLoginType::class);

        $builder->get('login_memory')->setData((bool) $request->getSession()->get('_security.login_memory'));

        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $Customer = $this->getUser();
            if ($Customer instanceof Customer) {
                $builder->get('login_email')
                    ->setData($Customer->getEmail());
            }
        }

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_MYPAGE_LOGIN_INITIALIZE, $event);

        $form = $builder->getForm();

        return [
            'error' => $utils->getLastAuthenticationError(),
            'form' => $form->createView(),
        ];
    }

    /**
     * 商品一覧画面.
     *
     * @Route("/salon_supplier_delete_favorite", name="salon_supplier_delete_favorite" , methods={"POST","GET"})
     */
    public function removeFavorite(Request $request){
        $id = $request->get('product_id');
        $Customer = $this->getUser();
        $Product = $this->productRepository->find($id);
        $CustomerFavoriteProduct = $this->customerFavoriteProductRepository->findOneBy(['Customer' => $Customer, 'Product' => $Product]);

        if ($CustomerFavoriteProduct) {
            $this->customerFavoriteProductRepository->delete($CustomerFavoriteProduct);
        }

        $event = new EventArgs(
            [
                'Customer' => $Customer,
                'CustomerFavoriteProduct' => $CustomerFavoriteProduct,
            ], $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_MYPAGE_DELETE_COMPLETE, $event);

        // log_info('お気に入り商品削除完了', [$Customer->getId(), $CustomerFavoriteProduct->getId()]);

        return $this->json(['status' => 'success', 'message' => 'お気に入りから削除']);
    }
}
