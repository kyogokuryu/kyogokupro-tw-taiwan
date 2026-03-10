<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\MypageReceipt2\Controller\Admin;

use Plugin\MypageReceipt2\Form\Type\Admin\MypageReceipt2ConfigType;
use Plugin\MypageReceipt2\Repository\MypageReceipt2ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ConfigController.
 */
class ConfigController extends \Eccube\Controller\AbstractController
{

    protected $configRepository;

    public function __construct(MypageReceipt2ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/mypage_receipt2/config", name="mypage_receipt2_admin_config")
     * @Template("@MypageReceipt2/admin/config.twig")
     *
     * @param Request $request
     * @param MypageReceipt2ConfigRepository $configRepository
     *
     * @return array
     */
    public function index(Request $request, MypageReceipt2ConfigRepository $configRepository)
    {
        $Config = $configRepository->get();
        $form = $this->createForm(MypageReceipt2ConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            log_info('Etuna checked item config', ['status' => 'Success']);
            $this->addSuccess('登録しました。', 'admin');

            return $this->redirectToRoute('mypage_receipt2_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
