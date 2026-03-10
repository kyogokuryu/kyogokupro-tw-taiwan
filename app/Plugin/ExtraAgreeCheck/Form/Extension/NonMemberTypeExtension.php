<?php

namespace Plugin\ExtraAgreeCheck\Form\Extension;

use Eccube\Form\Type\Front\NonMemberType;
use Plugin\ExtraAgreeCheck\Repository\ConfigRepository;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class NonMemberTypeExtension extends AbstractTypeExtension
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * ConfigController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var \Plugin\ExtraAgreeCheck\Entity\Config $Config */
        $Config = $this->configRepository->get();

        if ($Config->getNonmemberAddCheck()) {
            $builder->add('user_policy_check', CheckboxType::class, [
                'required' => true,
                'label' => null,
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function getExtendedType()
    {
        return NonMemberType::class;
    }

    /**
     * @return iterable
     */
    public static function getExtendedTypes()
    {
        return [NonMemberType::class];
    }
}
