<?php

namespace Plugin\HsdRelatedProduct\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\HsdRelatedProduct\Form\Type\Admin\ConfigType;
use Plugin\HsdRelatedProduct\Repository\ConfigRepository;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

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
    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/hsd_related_product/config", name="hsd_related_product_admin_config")
     * @Template("@HsdRelatedProduct/admin/config.twig")
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
            $this->addSuccess('登録しました。', 'admin');

            return $this->redirectToRoute('hsd_related_product_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
