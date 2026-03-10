<?php

namespace Customize\Form\Extension;

use Eccube\Form\Type\AddCartType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

class AddCartTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $Product = $options['product'];

        if ($Product->getStockFind()) {
//log_info("[AddcartTye]", $options);
            if(isset($options["kokokara_select"]) && $options["kokokara_select"] == true){
                $builder->add('quantity', HiddenType::class, ['attr'=>["value"=>"1"]]);            
            }else{
                $builder->add('quantity', ChoiceType::class, [
                    'choices' => array_combine(range(1,30), range(1,30))
                ]);
            }
        }
    }
    /**
    * {@inheritdoc}
    */
    public function getExtendedType()
    {
        return AddCartType::class;
    }
}