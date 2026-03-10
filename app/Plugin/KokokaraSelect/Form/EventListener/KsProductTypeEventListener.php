<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/16
 */

namespace Plugin\KokokaraSelect\Form\EventListener;


use Doctrine\ORM\EntityManagerInterface;
use Plugin\KokokaraSelect\Entity\KsProduct;
use Plugin\KokokaraSelect\Entity\KsSelectItem;
use Plugin\KokokaraSelect\Entity\KsSelectItemGroup;
use Plugin\KokokaraSelect\Entity\KsSelectItemStock;
use Plugin\KokokaraSelect\Service\KsSelectItemService;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class KsProductTypeEventListener
{

    /**
     * 削除確認を行うグループ情報
     * @var array
     */
    private $originSelectItemGroups;

    /**
     * 削除確認を行う選択商品情報
     * @var array
     */
    private $originSelectItems;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var KsSelectItemService */
    protected $ksSelectItemService;

    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        KsSelectItemService $ksSelectItemService
    )
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->ksSelectItemService = $ksSelectItemService;
    }

    /**
     * KsProductType PRE_SUBMIT
     *
     * @param FormEvent $event
     */
    public function onKsProductTypePreSubmit(FormEvent $event)
    {
        $this->originSelectItemGroups = [];

        $ksSelectItemGroups = $event->getForm()->get('KsSelectItemGroups')->getData();

        if ($ksSelectItemGroups) {

            /** @var KsSelectItemGroup $ksSelectItemGroup */
            foreach ($ksSelectItemGroups as $ksSelectItemGroup) {
                if ($ksSelectItemGroup->getId()) {
                    // 現在登録されているデータ保持
                    $this->originSelectItemGroups[$ksSelectItemGroup->getId()] = $ksSelectItemGroup;
                }
            }
        }
    }

    /**
     * KsProductType POST_SUBMIT
     *
     * @param FormEvent $event
     */
    public function onKsProductTypePostSubmit(FormEvent $event)
    {

        $form = $event->getForm();

        /** @var KsProduct $ksProduct */
        $ksProduct = $event->getData();

        $ksSelectItemGroupsForm = $form->get('KsSelectItemGroups');

        $ksSelectItemGroups = $ksSelectItemGroupsForm->getData();

        $valid = true;

        // グループ数チェック
        if (count($ksSelectItemGroups) == 0) {
            // グループ０件
            $ksSelectItemGroupsForm->addError(new FormError(trans('kokokara_select.admin.setting.ks_select_item_group.empty')));
            $valid = false;
        }

        // 選択数量の合計設定
        $sumQuantity = 0;
        /** @var KsSelectItemGroup $ksSelectItemGroup */
        foreach ($ksSelectItemGroups as $ksSelectItemGroup) {
            $value = $ksSelectItemGroup->getQuantity();

            if ($value) {
                $sumQuantity += (int)$value;
            }
        }

        // 数量設定
        $ksProduct->setQuantity($sumQuantity);

        if ($valid) {

            /** @var KsSelectItemGroup $ksSelectItemGroup */
            foreach ($ksSelectItemGroups as $ksSelectItemGroup) {
                $ksSelectItemGroup->setKsProduct($ksProduct);

                if (isset($this->originSelectItemGroups[$ksSelectItemGroup->getId()])) {
                    // 更新されるデータを除外
                    unset($this->originSelectItemGroups[$ksSelectItemGroup->getId()]);
                }
            }

            // データ削除
            /** @var KsSelectItemGroup $deleteSelectItemGroup */
            foreach ($this->originSelectItemGroups as $deleteSelectItemGroup) {
                // カートに保存されている選択情報クリア
                $this->ksSelectItemService
                    ->deleteRelationKsCartItem($deleteSelectItemGroup->getKsSelectItems());

                $this->entityManager->remove($deleteSelectItemGroup);
            }
        }
    }

    /**
     * KsSelectItemGroupType PRE_SUBMIT
     *
     * @param FormEvent $event
     */
    public function onKsSelectItemGroupTypePreSubmit(FormEvent $event)
    {
        $this->originSelectItems = [];

        // 登録されているデータ
        $ksSelectItems = $event->getForm()->get('KsSelectItems')->getData();

        if ($ksSelectItems) {

            /** @var KsSelectItem $ksSelectItem */
            foreach ($ksSelectItems as $ksSelectItem) {
                if ($ksSelectItem->getId()) {
                    // 現在登録されているデータ保持
                    $this->originSelectItems[$ksSelectItem->getId()] = $ksSelectItem;
                }
            }
        }
    }

    /**
     * KsSelectItemGroupType POST_SUBMIT
     *
     * @param FormEvent $event
     */
    public function onKsSelectItemGroupTypePostSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        /** @var KsSelectItemGroup $ksSelectItemGroup */
        $ksSelectItemGroup = $form->getData();

        $ksSelectItemsForm = $form->get('KsSelectItems');

        $ksSelectItems = $ksSelectItemsForm->getData();

        $valid = true;

        // グループ作成件数チェック
        if (count($ksSelectItems) == 0) {
            // 商品０件
            $ksSelectItemsForm->addError(new FormError(trans('kokokara_select.admin.setting.ks_select_item.empty')));
            $valid = false;
        }

        // 選択数量チェック
        $ksProductForm = $form->getParent()->getParent();
        $isDirect = $ksProductForm->get('directSelect')->getData();
        if (!$isDirect) {
            $errors = $this->validator->validate($form->get('quantity')->getData(), [new Assert\NotBlank()]);

            /** @var ConstraintViolation $error */
            foreach ($errors as $error) {
                $form->get('quantity')->addError(new FormError(
                    $error->getMessage(),
                    $error->getMessageTemplate(),
                    $error->getParameters(),
                    $error->getPlural(),
                    $error->getCause()
                ));

                $valid = false;
            }
        }

        if ($valid) {

            $totalQuantity = 0;

            /** @var KsSelectItem $ksSelectItem */
            foreach ($ksSelectItems as $ksSelectItem) {
                $ksSelectItem->setKsSelectItemGroup($ksSelectItemGroup);

                if (isset($this->originSelectItems[$ksSelectItem->getId()])) {
                    unset($this->originSelectItems[$ksSelectItem->getId()]);
                }

                if ($isDirect) {
                    // 投入数量カウント
                    $totalQuantity += $ksSelectItem->getQuantity();
                }
            }

            if ($isDirect) {
                // 選択数量に投入数量計をセット
                $ksSelectItemGroup->setQuantity($totalQuantity);
            }

            // データ削除
            /** @var KsSelectItem $deleteProductItem */
            foreach ($this->originSelectItems as $deleteProductItem) {
                // カートに保存されている選択情報クリア
                $this->ksSelectItemService
                    ->deleteRelationKsCartItem([$deleteProductItem]);

                $ksSelectItemStock = $deleteProductItem->getKsSelectItemStock();
                $this->entityManager->remove($ksSelectItemStock);
                $this->entityManager->remove($deleteProductItem);
            }

        }
    }

    /**
     * KsSelectItemType POST_SUBMIT
     *
     * @param FormEvent $event
     */
    public function onKsSelectItemTypePostSubmit(FormEvent $event)
    {

        $form = $event->getForm();

        /** @var KsSelectItem $ksSelectItem */
        $ksSelectItem = $form->getData();

        if (is_null($ksSelectItem->getStock())
            && empty($ksSelectItem->isStockUnlimited())) {
            // 割当在庫情報の設置なし
            $form->get('stockUnlimited')->addError(new FormError(trans('kokokara_select.admin.setting.ks_select_item.stock.empty')));
        }

        if (!$this->ksSelectItemService->isStockUnlimited($ksSelectItem)) {

            $productClass = $ksSelectItem->getProductClass();

            if (!$productClass->isStockUnlimited()) {

                $stock = $productClass->getStock();

                $setStock = $this->ksSelectItemService->getStock($ksSelectItem, true);
                if ($stock < $setStock) {
                    // 本体の在庫以上に割当
                    $form->get('stock')->addError(new FormError(trans('kokokara_select.admin.setting.ks_select_item.stock.over')));
                }
            }
        }

        // 投入数量チェック
        $ksSelectItemGroupForm = $form->getParent()->getParent();
        $ksProductForm = $ksSelectItemGroupForm->getParent()->getParent();
        if ($ksProductForm->get('directSelect')->getData()) {
            // 商品の固定選択
            if (empty($form->get('quantity')->getData())) {
                $form->get('quantity')->addError(new FormError(trans('kokokara_select.admin.setting.ks_select_item.quantity.error')));
            }
        }


        // 在庫テーブル調整
        if (!$ksSelectItem->getKsSelectItemStock()) {
            $ksSelectItemStock = new KsSelectItemStock();
            $ksSelectItemStock
                ->setKsSelectItem($ksSelectItem);
        } else {
            $ksSelectItemStock = $ksSelectItem->getKsSelectItemStock();
        }

        if ($ksSelectItem->isStockUnlimited()) {
            $ksSelectItemStock->setStock(null);
        } else {
            $ksSelectItemStock->setStock($ksSelectItem->getStock());
        }

        $ksSelectItem->setKsSelectItemStock($ksSelectItemStock);
    }

}
