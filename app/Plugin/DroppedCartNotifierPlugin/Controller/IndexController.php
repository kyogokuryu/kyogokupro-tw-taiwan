<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\DroppedCartNotifierPlugin\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class IndexController extends \Eccube\Controller\AbstractController
{
    /**
     * @Route("/cart_force_login", name="cart_force_login")
     */
    public function redirectToCartAndForceLogin()
    {
        if ($this->isGranted('ROLE_USER')) {
            // すでにログインしているときはカートページにリダイレクト
            return $this->redirectToRoute('cart');
        } else {
            // ログインされていないときは、ログイン後にカートへリダイレクト
            $this->setLoginTargetPath($this->generateUrl('cart', UrlGeneratorInterface::ABSOLUTE_URL));

            return $this->redirectToRoute('mypage_login');
        }
    }

}
