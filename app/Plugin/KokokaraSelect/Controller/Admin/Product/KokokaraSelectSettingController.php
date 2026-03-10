<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/10
 */

namespace Plugin\KokokaraSelect\Controller\Admin\Product;


use Eccube\Controller\AbstractController;
use Eccube\Entity\Product;
use Eccube\Form\Type\AddCartType;
use Eccube\Form\Type\Admin\SearchProductType;
use Eccube\Repository\CategoryRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Util\CacheUtil;
use Knp\Component\Pager\Paginator;
use Plugin\KokokaraSelect\Config\ConfigSetting;
use Plugin\KokokaraSelect\Entity\KsProduct;
use Plugin\KokokaraSelect\Entity\KsSelectItemGroup;
use Plugin\KokokaraSelect\Form\Type\Admin\KsProductType;
use Plugin\KokokaraSelect\Service\ConfigHelperTrait;
use Plugin\KokokaraSelect\Service\KsOrderService;
use Plugin\KokokaraSelect\Service\KsSelectItemService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * 管理画面
 * 選択商品の設定
 *
 * Class KokokaraSelectSettingController
 * @package Plugin\KokokaraSelect\Controller\Admin\Product
 */
class KokokaraSelectSettingController extends AbstractController
{

    use ConfigHelperTrait;

    private const SESSION_SEARCH_KEY = "eccube.admin.product.kokokara_select.product.search";

    private const SESSION_SEARCH_PAGE_KEY = "eccube.admin.product.kokokara_select.product.search.page_no";

    /** @var ProductRepository */
    protected $productRepository;

    /** @var CategoryRepository */
    protected $categoryRepository;

    /** @var KsSelectItemService */
    protected $ksSelectItemService;

    /** @var KsOrderService */
    protected $ksOrderService;

    public function __construct(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        KsSelectItemService $ksSelectItemService,
        KsOrderService $ksOrderService
    )
    {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->ksSelectItemService = $ksSelectItemService;
        $this->ksOrderService = $ksOrderService;
    }

    /**
     * @Route("/%eccube_admin_route%/product/kokokara_select/{id}", name="admin_product_kokokara_select_setting", requirements={"id" = "\d+"})
     * @Template("@KokokaraSelect/admin/Product/setting.twig")
     *
     * @param Request $request
     * @param Product $product
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function index(Request $request, Product $product)
    {

        if ($product->hasProductClass()) {
            // 規格商品は設定不可
            return $this->redirectToRoute('admin_product_product_edit', ['id' => $product->getId()]);
        }

        if ($this->ksSelectItemService->isSetting($product)) {
            // 選択商品は設定不可
            $this->addWarning('kokokara_select.admin.setting.warning.setting_product_class', 'admin');
            return $this->redirectToRoute('admin_product_product_edit', ['id' => $product->getId()]);
        }

        $ksProduct = $product->getKsProduct();

        if (!$ksProduct) {
            $ksProduct = new KsProduct();
            $ksProduct->setProduct($product);
        }

        $builder = $this->formFactory
            ->createBuilder(KsProductType::class, $ksProduct);

        $form = $builder->getForm();

        // 商品検索フォーム
        $builder = $this->formFactory
            ->createBuilder(SearchProductType::class);

        $searchProductModalForm = $builder->getForm();

        if ('POST' === $request->getMethod()) {

            // 登録処理
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                /** @var KsProduct $data */
                $data = $form->getData();

                $this->entityManager->persist($data);
                $this->entityManager->flush();

                $this->addSuccess('admin.common.save_complete', 'admin');

