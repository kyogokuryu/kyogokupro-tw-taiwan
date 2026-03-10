<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/11
 */

namespace Plugin\PinpointSale\Form\EventListener;


use Plugin\PinpointSale\Entity\PinpointRepeat;
use Symfony\Component\Form\FormEvent;

class PinpointRepeatTypeEventListener
{

    private $checkList;

    public function __construct()
    {
        $this->checkList = [
            'Week0' => PinpointRepeat::WEEK_0,
            'Week1' => PinpointRepeat::WEEK_1,
            'Week2' => PinpointRepeat::WEEK_2,
            'Week3' => PinpointRepeat::WEEK_3,
            'Week4' => PinpointRepeat::WEEK_4,
            'Week5' => PinpointRepeat::WEEK_5,
            'Week6' => PinpointRepeat::WEEK_6,
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function postSetData(FormEvent $event)
    {
        $form = $event->getForm();

        /** @var PinpointRepeat $data */
        $data = $event->getData();

        if (is_null($data)) {

            // 初期表示時全チェック
            $form->get('week_check')->setData(array_values($this->checkList));

            return;
        }

        $checkWeeks = [];
        foreach ($this->checkList as $key => $item) {

            $method = 'get' . $key;
            if ($data->{$method}() == 1) {
                $checkWeeks[] = $item;
            }
        }

        $form->get('week_check')->setData($checkWeeks);
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        /** @var PinpointRepeat $data */
        $data = $event->getData();

        if (is_null($data)) {
            return;
        }

        // 曜日の登録
        $checkWeeks = $form->get('week_check')->getData();

        $sumWeek = 0;
        foreach ($checkWeeks as $checkWeek) {
            $sumWeek += $checkWeek;
        }

        foreach ($this->checkList as $key => $item) {

            $method = 'set' . $key;
            if ($sumWeek & $item) {
                $value = 1;
            } else {
                $value = 0;
            }
            $data->{$method}($value);
        }
    }
}
