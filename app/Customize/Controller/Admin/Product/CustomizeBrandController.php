<?php
namespace Customize\Controller\Admin\Product;

use Customize\Form\Type\BrandType;
use Customize\Repository\BrandRepository;
use Eccube\Controller\AbstractController;
use Customize\Entity\Brand;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Service\CsvExportService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\HttpFoundation\File\File;

class CustomizeBrandController extends AbstractController
{

    /**
     * @var brandRepository
     */
    protected $brandRepository;

    /**
     * @var CsvExportService
     */
    protected $csvExportService;

    public function __construct(BrandRepository $brandRepository,
    CsvExportService $csvExportService) {
        $this->brandRepository = $brandRepository;
        $this->csvExportService = $csvExportService;
    }
    /**
     * @Route("/%eccube_admin_route%/product/brand", name="admin_product_brand")
     * @Route("/%eccube_admin_route%/product/page/{page_no}", requirements={"page_no" = "\d+"}, name="product_brand_page")
     * @Template("@admin/Product/brand.twig")
     */

     /**
     * @Route("/%eccube_admin_route%/product/brand", name="admin_product_brand")
     * @Template("@admin/Product/brand.twig")
     *
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function index(Request $request)
    {
        $Brand = new Brand();
        $Brands = $this->brandRepository->getList();

        /**
         * 新規登録用フォーム
         **/
        $builder = $this->formFactory
            ->createBuilder(BrandType::class, $Brand);

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Brand' => $Brand,
            ],
            $request
        );

        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_PRODUCT_BRAND_INDEX_INITIALIZE, $event);

        $form = $builder->getForm();

        /**
         * 編集用フォーム
         */
        $forms = [];
        foreach ($Brands as $EditBrand) {
            $id = $EditBrand->getId();
            $forms[$id] = $this
                ->formFactory
                ->createNamed('brand_'.$id, BrandType::class, $EditBrand);
        }

        if ('POST' === $request->getMethod()) {
            /*
             * 登録処理
             */
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $brand = $form->getData();
                $now = new \DateTime('now', new \DateTimeZone('Asia/Tokyo'));

                $brand->setCreatedAt($now);
                $brand->setUpdatedAt($now);

                $this->brandRepository->save($brand);

                $this->dispatchComplete($request, $form, $form->getData());

                $this->addSuccess('admin.common.save_complete', 'admin');

                return $this->redirectToRoute('admin_product_brand');
            }
            /*
             * 編集処理
             */
            foreach ($forms as $editForm) {
                $editForm->handleRequest($request);
                if ($editForm->isSubmitted() && $editForm->isValid()) {
                    $this->brandRepository->save($editForm->getData());

                    $this->dispatchComplete($request, $editForm, $editForm->getData());

                    $this->addSuccess('admin.common.save_complete', 'admin');

                    return $this->redirectToRoute('admin_product_brand');
                }
            }
        }

        $formViews = [];
        foreach ($forms as $key => $value) {
            $formViews[$key] = $value->createView();
        }

        return [
            'form' => $form->createView(),
            'Brand' => $Brand,
            'Brands' => $Brands,
            'forms' => $formViews,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/product/brand/new", requirements={"id" = "\d+"}, name="admin_product_brand_new")
     * @Route("/%eccube_admin_route%/product/brand/{id}/edit", requirements={"id" = "\d+"}, name="admin_product_brand_edit")
     * @Template("@admin/Product/brand_edit.twig")
     *
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function edit(Request $request,$id = null)
    {
        $Brands = $this->brandRepository->getList();

        if (is_null($id)) {
            $Brand = new Brand();
        } else {
            $Brand = $this->brandRepository->find($id);
            if (!$Brand) {
                throw new NotFoundHttpException();
            }
        }
        /**
         * 新規登録用フォーム
         **/
        $builder = $this->formFactory
            ->createBuilder(BrandType::class, $Brand);

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Brand' => $Brand,
            ],
            $request
        );

        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_PRODUCT_BRAND_INDEX_INITIALIZE, $event);

        $form = $builder->getForm();
       

        /**
         * 編集用フォーム
         */
        $forms = [];
        foreach ($Brands as $EditBrand) {
            $id = $EditBrand->getId();
            $forms[$id] = $this
                ->formFactory
                ->createNamed('brand_'.$id, BrandType::class, $EditBrand);
        }
        if ('POST' === $request->getMethod()) {
            $brand = $form->getData();
            

            /*
             * 登録処理
             */
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $brand = $form->getData();
                $now = new \DateTime('now', new \DateTimeZone('Asia/Tokyo'));
                
                $add_images = $form->get('add_images')->getData();
                $delete_image = $form->get('delete_images')->getData();
                if(count($delete_image) > 0){
                    $brand->setImage("");
                }

                if(count($add_images) > 0){
                    $image_name = reset($add_images);
                    $brand->setImage($image_name);
                   
                    $file = new File($this->eccubeConfig['eccube_temp_image_dir'].'/'.$image_name);
                    $file->move($this->eccubeConfig['eccube_save_image_dir']);
                }

                $brand->setCreatedAt($now);
                $brand->setUpdatedAt($now);

                $this->brandRepository->save($brand);

                $this->dispatchComplete($request, $form, $form->getData());

                $this->addSuccess('admin.common.save_complete', 'admin');

                return $this->redirectToRoute('admin_product_brand');
            }
            /*
             * 編集処理
             */
            foreach ($forms as $editForm) {
                $editForm->handleRequest($request);
                if ($editForm->isSubmitted() && $editForm->isValid()) {
                    $this->brandRepository->save($editForm->getData());

                    $this->dispatchComplete($request, $editForm, $editForm->getData());

                    $this->addSuccess('admin.common.save_complete', 'admin');

                    return $this->redirectToRoute('admin_product_brand');
                }
            }
        }

        $formViews = [];
        foreach ($forms as $key => $value) {
            $formViews[$key] = $value->createView();
        }

        return [
            'form' => $form->createView(),
            'Brand' => $Brand,
            'Brands' => $Brands,
            'forms' => $formViews,
        ];
    }


    /**
     * @Route("/%eccube_admin_route%/product/brand/{id}/delete", requirements={"id" = "\d+"}, name="admin_product_brand_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Brand $Brand)
    {
        $this->isTokenValid();

        log_info('ブランド削除開始', [$Brand->getId()]);

        try {
            $this->brandRepository->delete($Brand);

            $event = new EventArgs(
                [
                    'Brand' => $Brand,
                ], $request
            );
            $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_PRODUCT_BRAND_DELETE_COMPLETE, $event);

            $this->addSuccess('admin.common.delete_complete', 'admin');

            log_info('ブランド削除完了', [$Brand->getId()]);
        } catch (\Exception $e) {
            log_info('ブランド削除エラー', [$Brand->getId(), $e]);

            $message = trans('admin.common.delete_error.foreign_key', ['%name%' => $Brand->getName()]);
            $this->addError($message, 'admin');
        }

        return $this->redirectToRoute('admin_product_brand');
    }

    /**
     * @Route("/%eccube_admin_route%/product/brand/sort_no/move", name="admin_product_brand_sort_no_move", methods={"POST"})
     */
    public function moveSortNo(Request $request)
    {
        if ($request->isXmlHttpRequest() && $this->isTokenValid()) {
            $sortNos = $request->request->all();
            foreach ($sortNos as $brandId => $sortNo) {
                /* @var $Brand \Customize\Entity\Brand */
                $Brand = $this->brandRepository
                    ->find($brandId);
                $Brand->setSortNo($sortNo);
                $this->entityManager->persist($Brand);
            }
            $this->entityManager->flush();
        }

        return new Response();
    }

    protected function dispatchComplete(Request $request, FormInterface $form, Brand $Brand)
    {
        $event = new EventArgs(
            [
                'form' => $form,
                'Brand' => $Brand,
            ],
            $request
        );

        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_PRODUCT_BRAND_INDEX_COMPLETE, $event);
    }

        /**
     * @Route("/%eccube_admin_route%/product/brand/image/add", name="admin_brand_image_add", methods={"POST"})
     */
    public function addImage(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }



        $images = $request->files->get('admin_product_brand');
        $allowExtensions = ['gif', 'jpg', 'jpeg', 'png'];
        $files = [];
        if (count($images) > 0) {
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