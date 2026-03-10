<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Form\Type\Front;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Customer;
use Eccube\Form\Type\AddressType;
use Eccube\Form\Type\KanaType;
use Eccube\Form\Type\Master\JobType;
use Eccube\Form\Type\Master\SexType;
use Eccube\Form\Type\NameType;
use Eccube\Form\Validator\Email;//20220224 kikuzawa
use Symfony\Component\Form\Extension\Core\Type\EmailType;//20220224 kikuzawa
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;//20220601 kikuzawa
// use Eccube\Form\Type\RepeatedEmailType;
// use Eccube\Form\Type\RepeatedPasswordType;
use Eccube\Form\Type\PhoneNumberType;
use Eccube\Form\Type\PostalType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EntryType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * EntryType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        //紹介特典idに自身の会員idは設定できない 20220808 kikuzawa
        $exclude_id = '/^'.$options['data']['id'].'$/u';

        $builder
            ->add('name', NameType::class, [
                'required' => true,
            ])
            ->add('company_name', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_stext_len'],
                    ]),
                ],
            ])
            ->add('postal_code', PostalType::class)
            ->add('address', AddressType::class)
            ->add('phone_number', PhoneNumberType::class, [
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Email(['strict' => $this->eccubeConfig['eccube_rfc_email_check']]),
                ],
                'error_bubbling' => false,
                'trim' => true,
            ])
            ->add('password', TextType::class, [
                'required' => true,
                'error_bubbling' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'min' => $this->eccubeConfig['eccube_password_min_len'],
                        'max' => $this->eccubeConfig['eccube_password_max_len'],
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[[:graph:][:space:]]+$/i',
                        'message' => 'form_error.graph_only',
                    ]),
                ],
            ])
            // ->add('email', RepeatedEmailType::class)
            // ->add('password', RepeatedPasswordType::class)
            ->add('birth', BirthdayType::class, [
                'required' => false,//必須に変更 20220224 kikuzawa
                'input' => 'datetime',
                'years' => range(date('Y'), date('Y') - $this->eccubeConfig['eccube_birth_max']),
                'widget' => 'choice',
                'format' => 'yyyy/MM/dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'constraints' => [
                    //new Assert\NotBlank(),//制約追加 20220224 kikuzawa
                    new Assert\LessThanOrEqual([
                        'value' => date('Y-m-d', strtotime('-1 day')),
                        'message' => 'form_error.select_is_future_or_now_date',
                    ]),
                ],
            ])
            ->add('sex', SexType::class, [
                'required' => false,//必須に変更 20220224 kikuzawa
                //'constraints' => [
                //    new Assert\NotBlank(),//制約追加 20220224 kikuzawa
                //],
            ])
            ->add('job', JobType::class, [
                'required' => false,
            ])
            //会員情報にサロン(親ユーザー)と紐づける項目追加 20220510 kikuzawa
            ->add('salon_id', TextType::class, [
                'required' => false,
                'label' => 'サロンID',
                'constraints' => [
                    new Assert\Type([
                    'type' => 'numeric',
                    'message' => 'サロンIDを入力してください',
                    ]),
                //    new Assert\Regex(array('pattern' => $exclude_id, 'match' => false )),
                ],
            ])
            ->add('financial', TextType::class, [
                'required' => false,
                'label' => '金融機関名',
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_stext_len'],
                    ]),
                ],
            ])
            ->add('branch', TextType::class, [
                'required' => false,
                'label' => '支店名',
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_stext_len'],
                    ]),
                ],
            ])
            ->add('account_type', ChoiceType::class, [
                'label' => '口座種別',
                'choices' => [
                    '普通' => 1,
                    '当座' => 2,
                ],
                'expanded' => true,
            ])
            ->add('account_number', TextType::class, [
                'required' => false,
                'label' => '口座番号',
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_stext_len'],
                    ]),
                ],
            ])
            ->add('account_name', TextType::class, [
                'required' => false,
                'label' => '口座名義人名(カナ)',
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_stext_len'],
                    ]),
                    new Assert\Regex(array('pattern' => "/^[ァ-ヶｦ-ﾟー]+$/u", )),
                ],
            ]);
            //end 会員情報にサロン(親ユーザー)と紐づける項目追加 20220510 kikuzawa

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $Customer = $event->getData();
            if ($Customer instanceof Customer && !$Customer->getId()) {
                // $form = $event->getForm();

                // $form->add('user_policy_check', CheckboxType::class, [
                //         'required' => true,// 必須にする
                //         'label' => null,
                //         'mapped' => false,
                //         'constraints' => [
                //             new Assert\NotBlank(['message' => '利用規約、プライバシーポリシーに同意してください']), //変更//制約追加 20220224 kikuzawa
                //         ],
                //     ]);
            }
        }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Eccube\Entity\Customer',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        // todo entry,mypageで共有されているので名前を変更する
        return 'entry';
    }
}
