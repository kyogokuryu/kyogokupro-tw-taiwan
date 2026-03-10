<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/04
 */

namespace Plugin\KokokaraSelect\Form\Type;


use Doctrine\Common\Persistence\ManagerRegistry;
use Eccube\Common\EccubeConfig;
use Eccube\Form\DataTransformer\EntityToIdTransformer;
use Plugin\KokokaraSelect\Entity\KsSelectItem;
use Plugin\KokokaraSelect\Entity\KsSelectItemGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SelectItemType extends AbstractType
{

    protected $doctrine;

    /** @var EccubeConfig */
    protected $eccubeConfig;

    public function __construct(
        ManagerRegistry $doctrine,
        EccubeConfig $eccubeConfig
    )
    {
        $this->doctrine = $doctrine;
        $this->eccubeConfig = $eccubeConfig;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add(
                $builder
                    ->create('KsSelectItem', HiddenType::class, [
                        'data_class' => null,
                        'constraints' => [
                            new Assert\NotBlank(),
                        ],
                    ])
                    ->addModelTransformer(new EntityToIdTransformer($this->doctrine->getManager(), KsSelectItem::class))
            )
            ->add('groupId', HiddenType::class, [
                'mapped' => false,
            ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {

            $form = $event->getForm();
            $data = $event->getData();

            if (empty($data)) return;

            /** @var KsSelectItem $ksSelectItem */
            $ksSelectItem = $data['KsSelectItem'];

            /** @var KsSelectItemGroup $ksSelectItemGroup */
            $ksSelectItemGroup = $ksSelectItem->getKsSelectItemGroup();

            $maxQuantity = $ksSelectItemGroup->getQuantity();

            $choices = [];
            for ($i = 0; $i <= $maxQuantity; $i++) {
                $choices[$i] = $i;
            }

            // 数量選択のコンボ作成
            $form
                ->add('quantity', ChoiceType::class, [
                    'choices' => $choices,
                    'expanded' => false,
                    'multiple' => false,
                    'attr' => [
                        'class' => 'ks_quantity',
                    ],
                ]);

        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {

    }
}
