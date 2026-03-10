<?php
/**
 * This file is part of BundleSale4
 *
 * Copyright(c) Akira Kurozumi <info@a-zumi.net>
 *
 * https://a-zumi.net
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\BundleSale4\Request;


class Context extends \Eccube\Request\Context
{
    public function isRoute($name)
    {
        $request = $this->requestStack->getMasterRequest();

        if (null === $request) {
            return false;
        }

        return ($request->get('_route') === $name);
    }

    public function getMasterRequest()
    {
        $request = $this->requestStack->getMasterRequest();

        if (null === $request) {
            return false;
        }

        return $request;
    }

}
