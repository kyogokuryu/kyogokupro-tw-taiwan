<?php

namespace Plugin\JaccsPayment\Form\Type\Admin;

use Plugin\JaccsPayment\Entity\ShippingRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ShippingRequestType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $codeData = ShippingRequest::$DeliverCompanyCode;
        $codeChoices = [];
        foreach ($codeData as $key => $value) {
            $codeChoices[trans($value)] = $key;
        }

        $builder->add('delivery_slip_no', TextType::class, [
            'label' => trans('配送伝票番号'),
            'required' => false,
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length([
                    'max' => 20,
                    'min' => 5,
                ]),
                /*
                new Assert\Regex([
                    'pattern' => "/^[0-9-]{1,11}$|^[0-9-]{11}$/",
                    'message' => '半角英数字、[-]',
                ]),
                */
            ],
        ])->add('delivery_company_code', ChoiceType::class, [
            'label' => trans('運送会社'),
            'required' => false,
            'multiple' => false,
            'expanded' => false,
            'constraints' => [
                new Assert\Length([
                    'max' => 2,
                    'min' => 2,
                ]),
            ],
            'choices' => $codeChoices,
        ])->add('invoice_date', TextType::class, [
            'label' => trans('請求書発行日'),
            'required' => false,
            'constraints' => [
                /*
                new Assert\Regex([
                    'pattern' => "/^\d-+$/u",
                    'message' => 'form.type.numeric.invalid',
                ]),
                */
                new Assert\Length([
                    'max' => 10,
                    'min' => 10,
                ]),
            ],
        ]);
    }

    /**
     * @return null|string
     */
    public function getBlockPrefix()
    {
        return 'admin_jaccs_shipping_request';
    }
}