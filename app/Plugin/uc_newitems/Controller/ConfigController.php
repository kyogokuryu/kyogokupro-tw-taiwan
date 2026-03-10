<?php
/*
* Plugin Name : uc_newitems
*/

namespace Plugin\uc_newitems\Controller;

use Eccube\Controller\AbstractController;
use Plugin\uc_newitems\Form\Type\uc_newitemsConfigType;
use Plugin\uc_newitems\Repository\uc_newitemsConfigRepository;
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
     * @Route("/%eccube_admin_route%/uc_newitems/config", name="uc_newitems_admin_config")
     * @Template("@uc_newitems/admin/config.twig")
     *
     * @param Request $request
     * @param uc_newitemsConfigRepository $configRepository
     *
     * @return array
     */
	public function index(Request $request, uc_newitemsConfigRepository $configRepository, ContainerInterface $container)
	{
		$Config = $configRepository->get();
		$form = $this->createForm(uc_newitemsConfigType::class, $Config);
		$form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $block_type = $request->get('block_type');
            $Config = $form->getData();

            if ($Config['block_type'] != $block_type) {
                $this->copyBlock($container, $Config['block_type']);
            }

            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            log_info('config', ['status' => 'Success']);
            $this->addSuccess('uc_newitems.admin.config.save.complete', 'admin');

            return $this->redirectToRoute('uc_newitems_admin_config');
        }

		return [
            'Config' => $Config,
            'form' => $form->createView(),
        ];
	}

    /**
     * Copy block template.
     *
     * @param ContainerInterface $container
     * @param block_type int
     */
    private function copyBlock(ContainerInterface $container, $block_type)
    {
        $blockFileName = 'uc_newitems_block';
        $templateDir = $container->getParameter('eccube_theme_front_dir');
        $originBlock = __DIR__.'/../Resource/template/Block/'.$blockFileName.'_'.$block_type.'.twig';

        $file = new Filesystem();
        $file->copy($originBlock, $templateDir.'/Block/'.$blockFileName.'.twig', true);
    }
}
