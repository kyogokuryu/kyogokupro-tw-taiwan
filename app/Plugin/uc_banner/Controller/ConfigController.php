<?php
/*
* Plugin Name : uc_banner
*/

namespace Plugin\uc_banner\Controller;

use Eccube\Controller\AbstractController;
use Plugin\uc_banner\Entity\uc_bannerConfig;
use Plugin\uc_banner\Entity\uc_bannerData;
use Plugin\uc_banner\Form\Type\uc_bannerConfigType;
use Plugin\uc_banner\Repository\uc_bannerConfigRepository;
use Plugin\uc_banner\Form\Type\uc_bannerDataType;
use Plugin\uc_banner\Repository\uc_bannerDataRepository;
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
     * @Route("/%eccube_admin_route%/uc_banner/config", name="uc_banner_admin_config")
     * @Template("@uc_banner/admin/config.twig")
     *
     * @param Request $request
     * @param uc_bannerConfigRepository $configRepository
     *
     * @return array
     */
    public function index(Request $request, uc_bannerConfigRepository $configRepository, uc_bannerDataRepository $DataRepository, ContainerInterface $container)
    {
        $Config = $configRepository->get();
        $Images = $DataRepository->getImages();

        $form = $this->createForm(uc_bannerConfigType::class, $Config);
        $form->handleRequest($request);

        if($request->get('delete')){
            $id = $request->get('delete');
            $Config = $DataRepository->get($id);
            $this->entityManager->remove($Config);
            $this->entityManager->flush($Config);

            log_info('uc_banner delete', ['status' => 'Success']);
            $this->addSuccess('uc_banner.admin.setting.delete.complete', 'admin');

            return $this->redirectToRoute('uc_banner_admin_config');
        }

        // config save
        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();

            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            log_info('uc_banner config', ['status' => 'Success']);
            $this->addSuccess('uc_banner.admin.setting.save.complete', 'admin');

            return $this->redirectToRoute('uc_banner_admin_config');
        }

        return [
            'Config' => $Config,
            'Images' => $Images,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/uc_banner/edit", name="uc_banner_admin_edit")
     * @Template("@uc_banner/admin/edit.twig")
     *
     * @param Request $request
     * @param uc_bannerDataRepository $DataRepository
     *
     * @return array
     */
    public function edit(Request $request, uc_bannerDataRepository $DataRepository)
    {
        $id = $request->get('id');

        if (is_null($id)) {
            $Config = new uc_bannerData();
            // $Config->setId(0);
            $Config->setColumnXs(6);
            $Config->setColumnLg(4);
        } else {
            $Config = $DataRepository->get($id);
        }

        $form = $this->createForm(uc_bannerDataType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            
            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            log_info('uc_banner edit', ['status' => 'Success']);
            $this->addSuccess('uc_banner.admin.setting.save.complete', 'admin');

            return $this->redirectToRoute('uc_banner_admin_config');
        }

        return [
            'Image' => $Config,
            'form' => $form->createView(),
        ];
    }
}
