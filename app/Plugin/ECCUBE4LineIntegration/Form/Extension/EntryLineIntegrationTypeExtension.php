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

namespace Plugin\ECCUBE4LineIntegration\Form\Extension;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Customer;
use Eccube\Form\Type\Front\EntryType;
use Plugin\ECCUBE4LineIntegration\Controller\LineIntegrationController;
use Plugin\ECCUBE4LineIntegration\Entity\LineIntegration;
use Plugin\ECCUBE4LineIntegration\Repository\LineIntegrationRepository;
use Plugin\ECCUBE4LineIntegration\Repository\LineIntegrationSettingRepository;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints\NotNull;


class EntryLineIntegrationTypeExtension extends AbstractTypeExtension
{
    private $eccubeConfig;
    private $container;
    private $session;
    private $lineIntegrationRepository;
    private $lineIntegrationSettingRepository;
    private $lineIntegration;
    private $tokenStorage;
    private $authChecker;

    public function __construct(
        EccubeConfig $eccubeConfig,
        LineIntegrationRepository $lineIntegrationRepository,
        LineIntegrationSettingRepository $lineIntegrationSettingRepository,
        TokenStorageInterface $tokenStorage,
        ContainerInterface $container
    ) {
        $this->container = $container;
        $this->authChecker = $this->container->get('security.authorization_checker');
        $this->eccubeConfig = $eccubeConfig;
        $this->lineIntegrationRepository = $lineIntegrationRepository;
        $this->lineIntegrationSettingRepository = $lineIntegrationSettingRepository;
        $this->tokenStorage = $tokenStorage;
        $this->session = $this->container->get('session');

    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $line_notification_flg = true;    // デフォルトはLINE通知がON

        /** @var Customer $Customer */
        $Customer = $this->tokenStorage->getToken()->getUser();

        if ($this->authChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            /** @var LineIntegration $LineIntegration */
            $LineIntegration = $this->lineIntegrationRepository->findOneBy(['customer_id' => $Customer->getId()]);
            if (!is_null($LineIntegration)) {
                $line_notification_flg = $LineIntegration->getLineNotificationFlg();
            }
        }

        // LINEログインしている場合に表示
        $lineUserId = $this->session->get(LineIntegrationController::PLUGIN_LINE_INTEGRATION_SSO_USERID);
        if (!empty($lineUserId)) {
            $builder
                ->add('is_line_delete', CheckboxType::class, [
                    'required' => false,
                    'label' => '解除',
                    'mapped' => false,
                    'value' => '0',
                ])
                ->add('line_notification_flg', ChoiceType::class, [
                    'required' => true,
                    'label' => '接收LINE通知',
                    'choices' => array_flip([
                        '1' => '同意',
                        '0' => '不同意',
                    ]),
                    "eccube_form_options" => (isset($_REQUEST['mode']) && $_REQUEST['mode'] == "confirm")?[
                        "auto_render" => true,
                        "form_theme" => "ECCUBE4LineIntegration/Resource/template/entry_confirm_add_line_notification.twig",
                    ]:[],
                    'expanded' => true,
                    'multiple' => false,
                    'mapped' => false,
                    'data' => $line_notification_flg,
                ]);
        }

    }

    public function getExtendedType()
    {
        return EntryType::class;
    }

    public static function getExtendedTypes(): iterable
    {
        return [EntryType::class];
    }

}
