<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\JaccsPayment\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\JaccsPayment\Form\Type\Admin\ConfigType;
use Plugin\JaccsPayment\Repository\ConfigRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Class ConfigController
 */
class ConfigController extends AbstractController
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
    public function __construct(
        ConfigRepository $configRepository
    ) {
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/jaccs_payment/config", name="jaccs_payment_admin_config")
     * @Template("@JaccsPayment/admin/config.twig")
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->get();
        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            $this->addSuccess('jaccs_payment.admin.save.success', 'admin');

            return $this->redirectToRoute('jaccs_payment_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
