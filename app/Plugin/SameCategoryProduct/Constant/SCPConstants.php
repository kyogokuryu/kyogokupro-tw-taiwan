<?php
/*
 * Copyright(c) 2020 YAMATO.CO.LTD
 */
namespace Plugin\SameCategoryProduct\Constant;

// defineしか出来ないことをここでやる

// ディレクトリ区切り文字のラッパ
defined('DS') || define('DS', DIRECTORY_SEPARATOR);
// get_same_category_products.sql
defined('GET_SAME_CATEGORY_PRODUCTS') || define('GET_SAME_CATEGORY_PRODUCTS', dirname(dirname(__FILE__)) . DS . 'Resource' . DS . 'sql' . DS . 'get_same_category_products.sql');

/**
 * 定数用クラス
 *
 * @author masakiokada
 */
class SCPConstants
{

    private function __construct()
    {}

    /**
     * get_same_category_products.sql
     */
    const GET_SAME_CATEGORY_PRODUCTS = GET_SAME_CATEGORY_PRODUCTS;
}
