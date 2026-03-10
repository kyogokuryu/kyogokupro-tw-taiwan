<?php
/*
* Plugin Name : [code]
*/

namespace Plugin\[code]\Controller;

use Eccube\Controller\AbstractController;
use Plugin\[code]\Form\Type\[code]ConfigType;
use Plugin\[code]\Repository\[code]ConfigRepository;
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
     * @Route("/%eccube_admin_route%/[code_lower]/config", name="[code_lower]_admin_config")
     * @Template("@[code]/admin/config.twig")
     *
     * @param Request $request
     * @param [code]ConfigRepository $configRepository
     * @param ContainerInterface $container
     *
     * @return array
     */
	public function index(Request $request, [code]ConfigRepository $configRepository, ContainerInterface $container)
	{
		$Config = $configRepository->get();
		$form = $this->createForm([code]ConfigType::class, $Config);
		$form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // フォームの値取得
            $Config = $form->getData();

            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            log_info('config', ['status' => 'Success']);
            $this->addSuccess('[code_lower].admin.config.save.complete', 'admin');

            return $this->redirectToRoute('[code_lower]_admin_config');
        }

		return [
            'Config' => $Config,
            'form' => $form->createView(),
        ];
	}
}
