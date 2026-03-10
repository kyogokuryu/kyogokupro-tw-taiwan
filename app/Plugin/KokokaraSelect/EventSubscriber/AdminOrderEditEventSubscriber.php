<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/29
 */

namespace Plugin\KokokaraSelect\EventSubscriber;


use Doctrine\Common\Collections\ArrayCollection;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Order;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\OrderItemRepository;
use Plugin\KokokaraSelect\Form\Type\Admin\KsSelectItemSearchType;
use Plugin\KokokaraSelect\Service\KsOrderItemService;
use Plugin\KokokaraSelect\Service\KsOrderMailService;
use Plugin\KokokaraSelect\Service\KsService;
use Plugin\KokokaraSelect\Service\TwigRenderService\EventSubscriber\TwigRenderTrait;
use Plugin\KokokaraSelect\Service\VersionHelperTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormFactoryInterface;

class AdminOrderEditEventSubscriber implements EventSubscriberInterface
{

    use TwigRenderTrait;

    use VersionHelperTrait;

    /** @var FormFactoryInterface */
    protected $formFactory;

    protected $ksService;

    /** @var KsOrderItemService */
    protected $ksOrderItemService;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    public function __construct(
        FormFactoryInterface $formFactory,
        KsService $ksService,
        KsOrderItemService $ksOrderItemService,
        OrderItemRepository $orderItemRepository
    )
    {
        $this->formFactory = $formFactory;
        $this->ksService = $ksService;
        $this->ksOrderItemService = $ksOrderItemService;
        $this->orderItemRepository = $orderItemRepository;
    }

    /**
     * 受注編集画面テンプレート
     *
     * @param TemplateEvent $event
     */
    public function onTemplateAdminOrderEdit(TemplateEvent $event)
    {
        $this->initRenderService($event);

        // 選択商品用情報追加
        $child = $this->twigRenderService
            ->eachChildBuilder()
            ->findThis()
            ->find('td')
            ->eq(0)
            ->targetFindAndIndexKey('#kokokara_select_', 'kokokaraSelectIndex')
            ->setInsertModeAppend();

        $this->twigRenderService
            ->eachBuilder()
            ->find('#table-form-field')
            ->find('tbody > tr')
            ->setEachIndexKey('kokokaraSelectIndex')
            ->each($child->build());

        // 商品検索モーダル
        $this->createInsertBuilder()
            ->find('#addProduct')
            ->setTargetId('addKokokaraSelectProduct')
            ->setInsertModeAfter();

        $this->addTwigRenderSnippet(
            '@KokokaraSelect/admin/Order/kokokara_select_edit.twig',
            '@KokokaraSelect/admin/Order/kokokara_select_edit_js.twig'
        );

        // Form追加
        $builder = $this->formFactory
            ->createBuilder(KsSelectItemSearchType::class);

        $form = $builder->getForm();

        $event->setParameter('searchKokokaraSelectProductModalForm', $form->createView());

        // 選択受注判定
        $TargetOrder = $event->getParameter('Order');
        if ($TargetOrder) {

            // EC-CUBE 4.0.3 以降のみ固定選択商品の複数配送許可
            if ($this->isMultipleVersion()) {
                $checkOption = KsService::IS_KS_ORDER_NORMAL;
            } else {
                // 4.0.2以下
                $checkOption = KsService::IS_KS_ORDER_DEFAULT;
            }

            $selectOrder = $this->ksService->isKsOrder($TargetOrder, $checkOption);
        } else {
            $selectOrder = false;
        }

        if ($TargetOrder->getId()) {
            $OriginOrderItems = $this->orderItemRepository->findBy(['Order' => $TargetOrder]);
            $formCount = count($OriginOrderItems);
        } else {
            $formCount = 0;
        }

        $event->setParameter('ksFormIndexCount', $formCount);

        $event->setParameter('KsSelectOrder', $selectOrder);
    }

    /**
     * その他明細追加
     *
     * @param TemplateEvent $event
     */
    public function onAdminOrderOrderItemTypeTemplate(TemplateEvent $event)
    {
        /** @var ArrayCollection $OrderItemTypes */
        $OrderItemTypes = $event->getParameter('OrderItemTypes');

        /** @var OrderItemType $targetOrderItemType */
        $targetOrderItemType = $this->ksOrderItemService->getKsOrderItemType();

        /** @var OrderItemType $orderItemType */
        foreach ($OrderItemTypes as $item) {

            // 4.0.4以降の変更に対応
            if (is_array($item)) {
                $orderItemType = $item['OrderItemType'];
            } else {
                $orderItemType = $item;
            }

            if ($orderItemType->getId() == $targetOrderItemType->getId()) {
                $OrderItemTypes->removeElement($orderItemType);
            }
        }

        $event->setParameter('OrderItemTypes', $OrderItemTypes);
    }

    /**
     * 出荷登録
     *
     * @param TemplateEvent $event
     */
    public function onAdminOrderShippingTemplate(TemplateEvent $event)
    {
        $this->initRenderService($event);

        // 選択商品用情報追加
        $child = $this->twigRenderService
            ->eachChildBuilder()
            ->findThis()
            ->find('td')
            ->eq(0)
            ->targetFindAndIndexKey('#kokokara_select_', 'kokokaraSelectIndex')
            ->setInsertModeAppend();

        $this->twigRenderService
            ->eachBuilder()
            ->find('[id^=table-form-field_]')
            ->find('tbody > tr')
            ->setEachIndexKey('kokokaraSelectIndex')
            ->each($child->build());

        $this->addTwigRenderSnippet(
            '@KokokaraSelect/admin/Order/kokokara_select_shipping_edit.twig',
            '@KokokaraSelect/admin/Order/kokokara_select_shipping_edit_js.twig');
    }

    /**
     * メール通知
     *
     * @param EventArgs $event
     */
    public function onAdminOrderMailIndexChange(EventArgs $event)
    {

        $request = $event->getRequest();

        if ($request->request->has('mode')) {
            $mode = $request->get('mode');

            if ($mode == 'change' || $mode == 'confirm' || $mode == 'complete') {
                /** @var Order $order */
                $order = $event->getArgument('Order');

                $mailMsg = $order->getCompleteMailMessage();

                if (strpos($mailMsg, KsOrderMailService::KOKOKARA_SELECT_ADD_MAIL_MSG) !== false) {
                    // 処理不要
                    return;
                }

                // 選択商品情報追加
                $order->appendCompleteMailMessage(KsOrderMailService::KOKOKARA_SELECT_ADD_MAIL_MSG);
            }
        }

    }

    public static function getSubscribedEvents()
    {
        return [
            // 受注編集
            '@admin/Order/edit.twig' => 'onTemplateAdminOrderEdit',
            // その他明細追加
            '@admin/Order/order_item_type.twig' => ['onAdminOrderOrderItemTypeTemplate'],
            // 出荷登録
            '@admin/Order/shipping.twig' => ['onAdminOrderShippingTemplate'],
            // メール通知
            EccubeEvents::ADMIN_ORDER_MAIL_INDEX_INITIALIZE => ['onAdminOrderMailIndexChange'],
        ];
    }
}
