<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\MypageReceipt2\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Plugin\MypageReceipt2\Entity\MypageReceipt2Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use Eccube\Form\Type\Master\OrderStatusType;

/**
 * Class MypageReceipt2ConfigType.
 */
class MypageReceipt2ConfigType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * MypageReceipt2ConfigType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('mypage_receipt2_enable', ChoiceType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'choices' => array(
                    '稼働' => '1',
                    '非稼働' => '0'
                ),
                'multiple'  => false,
                'expanded' => true,
            ])
            ->add('OrderStatus', OrderStatusType::class, [
                'required' => false,
            ])
        ;
    }

    /**
     * Config.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MypageReceipt2Config::class,
        ]);
    }
}
