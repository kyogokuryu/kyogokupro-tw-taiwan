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

namespace Plugin\ECCUBE4LineIntegration\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class IndexController extends \Eccube\Controller\AbstractController
{
    /**
     * @Route("/cart_line_login", name="cart_line_login")
     */
    public function redirectToCartAndForceLogin()
    {
        if ($this->isGranted('ROLE_USER')) {
            // すでにログインしているときはカートページにリダイレクト
            return $this->redirectToRoute('cart');
        } else {
            // ログインされていないときは、ログイン後にカートへリダイレクト
            $url = $this->generateUrl('cart', array() ,UrlGeneratorInterface::ABSOLUTE_URL);
            $this->setLoginTargetPath($url);                            // EC-CUBE標準のログイン後にカートへリダイレクト
            $this->session->set('dropped-cart-notifier-redirect', $url);// LINE連携プラグインによるログイン後リダイレクト

            return $this->redirectToRoute('mypage_login');
        }
    }

}
