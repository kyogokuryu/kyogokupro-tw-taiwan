<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/10/25
 */

namespace Plugin\KokokaraSelect\Service\TwigRenderService\EventSubscriber;


use Eccube\Event\TemplateEvent;
use Plugin\KokokaraSelect\Service\TwigRenderService\Form\FormHelper;
use Plugin\KokokaraSelect\Service\TwigRenderService\TwigRenderService;
use Symfony\Component\Form\FormView;
use Twig\Environment;

class TwigRenderHelper
{

    /** @var TwigRenderService */
    protected $twigRenderService;

    /** @var FormHelper */
    protected $formHelper;

    protected $twig;

    // 規格入力チェック
    public const MAIN_INPUT_VALID = 'mainInputValid';

    // プラグイン入力チェック
    public const PLG_INPUT_VALID = "plgInputValid";

    // 商品規格登録用パーツテンプレート/JS名
    private const PRODUCT_CLASS_PARTS_NAME = '/Service/TwigRenderService/Resource/admin_product_class_parts.twig';
    private const PRODUCT_CLASS_PARTS_JS_NAME = '/Service/TwigRenderService/Resource/admin_product_class_parts_js.twig';

    private $nameSpace;

    public function __construct(
        TwigRenderService $twigRenderService,
        FormHelper $formHelper,
        Environment $twig
    )
    {
        $this->twigRenderService = $twigRenderService;
        $this->formHelper = $formHelper;
        $this->twig = $twig;

        $classNames = explode("\\", get_class($this));
        $this->nameSpace = $classNames[1];
    }

    /**
     * 商品規格登録へのForm拡張
     *
     * @param TemplateEvent $event
     * @param string $userAreaTemplateTitle リストタイトル用テンプレート
     * @param string $userAreaTemplate リストBody用テンプレート
     * @param string $plgRootFormName 対象Form名
     * @param string $titleButtonName 切り替えボタン名称
     * @param string $protoTypeName prototype用テンプレート
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function addProductClassEditArea(
        TemplateEvent $event,
        $userAreaTemplateTitle,
        $userAreaTemplate,
        $plgRootFormName,
        $titleButtonName,
        $protoTypeName = null
    )
    {

        /** @var FormView $form */
        $form = $event->getParameter('form');

        $titleButtonDefault = '通常設定に戻す';

        // エラー状態の判定
        $formProductClasses = $form['product_classes'];

        $list = [];

        /** @var FormView $formRow */
        foreach ($formProductClasses as $formRow) {
            $this->formHelper->validList($list, $formRow);
        }

        $mainValid = true;
        $plgValid = true;

        // 入力エラー時の制御
        foreach ($list as $item) {

            if ($this->formHelper
                ->isParentName($plgRootFormName, $item)) {

                $plgValid = false;
            } else {
                $mainValid = false;
            }
        }

        // 切り替えボタン追加
        $this->twigRenderService
            ->insertBuilder()
            ->find('form')
            ->find('.justify-content-between')
            ->find('div > span')
            ->eq(0)
            ->setTargetId($this->getTargetKey('change'))
            ->setInsertModeAppend();

        if (!$mainValid
            && !$plgValid) {

            // エラーメッセージ
            $this->twigRenderService
                ->insertBuilder()
                ->find('form')
                ->find('.justify-content-between')
                ->find('div')
                ->eq(0)
                ->setTargetId($this->getTargetKey('msg'))
                ->setInsertModeAppend();
        }

        // タイトル
        $this->twigRenderService
            ->insertBuilder()
            ->find('#ex-product_class > table > thead')
            ->eq(0)
            ->setTargetId($this->getTargetKey('thead'))
            ->setInsertModeAfter();

        // リスト
        $eachChild = $this->twigRenderService->eachChildBuilder();
        $eachChild
            ->findAndDataKey('#ex-product_class-', $this->getTargetKey('name'))
            ->targetFindThis()
            ->setInsertModeAfter();

        $this->twigRenderService
            ->eachBuilder()
            ->find('.' . $this->getTargetKey('target'))
            ->each($eachChild->build());

        // テンプレート名
        $partsTemplateName = $this->nameSpace . self::PRODUCT_CLASS_PARTS_NAME;

        // 共通パラメータ
        $commonParams = [
            'plgRootFormName' => $plgRootFormName,
            'rootTargetKey' => $this->getTargetKey(),
            'titleTargetKey' => $this->getTargetKey('change'),
            'titleButtonName' => $titleButtonName,
            'tableTitleTargetKey' => $this->getTargetKey('thead'),
            'rowTargetKey' => $this->getTargetKey('target'),
        ];

        // テンプレート生成
        $params = [
            'messageTargetKey' => $this->getTargetKey('msg'),
            'ClassName1' => $event->getParameter('ClassName1'),
            'ClassName2' => $event->getParameter('ClassName2'),
            'form' => $event->getParameter('form'),
            'nameTargetKey' => $this->getTargetKey('name'),
            'prototype' => $protoTypeName,
            'userAreaTemplateTitle' => $userAreaTemplateTitle,
            'userAreaTemplate' => $userAreaTemplate,
        ];

        $params = array_merge($commonParams, $params);
        $insertTemplate = $this->twigRenderService->readTemplate($partsTemplateName, $params);

        // JSファイル名
        $partsJsName = $this->nameSpace . self::PRODUCT_CLASS_PARTS_JS_NAME;

        // JS生成
        $jsParams = [
            'titleButtonDefaultName' => $titleButtonDefault,
            self::MAIN_INPUT_VALID => $mainValid,
            self::PLG_INPUT_VALID => $plgValid,
        ];

        $jsParams = array_merge($commonParams, $jsParams);
        $insertScript = $this->twigRenderService->readTemplate($partsJsName, $jsParams);

        // パーツ挿入
        $this->twigRenderService
            ->insertBuilder()
            ->setInsertModeNone()
            ->setTemplate($insertTemplate, false)
            ->setScript($insertScript, false);

    }

    private function getTargetKey($suffix = "")
    {
        return strtolower($this->nameSpace . '_' . $suffix);
    }

}
