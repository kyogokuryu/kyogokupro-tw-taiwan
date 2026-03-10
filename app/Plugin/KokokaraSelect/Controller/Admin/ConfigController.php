<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/06
 */

namespace Plugin\KokokaraSelect\Controller\Admin;


use Plugin\KokokaraSelect\Service\PlgConfigService\Controller\AbstractConfigController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ConfigController extends AbstractConfigController
{

    /**
     * @Route("/%eccube_admin_route%/kokokara_select/config", name="kokokara_select_admin_config")
     * @Template("@KokokaraSelect/admin/config.twig")
     *
     * @param Request $request
     * @return mixed|void
     */
    public function index(Request $request)
    {
        return $this->configControl($request);
    }
}
