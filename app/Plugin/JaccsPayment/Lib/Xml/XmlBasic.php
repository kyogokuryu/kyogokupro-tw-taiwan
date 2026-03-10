<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\JaccsPayment\Lib\Xml;

/**
 * XML作成基本クラス
 *
 * @author ouyou
 */
class XmlBasic implements \Serializable
{
    /**
     * @var string
     */
    protected $tagName;

    /**
     * @var DOMDocument
     */
    protected $domDocument;

    public function __construct()
    {
        //クラス名によりtag名を取る
        $arr = explode('\\', get_class($this));
        $this->setTagName(lcfirst(array_pop($arr)));
        unset($arr);
    }

    /**
     * @param string $tagName
     *
     * @return JACCS_XML_XmlBasic
     */
    protected function setTagName($tagName)
    {
        $this->tagName = $tagName;

        return $this;
    }

    /**
     * @return string
     */
    public function getTagName()
    {
        return $this->tagName;
    }

    /**
     * @return DOMDocument
     */
    public function getDOMDocument($isNew = false)
    {
        if (!$this->domDocument || $isNew) {
            $this->domDocument = new \DOMDocument('1.0', 'UTF-8');
        }

        return $this->domDocument;
    }

    /**
     * @param DOMDocument $domDocument
     *
     * @return JACCS_XML_XmlBasic
     */
    public function setDOMDocument(\DOMDocument $domDocument)
    {
        $this->domDocument = $domDocument;

        return $this;
    }

    /**
     * XML構成を作成する
     */
    public function toXmlDom()
    {
        $values = get_class_vars(get_class($this));

        unset($values['tagName']);
        unset($values['domDocument']);

        $root = $this->getDOMDocument()->createElement($this->getTagName());

        if (count($values)) {
            foreach ($values as $tagName => $value) {
                $func = 'get'.$tagName;
                $value = $this->$func();

                if (isset($value)) {
                    if (!is_array($value)) {
                        $items = [];
                        $items[] = $value;
                    } else {
                        $items = $value;
                    }
                    foreach ($items as $item) {
                        if ($item instanceof XmlBasic) {
                            $item->setDOMDocument($this->getDOMDocument());
                            $root->appendChild($item->toXmlDom());
                        } else {
                            $app = $this->getDOMDocument()->createElement($tagName, self::xmlencode($item));
                            $root->appendChild($app);
                        }
                    }
                }
            }
        }

        return $root;
    }

    protected static function xmlencode($tag)
    {
        $tag = str_replace('&', '&amp;', $tag);
        $tag = str_replace('<', '&lt;', $tag);
        $tag = str_replace('>', '&gt;', $tag);
        $tag = str_replace("'", '&apos;', $tag);
        $tag = str_replace('"', '&quot;', $tag);

        return $tag;
    }

    /**
     * XML TEXT出力
     *
     * @return string
     */
    public function toXmlText()
    {
        $this->getDOMDocument(true)->appendChild($this->toXmlDom());

        return $this->getDOMDocument()->saveXML();
    }

    /**
     * エラー情報を読み込み
     *
     * @param DOMNodeList $tags
     */
    protected function setErrorXml(\DOMNodeList $tags)
    {
        foreach ($tags as $tag) {
            $this->setErrors(new Errors());
            $this->getErrors()->deCodeXmlErrors($tag);
            break;
        }
    }

    public function serialize()
    {
        $data = [];
        $var = get_object_vars($this);
        unset($var['domDocument']);
        foreach ($var as $key => $value) {
            if ($value instanceof XmlBasic) {
                $data['serializable'][$key] = serialize($value);
            } else {
                $data[$key] = $value;
            }
        }

        return serialize($data);
    }

    public function unserialize($data)
    {
        $data = unserialize($data);

        foreach ($data as $key => $value) {
            if ($key == 'serializable') {
                foreach ($value as $objKey => $objValue) {
                    $this->$objKey = unserialize($objValue);
                }
            } else {
                $this->$key = $value;
            }
        }
    }
}
