<?php


namespace Plugin\PinpointSale\Config;


use Eccube\Event\TemplateEvent;
use Plugin\PinpointSale\Service\PlgConfigService\ConfigService;

/**
 * Trait ConfigSupportTrait
 * @package Plugin\PinpointSale\Config
 *
 * @property ConfigService configService
 */
trait ConfigSupportTrait
{

    /**
     * テンプレートのパラメータにタイムセール名称設定
     *
     * @param TemplateEvent $event
     */
    public function setDiscountName(TemplateEvent $event)
    {
        if ($this->configService) {
            $discountTitle = $this->configService->getKeyString(ConfigSetting::SETTING_KEY_DISCOUNT_NAME);
            $event->setParameter('discountName', $discountTitle);
        }
    }
}
