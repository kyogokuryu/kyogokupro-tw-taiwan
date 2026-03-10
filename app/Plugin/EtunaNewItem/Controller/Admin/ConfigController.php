<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) Takashi Otaki All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\EtunaNewItem\Controller\Admin;

use Plugin\EtunaNewItem\Form\Type\Admin\EtunaNewItemConfigType;
use Plugin\EtunaNewItem\Repository\EtunaNewItemConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ConfigController.
 */
class ConfigController extends \Eccube\Controller\AbstractController
{
    /**
     * @Route("/%eccube_admin_route%/etuna_new_item/config", name="etuna_new_item_admin_config")
     * @Template("@EtunaNewItem/admin/config.twig")
     *
     * @param Request $request
     * @param EtunaNewItemConfigRepository $configRepository
     *
     * @return array
     */
    public function index(Request $request, EtunaNewItemConfigRepository $configRepository)
    {
        $Config = $configRepository->get();
        $form = $this->createForm(EtunaNewItemConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            log_info('Etuna New item config', ['status' => 'Success']);
            $this->addSuccess('登録しました。', 'admin');

            return $this->redirectToRoute('etuna_new_item_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
