<?php
/*
* Plugin Name : uc_banner_cbd02
*/

namespace Plugin\uc_banner_cbd02\Controller;

use Eccube\Controller\AbstractController;
use Plugin\uc_banner_cbd02\Entity\uc_banner_cbd02Config;
use Plugin\uc_banner_cbd02\Entity\uc_banner_cbd02Data;
use Plugin\uc_banner_cbd02\Form\Type\uc_banner_cbd02ConfigType;
use Plugin\uc_banner_cbd02\Repository\uc_banner_cbd02ConfigRepository;
use Plugin\uc_banner_cbd02\Form\Type\uc_banner_cbd02DataType;
use Plugin\uc_banner_cbd02\Repository\uc_banner_cbd02DataRepository;
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
     * @Route("/%eccube_admin_route%/uc_banner_cbd02/config", name="uc_banner_cbd02_admin_config")
     * @Template("@uc_banner_cbd02/admin/config.twig")
     *
     * @param Request $request
     * @param uc_banner_cbd02ConfigRepository $configRepository
     *
     * @return array
     */
    public function index(Request $request, uc_banner_cbd02ConfigRepository $configRepository, uc_banner_cbd02DataRepository $DataRepository, ContainerInterface $container)
    {
        $Config = $configRepository->get();
        $Images = $DataRepository->getImages();

        $form = $this->createForm(uc_banner_cbd02ConfigType::class, $Config);
        $form->handleRequest($request);

        if($request->get('delete')){
            $id = $request->get('delete');
            $Config = $DataRepository->get($id);
            $this->entityManager->remove($Config);
            $this->entityManager->flush($Config);

            log_info('uc_banner_cbd02 delete', ['status' => 'Success']);
            $this->addSuccess('uc_banner_cbd02.admin.setting.delete.complete', 'admin');

            return $this->redirectToRoute('uc_banner_cbd02_admin_config');
        }

        // config save
        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();

            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            log_info('uc_banner_cbd02 config', ['status' => 'Success']);
            $this->addSuccess('uc_banner_cbd02.admin.setting.save.complete', 'admin');

            return $this->redirectToRoute('uc_banner_cbd02_admin_config');
        }

        return [
            'Config' => $Config,
            'Images' => $Images,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/uc_banner_cbd02/edit", name="uc_banner_cbd02_admin_edit")
     * @Template("@uc_banner_cbd02/admin/edit.twig")
     *
     * @param Request $request
     * @param uc_banner_cbd02DataRepository $DataRepository
     *
     * @return array
     */
    public function edit(Request $request, uc_banner_cbd02DataRepository $DataRepository)
    {
        $id = $request->get('id');

        if (is_null($id)) {
            $Config = new uc_banner_cbd02Data();
            // $Config->setId(0);
            $Config->setColumnXs(6);
            $Config->setColumnLg(4);
        } else {
            $Config = $DataRepository->get($id);
        }

        $form = $this->createForm(uc_banner_cbd02DataType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            
            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            log_info('uc_banner_cbd02 edit', ['status' => 'Success']);
            $this->addSuccess('uc_banner_cbd02.admin.setting.save.complete', 'admin');

            return $this->redirectToRoute('uc_banner_cbd02_admin_config');
        }

        return [
            'Image' => $Config,
            'form' => $form->createView(),
        ];
    }
}
