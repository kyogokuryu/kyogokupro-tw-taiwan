<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/30
 */

namespace Plugin\KokokaraSelect\Controller\Admin\Order;


use Eccube\Controller\AbstractController;
use Eccube\Entity\ProductClass;
use Eccube\Repository\ProductClassRepository;
use Plugin\KokokaraSelect\Entity\KsProduct;
use Plugin\KokokaraSelect\Repository\KsProductRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class EditExController extends AbstractController
{

    /** @var KsProductRepository */
    protected $ksProductRepository;

    protected $productClassRepository;

    public function __construct(
        KsProductRepository $ksProductRepository,
        ProductClassRepository $productClassRepository
    )
    {
        $this->ksProductRepository = $ksProductRepository;
        $this->productClassRepository = $productClassRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/order/search/kokokara_select_product", name="admin_order_search_kokokara_select_product")
     * @Template("@KokokaraSelect/admin/Order/search_kokokara_select_product.twig")
     *
     * @param Request $request
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function searchKokokaraSelectProduct(Request $request)
    {
        if ($request->isXmlHttpRequest() && $this->isTokenValid()) {
            log_debug('search kokokara select product start.');

            $productClassId = $request->get('productClassId');

            /** @var ProductClass $productClass */
            $productClass = $this->productClassRepository->find($productClassId);

            $product = $productClass->getProduct();

            $qb = $this->ksProductRepository
                ->getQueryBuilderByProductSearch($product->getId());

            /** @var KsProduct $ksProduct */
            $ksProduct = $qb->getQuery()->getSingleResult();

            if (!$ksProduct) {
                log_debug('search kokokara select product not found.');
            }

            return [
                'parentProductClassId' => $productClassId,
                'ksProduct' => $ksProduct,
            ];
        }
    }
}
