<?php

namespace Plugin\ECCUBE4LineIntegration\Form\Type;

use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\Master\PrefType;
use Eccube\Form\Type\Master\CustomerStatusType;
use Eccube\Form\Type\Master\SexType;
use Eccube\Form\Type\PriceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints as Assert;

class LineSearchType extends AbstractType
{

    private $eccubeConfig;

    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $config = $this->eccubeConfig;
        $months = range(1, 12);
        $builder
            ->add('id', TextType::class, array(
                'label' => '会員ID',
                'required' => false,
                'constraints' => array(
                    new Assert\Length(array('max' => $config['eccube_id_max_len'])),
                ),
            ))
            ->add('email', TextType::class, array(
                'label' => 'メールアドレス',
                'required' => false,
                'constraints' => array(
                    new Assert\Length(array('max' => $config['eccube_id_max_len'])),
                ),
            ))
            ->add('name', TextType::class, array(
                'label' => '氏名',
                'required' => false,
                'constraints' => array(
                    new Assert\Length(array('max' => $config['eccube_id_max_len'])),
                ),
            ))
            ->add('pref', PrefType::class, array(
                'label' => '都道府県',
                'required' => false,
            ))
            ->add('customer_status', CustomerStatusType::class, array(
                'label' => '会員ステータス',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
            ))
            ->add('sex', SexType::class, array(
                'label' => '性別',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
            ))
            ->add('birth_month', ChoiceType::class, array(
                'label' => '誕生月',
                'required' => false,
                'choices' => array_combine($months, $months),
            ))
            ->add('birth_start', BirthdayType::class, array(
                'label' => '誕生日',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
            ))
            ->add('birth_end', BirthdayType::class, array(
                'label' => '誕生日',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
            ))
            ->add('buy_total_start', PriceType::class, array(
                'label' => '購入金額',
                'required' => false,
                'constraints' => array(
                    new Assert\Length(array('max' => $config['eccube_price_len'])),
                ),
            ))
            ->add('buy_total_end', PriceType::class, array(
                'label' => '購入金額',
                'required' => false,
                'constraints' => array(
                    new Assert\Length(array('max' => $config['eccube_price_len'])),
                ),
            ))
            ->add('buy_times_start', IntegerType::class, array(
                'label' => '購入回数',
                'required' => false,
                'constraints' => array(
                    new Assert\Length(array('max' => $config['eccube_int_len'])),
                ),
            ))
            ->add('buy_times_end', IntegerType::class, array(
                'label' => '購入回数',
                'required' => false,
                'constraints' => array(
                    new Assert\Length(array('max' => $config['eccube_int_len'])),
                ),
            ))
            ->add('create_date_start', DateType::class, array(
                'label' => '登録日',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
            ))
            ->add('create_date_end', DateType::class, array(
                'label' => '登録日',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
            ))
            ->add('update_date_start', DateType::class, array(
                'label' => '更新日',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
            ))
            ->add('update_date_end', DateType::class, array(
                'label' => '更新日',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
            ))
            ->add('last_buy_start', DateType::class, array(
                'label' => '最終購入日',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
            ))
            ->add('last_buy_end', DateType::class, array(
                'label' => '最終購入日',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
            ))
            ->add('buy_product_name', TextType::class, array(
                'label' => '購入商品名',
                'required' => false,
                'constraints' => array(
                    new Assert\Length(array('max' => $config['eccube_id_max_len'])),
                ),
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'line_search';
    }
}
