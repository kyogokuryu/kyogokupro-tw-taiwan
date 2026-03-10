<?php


namespace Plugin\KokokaraSelect\Service;


use Eccube\Common\Constant;

trait VersionHelperTrait
{

    /**
     * 複数配送利用可能バージョンチェック
     * 4.0.3 以降の場合はTrueを返却
     *
     * @return bool
     */
    public function isMultipleVersion()
    {
        // EC-CUBE 4.0.3 以降のみ固定選択商品の複数配送許可
        if (version_compare(Constant::VERSION, '4.0.3') < 0) {
            // 4.0.2以下
            return false;
        } else {
            return true;
        }
    }

    /**
     * 4.0.5 以降の場合はTrueを返却
     *
     * @return bool
     */
    public function isVersion405()
    {
        // EC-CUBE 4.0.5 以降のみ固定選択商品の複数配送許可
        if (version_compare(Constant::VERSION, '4.0.5') < 0) {
            // 4.0.5以下
            return false;
        } else {
            return true;
        }
    }
}
