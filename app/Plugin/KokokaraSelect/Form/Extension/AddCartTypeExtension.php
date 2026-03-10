<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/04
 */

namespace Plugin\KokokaraSelect\Form\Extension;


use Eccube\Entity\Product;
use Eccube\Form\Type\AddCartType;
use Plugin\KokokaraSelect\Form\Type\SelectItemType;
use Plugin\KokokaraSelect\Service\KsCartHelper;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddCartTypeExtension extends AbstractTypeExtension
{

    /** @var KsCartHelper */
    private $ksCartHelper;

    public function __construct(
        KsCartHelper $ksCartHelper
    )
    {
        $this->ksCartHelper = $ksCartHelper;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /* @var $Product Product */
        $Product = $options['product'];

        // ここから選択商品のみ拡張
        if ($options['kokokara_select']) {

            $editId = $options['ks_cart_edit_id'];
            $selectItemsData = $this->ksCartHelper->getFormSelectItemsData($Product, $editId);

            $builder
                ->add('selectItems', CollectionType::class, [
                    'entry_type' => SelectItemType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'prototype' => true,
                    'mapped' => false,
                    'data' => $selectItemsData,
                ])
                ->add('ksCartKey', HiddenType::class, [
                    'mapped' => false,
                    'data' => $editId,
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'kokokara_select' => false,
            'ks_cart_edit_id' => null,
        ]);
    }

    public function getExtendedType()
    {
        return AddCartType::class;
    }
}
