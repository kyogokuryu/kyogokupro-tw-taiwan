<?php

namespace Plugin\Paidy4\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Plugin\Paidy4\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ConfigType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * ConfigType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * Build config type form
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return type
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('public_key', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ パブリックキーが入力されていません。']),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_smtext_len']]),
                    new Assert\Regex([
                        'pattern' => '/^[[:graph:]]+$/i',
                        'message' => 'form_error.graph_only',
                    ]),
                ],
            ])

            ->add('secret_key', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ シークレットキーが入力されていません。']),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_smtext_len']]),
                    new Assert\Regex(
                        [
                            'pattern' => '/^[[:graph:]]+$/',
                            'message' => 'form_error.graph_only',
                        ]
                    ),
                ],
            ])

            ->add('logo_url', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_mtext_len']]),
                ],
            ])

            ->add('payment_method', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                ],
            ])

            ->add('store_name', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ ストア名が入力されていません。']),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                ],
            ])

            ->add('mail_title', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ 注文受付メール タイトルが入力されていません。']),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                ],
            ])

            ->add('mail_description_text', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ 注文受付メール 説明文が入力されていません。']),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_ltext_len']]),
                ],
            ])

            ->add('charge_type', ChoiceType::class, [
                'choices' => [
                    'Authorizeのみ' => $this->eccubeConfig['paidy']['charge_type']['authorize'],
                    'Authorize + Capture' => $this->eccubeConfig['paidy']['charge_type']['capture'],
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ チャージタイプが選択されていません。']),
                    new Assert\Regex([
                        'pattern' => "/^\d+$/u",
                        'message' => 'form_error.numeric_only',
                    ]),
                ],
                'multiple' => false,
                'expanded' => true,
            ])

            ->add('webhook_request_ip', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ Webhookリクエスト元IPが入力されていません。']),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_mtext_len']]),
                    new Assert\Regex([
                        'pattern' => "/^[\d.]+$/m",
                        'message' => '数字かピリオドで入力してください。',
                    ]),
                ],
            ])

            ->add('description_disp', CheckboxType::class, [
                'label' => 'paidy.admin.config.description_disp__msg',
                'value' => '1',
                'required' => false,
            ])

            ->add('description_text', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ Paidy決済の説明が入力されていません。']),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_ltext_len']]),
                ],
            ])

            ->add('link_disp', CheckboxType::class, [
                'label' => 'paidy.admin.config.link_disp__msg',
                'value' => '1',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
        ]);
    }
}
