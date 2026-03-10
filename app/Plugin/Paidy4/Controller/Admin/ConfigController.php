<?php

namespace Plugin\Paidy4\Controller\Admin;

use Eccube\Common\EccubeConfig;
use Eccube\Controller\AbstractController;
use Plugin\Paidy4\Form\Type\Admin\ConfigType;
use Plugin\Paidy4\Repository\ConfigRepository;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class ConfigController extends AbstractController
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * ConfigController constructor.
     *
     * @param EccubeConfig $eccubeConfig
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        ConfigRepository $configRepository
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/paidy4/config", name="paidy4_admin_config")
     * @Template("@Paidy4/admin/config.twig")
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->get();
        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();

            if ($form->isSubmitted() && $form->isValid()) {
                $this->entityManager->persist($Config);
                $this->entityManager->flush($Config);

                $this->addSuccess('paidy.admin.save.success', 'admin');
                return $this->redirectToRoute('paidy4_admin_config');
            }

        } else if ($form->isSubmitted()) {
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error;
            }
        }

        return [
            'form' => $form->createView(),
            'default_webhook_ip' => $this->eccubeConfig['paidy']['default_webhook_ip'],
        ];
    }
}
