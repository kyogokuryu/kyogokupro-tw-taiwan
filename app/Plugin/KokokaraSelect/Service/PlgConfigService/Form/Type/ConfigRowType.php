<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/03/30
 */

namespace Plugin\KokokaraSelect\Service\PlgConfigService\Form\Type;


use Eccube\Form\Type\ToggleSwitchType;
use Plugin\KokokaraSelect\Service\PlgConfigService\ConfigHelper;
use Plugin\KokokaraSelect\Service\PlgConfigService\Entity\ConfigInterface;
use Plugin\KokokaraSelect\Service\PlgConfigService\Entity\ConfigOptionInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigRowType extends AbstractType
{

    protected $configHelper;

    public function __construct(ConfigHelper $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $form = $event->getForm();

                /** @var ConfigInterface $data */
                $data = $event->getData();

                if (!empty($data)) {

                    $options = [
                        'label' => $data->getName(),
                    ];

                    if ($this->configHelper->isSettingOption($data->getConfigKey())) {
                        $options = array_merge($options, $this->configHelper->getSettingOption($data->getConfigKey()));
                    }

                    switch ($data->getConfigType()) {
                        case ConfigHelper::TYPE_STRING:

                            $defaultOptions = [
                                'attr' => [
                                    'class' => 'col-6'
                                ]
                            ];

                            $options = array_merge($defaultOptions, $options);

                            $form
                                ->add('textValue', TextType::class, $options);
                            break;
                        case ConfigHelper::TYPE_CHOICE:
                            $form
                                ->add('intValue', ChoiceType::class, $options);
                            break;
                        case ConfigHelper::TYPE_BOOL:
                            $form
                                ->add('boolValue', ToggleSwitchType::class, $options);
                            break;
                        case ConfigHelper::TYPE_OPTIONS:

                            $defaultOptions = [
                                'expanded' => true,
                                'multiple' => true,
                            ];

                            $options = array_merge($defaultOptions, $options);

                            $form
                                ->add('optionsValue', ChoiceType::class, $options);
                            break;
                    }
                }

            });

        $builder
            ->add('configType', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('groupId', HiddenType::class, [
                'mapped' => false,
            ])
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {

                $form = $event->getForm();

                /** @var ConfigInterface $data */
                $data = $event->getData();

                if (!empty($data)) {
                    $form->get('configType')->setData($data->getConfigType());
                    $form->get('groupId')->setData($data->getGroupId());

                    if($data->getConfigType() == ConfigHelper::TYPE_OPTIONS) {
                        $optionsData = [];
                        /** @var ConfigOptionInterface $configOption */
                        foreach ($data->getConfigOptions() as $configOption) {
                            $optionsData[] = $configOption->getValue();
                        }
                        $form->get('optionsValue')->setData($optionsData);
                    }
                }

            });

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->configHelper->getEntityPlgConfig(),
        ]);
    }
}
