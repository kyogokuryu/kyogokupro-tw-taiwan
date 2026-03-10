<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/19
 */

namespace Plugin\KokokaraSelect\Service\MultiCSVService;


use Eccube\Util\StringUtil;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MultiCsvService
{

    protected $validator;

    public function __construct(
        ValidatorInterface $validator
    )
    {
        $this->validator = $validator;
    }

    /**
     * 空白チェック
     *
     * @param $value
     * @return bool
     */
    public function isBlank($value)
    {
        if (StringUtil::isBlank($value)) {
            return true;
        }

        return false;
    }

    /**
     * 数値チェック
     *
     * @param $value
     * @return bool
     */
    public function isNumber($value)
    {
        if (preg_match('/^\d+$/', $value)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 金額0以上チェック
     *
     * @param $value
     * @return bool
     */
    public function isPrice($value)
    {
        $errors = $this->validator->validate($value, new GreaterThanOrEqual(['value' => 0]));
        return ($errors->count() === 0 ? true : false);
    }
}
