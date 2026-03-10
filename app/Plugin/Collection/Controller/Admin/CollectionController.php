<?php

namespace Plugin\Collection\Controller\Admin;

use Eccube\Controller\Admin\AbstractCsvImportController;
use Eccube\Common\Constant;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Repository\CategoryRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Util\FormUtil;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use Plugin\Collection\Entity\Collection;
use Plugin\Collection\Entity\CollectionProduct;
use Plugin\Collection\Form\Type\Admin\CollectionType;
use Plugin\Collection\Form\Type\Admin\SearchCollectionType;
use Plugin\Collection\Form\Type\Admin\SearchProductModalType;
use Plugin\Collection\Repository\CollectionProductRepository;
use Plugin\Collection\Repository\CollectionRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Plugin\Collection\Service\CalculateService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use function GuzzleHttp\json_encode;

class CollectionController extends AbstractCsvImportController
{
    /**
     * @var CollectionRepository
     */
    protected $collectionRepository;

    /**
     * @var CollectionProductRepository
     */
    protected $collectionProductRepository;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var CalculateService
     */
    protected $calculateService;

    /**
     * @var array
     */
    protected $errors;

    /**
     * CollectionController constructor.
     *
     * @param CollectionRepository $collectionRepository
     */
    public function __construct(
        CollectionRepository $collectionRepository,
        CollectionProductRepository $collectionProductRepository,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        PageMaxRepository $pageMaxRepository,
        calculateService $calculateService
    ) {
        $this->collectionRepository = $collectionRepository;
        $this->collectionProductRepository = $collectionProductRepository;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->pageMaxRepository = $pageMaxRepository;
        $this->calculateService = $calculateService;
        $this->errors = [];
    }

    /**
     * @Route("/%eccube_admin_route%/collection", name="admin_collection")
     * @Route("/%eccube_admin_route%/collection/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_collection_page")
     * @Template("@Collection/admin/index.twig")
     */
    public function index(Request $request, $page_no = null, PaginatorInterface $paginator)
    {
        $builder = $this->formFactory
            ->createBuilder(SearchCollectionType::class);
        $searchForm = $builder->getForm();

        /**
         * number of items per page is given priority as below:
         * - request parameter
         * - session
         * - default value
         * if save it to session, collate mtb_page_max with it and save when that matched
         **/
        $page_count = $this->session->get('collection.admin.collection.search.page_count',
            $this->eccubeConfig->get('eccube_default_page_count'));

        $page_count_param = (int) $request->get('page_count');
        $pageMaxis = $this->pageMaxRepository->findAll();

        if ($page_count_param) {
            foreach ($pageMaxis as $pageMax) {
                if ($page_count_param == $pageMax->getName()) {
                    $page_count = $pageMax->getName();
                    $this->session->set('collection.admin.collection.search.page_count', $page_count);
                    break;
                }
            }
        }

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);

