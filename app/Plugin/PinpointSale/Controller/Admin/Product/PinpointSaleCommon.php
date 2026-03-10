<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/31
 */

namespace Plugin\PinpointSale\Controller\Admin\Product;


use Eccube\Controller\AbstractController;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Plugin\PinpointSale\Config\ConfigSetting;
use Plugin\PinpointSale\Entity\Pinpoint;
use Plugin\PinpointSale\Entity\ProductPinpoint;
use Plugin\PinpointSale\Form\Type\PinpointType;
use Plugin\PinpointSale\Repository\PinpointRepository;
use Plugin\PinpointSale\Service\PlgConfigService\ConfigService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class PinpointSaleCommon extends AbstractController
{

    /** @var Pinpoint */
    protected $pinpointRepository;

    /** @var ConfigService */
    protected $configService;

    public function __construct(
        PinpointRepository $pinpointRepository,
        ConfigService $configService
    )
    {
        $this->pinpointRepository = $pinpointRepository;
        $this->configService = $configService;
    }

    /**
     * @Route("/%eccube_admin_route%/product/pinpoint_sale_common", name="admin_pinpoint_sale_common")
     * @Template("@PinpointSale/admin/Product/pinpoint_sale_common.twig")
     *
     * @param Request $request
     * @return array|RedirectResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function index(Request $request)
    {

        // 一覧情報
        $pinpointSales = $this->pinpointRepository->getList(Pinpoint::TYPE_COMMON);

        $pinpoint = new Pinpoint();

        // 新規登録フォーム
        $builder = $this->formFactory
            ->createBuilder(PinpointType::class, $pinpoint, [
                'pinpoint_sale_common' => true,
            ]);

        $form = $builder->getForm();

        // 編集フォーム
        $forms = [];
        /** @var Pinpoint $pinpointSale */
        foreach ($pinpointSales as $pinpointSale) {
            $key = $pinpointSale->getId();
            $forms[$key] = $this->formFactory
                ->createNamed('pinpoint_sale_common_' . $key, PinpointType::class,
                    $pinpointSale,
                    [
                        'pinpoint_sale_common' => true,
                    ]);
        }

        if ('POST' === $request->getMethod()) {

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                log_info('タイムセール設定新規登録開始', [$pinpoint->toArray()]);

                // 保存
                $this->pinpointRepository->save($pinpoint);
                $this->addSuccess('admin.common.save_complete', 'admin');

                log_info('タイムセール設定新規登録終了');

                return $this->redirectToRoute('admin_pinpoint_sale_common');
            }

            // 編集処理
            /** @var FormInterface $editForm */
            foreach ($forms as $editForm) {
                $editForm->handleRequest($request);
                if ($editForm->isSubmitted() && $editForm->isValid()) {

                    $pinpoint = $editForm->getData();

                    log_info('タイムセール設定更新開始', [$pinpoint->toArray()]);

                    // 保存
                    $this->pinpointRepository->save($pinpoint);
                    $this->entityManager->flush();

                    $this->addSuccess('admin.common.save_complete', 'admin');

                    log_info('タイムセール設定新規登録終了');

                    return $this->redirectToRoute('admin_pinpoint_sale_common');
                }
            }

        }

        $formViews = [];
        foreach ($forms as $key => $value) {
            $formViews[$key] = $value->createView();
        }

        return [
            'form' => $form->createView(),
            'forms' => $formViews,
            'pinpointSales' => $pinpointSales,
        ];
    }

    /**
     * 削除処理
     *
     * @Route("/%eccube_admin_route%/product/pinpoint_sale_common/{id}/delete", name="admin_pinpoint_sale_common_delete", methods={"DELETE"})
     *
     * @param Request $request
     * @param Pinpoint $pinpoint
     * @return RedirectResponse
     */
    public function delete(Request $request, Pinpoint $pinpoint)
    {
        $this->isTokenValid();

        log_info('タイムセール設定削除開始', [$pinpoint->getId()]);

        try {

            // 削除
            $this->pinpointRepository->delete($pinpoint);
            $this->entityManager->flush();

            $this->addSuccess('admin.common.delete_complete', 'admin');

            log_info('タイムセール設定削除終了', [$pinpoint->getId()]);

        } catch (\Exception $e) {
            log_info('タイムセール設定削除エラー', [$pinpoint->getId(), $e]);

            $this->addError('admin.common.delete_error', 'admin');
        }

        return $this->redirectToRoute('admin_pinpoint_sale_common');
    }

    /**
     * 並び替え
     *
     * @Route("/%eccube_admin_route%/product/pinpoint_sale_common/sort_no/move", name="admin_pinpoint_sale_common_sort_no_move", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function moveSortNo(Request $request)
    {
        if ($request->isXmlHttpRequest() && $this->isTokenValid()) {
            $sortNos = $request->request->all();
            foreach ($sortNos as $pinpointId => $sortNo) {
                /* @var $pinpoint Pinpoint */
                $pinpoint = $this->pinpointRepository
                    ->find($pinpointId);
                $pinpoint->setSortNo($sortNo);
                $this->entityManager->persist($pinpoint);
            }
            $this->entityManager->flush();
        }

        return new Response();
    }

    /**
     * @Route("/%eccube_admin_route%/product/pinpoint_sale/{id}/load", name="admin_product_pinpoint_sale_load", methods={"GET"}, requirements={"id" = "\d+"})
     * @Template("@PinpointSale/admin/Product/index/pinpoint_sale_popup.twig")
     *
     * @param Request $request
     * @param Product $product
     * @return array
     */
    public function loadPinpointSale(Request $request, Product $product)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $data = [];
        if (!$product) {
            throw new NotFoundHttpException();
        }

        // タイムセール名称取得
        $discountTitle = $this->configService->getKeyString(ConfigSetting::SETTING_KEY_DISCOUNT_NAME);

        $hasProductClass = $product->hasProductClass();

        /** @var ProductClass $productClass */
        foreach ($product->getProductClasses() as $productClass) {

            if (!$productClass->isVisible()) continue;

            $class1Name = "";
            if ($productClass->getClassCategory1()) {
                $class1Name = $productClass->getClassCategory1()->getName();
            }

            $class2Name = "";
            if ($productClass->getClassCategory2()) {
                $class2Name = $productClass->getClassCategory2()->getName();
            }

            if ($productClass->getProductPinpoints()->count() > 0) {

                $productPinpoints = $productClass->getProductPinpoints();

                // 限定セール対象
                /** @var ProductPinpoint $productPinpoint */
                foreach ($productPinpoints as $productPinpoint) {

                    $pinpoint = $productPinpoint->getPinpoint();
                    $data[] = [
                        'class1Name' => $class1Name,
                        'class2Name' => $class2Name,
                        'pinpoint' => $pinpoint,
                    ];
                }
            }
        }

        return [
            'discountName' => $discountTitle,
            'data' => $data,
            'hasProductClass' => $hasProductClass,
        ];
    }
}
