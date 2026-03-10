<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/10
 */

namespace Plugin\PinpointSale\Form\EventListener;


use Doctrine\ORM\EntityManagerInterface;
use Plugin\PinpointSale\Entity\Pinpoint;
use Plugin\PinpointSale\Entity\PinpointRepeat;
use Plugin\PinpointSale\Form\Helper\FormHelper;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PinpointTypeEventListener
{

    /** @var FormHelper */
    protected $formHelper;

    /** @var ValidatorInterface */
    private $validator;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        FormHelper $formHelper,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    )
    {
        $this->formHelper = $formHelper;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
    }

    public function postSetData(FormEvent $event)
    {
        $form = $event->getForm();

        /** @var Pinpoint $data */
        $data = $event->getData();

        if (is_null($data)
            || !$data->getPinpointRepeat()) {

            if (is_null($data)) {
                $form->get('sale_type')->setData(Pinpoint::TYPE_PRICE);
            }

            // 初期値設定
            // 繰り返しOFF
            $form->get('repeat_status')->setData(PinpointRepeat::REPEAT_OFF);

            return;
        }

        $form->get('repeat_status')->setData(PinpointRepeat::REPEAT_ON);
    }

    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();

        $form = $event->getForm();

        if(is_null($form->getParent())) {
            // 共通設定画面の場合はリセット処理不要
            return;
        }

        $saleType = $data['sale_type'];
        $delFlg = $data['del_flg'];

        if (Pinpoint::TYPE_PRICE == $saleType) {
            // 割引率の入力リセット
            $data['saleRate'] = null;
            $data['sale_rate_common'] = null;
        } elseif (Pinpoint::TYPE_RATE == $saleType) {
            // 販売価格の入力リセット
            $data['salePrice'] = null;
            $data['sale_rate_common'] = null;
        }

        // 共通 or 削除
        if (Pinpoint::TYPE_COMMON == $saleType
            || $delFlg == 1) {
            // 共通設定の入力リセット
            $data['saleRate'] = null;
            $data['salePrice'] = null;
            unset($data['start_time']);
            unset($data['end_time']);
            $data['repeat_status'] = 0;
            unset($data['PinpointRepeat']);

            if ($delFlg == 1) {
                $data['sale_rate_common'] = null;
            }
        }

        $event->setData($data);
    }

    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        /** @var Pinpoint $data */
        $data = $event->getData();

        if ($data) {
            // 共通情報の場合データ復元
            if ($data->isSaleTypeCommon()) {
                // 登録済みかつ、共通設定画面でない場合に実施
                if ($data->getId() && $form->getParent()) {
                    $this->entityManager->refresh($data);
                    if ($data->getPinpointRepeat()) {
                        $this->entityManager->refresh($data->getPinpointRepeat());
                    }
                }
            }
        }

        $pinpoint = $form->getData();

        $repeatStatus = $form->get('repeat_status')->getData();

        $visible = true;

        if ($form->getParent()) {
            /** @var FormInterface $classForm */
            $classForm = $form->getParent()->getParent();
            if ($classForm && $classForm->has('checked')) {
                $visible = $classForm->get('checked')->getData();
            }
        }

        if ($form->get('del_flg')->getData() == 1
            || $repeatStatus == PinpointRepeat::REPEAT_OFF
            || !$visible) {

            // 繰り返し設定OFF
            /** @var Pinpoint $pinpoint */
            $pinpoint = $form->getData();
            $pinpoint->setPinpointRepeat(null);

        } else {
            // 繰り返し設定ON
            if (!$this->isValid($form->get('PinpointRepeat'))) {
                return;
            }
        }
    }

    /**
     * エラーチェック
     *
     * @param FormInterface $form
     * @return bool
     */
    private function isValid($form)
    {
        $errorFlg = false;

        // 開始終了時間必須チェック
        $startTimeForm = $form->get('start_time');
        $endTimeForm = $form->get('end_time');

        $startTimeResult = $this->validFormError(
            $startTimeForm,
            [new Assert\NotBlank()],
            $errors
        );

        $constraints = [new Assert\NotBlank()];

        if ($startTimeResult) {

            $constraints = array_merge($constraints, [
                new Assert\GreaterThan([
                    'value' => $startTimeForm->getData(),
                    'message' => trans('pinpoint_sale.pinpoint_repeat_time_end_error'),
                ])
            ]);
        }

        $this->validFormError(
            $endTimeForm,
            $constraints,
            $errors
        );

        // 繰り返し曜日必須チェック（いずれかの曜日）
        $this->validFormError(
            $form->get('week_check'),
            [new Assert\NotBlank([
                'message' => trans('pinpoint_sale.pinpoint_repeat_week_error'),
            ])],
            $errors
        );

        return !$errorFlg;
    }

    /**
     * エラーチェックおよびFormへのエラー情報反映
     *
     * @param FormInterface $form
     * @param $constraints
     * @param $errorFlg
     * @return bool true:正常
     */
    private function validFormError($form, $constraints, &$errorFlg)
    {
        $errors = $this->validator
            ->validate($form->getData(), $constraints);

        // Formにエラー情報セット
        $result = $this->formHelper->setFormError($form, $errors);
        $errorFlg = ($errorFlg ? $errorFlg : $result);

        return !$result;
    }

}
