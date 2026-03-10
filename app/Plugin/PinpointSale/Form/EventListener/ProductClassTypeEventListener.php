<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/09
 */

namespace Plugin\PinpointSale\Form\EventListener;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Plugin\PinpointSale\Entity\Pinpoint;
use Plugin\PinpointSale\Entity\ProductPinpoint;
use Plugin\PinpointSale\Form\Helper\FormHelper;
use Plugin\PinpointSale\Repository\ProductPinpointRepository;
use Plugin\PinpointSale\Service\PinpointSaleHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductClassTypeEventListener
{

    /** @var EntityManager */
    private $entityManager;

    /** @var ContainerInterface */
    private $container;

    /** @var FormHelper */
    protected $formHelper;

    /** @var ValidatorInterface */
    private $validator;

    /** @var ProductPinpointRepository */
    protected $productPinpointRepository;

    /** @var PinpointSaleHelper */
    protected $pinpointSaleHelper;

    /**
     * 入力チェック不要
     */
    private const VALID_EMPTY = 9;

    /**
     * 正常
     */
    private const VALID_TRUE = 1;

    /**
     * 異常
     */
    private const VALID_FALSE = 0;

    public function __construct(
        EntityManager $entityManager,
        ContainerInterface $container,
        FormHelper $formHelper,
        ValidatorInterface $validator,
        ProductPinpointRepository $productPinpointRepository,
        PinpointSaleHelper $pinpointSaleHelper
    )
    {
        $this->entityManager = $entityManager;
        $this->container = $container;
        $this->formHelper = $formHelper;
        $this->validator = $validator;
        $this->productPinpointRepository = $productPinpointRepository;
        $this->pinpointSaleHelper = $pinpointSaleHelper;
    }

    /**
     * @param FormEvent $event
     */
    public function postSetData(FormEvent $event)
    {
        /** @var FormInterface $form */
        $form = $event->getForm();

        /** @var ProductClass $data */
        $data = $event->getData();

        /** @var FormInterface $productPinpointsForm */
        $productPinpointsForm = $form->get('productPinpoints');

        if (is_null($data)) {
            // 初期データなし
            $collection = new ArrayCollection();
            $productPinpointsForm->setData($collection);

            return;

        } else {

            /** @var ArrayCollection $productPinpoints */
            $productPinpoints = $productPinpointsForm->getData();

            // タイムセール共通設定
            /** @var FormInterface $productPinpointForm */
            foreach ($productPinpointsForm as $key => $productPinpointForm) {

                $pinpointsForm = $productPinpointForm->get('pinpoint');

                /** @var Pinpoint $pinpoint */
                $pinpoint = $productPinpointForm->get('pinpoint')->getData();

                if ($pinpoint->getSaleType() == Pinpoint::TYPE_COMMON) {

                    $newProductPinpoint = new ProductPinpoint();
                    $newPinpoint = new Pinpoint();
                    $newPinpoint->setSaleType(Pinpoint::TYPE_COMMON);

                    $newProductPinpoint
                        ->setPinpoint($newPinpoint)
                        ->setProductClass($data);

                    // データ差し替え
                    $productPinpoints->set($key, $newPinpoint);

                    // Form選択設定
                    $pinpointsForm->get('sale_type')->setData(Pinpoint::TYPE_COMMON);
                    $pinpointsForm->get('sale_rate_common')->setData($pinpoint);
                }

            }
        }

        if (is_null($data->getProductPinpoints())
            || $data->getProductPinpoints()->count() == 0) {

            $defaultObj = new ProductPinpoint();

            // 初期値セット
            $pinpoint = new Pinpoint();
            $pinpoint
                ->setSaleType(Pinpoint::TYPE_PRICE);

            $defaultObj
                ->setPinpoint($pinpoint)
                ->setProductClass($data);

            $collection = new ArrayCollection();
            $collection->add($defaultObj);

            $productPinpointsForm->setData($collection);

        }

    }

    /**
     * @param FormEvent $event
     * @throws ORMException
     */
    public function postSubmit(FormEvent $event)
    {

        /** @var FormInterface $form */
        $form = $event->getForm();

        /** @var ProductClass $data */
        $data = $event->getData();

        /** @var FormInterface $productPinpointsForm */
        $productPinpointsForm = $form->get('productPinpoints');

        // 登録対象外
        if (!$data->isVisible()) {
            // 不要データの削除
            foreach ($productPinpointsForm as $key => $item) {
                $this->removeProductPinpointForm($productPinpointsForm, $key);
            }

            return;
        }

        $errorFlg = false;

        $dateTimeList = [];
        $commonList = [];
        $updateList = [];

        /**
         * @var  $key
         * @var  FormInterface $productPinpointForm
         */
        foreach ($productPinpointsForm as $key => $productPinpointForm) {

            /** @var ProductPinpoint $productPinpoint */
            $productPinpoint = $productPinpointForm->getData();
            $productPinpoint->setProductClass($data);

            $pinpointForm = $productPinpointForm->get('pinpoint');

            // del_flg = 1 は不要
            $delFlg = $pinpointForm->get('del_flg')->getData();

            if ($delFlg == 1) {
                $this->removeProductPinpointForm($productPinpointsForm, $key);

                continue;
            }

            // セール種類
            $saleType = $pinpointForm->get('sale_type')->getData();

            // 入力チェック
            if (Pinpoint::TYPE_PRICE == $saleType
                || Pinpoint::TYPE_RATE == $saleType) {

                $result = $this->valid($form, $pinpointForm);
            } else {
                $result = $this->validSaleTypeCommon($pinpointForm);
            }

            if ($result == self::VALID_EMPTY) {
                $this->removeProductPinpointForm($productPinpointsForm, $key);

                continue;
            } elseif ($result == self::VALID_FALSE) {
                $errorFlg = true;
                continue;
            }

            /** @var Pinpoint $pinpointData */
            $pinpointData = $pinpointForm->getData();
            $saleTypeOrigin = $pinpointData->getSaleType();

            // 登録対象

            if (Pinpoint::TYPE_COMMON == $saleType) {
                // 共通設定
                /** @var Pinpoint $pinpoint */
                $pinpoint = $pinpointForm->get('sale_rate_common')->getData();

                // 登録対象外
                $updateList[$key] = $pinpoint;

                $commonList[] = $pinpointForm->get('sale_rate_common');

                // 共通は相互チェックしない
                continue;

            } else {
                // 単価・割引率
                if (Pinpoint::TYPE_COMMON == $saleTypeOrigin) {
                    // Form からコピー
                    $pinpoint = new Pinpoint();
                    $pinpoint->copySetting($pinpointForm);

                    $updateList[$key] = $pinpoint;

                } else {
                    /** @var Pinpoint $pinpoint */
                    $pinpoint = $pinpointForm->getData();
                }

                $pinpoint
                    ->setSaleType($saleType)
                    ->setSortNo(0);

                $targetForm = $pinpointForm;
            }

            $dateTimeList[] = [
                'start' => $pinpoint->getStartTime(),
                'end' => $pinpoint->getEndTime(),
                'valid' => true,
                'form' => $targetForm,
            ];
        }

        // 共通設定重複チェック
        if (!$this->validSaleTypeCommonSelect($commonList)) {
            $errorFlg = true;
        }

        // 日付相互チェック
        if (!$this->dateTimeValid($dateTimeList)) {
            return;
        }

        if ($errorFlg) return;

        // 共通設定の場合 タイムセール情報差し替え
        foreach ($updateList as $key => $pinpoint) {

            $this->removeProductPinpointForm($productPinpointsForm, $key);

            /** @var ArrayCollection $productPinpoints */
            $productPinpoints = $productPinpointsForm->getData();

            $productPinpoint = new ProductPinpoint();
            $productPinpoint
                ->setPinpoint($pinpoint)
                ->setProductClass($data);

            $productPinpoints->add($productPinpoint);
        }

        $this->resetProductClass($data);
    }

    /**
     * @param FormInterface $form
     * @param FormInterface $pinpointForm
     * @return int
     */
    private function valid($form, $pinpointForm)
    {

        // セール種類
        $saleType = $pinpointForm->get('sale_type')->getData();

        if ($saleType == Pinpoint::TYPE_PRICE) {
            // 単価
            $saleForm = $pinpointForm->get('salePrice');
            $saleValue = $pinpointForm->get('salePrice')->getData();

            $price02 = $form->get('price02')->getData();

            if ($price02) {
                // 単価入力チェック
                // 必須・販売価格以下
                $salePriceRateConstraints = [
                    new Assert\NotBlank(),
                    new Assert\LessThanOrEqual([
                        'value' => $price02,
                        'message' => trans('pinpoint_sale.admin.pinpoint_sale_price_error'),
                    ])
                ];
            } else {
                $salePriceRateConstraints = [];
            }


        } elseif ($saleType == Pinpoint::TYPE_RATE) {
            // 割引率
            $saleForm = $pinpointForm->get('saleRate');
            $saleValue = $pinpointForm->get('saleRate')->getData();

            // 割引率入力チェック
            // 必須チェック
            $salePriceRateConstraints = [
                new Assert\NotBlank(),
            ];

        } else {
            return self::VALID_EMPTY;
        }

        // 開始時間・終了時間
        $startTime = $pinpointForm->get('start_time')->getData();
        $endTime = $pinpointForm->get('end_time')->getData();

        // 単価or割引率 と 開始終了日時が空の場合未設定(del_flg=1と同等)とみなす
        if (empty($saleValue) && empty($startTime) && empty($endTime)) {
            return self::VALID_EMPTY;
        }

        $errorFlg = false;

        // 単価 or 割引率のチェック
        $errors = $this->validator
            ->validate($saleValue, $salePriceRateConstraints);
        $result = $this->formHelper->setFormError($saleForm, $errors);
        $errorFlg = ($errorFlg ? $errorFlg : $result);

        // 開始日時チェック
        $customStartTime = $pinpointForm->get('start_time');
        $customStartTimeResult = $this->validCustomDateTime(
            $customStartTime,
            [new Assert\NotBlank()],
            $errorFlg
        );

        // 終了日時チェック
        $customEndTime = $pinpointForm->get('end_time');
        $customEndTimeResult = $this->validCustomDateTime(
            $customEndTime,
            [new Assert\NotBlank()],
            $errorFlg
        );

        if ($customStartTimeResult && $customEndTimeResult) {

            $errors = $this->validator
                ->validate($customEndTime->getData(), [
                    new Assert\GreaterThan([
                        'value' => $customStartTime->getData(),
                    ])
                ]);

            /** @var \DateTime $startDate */
            $startDate = $customStartTime->getData();

            /** @var \DateTime $endDate */
            $endDate = $customEndTime->getData();

            if ($startDate->format('Ymd') == $endDate->format('Ymd')) {
                // 同日
                $result = $this->formHelper->setFormError($customEndTime->get('custom_time'), $errors);
            } else {
                $result = $this->formHelper->setFormError($customEndTime->get('custom_date'), $errors);
            }

            $errorFlg = ($errorFlg ? $errorFlg : $result);
        }

        if ($errorFlg) {
            return self::VALID_FALSE;
        }

        return self::VALID_TRUE;
    }

    /**
     * 共通設定選択時の入力チェック
     *
     * @param $pinpointForm
     * @return int
     */
    private function validSaleTypeCommon($pinpointForm)
    {

        // 共通設定
        $saleForm = $pinpointForm->get('sale_rate_common');
        $saleValue = $pinpointForm->get('sale_rate_common')->getData();

        // 必須チェック
        $errors = $this->validator
            ->validate($saleValue, [
                new Assert\NotBlank(),
            ]);
        $errorFlg = $this->formHelper->setFormError($saleForm, $errors);

        if ($errorFlg) {
            // セール共通設定必須エラー
            return self::VALID_FALSE;
        }

        return self::VALID_TRUE;
    }

    /**
     * 共通設定重複チェック
     *
     * @param $commonList
     * @return bool true:正常
     */
    private function validSaleTypeCommonSelect($commonList)
    {

        $result = true;

        // チェック対象をソート
        usort($commonList, function (FormInterface $arrDataA, FormInterface $arrDataB) {

            $pinpointA = $arrDataA->getData();
            $pinpointB = $arrDataB->getData();

            return $this->pinpointSaleHelper->sortProductPinpoint($pinpointA, $pinpointB);
        });

        $commonListCheck = [];
        /** @var FormInterface $item */
        foreach ($commonList as $item) {
            $pinpoint = $item->getData();
            if (!isset($commonListCheck[$pinpoint->getId()])) {
                $commonListCheck[$pinpoint->getId()] = true;
            } else {
                // 重複
                $item->addError(new FormError(trans('pinpoint_sale.admin.pinpoint_sale_common_error')));
                $result = false;
            }
        }

        return $result;
    }

    /**
     * タイムセール期間の相互チェック
     *
     * @param $dateTimeList
     * @return bool
     */
    private function dateTimeValid($dateTimeList)
    {

        // チェック対象をソート
        usort($dateTimeList, function ($arrDataA, $arrDataB) {

            $pinpointA = $arrDataA['form']->getData();
            $pinpointB = $arrDataB['form']->getData();

            return $this->pinpointSaleHelper->sortProductPinpoint($pinpointA, $pinpointB);
        });

        $max = count($dateTimeList);
        $targetMax = $max - 1;

        $errorFlg = false;

        for ($i = 0; $i < $targetMax; $i++) {

            $targetTime = $dateTimeList[$i];

            /** @var \DateTime $targetStartDateTime */
            $targetStartDateTime = $targetTime['start'];

            /** @var \DateTime $targetEndDateTime */
            $targetEndDateTime = $targetTime['end'];

            for ($j = $i + 1; $j < $max; $j++) {

                $checkTime = $dateTimeList[$j];

                if (!$checkTime['valid']) continue;

                /** @var \DateTime $targetStartDateTime */
                $checkStartDateTime = $checkTime['start'];

                /** @var \DateTime $targetEndDateTime */
                $checkEndDateTime = $checkTime['end'];

                if (!$this->isCheckDateTimeError(
                    $targetStartDateTime, $targetEndDateTime, $checkStartDateTime)) {
                    // 開始日NG
                    /** @var FormInterface $form */
                    $form = $checkTime['form'];

                    $errorForm = $form->get('start_time')->get('custom_date');

                    $this->formHelper
                        ->setFormErrorDirect($errorForm, 'pinpoint_sale.admin.pinpoint_datetime_from_error');

                    $dateTimeList[$j]['valid'] = false;
                    $errorFlg = true;
                }

                if (!$this->isCheckDateTimeError(
                    $targetStartDateTime, $targetEndDateTime, $checkEndDateTime)) {
                    // 終了日NG
                    /** @var FormInterface $form */
                    $form = $checkTime['form'];
                    $errorForm = $form->get('end_time')->get('custom_date');

                    $this->formHelper
                        ->setFormErrorDirect($errorForm, 'pinpoint_sale.admin.pinpoint_datetime_to_error');

                    $dateTimeList[$j]['valid'] = false;
                    $errorFlg = true;
                }

            }
        }
        return !$errorFlg;
    }

    /**
     * @param \DateTime $targetDateTimeFrom
     * @param \DateTime $targetDateTimeTo
     * @param \DateTime $checkDateTime
     *
     * @return bool true:正常 false:異常
     */
    private function isCheckDateTimeError($targetDateTimeFrom, $targetDateTimeTo, $checkDateTime)
    {
        if ($targetDateTimeFrom <= $checkDateTime
            && $checkDateTime < $targetDateTimeTo) {

            return false;
        }
        return true;
    }

    /**
     * @param FormInterface $productPinpointsForm
     * @param $key
     * @throws ORMException
     */
    private function removeProductPinpointForm($productPinpointsForm, $key)
    {
        /** @var ArrayCollection $productPinpoints */
        $productPinpoints = $productPinpointsForm->getData();

        /** @var ProductPinpoint $productPinpoint */
        $productPinpoint = $productPinpoints->get($key);
        if (!is_null($productPinpoint)) {

            $pinpoint = $productPinpoint->getPinpoint();
            if ($pinpoint->getSaleType() == Pinpoint::TYPE_COMMON) {
                // タイムセールと商品の結合情報削除
                $this->entityManager->remove($productPinpoint);

                // 共通設定をもとに戻す
                $this->entityManager->refresh($pinpoint);
                if ($pinpoint->getPinpointRepeat()) {
                    $this->entityManager->refresh($pinpoint->getPinpointRepeat());
                }

            } else {

                // タイムセールと商品の結合情報削除
                $this->entityManager->remove($productPinpoint);

                // タイムセール情報削除
                $this->entityManager->remove($pinpoint);
            }
        }

        $productPinpoints->remove($key);
    }

    /**
     * 規格初期化時
     *
     * @param ProductClass $data
     * @throws ORMException
     */
    private function resetProductClass(ProductClass $data)
    {

        if ($data->getId()) {
            return;
        }

        $productId = $this->pinpointSaleHelper->getActiveId();
        if (empty($productId)) {
            return;
        }
        $product = $this->entityManager->getRepository(Product::class)->find($productId);

        /** @var ProductClass $ExistsProductClass */
        $ExistsProductClass = $this->entityManager->getRepository(ProductClass::class)->findOneBy([
            'Product' => $product,
            'ClassCategory1' => $data->getClassCategory1(),
            'ClassCategory2' => $data->getClassCategory2(),
        ]);

        // 過去の登録情報があればその情報を復旧する.
        if ($ExistsProductClass) {

            $productPinpoints = $ExistsProductClass->getProductPinpoints();

            // 過去情報クリア
            /** @var ProductPinpoint $productPinpoint */
            foreach ($productPinpoints as $productPinpoint) {
                $pinpoint = $productPinpoint->getPinpoint();
                if (!$pinpoint->isSaleTypeCommon()) {
                    // 単価or値引率 の場合削除
                    $this->entityManager->remove($pinpoint);
                }
                // 商品とタイムセールのリレーション削除
                $this->entityManager->remove($productPinpoint);
            }

            $ExistsProductClass->copyProperties($data, [
                'id',
                'price01_inc_tax',
                'price02_inc_tax',
                'create_date',
                'update_date',
                'Creator',
            ]);
            $data = $ExistsProductClass;
        }

        $newProductPinpoints = $data->getProductPinpoints();

        foreach ($newProductPinpoints as $productPinpoint) {
            $productPinpoint->setProductClass($data);
        }
    }

    /**
     * エラーチェックおよびFormへのエラー情報反映
     *
     * @param FormInterface $form
     * @param $constraints
     * @param $errorFlg
     * @return bool true:正常
     */
    private function validCustomDateTime($form, $constraints, &$errorFlg)
    {
        $errors = $this->validator
            ->validate($form->get('custom_date')->getData(), $constraints);

        // Formにエラー情報セット
        $customDateResult = $this->formHelper->setFormError($form->get('custom_date'), $errors);
        $errorFlg = ($errorFlg ? $errorFlg : $customDateResult);

        $errors = $this->validator
            ->validate($form->get('custom_time')->getData(), $constraints);

        // Formにエラー情報セット
        $customTimeResult = $this->formHelper->setFormError($form->get('custom_time'), $errors);

        $errorFlg = ($errorFlg ? $errorFlg : $customTimeResult);

        return (!$customDateResult && !$customTimeResult ? true : false);
    }
}