                return $this->redirectToRoute('admin_product_kokokara_select_setting', ['id' => $product->getId()]);
            }

        }

        return [
            'editId' => $product->getId(),
            'form' => $form->createView(),
            'searchProductModalForm' => $searchProductModalForm->createView(),
            'clearForm' => $this->createForm(FormType::class)->createView(),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/product/search/kokokara_select_item", name="admin_kokokara_select_search_product")
     * @Route("/%eccube_admin_route%/product/search/kokokara_select_item/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_kokokara_select_search_product_page")
     * @Template("@KokokaraSelect/admin/Product/search_product.twig")
     *
     * @param Request $request
     * @param null $page_no
     * @param Paginator $paginator
     * @return array
     */
    public function searchProduct(Request $request, $page_no = null, Paginator $paginator)
    {
        if ($request->isXmlHttpRequest() && $this->isTokenValid()) {
            log_debug('CartUpRecommend search product start.');
            $page_count = $this->eccubeConfig['eccube_default_page_count'];
            $session = $this->session;

            if ('POST' === $request->getMethod()) {
                $page_no = 1;

                $searchData = [
                    'id' => $request->get('id'),
                ];

                if ($categoryId = $request->get('category_id')) {
                    $Category = $this->categoryRepository->find($categoryId);
                    $searchData['category_id'] = $Category;
                }

                if ($edit = $request->get('edit_id')) {
                    $searchData['edit_id'] = $edit;
                }

                $session->set(self::SESSION_SEARCH_KEY, $searchData);
                $session->set(self::SESSION_SEARCH_PAGE_KEY, $page_no);
            } else {
                $searchData = (array)$session->get(self::SESSION_SEARCH_KEY);
                if (is_null($page_no)) {
                    $page_no = intval($session->get(self::SESSION_SEARCH_PAGE_KEY));
                } else {
                    $session->set(self::SESSION_SEARCH_PAGE_KEY, $page_no);
                }
            }

            // 商品検索
            $qb = $this->productRepository
                ->getQueryBuilderBySearchDataForAdmin($searchData);

            // 自分自身を除く
            if (!empty($searchData['edit_id'])) {
                $qb
                    ->andWhere('p.id != :editId')
                    ->setParameter('editId', $searchData['edit_id']);
            }

            // 選択商品を除く
            $qbSub = $this->entityManager->createQueryBuilder()
                ->select('exclude_product.id')
                ->from(KsProduct::class, 'ks_product')
                ->join('ks_product.Product', 'exclude_product');

            $qb
                ->andWhere($qb->expr()->notIn('p.id', $qbSub->getDQL()));

            /** @var \Knp\Component\Pager\Pagination\SlidingPagination $pagination */
            $pagination = $paginator->paginate(
                $qb,
                $page_no,
                $page_count,
                ['wrap-queries' => true]
            );

            /** @var $Products \Eccube\Entity\Product[] */
            $Products = $pagination->getItems();

            if (empty($Products)) {
                log_debug('CartUpRecommend search product not found.');
            }

            $forms = [];
            foreach ($Products as $Product) {
                /* @var $builder FormBuilderInterface */
                $builder = $this->formFactory->createNamedBuilder('', AddCartType::class, null, [
                    'product' => $this->productRepository->findWithSortedClassCategories($Product->getId()),
                ]);
                $addCartForm = $builder->getForm();
                $forms[$Product->getId()] = $addCartForm->createView();
            }

            // 選択商品連続設定用
            $addProductRepeat = $this->configService->isKeyBool(ConfigSetting::SETTING_KEY_ADD_PRODUCT_REPEAT);

            return [
                'forms' => $forms,
                'Products' => $Products,
                'pagination' => $pagination,
                'addProductRepeat' => $addProductRepeat,
            ];
        }

        return null;
    }

    /**
     * 選択商品を初期化する.
     *
     * @Route("/%eccube_admin_route%/product/{id}/clear", requirements={"id" = "\d+"}, name="admin_kokokara_select_clear")
     *
     * @param Request $request
     * @param Product $Product
     * @param CacheUtil $cacheUtil
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Exception
     */
    public function clearKokokaraSelect(Request $request, Product $Product, CacheUtil $cacheUtil)
    {

        $form = $this->createForm(FormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 既に購入済の場合は不可とする
            if ($this->ksOrderService->isBuyIngProduct($Product)) {
                // 不可
                $this->addError('kokokara_select.admin.setting.clear.error', "admin");
                return $this->redirectToRoute('admin_product_kokokara_select_setting', ['id' => $Product->getId()]);
            }

            // 選択商品情報クリア処理
            $ksProduct = $Product->getKsProduct();

            if ($ksProduct) {
                // 削除
                /** @var KsSelectItemGroup $ksSelectItemGroup */
                foreach ($ksProduct->getKsSelectItemGroups() as $ksSelectItemGroup) {
                    $this->ksSelectItemService->deleteRelationKsCartItem($ksSelectItemGroup->getKsSelectItems());
                }

                $this->entityManager->remove($ksProduct);
                $this->entityManager->flush($ksProduct);
            }

            $this->addSuccess('kokokara_select.admin.setting.clear.success', 'admin');

            $cacheUtil->clearDoctrineCache();
        }

        return $this->redirectToRoute('admin_product_kokokara_select_setting', ['id' => $Product->getId()]);
    }
}
