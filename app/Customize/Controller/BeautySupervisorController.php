<?php

namespace Customize\Controller;

use Eccube\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class BeautySupervisorController extends AbstractController
{

    /**
     * 
     * @Route("/beauty-supervisor-ryu-kyogoku", name="beauty")
     * 
     * @Template("BeautySupervisor/index.twig")
     * 
     */
    public function index()
    {
        return [];
    }
}
