<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/06/23
 */

namespace Plugin\PinpointSale\Form\Extension;


use Eccube\Form\Type\Admin\ProductClassType;
use Plugin\PinpointSale\Entity\ProductPinpoint;
use Plugin\PinpointSale\Form\EventListener\ProductClassTypeEventListener;
use Plugin\PinpointSale\Form\Type\ProductPinpointType;
use Plugin\PinpointSale\Service\PinpointSaleHelper;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class ProductClassTypeExtension extends AbstractTypeExtension
{

    /** @var ProductClassTypeEventListener */
    private $formEventListener;

    /** @var PinpointSaleHelper */
    protected $pinpointSaleHelper;

    public function __construct(
        ProductClassTypeEventListener $formEventListener,
        PinpointSaleHelper $formHelper
    )
    {
        $this->formEventListener = $formEventListener;
        $this->pinpointSaleHelper = $formHelper;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('productPinpoints', CollectionType::class, [
                'entry_type' => ProductPinpointType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
            ])
            ->addEventListener(FormEvents::POST_SET_DATA, [
                $this->formEventListener, "postSetData"
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, [
                $this->formEventListener, "postSubmit"
            ]);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        // ソート条件
        // 1. 個別設定 (StartTime DESC)
        // 2. 共通設定 (SortNo DESC)
        usort($view['productPinpoints']->children, function (FormView $a, FormView $b) {

            /** @var ProductPinpoint $productPinpointA */
            $productPinpointA = $a->vars['data'];
            $pinpointA = $productPinpointA->getPinpoint();

            /** @var ProductPinpoint $productPinpointB */
            $productPinpointB = $b->vars['data'];
            $pinpointB = $productPinpointB->getPinpoint();

            return $this->pinpointSaleHelper->sortProductPinpoint($pinpointA, $pinpointB);
        });
    }

    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return ProductClassType::class;
    }
}
