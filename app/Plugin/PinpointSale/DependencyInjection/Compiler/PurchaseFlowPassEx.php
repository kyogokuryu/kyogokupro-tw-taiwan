<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/18
 */

namespace Plugin\PinpointSale\DependencyInjection\Compiler;


use Eccube\Service\PurchaseFlow\Processor\PointProcessor;
use Plugin\PinpointSale\Service\PurchaseFlow\Processor\PinpointSaleDiscountProcessor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PurchaseFlowPassEx implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $plugins = $container->getParameter('eccube.plugins.enabled');

        if (empty($plugins)) {
            $container->log($this, 'enabled plugins not found.');
            return;
        }

        $pluginsCheck = array_flip($plugins);

        if (isset($pluginsCheck['PinpointSale'])) {
            $this->addPinpointSaleDiscountProcessor($container);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function addPinpointSaleDiscountProcessor($container)
    {
        $purchaseAddList = [
            'eccube.purchase.flow.shopping.discount_processors',
        ];

        $index = 0;

        foreach ($purchaseAddList as $addKey) {
            $definition = $container->getDefinition($addKey);
            $itemValidators = $definition->getArgument(0);

            /** @var Reference $itemValidator */
            foreach ($itemValidators as $itemValidator) {
                if (PointProcessor::class == $itemValidator->__toString()) {
                    break;
                }
                $index++;
            }

            if ($index > 0 && $index == count($itemValidators)) {
                // 先頭に追加
                array_unshift(
                    $itemValidators,
                    new Reference(PinpointSaleDiscountProcessor::class)
                );
            } else {
                // PointProcessorの前に追加
                array_splice(
                    $itemValidators,
                    $index,
                    0,
                    [new Reference(PinpointSaleDiscountProcessor::class)]
                );
            }

            $definition->setArgument(0, $itemValidators);
        }
    }
}
