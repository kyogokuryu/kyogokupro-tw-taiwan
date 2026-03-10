<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/25
 */

namespace Plugin\PinpointSale\Config;


use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\RoundingType;
use Eccube\Entity\Master\TaxType;
use Plugin\PinpointSale\Service\PlgConfigService\Common\ConfigSettingInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ConfigSetting implements ConfigSettingInterface
{

    /** @var EccubeConfig */
    protected $eccubeConfig;

    /* Key */

    // 割引率 端数処理
    const SETTING_KEY_RATE_TYPE = 'DISCOUNT_RATE_TYPE';

    // 割引レコード課税設定
    const SETTING_KEY_DISCOUNT_TAX = 'DISCOUNT_TAX';

    // タイムセール名称
    const SETTING_KEY_DISCOUNT_NAME = 'DISCOUNT_NAME';

    // 限定値引表示
    const SETTING_KEY_PRODUCE_DETAIL_VIEW = 'DISCOUNT_PRODUCT_DETAIL_VIEW';
    const SETTING_KEY_CART_VIEW = 'DISCOUNT_CART_VIEW';
    const SETTING_KEY_SHOPPING_VIEW = 'DISCOUNT_SHOPPING_VIEW';
    const SETTING_KEY_CONFIRM_VIEW = 'DISCOUNT_CONFIRM_VIEW';
    const SETTING_KEY_HISTORY_VIEW = 'DISCOUNT_HISTORY_VIEW';

    // JavaScript追加
    const SETTING_KEY_PRODUCT_DETAIL_JS = 'DISCOUNT_PRODUCT_DETAIL_JS';

    /* グループ */
    // 共通
    const SETTING_GROUP_COMMON = 1;

    // 商品詳細
    const SETTING_GROUP_PRODUCT_DETAIL = 2;

    // カート
    const SETTING_GROUP_CART = 3;

    // 購入
    const SETTING_GROUP_SHOPPING = 4;

    // 注文確認
    const SETTING_GROUP_CONFIRM = 5;

    // 購入履歴
    const SETTING_GROUP_HISTORY = 6;

    /* 値 */

    /* 割引率 端数 */
    const DISCOUNT_RATE_TYPE_ROUND = RoundingType::ROUND;

    const DISCOUNT_RATE_TYPE_ROUND_DOWN = RoundingType::FLOOR;

    const DISCOUNT_RATE_TYPE_ROUND_UP = RoundingType::CEIL;

    public function __construct(
        EccubeConfig $eccubeConfig
    )
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * @return array
     */
    public function getGroups()
    {
        return [
            self::SETTING_GROUP_COMMON => 'pinpoint_sale.admin.config_group_common',
            self::SETTING_GROUP_PRODUCT_DETAIL => 'pinpoint_sale.admin.config_group_product_detail',
            self::SETTING_GROUP_CART => 'pinpoint_sale.admin.config_group_cart',
            self::SETTING_GROUP_SHOPPING => 'pinpoint_sale.admin.config_group_shopping',
            self::SETTING_GROUP_CONFIRM => 'pinpoint_sale.admin.config_group_confirm',
            self::SETTING_GROUP_HISTORY => 'pinpoint_sale.admin.config_group_history',
        ];
    }

    /**
     * @return array
     */
    public function getFormOptions()
    {
        return [
            // 端数処理
            self::SETTING_KEY_RATE_TYPE => [
                'choices' => [
                    'pinpoint_sale.admin.config_rate_1' => self::DISCOUNT_RATE_TYPE_ROUND,
                    'pinpoint_sale.admin.config_rate_2' => self::DISCOUNT_RATE_TYPE_ROUND_DOWN,
                    'pinpoint_sale.admin.config_rate_3' => self::DISCOUNT_RATE_TYPE_ROUND_UP,
                ],
                'expanded' => false,
                'multiple' => false,
                'attr' => ['class' => 'col-2']
            ],
            // 課税区分
            self::SETTING_KEY_DISCOUNT_TAX => [
                'choices' => [
                    'pinpoint_sale.admin.config_tax_1' => TaxType::TAXATION,
                    'pinpoint_sale.admin.config_tax_2' => TaxType::NON_TAXABLE,
                    'pinpoint_sale.admin.config_tax_3' => TaxType::TAX_EXEMPT,
                ],
                'expanded' => false,
                'multiple' => false,
                'attr' => ['class' => 'col-2']
            ],
            // セール名称
            self::SETTING_KEY_DISCOUNT_NAME => [
                'attr' => [
                    'class' => 'col-4'
                ],
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                ],
            ]
        ];
    }
}
