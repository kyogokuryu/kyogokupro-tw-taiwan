<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/18
 */


use Plugin\PinpointSale\DependencyInjection\Compiler\PurchaseFlowPassEx;

$container->addCompilerPass(new PurchaseFlowPassEx());
