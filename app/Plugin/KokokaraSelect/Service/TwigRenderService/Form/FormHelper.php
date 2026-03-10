<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/25
 */

namespace Plugin\KokokaraSelect\Service\TwigRenderService\Form;


use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormView;

class FormHelper
{

    /**
     * Valid がNGとなったFormView取得
     *
     * @param $list
     * @param FormView $formView
     */
    public function validList(&$list, FormView $formView)
    {
        if (count($formView->children) == 0) {

            /** @var FormErrorIterator $formError */
            $formError = $formView->vars['errors'];

            // 対象
            if (!$formView->vars['valid']
                && $formError->count() > 0) {
                $list[] = $formView;
            }
        } else {
            foreach ($formView->children as $key => $child) {

                /** @var FormErrorIterator $formError */
                $formError = $child->vars['errors'];

                if (!$child->vars['valid']
                    && $formError->count() > 0) {
                    $list[] = $child;
                }

                $this->validList($list, $child);
            }
        }
    }

    /**
     * 指定した親の名称が存在するかチェック
     *
     * @param $parentName
     * @param FormView $formView
     * @return bool
     */
    public function isParentName($parentName, FormVIew $formView)
    {
        $result = false;

        while ($name = $this->getParentName($formView)) {

            if ($name == "") break;

            if ($parentName == $name) {
                $result = true;
                break;
            }

            $formView = $formView->parent;
        }

        return $result;
    }

    /**
     * Parentのnameを取得
     *
     * @param FormView $formView
     * @return string
     */
    private function getParentName(FormView $formView)
    {
        if ($formView->parent) {

            $parent = $formView->parent;

            // nameに数値が設定されている場合 親をチェック
            if (is_numeric($parent->vars['name'])) {
                return $this->getParentName($parent);
            } else {
                return $formView->parent->vars['name'];
            }

        }

        return "";
    }
}
