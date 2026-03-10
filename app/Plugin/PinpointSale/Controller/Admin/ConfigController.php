<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/25
 */

namespace Plugin\PinpointSale\Controller\Admin;


use Plugin\PinpointSale\Service\PlgConfigService\Controller\AbstractConfigController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ConfigController extends AbstractConfigController
{

    /**
     * @Route("/%eccube_admin_route%/pinpoint_sale/config", name="pinpoint_sale_admin_config")
     * @Template("@PinpointSale/admin/config.twig")
     *
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        return $this->configControl($request);
    }
}
