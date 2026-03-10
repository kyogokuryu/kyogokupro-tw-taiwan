<?php

namespace Customize\Form\Extension\Front;

use Eccube\Form\Type\Front\CustomerLoginType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Form\Validator\Email;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class CustomerLoginTypeExtension extends AbstractTypeExtension
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var AuthenticationUtils
     */
    protected $authenticationUtils;

    public function __construct(AuthenticationUtils $authenticationUtils, EccubeConfig $eccubeConfig)
    {
        $this->authenticationUtils = $authenticationUtils;
        $this->eccubeConfig = $eccubeConfig;
    }
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CustomerLoginType::class;
    }

    /**
     * Return the class of the type being extended.
     */
    public static function getExtendedTypes(): iterable
    {
        return [CustomerLoginType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('login_email', TextType::class, [
            'attr' => [
                'max_length' => $this->eccubeConfig['eccube_stext_len'],
            ],
            'constraints' => [
                new Assert\NotBlank(),
             //   new Email(['strict' => $this->eccubeConfig['eccube_rfc_email_check']]),
            ],
            'data' => $this->authenticationUtils->getLastUsername(),
        ]);
        $builder->add('login_memory', CheckboxType::class, [
            'required' => false,
        ]);
        $builder->add('login_pass', PasswordType::class, [
            'attr' => [
                'max_length' => $this->eccubeConfig['eccube_stext_len'],
            ],
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ]);
    }
}