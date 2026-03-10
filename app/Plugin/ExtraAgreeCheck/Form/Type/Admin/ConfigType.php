<?php

namespace Plugin\ExtraAgreeCheck\Form\Type\Admin;

use Plugin\ExtraAgreeCheck\Entity\Config;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class ConfigType extends \Symfony\Component\Form\AbstractType
{
    /**
     * @param FormBuilderInterface  $builder
     * @param array                 $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nonmember_add_check', CheckboxType::class, [
                'label' => 'plugin.extra_agree_check.admin.add_check.label',
                'required' => false,
            ])
            ->add('nonmember_check_label', TextType::class, [
                'label' => null,
                'required' => false,
                'constraints' => [
                    new Length(['max' => 255]),
                ],
            ])
            ->add('contact_add_check', CheckboxType::class, [
                'label' => 'plugin.extra_agree_check.admin.add_check.label',
                'required' => false,
            ])
            ->add('contact_check_label', TextType::class, [
                'label' => null,
                'required' => false,
                'constraints' => [
                    new Length(['max' => 255]),
                ],
            ])
            ->add('auto_insert', CheckboxType::class, [
                'label' => 'plugin.extra_agree_check.admin.auto_insert.label',
                'required' => false,
            ]);

        // バリデーションの設定. 自動挿入にチェックが付いているときだけ検証する.
        $this->addValidations($builder);
    }

    /**
     * @param OptionsResolver   $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
        ]);
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addValidations(FormBuilderInterface $builder)
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $form->getData();

            if (!$form['auto_insert']->getData()) {
                // 自動挿入にチェックがついていない場合はバリデーションしない.
                return;
            }

            if ($form['nonmember_add_check']->getData() && empty($data['nonmember_check_label'])) {
                $form['nonmember_check_label']->addError(new FormError(trans('plugin.extra_agree_check.admin.check_label.error')));
            }
            if ($form['contact_add_check']->getData() && empty($data['contact_check_label'])) {
                $form['contact_check_label']->addError(new FormError(trans('plugin.extra_agree_check.admin.check_label.error')));
            }
        });
    }
}
