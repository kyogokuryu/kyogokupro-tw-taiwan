<?php
/*
* Plugin Name : ProductPlus
*
* Copyright (C) BraTech Co., Ltd. All Rights Reserved.
* http://www.bratech.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\ProductPlus\Service;

use Plugin\ProductPlus\Repository\ProductItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Filesystem\Filesystem;

class ProductPlusService
{
    private $container;
    private $entityManager;
    private $productItemRepository;

    public function __construct(
            ContainerInterface $container,
            EntityManagerInterface $entityManager,
            ProductItemRepository $productItemRepository
            )
    {
        $this->container = $container;
        $this->entityManager = $entityManager;
        $this->productItemRepository = $productItemRepository;
    }

    public function checkInstallPlugin($code)
    {
        $pluginRepository = $this->entityManager->getRepository('Eccube\Entity\Plugin');
        $Plugin = $pluginRepository->findOneBy(['code' => $code, 'enabled' => true]);
        if($Plugin){
            return true;
        }else{
            return false;
        }
    }

    function getEnabledProductPlusForm()
    {
        $readPaths = [
            $this->container->getParameter('eccube_theme_admin_dir'),
            $this->container->getParameter('eccube_theme_admin_default_dir') ,
            ];
        foreach ($readPaths as $readPath) {
            $filePath = $readPath . '/Product/product.twig';
            $fs = new Filesystem();
            if ($fs->exists($filePath)) {
                $source = file_get_contents($filePath);
                break;
            }
        }

        $ProductItems = [];

        if(isset($source)){
            if(preg_match_all('/form\.productplus_(\d+)/u',$source, $result)){
                if(count($result[1]) > 0){
                    foreach($result[1] as $product_item_id){
                        $ProductItem = $this->productItemRepository->find($product_item_id);
                        if($ProductItem){
                            if(!in_array($ProductItem,$ProductItems)){
                                $ProductItems[] = $ProductItem;
                            }
                        }
                    }
                }
            }
        }

        return $ProductItems;
    }
}
