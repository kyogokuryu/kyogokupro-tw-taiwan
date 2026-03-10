<?php

namespace Eccube\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Eccube\Controller\AbstractController;

class CloudScaleUpController extends AbstractController
{
     /**
     * クラウドスケールアップ.
     *
     * @Route("/cloud_scale_up", name="cloud_scale_up")
     * @Template("CloudScaleUp/index.twig")
     */

    public function index(Request $request)
    {
        return [];
    }
}
