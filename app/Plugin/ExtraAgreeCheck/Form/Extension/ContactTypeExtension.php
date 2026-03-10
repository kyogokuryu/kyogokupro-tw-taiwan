<?php

namespace Plugin\ExtraAgreeCheck\Form\Extension;

use Eccube\Form\Type\Front\ContactType;
use Plugin\ExtraAgreeCheck\Repository\ConfigRepository;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ContactTypeExtension extends AbstractTypeExtension
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

        if ($Config->getContactAddCheck()) {
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
        return ContactType::class;
    }

    /**
     * @return iterable
     */
    public static function getExtendedTypes()
    {
        return [ContactType::class];
    }
}
