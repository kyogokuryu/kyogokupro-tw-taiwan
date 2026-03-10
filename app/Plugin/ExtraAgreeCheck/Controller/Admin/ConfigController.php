<?php

namespace Plugin\ExtraAgreeCheck\Controller\Admin;

use Plugin\ExtraAgreeCheck\Form\Type\Admin\ConfigType;
use Plugin\ExtraAgreeCheck\Repository\ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ConfigController extends \Eccube\Controller\AbstractController
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
    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/extra_agree_check/config", name="extra_agree_check_admin_config")
     * @Template("@ExtraAgreeCheck/admin/config.twig")
     *
     * @param Request   $request
     *
     * @return array|RedirectResponse
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->get();
        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $this->entityManager->persist($Config);
            $this->entityManager->flush();

            log_info('ExtraAgreeCheck config', ['status' => 'Success']);
            $this->addSuccess('plugin.extra_agree_check.admin.save.complete', 'admin');

            return $this->redirectToRoute('extra_agree_check_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
