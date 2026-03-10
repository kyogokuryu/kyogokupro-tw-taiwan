<?php

namespace Plugin\ECCUBE4LineIntegration\Form\Type;

use Eccube\Common\EccubeConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Eccube\Form\Type\ToggleSwitchType;
use Symfony\Component\Validator\Constraints as Assert;

class LineSettingType extends AbstractType
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
        $builder
                ->add('line_access_token', TextType::class, array(
                    'label' => 'line_access_token',
                    'required' => false,
                    'constraints' => array(
                        new Assert\Length(array('max' => $config['eccube_id_max_len'])),
                    ),
                ))
                ->add('line_channel_id', TextType::class, array(
                    'label' => 'line_channel_id',
                    'required' => false,
                    'constraints' => array(
                        new Assert\Length(array('max' => $config['eccube_id_max_len'])),
                    ),
                ))
                ->add('line_channel_secret', TextType::class, array(
                    'label' => 'line_channel_secret',
                    'required' => false,
                    'constraints' => array(
                        new Assert\Length(array('max' => $config['eccube_id_max_len'])),
                    ),
                ))
                ->add('cart_notify_is_enabled', ToggleSwitchType::class, array(
                    'label' => 'かご落ち機能を有効にする',
                    'required' => false,
                    'constraints' => array(
                        new Assert\NotBlank(),
                    ),
                ))
                ->add('cart_notify_past_day_to_notify', IntegerType::class, array(
                    'label' => 'cart_notify_past_day_to_notify',
                    'required' => false,
                    'attr' => array('min' => 0, 'max' => 100),
                ))
                ->add('cart_notify_max_cart_item_count', IntegerType::class, array(
                    'label' => 'cart_notify_max_cart_item_count',
                    'required' => false,
                    'attr' => array('min' => 1, 'max' => 10),
                ))
                ->add('cart_notify_base_url', TextType::class, array(
                    'label' => 'cart_notify_base_url',
                    'required' => false,
                    'constraints' => array(
                        new Assert\Length(array('max' => $config['eccube_id_max_len'])),
                        new Assert\Url(),
                    ),
                ))
                ->add('line_add_cancel_redirect_url', TextType::class, array(
                    'label' => 'line_add_cancel_redirect_url',
                    'required' => true,
                    'constraints' => array(
                        new Assert\Length(array('max' => $config['eccube_id_max_len'])),
                        new Assert\Url(),
                    ),
                ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'line_setting';
    }
}
