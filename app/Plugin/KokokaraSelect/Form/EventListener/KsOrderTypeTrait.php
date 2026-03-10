<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/29
 */

namespace Plugin\KokokaraSelect\Form\EventListener;


use Doctrine\Common\Annotations\Annotation\Required;

trait KsOrderTypeTrait
{

    /** @var KsOrderTypeEventListener */
    protected $ksOrderTypeEventListener;

    /**
     * @Required
     *
     * @param KsOrderTypeEventListener $ksOrderTypeEventListener
     */
    public function setKsOrderTypeEventListener(KsOrderTypeEventListener $ksOrderTypeEventListener)
    {
        $this->ksOrderTypeEventListener = $ksOrderTypeEventListener;
    }
}
