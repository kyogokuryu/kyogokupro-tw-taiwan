<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 *
 */

namespace Plugin\KokokaraSelect\Service\MultiCSVService\Entity;


class CsvInfoHeader
{

    // カラム名
    private $name;

    // ID
    private $id;

    // Key情報
    private $key;

    // 必須
    private $required;

    // 説明
    private $description;

    // 更新・登録対象
    private $target;

    // 削除対象
    private $delete;

    // 独自チェック処理
    private $checkFunction;

    // 独自データセット処理
    private $updateFunction;

    // 出力時データ変換処理
    private $exportConvertFunction;

    public function __construct()
    {
        // 初期値設定
        $this->key = false;
        $this->required = false;
        $this->description = "";
        $this->target = false;
        $this->delete = false;
        $this->checkFunction = null;
        $this->updateFunction = null;
        $this->exportConvertFunction = null;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return CsvInfoHeader
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return CsvInfoHeader
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function isKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $key
     * @return CsvInfoHeader
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    public function keyON()
    {
        $this->key = true;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param bool $required
     * @return CsvInfoHeader
     */
    public function setRequired($required)
    {
        $this->required = $required;
        return $this;
    }

    public function requiredON()
    {
        $this->required = true;
        return $this;
    }


    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     * @return CsvInfoHeader
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return bool
     */
    public function isTarget()
    {
        return $this->target;
    }

    /**
     * @param bool $target
     * @return CsvInfoHeader
     */
    public function setTarget($target)
    {
        $this->target = $target;
        return $this;
    }

    public function targetON()
    {
        $this->target = true;
        return $this;
    }

    /**
     * @return mixed
     */
    public function isDelete()
    {
        return $this->delete;
    }

    /**
     * @param mixed $delete
     * @return CsvInfoHeader
     */
    public function setDelete($delete)
    {
        $this->delete = $delete;
        return $this;
    }

    public function deleteON()
    {
        $this->delete = true;
        return $this;
    }

    /**
     * @return callable
     */
    public function getCheckFunction()
    {
        return $this->checkFunction;
    }

    public function isCheckFunction()
    {
        return (empty($this->checkFunction) ? false : true);
    }

    /**
     * @param callable $checkFunction
     * @return CsvInfoHeader
     */
    public function setCheckFunction(callable $checkFunction)
    {
        $this->checkFunction = $checkFunction;
        return $this;
    }

    /**
     * @return callable
     */
    public function getUpdateFunction()
    {
        return $this->updateFunction;
    }

    public function isUpdateFunction()
    {
        return (empty($this->updateFunction) ? false : true);
    }

    /**
     * @param callable $updateFunction
     * @return CsvInfoHeader
     */
    public function setUpdateFunction(callable $updateFunction)
    {
        $this->updateFunction = $updateFunction;
        return $this;
    }

    /**
     * @return callable
     */
    public function getExportConvertFunction()
    {
        return $this->exportConvertFunction;
    }

    /**
     * @param callable $exportConvertFunction
     * @return CsvInfoHeader
     */
    public function setExportConvertFunction($exportConvertFunction)
    {
        $this->exportConvertFunction = $exportConvertFunction;
        return $this;
    }

    public function isExportConvertFunction()
    {
        return (empty($this->exportConvertFunction) ? false : true);
    }

}
