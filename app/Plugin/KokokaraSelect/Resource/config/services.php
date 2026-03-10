<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/10
 */

use Plugin\KokokaraSelect\DependencyInjection\Compiler\PurchaseFlowPassEx;

$container->addCompilerPass(new PurchaseFlowPassEx());
