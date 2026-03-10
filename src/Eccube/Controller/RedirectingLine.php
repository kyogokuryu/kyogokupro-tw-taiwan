<?php

namespace Eccube\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class RedirectingLine extends AbstractController
{
    /**
     * 
     * @Route("/line", name="redirecting_line", methods={"GET"})
     * @Template("redirecting_line.twig")
     */
    public function index()
    {
        return ;
    }
}