            if ($searchForm->isValid()) {
                /**
                 * save search criteria to session if searched.
                 * and set the page number to initial number.
                 */
                $page_no = 1;
                $searchData = $searchForm->getData();

                $this->session->set('collection.admin.collection.search', FormUtil::getViewData($searchForm));
                $this->session->set('collection.admin.collection.search.page_no', $page_no);

            } else {
                // if error occurs in searching, open search criteria block and display error message
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $page_no,
                    'page_count' => $page_count,
                    'has_errors' => true,
                ];
            }
        } else {
            // method: GET
            if (null !== $page_no || $request->get('resume')) {
                /*
                 * in case using pagination or coming back from other page,
                 * resrore search criteria from session
                 */
                if ($page_no) {
                    // in case using pagination
                    $this->session->set('collection.admin.collection.search.page_no', (int) $page_no);
                } else {
                    // in case coming back from other page
                    $page_no = $this->session->get('collection.admin.collection.search.page_no', 1);
                }
                $viewData = $this->session->get('collection.admin.collection.search', []);
                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);

            } else {
                /**
                 * in case initial access
                 */
                $page_no = 1;
                $viewData = [];
                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);

                // initialize search criteria and page number in session
                $this->session->set('collection.admin.collection.search', $viewData);
                $this->session->set('collection.admin.collection.search.page_no', $page_no);
            }
        }

        $qb = $this->collectionRepository->getQueryBuilderBySearchDataForAdmin($searchData);
        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $page_count
        );

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $page_count,
            'has_errors' => false,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/collection/{collection_id}/delete", requirements={"id" = "\d+"}, name="admin_collection_delete", methods={"DELETE"})
     */
    public function delete(Request $request, $collection_id)
    {
        $this->isTokenValid();

        log_info('特集削除開始', [$collection_id]);

        $page_no = intval($this->session->get('collection.admin.collection.search.page_no'));
        $page_no = $page_no ? $page_no : Constant::ENABLED;

        $Collection = $this->collectionRepository->find($collection_id);

        if (!$Collection) {
            $this->deleteMessage();

            return $this->redirect($this->generateUrl('admin_customer_page',
                    ['page_no' => $page_no]).'?resume='.Constant::ENABLED);
        }

        try {
            $this->collectionRepository->delete($collection_id);
            $this->entityManager->flush($Collection);
            $this->addSuccess('admin.common.delete_complete', 'admin');
        } catch (ForeignKeyConstraintViolationException $e) {
            log_error('特集削除失敗', [$e], 'admin');

            $message = trans('admin.common.delete_error_foreign_key', ['%name%' => $Collection->getName()]);
            $this->addError($message, 'admin');
        }

        log_info('特集削除完了', [$collection_id]);

        return $this->redirect($this->generateUrl('admin_collection_page',
                ['page_no' => $page_no]).'?resume='.Constant::ENABLED);
    }

    /**
     * @Route("/%eccube_admin_route%/collection/new", name="admin_collection_new")
     * @Route("/%eccube_admin_route%/collection/{collection_id}/edit", requirements={"collection_id" = "\d+"}, name="admin_collection_edit")
     * @Template("@Collection/admin/edit.twig")
     */
    public function edit(Request $request, $collection_id = null)
    {
        if ($collection_id === null) {
            // new
            $Collection = new Collection;
            $Collection
                ->setVisible(0)
                ->setDeleted(0)
                ->setCreateDate(new \DateTime);

        } else {
            // edit
            $Collection = $this->collectionRepository->find($collection_id);
            if (!$Collection || $Collection->getDeleted()) {
                throw new NotFoundHttpException();
            }
        }

        $builder = $this->formFactory->createBuilder(CollectionType::class, $Collection);
        $form = $builder->getForm();
        $headers = $this->getCsvHeader();

        $images = [];
        if (!empty($Collection->getFileName())) {
            $images[] = $Collection->getFileName();
        }

        $form['images']->setData($images);

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            $formCollectionProducts = $form['CollectionProducts']->getData();
            $productIds = [];
            foreach ($formCollectionProducts as $collectionProduct) {
                if (in_array($collectionProduct->getProduct()->getId(), $productIds)) {
                    $message = trans('collection.admin.edit.collectionproduct.unique');
                    $form['CollectionProducts']->addError(new FormError($message), 'admin');
                    $this->addError($message, 'admin');
                    break;
                } else {
                    $productIds[] = $collectionProduct->getProduct()->getId();
                }
            }

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();

                $formFile = $form['import_file']->getData();
                if (!empty($formFile)) {
                    $data = $this->getImportData($formFile);

                    /**
                     * validation
                     */
                    if ($data === false) {
                        // VALID_021: csv invalid format
                        $this->addErrors(trans('admin.common.csv_invalid_format'));

                        return $this->renderWithError($form);
                    }

                    $getId = function ($item) {
                        return $item['id'];
                    };
                    $requireHeader = array_keys(array_map($getId, array_filter($headers, function ($value) {
                        return $value['required'];
                    })));

                    $columnHeaders = $data->getColumnHeaders();
                    if (count(array_diff($requireHeader, $columnHeaders)) > 0) {
                        $this->addErrors(trans('admin.common.csv_invalid_format'));

                        return $this->renderWithError($form, $headers, false);
                    }

                    $productIdCol = trans('admin.product.product_id');
                    $productIds = [];
                    foreach ($data as $line => $row) {
                        if (!array_key_exists($productIdCol, $row)
                            || $row[$productIdCol] === null) {
                            // VALID_023: required column is empty
                            $this->addErrors(trans('admin.common.csv_invalid_required', ['%line%' => $line, '%name%' => $productIdCol]));

                            return $this->renderWithError($form);
                        }

                        if (!is_numeric($row[$productIdCol])) {
                            // VALID_007: value is not numeric
                            // since numeric_only validation message is already defined in validatior.yaml,
                            // define anew.
                            $this->addErrors(trans('collection.admin.collection.csv.validation.numeric_only'));

                            return $this->renderWithError($form);
                        }

                        if (in_array($row[$productIdCol], $productIds)) {
                            $this->addErrors(trans('collection.admin.collection.csv.validation.unique'));

                            return $this->renderWithError($form);
                        }

                        // this row passed the validation
                        $productIds[] = $row[$productIdCol];
                    }

                    /**
                     * delete CollectionProducts
                     */
                    foreach ($Collection->getCollectionProducts() as $CollectionProduct) {
                        $Collection->removeCollectionProduct($CollectionProduct);
                        $em->remove($CollectionProduct);

                    }

                    /**
                     * add CollectionProducts defined in csv file
                     */
                    foreach ($productIds as $index => $productId) {
                        $CollectionProduct = new CollectionProduct;
                        $Product = $this->productRepository->find($productId);
                        if ($Product === null) {
                            // VALID_022: if product ID defined in csv file does not existed
                            $this->addErrors(trans('admin.common.csv_invalid_not_found', ['%line%' => $line, '%name%' => $productId]));

                            return $this->renderWithError($form);
                        }

                        $CollectionProduct
                            ->setCollection($Collection)
                            ->setProduct($Product)
                            ->setSortNo(count($productIds) - $index);
                        $em->persist($CollectionProduct);
                    }

                } else {
                    // there is no input csv

                    /**
                     * update CollectionProduct
                     */
                    $CollectionProducts = $form['CollectionProducts']->getData();
                    foreach ($CollectionProducts as $CollectionProduct) {
                        // CollectionProducts deleted on ui has been temporarily set CollectionProduct::INVALID_SORT_NO
                        // to remove it here.
                        if ($CollectionProduct->getSortNo() === CollectionProduct::INVALID_SORT_NO) {
                            $em->remove($CollectionProduct);

                        } else {
                            $em->persist($CollectionProduct);
                        }

                    }
                }

                //画像の削除
                $delete_images = $form->get('delete_images')->getData();
                foreach ($delete_images as $delete_image) {
                    $Collection->setFileName(null);
                    $fs = new Filesystem();
                    $fs->remove($this->eccubeConfig['eccube_save_image_dir'] . '/' . $delete_image);
                }

                // 画像の登録
                $add_images = $form->get('add_images')->getData();
                foreach ($add_images as $add_image) {
                    $Collection->setFileName($add_image);
                    $file = new File($this->eccubeConfig['eccube_temp_image_dir'] . '/' . $add_image);
                    $file->move($this->eccubeConfig['eccube_save_image_dir']);
                }


                // update Collection
                if ($request->get('_route') === 'admin_collection_new') {
                    $maxSortNo = $this->collectionRepository->getMaxSortNo();
                    $Collection->setSortNo($maxSortNo + 1);
                }
                $Collection->setUpdateDate(new \DateTime());
                $em->persist($Collection);

                // flush database
                $em->flush();

                // display success message when error doesn't occur
                $this->session->getFlashBag()->add('eccube.admin.success', trans('admin.common.save_complete'));

                return $this->redirectToRoute('admin_collection_edit', ['collection_id' => $Collection->getId()]);

            }

        }

        // search product modal
        $searchProductModalForm = $this->formFactory->createBuilder(SearchProductModalType::class)->getForm();

        return [
            'form' => $form->createView(),
            'searchProductModalForm' => $searchProductModalForm->createView(),
            'headers' => $headers,
            'errors' => $this->errors,
        ];
    }
    /**
     * @Route("/%eccube_admin_route%/collection/image/add", name="admin_product_collection_image_add", methods={"POST"})
     * @Template("@Collection/admin/edit.twig")
     */
    public function addImage(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $formParameters = $request->files;
        foreach ($formParameters as $formParameter) {
            if (array_key_exists('file_name', $formParameter)) {
                $images = $formParameter['file_name'];
            } else {
                $images = [];
            }
        }

        $files = [];
        foreach ($images as $image) {
            // ファイルフォーマット検証
            $mimeType = $image->getMimeType();
            if (0 !== strpos($mimeType, 'image')) {
                throw new UnsupportedMediaTypeHttpException();
            }

            // 拡張子
            $allowExtensions = ['gif', 'jpg', 'jpeg', 'png'];
            $extension = $image->getClientOriginalExtension();
            if (!in_array(strtolower($extension), $allowExtensions)) {
                throw new UnsupportedMediaTypeHttpException();
            }

            $filename = date('mdHis') . uniqid('_') . '.' . $extension;
            $image->move($this->eccubeConfig['eccube_temp_image_dir'], $filename);
            $files[] = $filename;
            break;
        }

        if (empty($files)) {
            return $this->json(['files' => null], 301);
        } else {
            return $this->json(['files' => $files], 200);
        }
    }

    /**
     * get defined csv header
     *
     * @return array
     */
    protected function getCsvHeader()
    {
        return [
            trans('admin.product.product_id') => [
                'id' => 'id',
                'description' => 'collection.admin.collection.csv.id_description',
                'required' => true,
            ]
        ];
    }

    /**
     * add error message
     *
     * @param string $message error message to add
     */
    protected function addErrors(string $message)
    {
        $this->errors[] = $message;
    }

    /**
     * get informations to render
     * this function is to force to render when error occurs
     *
     * @param $form
     * @return array
     */
    protected function renderWithError($form)
    {
        // delete temp file
        $this->removeUploadedFile();

        // search product modal
        $searchProductModalForm = $this->formFactory->createBuilder(SearchProductModalType::class)->getForm();

        return [
            'form' => $form->createView(),
            'searchProductModalForm' => $searchProductModalForm->createView(),
            'headers' => $this->getCsvHeader(),
            'errors' => $this->errors,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/collection/move_sort_no", name="admin_collection_move_sort_no", methods={"POST"})
     */
    public function moveSortNo(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        if ($this->isTokenValid()) {
            $sortNos = $request->request->all();
            foreach ($sortNos as $collectionId => $sortNo) {
                $Collection = $this->collectionRepository->find($collectionId);
                $Collection->setSortNo($sortNo);
                $this->entityManager->persist($Collection);
            }
            $this->entityManager->flush();

            return new Response();
        }
    }

    /**
     * @Route("/%eccube_admin_route%/collection/{collection_id}/reverse_visible", requirements={"collection_id" = "\d+"}, name="admin_collection_reverse_visible", methods={"PUT"})
     */
    public function reverseVisible(Request $request, $collection_id)
    {
        $this->isTokenValid();

        $Collection = $this->collectionRepository->find($collection_id);
        // bool reverse
        $toggled = ! (bool)($Collection->getVisible());
        $Collection->setVisible((int)$toggled);

        $this->entityManager->flush();

        if ($Collection->getVisible()) {
            $this->addSuccess(trans('admin.common.to_show_complete', ['%name%' => $Collection->getName()]), 'admin');
        } else {
            $this->addSuccess(trans('admin.common.to_hide_complete', ['%name%' => $Collection->getName()]), 'admin');
        }

        return $this->redirectToRoute('admin_collection');
    }

    /**
     * to download product ID CSV
     *
     * @Route("/%eccube_admin_route%/collection/{collection_id}/csv_download", requirements={"collection_id" = "\d+"}, name="admin_collection_edit_csv_download")
     */
    public function csvDownload(Request $request, $collection_id = null)
    {
        if ($collection_id === null) {
            // new
            $Collection = new Collection;

        } else {
            // edit
            $Collection = $this->collectionRepository->find($collection_id);
        }

        $CollectionProducts = $Collection->getCollectionProducts();
        $response = new StreamedResponse();
        $response->setCallback(function () use ($request, $CollectionProducts) {
            $rows = [];

            // output header
            $columns = [
                trans('admin.product.product_id'),
                trans('admin.product.name'),
                trans('admin.product.stock'),
            ];
            $rows[] = array_map(function ($column) {
                return mb_convert_encoding($column, $this->eccubeConfig['eccube_csv_export_encoding'], 'UTF-8');
            }, $columns);

            // output body
            foreach ($CollectionProducts as $CollectionProduct) {
                $newRow = [];

                // product id
                $newRow[] = $CollectionProduct->getProduct()->getId();

                // product name
                $newRow[] = $CollectionProduct->getProduct()->getName();

                // product stock
                $newRow[] = $this->calculateService->calculate($CollectionProduct->getProduct()->getId());

                $rows[] = array_map(function ($field) {
                    return mb_convert_encoding($field, $this->eccubeConfig['eccube_csv_export_encoding'], 'UTF-8');
                }, $newRow);
            }

            $fp = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($fp, $row, $this->eccubeConfig['eccube_csv_export_separator']);
            }
            fclose($fp);
        });

        // define filename
        $dt = new \DateTime;
        $filename = "collection_product_{$dt->format('YmdHis')}.csv";

        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename='.$filename);
        $response->send();

        return $response;
    }

    /**
     * display search product
     *
     * @param Request $request
     * @param int $page_no
     *
     * @return array
     * @Route("/%eccube_admin_route%/collection/search/product", name="plugin_collection_search_product")
     * @Route("/%eccube_admin_route%/collection/search/product/page/{page_no}", requirements={"page_no" = "\d+"}, name="plugin_collection_search_product_page")
     * @Template("@Collection/admin/search_product.twig")
     */
    public function searchProduct(Request $request, $page_no = null, Paginator $paginator)
    {
        if (!$request->isXmlHttpRequest()) {
            return [];
        }

        log_debug('Search product start.');

        $pageCount = $this->eccubeConfig['eccube_default_page_count'];
        $session = $this->session;
        if ('POST' === $request->getMethod()) {
            $page_no = 1;
            $searchData = [
                'name' => trim($request->get('id')),
            ];

            if ($categoryId = $request->get('category_id')) {
                $searchData['category_id'] = $categoryId;
            }

            $session->set('eccube.plugin.recommend.product.search', $searchData);
            $session->set('eccube.plugin.recommend.product.search.page_no', $page_no);
        } else {
            $searchData = (array) $session->get('eccube.plugin.recommend.product.search');
            if (is_null($page_no)) {
                $page_no = intval($session->get('eccube.plugin.recommend.product.search.page_no'));
            } else {
                $session->set('eccube.plugin.recommend.product.search.page_no', $page_no);
            }
        }

        //set parameter
        $searchData['id'] = $searchData['name'];

        if (!empty($searchData['category_id'])) {
            $searchData['category_id'] = $this->categoryRepository->find($searchData['category_id']);
        }

        $qb = $this->productRepository->getQueryBuilderBySearchDataForAdmin($searchData);

        /** @var \Knp\Component\Pager\Pagination\SlidingPagination $pagination */
        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $pageCount,
            ['wrap-queries' => true]
        );

        /** @var ArrayCollection */
        $arrProduct = $pagination->getItems();

        log_debug('Search product finish.');
        if (count($arrProduct) == 0) {
            log_debug('Search product not found.');
        }

        return [
            'pagination' => $pagination,
        ];
    }


    /**
     * calculate product's stock and price
     * for ajax
     *
     * @param Request $request
     * @param $product_id
     * @return string
     *
     * @Route("/%eccube_admin_route%/collection/calculate/{product_id}", requirements={"product_id" = "\d+"}, name="plugin_collection_calculate")
     */
    public function getStock(Request $request, $product_id)
    {
        return new Response(json_encode([
            'stock' => $this->calculateService->calculate($product_id),
            'price' => $this->calculateService->calculatePrice($product_id)
        ]));
    }
}
