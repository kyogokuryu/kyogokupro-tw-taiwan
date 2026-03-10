<?php
/*
* Plugin Name : uc_banner_cbd01
*/

namespace Plugin\uc_banner_cbd01\Controller;

use Eccube\Controller\AbstractController;
use Plugin\uc_banner_cbd01\Entity\uc_banner_cbd01Config;
use Plugin\uc_banner_cbd01\Entity\uc_banner_cbd01Data;
use Plugin\uc_banner_cbd01\Form\Type\uc_banner_cbd01ConfigType;
use Plugin\uc_banner_cbd01\Repository\uc_banner_cbd01ConfigRepository;
use Plugin\uc_banner_cbd01\Form\Type\uc_banner_cbd01DataType;
use Plugin\uc_banner_cbd01\Repository\uc_banner_cbd01DataRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ConfigController.
 */
class ConfigController extends AbstractController
{
    /**
     * @Route("/%eccube_admin_route%/uc_banner_cbd01/config", name="uc_banner_cbd01_admin_config")
     * @Template("@uc_banner_cbd01/admin/config.twig")
     *
     * @param Request $request
     * @param uc_banner_cbd01ConfigRepository $configRepository
     *
     * @return array
     */
    public function index(Request $request, uc_banner_cbd01ConfigRepository $configRepository, uc_banner_cbd01DataRepository $DataRepository, ContainerInterface $container)
    {
        $Config = $configRepository->get();
        $Images = $DataRepository->getImages();

        $form = $this->createForm(uc_banner_cbd01ConfigType::class, $Config);
        $form->handleRequest($request);

        if($request->get('delete')){
            $id = $request->get('delete');
            $Config = $DataRepository->get($id);
            $this->entityManager->remove($Config);
            $this->entityManager->flush($Config);

            log_info('uc_banner_cbd01 delete', ['status' => 'Success']);
            $this->addSuccess('uc_banner_cbd01.admin.setting.delete.complete', 'admin');

            return $this->redirectToRoute('uc_banner_cbd01_admin_config');
        }

        // config save
        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();

            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            log_info('uc_banner_cbd01 config', ['status' => 'Success']);
            $this->addSuccess('uc_banner_cbd01.admin.setting.save.complete', 'admin');

            return $this->redirectToRoute('uc_banner_cbd01_admin_config');
        }

        return [
            'Config' => $Config,
            'Images' => $Images,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/uc_banner_cbd01/edit", name="uc_banner_cbd01_admin_edit")
     * @Template("@uc_banner_cbd01/admin/edit.twig")
     *
     * @param Request $request
     * @param uc_banner_cbd01DataRepository $DataRepository
     *
     * @return array
     */
    public function edit(Request $request, uc_banner_cbd01DataRepository $DataRepository)
    {
        $id = $request->get('id');

        if (is_null($id)) {
            $Config = new uc_banner_cbd01Data();
            // $Config->setId(0);
            $Config->setColumnXs(6);
            $Config->setColumnLg(4);
        } else {
            $Config = $DataRepository->get($id);
        }

        $form = $this->createForm(uc_banner_cbd01DataType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            
            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            log_info('uc_banner_cbd01 edit', ['status' => 'Success']);
            $this->addSuccess('uc_banner_cbd01.admin.setting.save.complete', 'admin');

            return $this->redirectToRoute('uc_banner_cbd01_admin_config');
        }

        return [
            'Image' => $Config,
            'form' => $form->createView(),
        ];
    }
}
