<?php
/*
 * Copyright(c) 2020 YAMATO.CO.LTD
 */
namespace Plugin\SameCategoryProduct\Controller\Block;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Repository\ProductRepository;
use Plugin\SameCategoryProduct\Service\SameCategoruyProductService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * 同カテゴリ商品コントローラ
 *
 * @author Masaki Okada
 */
class SameCategoryProductController extends AbstractController
{

    /** @var RequestStack */
    protected $session;

    /** @var SameCategoruyProductService */
    protected $productService;

    /**
     * コンストラクタ
     *
     * @param SessionInterface $session
     * @param ProductRepository $productRepository
     */
    public function __construct(SessionInterface $session, SameCategoruyProductService $productService)
    {
        $this->session = $session;
        $this->productService = $productService;
    }

    /**
     * 商品詳細ページのみ、同じカテゴリの商品を４件まで表示する。
     *
     * @Route("/block/same_category_product", name="block_same_category_product")
     * @Template("Block/same_category_product.twig")
     *
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {

        // グローバル変数の$_SERVER['REQUEST_URI']が入っている
        $redirectUrl = $request->server->get('REDIRECT_URL');

        // 商品詳細ページ以外は処理しない
        if (preg_match('/.*\/products\/detail\/\d+$/', $redirectUrl) == 0) {
            return null;
        }

        // セッションから商品IDを取得
        $productId = $this->session->get('plugin.same_category_product.product_id');

        // 同じカテゴリの商品リストを取得
        $sameCategoryProducts = $this->productService->getSameCategoryProducts($productId);

        return [
            'SameCategoryProducts' => $sameCategoryProducts
        ];
    }
}
