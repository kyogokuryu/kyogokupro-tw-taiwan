<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/05
 */

namespace Plugin\PinpointSale\Form\Type;


use Plugin\PinpointSale\Form\Transformer\CustomDateTimeTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CustomDateTimeType extends AbstractType
{

    // 必須なし
    const BLANK_MODE_NONE = 0;

    // 日付・時間必須
    const BLANK_MODE_ALL = 1;

    // 日付入力時・時間必須
    const BLANK_MODE_INPUT_DAY = 2;

    /** @var ValidatorInterface */
    protected $validator;

    public function __construct(
        ValidatorInterface $validator
    )
    {
        $this->validator = $validator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $dateName = $options['date_name'];
        $timeName = $options['time_name'];

        $blankMode = $options['blank_mode'];

        if ($blankMode == self::BLANK_MODE_ALL) {
            $constraints = [
                new Assert\NotBlank()
            ];
        } else {
            $constraints = [];
        }

        $builder
            ->add($dateName, DateType::class, [
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'required' => false,
                'placeholder' => [
                    'year' => '----', 'month' => '--', 'day' => '--'
                ],
                'constraints' => $constraints,
                'attr' => [
                    'class' => 'pinpoint_custom_date_type datetimepicker-input',
                    'data-toggle' => 'datetimepicker',
                ]
            ])
            ->add($timeName, TimeType::class, [
                'input' => 'datetime',
                'widget' => 'choice',
                'required' => false,
                'placeholder' => [
                    'hour' => '--', 'minute' => '--',
                ],
                'constraints' => $constraints,
            ]);

        $builder
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($dateName, $timeName, $blankMode) {

                $form = $event->getForm();

                if (self::BLANK_MODE_INPUT_DAY == $blankMode) {
                    if ($form->get($dateName)->getData()) {
                        $errors = $this->validator->validate($form->get($timeName)->getData(), [new Assert\NotBlank()]);

                        /** @var ConstraintViolation $error */
                        foreach ($errors as $error) {
                            $form->get($timeName)->addError(new FormError(
                                $error->getMessage(),
                                $error->getMessageTemplate(),
                                $error->getParameters(),
                                $error->getPlural(),
                                $error->getCause()
                            ));
                        }
                    }
                }
            });

        // Transformer
        $builder->addModelTransformer(new ReversedTransformer(
            new CustomDateTimeTransformer()
        ));

        $builder->setAttribute('date_name', $options['date_name']);
        $builder->setAttribute('time_name', $options['time_name']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $builder = $form->getConfig();
        $view->vars['date_name'] = $builder->getAttribute('date_name');
        $view->vars['time_name'] = $builder->getAttribute('time_name');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'date_name' => 'custom_date',
            'time_name' => 'custom_time',
            'blank_mode' => self::BLANK_MODE_NONE,
        ]);
    }

}
