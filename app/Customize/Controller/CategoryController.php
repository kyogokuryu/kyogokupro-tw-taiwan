<?php

namespace Customize\Controller;

use Eccube\Entity\Category;
use Customize\Repository\CategoryRepository;
use Doctrine\ORM\EntityManager;
use Eccube\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Knp\Component\Pager\Paginator;
use Eccube\Repository\ProductRepository;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;

class CategoryController extends AbstractController
{
    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    protected $productRepository;

    protected $entityManager;

    protected $packages;

    public function __construct(
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        EntityManager $entityManager,
        Packages $packages
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
        $this->packages = $packages;
    }

    /**
     * 
     * @Route("/categories", name="categories_index", methods={"GET"})
     * 
     * @Template("Category/search.twig")
     */
    public function index(Request $request)
    {
        $categories = $this->categoryRepository->getAvailableCategories();

        return [
            'categories' => $categories
        ];
    }

    /**
     * 商品一覧画面.
     *
     * @Route("/category/{id}", name="categories_detail",requirements={"id" = "\d+"})
     * @Template("Category/detail.twig")
     */
    public function categoriesDetail(Request $request, Category $category, Paginator $paginator)
    {
        $searchData = [
            'pageno' => $request->query->getInt('pageno', 1),
            'disp_number' => $request->query->getInt('disp_number', 50),
            'category_id' => $category
        ];
        $qb = $this->productRepository->getQueryBuilderBySearchData($searchData);
        $query = $qb->getQuery()
            ->useResultCache(true, $this->eccubeConfig['eccube_result_cache_lifetime_short']);
        $pagination = $paginator->paginate(
            $query,
            $searchData['pageno'],
            $searchData['disp_number']
        );

        $ReviewAveList = array();
        $ReviewCntList = array();
        foreach ($pagination as $Product) {
            $rate = $this->entityManager->getRepository('Plugin\ProductReview4\Entity\ProductReview')->getAvgAll($Product);
            $ReviewAveList[$Product->getId()] = round($rate['recommend_avg']);
            $ReviewCntList[$Product->getId()] = intval($rate['review_count']);
        }

        if ($request->isXmlHttpRequest()) {
            $products = [];
            foreach ($pagination as $product) {
                $products[] = $this->convertData($product);
            }
            return $this->json([
                'products' => $products,
            ]);
        } else {
            return [
                'products' => $pagination,
                'ReviewAveList' => $ReviewAveList,
                'ReviewCntList' => $ReviewCntList,
                'category' => $category,
            ];
        }
    }

    public function convertData($product) {
        $lastOrderCustomers = $this->productRepository->getCustomerLastOrder($product->getId());
        $mails = null;
        if ($lastOrderCustomers) {
           foreach ($lastOrderCustomers as $lastOrderCustomer) {
               $mails[] = $lastOrderCustomer['email'];
           }
        }
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price02' => !empty($product->getProductClasses()[0]['price02_inc_tax']) ? number_format($product->getProductClasses()[0]['price02_inc_tax']) : 0,
            'price01' => !empty($product->getProductClasses()[0]['price01_inc_tax']) ? number_format($product->getProductClasses()[0]['price01_inc_tax']) : 0,
            'image' => $this->packages->getUrl( $product->getMainFileName() ? $product->getMainFileName()->getFileName() : 'no_image_product.png','save_image'),
            'ProductClass' => $product->getProductClasses() ? $product->getProductClasses()[0]->getId() : null,
            'Review' => $this->entityManager->getRepository('Plugin\ProductReview4\Entity\ProductReview')->getAvgAll($product),
            'lastOrderCustomers' => $mails,
        ];
    }
}
