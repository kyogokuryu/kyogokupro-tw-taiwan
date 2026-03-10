<?php
/*
 * Copyright(c) 2020 YAMATO.CO.LTD
 */
namespace Plugin\SameCategoryProduct;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Product;
use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * 同カテゴリ商品イベント
 *
 * @author Masaki Okada
 */
class SameaCategoryProductEvent implements EventSubscriberInterface
{

    /**
     * セッションID
     */
    const SEDDION_ID = 'plugin.same_category_product.product_id';

    /** @var EccubeConfig $eccubeConfig */
    private $eccubeConfig;

    /** @var EccubeConfig $session */
    private $session;

    /**
     * コンストラクタ
     *
     * @param SessionInterface $session
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(SessionInterface $session, EccubeConfig $eccubeConfig)
    {
        $this->session = $session;
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Product/detail.twig' => 'productDetail'
        ];
    }

    /**
     * コントローラを呼び出す前に、セッションにパラメタをセットする。
     *
     * @param TemplateEvent $event
     */
    public function productDetail(TemplateEvent $event)
    {
        /** @var Product $Product */
        $Product = $event->getParameter('Product');

        // 商品IDをセット
        $this->session->set(self::SEDDION_ID, $Product->getId());
    }
}
