<?php
/*
* Plugin Name : [code]
*/

namespace Plugin\[code]\Form\Type;

use Eccube\Common\EccubeConfig;
use Eccube\Repository\CategoryRepository;
use Eccube\Repository\TagRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class [code]ConfigType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
    * @var ContainerInterface
    */
    protected $containerInterface;

    /**
     * [code]ConfigType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig, ContainerInterface $container)
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->containerInterface = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
        ->add('title', TextType::class, [
            'label' => 'title',
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ])
        ->add('display_order', ChoiceType::class, [
            'label' => '表示順序',
            'choices' => array_flip([
                '0' => '24時間',
                '1' => '週間',
                '2' => '月間',
                '3' => '年間',
            ]),
            'expanded' => false,
            'multiple' => false,
            'placeholder' => false,
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ])
        ->add('block_type', ChoiceType::class, [
            'label' => 'ブロックタイプ',
            'choices' => array_flip([
                '0' => trans('admin.common.choiceType.default'),
                '1' =>  trans('admin.common.choiceType.sidebar'),
                '2' =>  trans('admin.common.choiceType.slides'),
            ]),
            'expanded' => false,
            'multiple' => false,
            'placeholder' => false,
            'constraints' => [
                new Assert\NotBlank(),
       
            ],
        ])
        ->add('display_num', IntegerType::class, [
            'label' => '表示数',
            'attr' => array_flip([
                'min' => 0,
                'max' => 99
            ]),
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ])
        ->add('category_id', ChoiceType::class, [
            'label' => 'カテゴリー',
            'choices' => array_flip($this->getCategoryList()),
            'expanded' => false,
            'multiple' => false,
            'placeholder' => false,
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ])
        ->add('item_name', ChoiceType::class, [
            'label' => '商品名の表示',
            'choices' => array_flip([
                '0' =>  trans('admin.common.choiceType.yes'),
                '1' =>  trans('admin.common.choiceType.no'),
            ]),
            'expanded' => false,
            'multiple' => false,
            'placeholder' => false,
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ])
        ->add('item_price', ChoiceType::class, [
            'label' => '商品価格の表示',
            'choices' => array_flip([
                '0' =>  trans('admin.common.choiceType.yes'),
                '1' =>  trans('admin.common.choiceType.no'),
            ]),
            'expanded' => false,
            'multiple' => false,
            'placeholder' => false,
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ])
        ->add('item_description', ChoiceType::class, [
            'label' => '商品説明の表示',
            'choices' => array_flip([
                '0' =>  trans('admin.common.choiceType.yes'),
                '1' =>  trans('admin.common.choiceType.no'),
            ]),
            'expanded' => false,
            'multiple' => false,
            'placeholder' => false,
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ]);
    }

    public function getCategoryList()
    {
        $Categories = $this->containerInterface->get(CategoryRepository::class)->findBy(
            array('hierarchy' => 1),
            array('sort_no' => 'ASC')
        );
        $category_array = array(
            '0' => '未選択',
        );
        foreach ($Categories as $category) {
            if( $category['hierarchy'] == 1 ){
                $category_child = $category->getSelfAndDescendants();
                foreach ($category_child as $child) {
                    $category_array += array(
                        $child['id'] => $child->getNameWithLevel(),
                    );
                }
            }
        }

        return $category_array;
    }
}
