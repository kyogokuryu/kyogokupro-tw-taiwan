<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/10
 */

namespace Plugin\KokokaraSelect\DependencyInjection\Compiler;


use Eccube\Service\PurchaseFlow\Processor\StockReduceProcessor;
use Plugin\KokokaraSelect\Service\PurchaseFlow\Processor\KokokaraSelectStockReduceProcessor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PurchaseFlowPassEx implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        $plugins = $container->getParameter('eccube.plugins.enabled');

        if (empty($plugins)) {
            $container->log($this, 'enabled plugins not found.');
            return;
        }

        $pluginsCheck = array_flip($plugins);

        if (isset($pluginsCheck['KokokaraSelect'])) {
            $this->addKokokaraSelectProcessor($container);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    public function addKokokaraSelectProcessor($container)
    {
        $purchaseAddList = [
            'eccube.purchase.flow.shopping.purchase',
        ];

        $index = 0;

        foreach ($purchaseAddList as $addKey) {
            $definition = $container->getDefinition($addKey);
            $purchaseProcessors = $definition->getArgument(0);

            /** @var Reference $purchaseProcessor */
            foreach ($purchaseProcessors as $purchaseProcessor) {
                if (StockReduceProcessor::class == $purchaseProcessor->__toString()) {
                    break;
                }
                $index++;
            }

            if ($index > 0 && $index == count($purchaseProcessors)) {
                // 最後に追加
                $purchaseProcessors[] = new Reference(KokokaraSelectStockReduceProcessor::class);

            } else {
                // StockReduceProcessorの後に追加
                array_splice(
                    $purchaseProcessors,
                    ($index + 1),
                    0,
                    [new Reference(KokokaraSelectStockReduceProcessor::class)]
                );
            }

            $definition->setArgument(0, $purchaseProcessors);
        }
    }
}
