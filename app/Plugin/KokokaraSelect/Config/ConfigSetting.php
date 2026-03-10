<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/25
 */

namespace Plugin\KokokaraSelect\Config;


use Eccube\Common\EccubeConfig;
use Plugin\KokokaraSelect\Service\PlgConfigService\Common\ConfigSettingInterface;

class ConfigSetting implements ConfigSettingInterface
{

    /** @var EccubeConfig */
    protected $eccubeConfig;

    /* Key */

    // グループ名称未設定時
    const SETTING_KEY_GROUP_DEFAULT_NAME = 'SETTING_GROUP_DEFAULT_NAME';

    // 選択商品の呼び名
    const SETTING_KEY_SELECT_ITEM_DEFAULT_NAME = 'SETTING_KEY_SELECT_ITEM_DEFAULT_NAME';

    // 選択商品設定時の連続設定
    const SETTING_KEY_ADD_PRODUCT_REPEAT = 'SETTING_KEY_ADD_PRODUCT_REPEAT';

    // カートでの選択情報表示
    const SETTING_KEY_SELECT_ITEM_VIEW_CART = 'SETTING_KEY_SELECT_ITEM_VIEW_CART';

    // 購入での選択情報表示
    const SETTING_KEY_SELECT_ITEM_VIEW_SHOPPING = 'SETTING_KEY_SELECT_ITEM_VIEW_SHOPPING';

    // 注文確認での選択情報表示
    const SETTING_KEY_SELECT_ITEM_VIEW_CONFIRM = 'SETTING_KEY_SELECT_ITEM_VIEW_CONFIRM';

    // マイページでの選択情報表示
    const SETTING_KEY_SELECT_ITEM_VIEW_HISTORY = 'SETTING_KEY_SELECT_ITEM_VIEW_HISTORY';

    // 商品一覧でのカートボタン表示
    const SETTING_KEY_CART_BUTTON_VIEW = 'SETTING_KEY_CART_BUTTON_VIEW';

    // 固定セット商品の呼び名
    const SETTING_KEY_DIRECT_SELECT_ITEM_DEFAULT_NAME = 'SETTING_KEY_DIRECT_SELECT_ITEM_DEFAULT_NAME';


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

    /* デフォルトグループ名称 */
    const DEFAULT_GROUP_NAME = 'グループ';

    /* デフォルト選択商品呼び名 */
    const DEFAULT_SELECT_ITEM_NAME = '選択商品';

    /* デフォルト固定セット商品呼び名 */
    const DEFAULT_DIRECT_SELECT_ITEM_NAME = 'セット商品';

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
            self::SETTING_GROUP_COMMON => 'kokokara_select.admin.config_group_common',
            self::SETTING_GROUP_PRODUCT_DETAIL => 'kokokara_select.admin.config_group_product_detail',
            self::SETTING_GROUP_CART => 'kokokara_select.admin.config_group_cart',
            self::SETTING_GROUP_SHOPPING => 'kokokara_select.admin.config_group_shopping',
            self::SETTING_GROUP_CONFIRM => 'kokokara_select.admin.config_group_confirm',
            self::SETTING_GROUP_HISTORY => 'kokokara_select.admin.config_group_history',
        ];
    }

    /**
     * @return array
     */
    public function getFormOptions()
    {
        return [];
    }
}
