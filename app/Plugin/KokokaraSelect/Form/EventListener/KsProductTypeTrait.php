<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/16
 */

namespace Plugin\KokokaraSelect\Form\EventListener;


use Doctrine\Common\Annotations\Annotation\Required;

trait KsProductTypeTrait
{

    /** @var KsProductTypeEventListener */
    protected $ksProductTypeEventListener;

    /**
     * @Required
     *
     * @param KsProductTypeEventListener $ksProductTypeEventListener
     */
    public function setKsProductTypeEventListener(KsProductTypeEventListener $ksProductTypeEventListener)
    {
        $this->ksProductTypeEventListener = $ksProductTypeEventListener;
    }
}
