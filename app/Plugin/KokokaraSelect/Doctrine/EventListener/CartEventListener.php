<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/26
 */

namespace Plugin\KokokaraSelect\Doctrine\EventListener;


use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Cart;
use Eccube\Entity\CartItem;
use Eccube\Service\Cart\CartItemComparator;
use Plugin\KokokaraSelect\Entity\KsCartSelectItemGroup;
use Plugin\KokokaraSelect\Repository\KsCartSelectItemGroupRepository;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartEventListener
{

    /** @var Session */
    protected $session;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /**
     * @var CartItemComparator
     */
    protected $cartItemComparator;

    public function __construct(
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        CartItemComparator $cartItemComparator
    )
    {
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->cartItemComparator = $cartItemComparator;
    }

    public function postLoad(Cart $entity)
    {
        $this->updateCartKey($entity);
    }

    public function prePersist(Cart $entity)
    {
        $this->updateCartKey($entity);
    }

    private function updateCartKey($entity)
    {
        // 非会員の情報がセッションに残っている場合 会員へマージ
        $cartKeys = $this->session->get('cart_keys', []);

        /** @var KsCartSelectItemGroupRepository $ksCartSelectItemGroupRepository */
        $ksCartSelectItemGroupRepository = $this->entityManager->getRepository(KsCartSelectItemGroup::class);

        if ($cartKeys) {
            foreach ($cartKeys as $cartKey) {

                if ($entity->getCartKey() != $cartKey) {

                    $ksCartSelectItemGroups = $ksCartSelectItemGroupRepository->findBy(['cartKey' => $cartKey]);

                    /** @var KsCartSelectItemGroup $ksCartSelectItemGroup */
                    foreach ($ksCartSelectItemGroups as $ksCartSelectItemGroup) {

                        $ksCartSelectItemGroup
                            ->setCartKey($entity->getCartKey());

                        /** @var CartItem $cartItem */
                        foreach ($entity->getCartItems() as $cartItem) {

                            $targetCartItem = $ksCartSelectItemGroup->getCartItem();

                            if ($this->cartItemComparator->compare($cartItem, $targetCartItem)) {
                                // 一致 紐付けるCartItemを変更
                                $ksCartSelectItemGroup
                                    ->setCartItem($cartItem);

                                $cartItem->addKsCartSelectItemGroup($ksCartSelectItemGroup);

                                break;
                            }
                        }

                        $this->entityManager->persist($ksCartSelectItemGroup);
                        $this->entityManager->flush($ksCartSelectItemGroup);
                    }
                }
            }
        }
    }

    public function preRemove(Cart $entity)
    {
        /** @var CartItem $cartItem */
        foreach ($entity->getCartItems() as $cartItem) {

            /** @var KsCartSelectItemGroup $ksCartSelectItemGroup */
            foreach ($cartItem->getKsCartSelectItemGroups() as $ksCartSelectItemGroup) {
                $this->entityManager->remove($ksCartSelectItemGroup);
            }

            $this->entityManager->remove($cartItem);
        }
    }
}
