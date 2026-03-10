<?php
/**
 * Created by SYSTEM_KD
 * Date: 2019-03-20
 */

namespace Plugin\KokokaraSelect\Service\TwigRenderService;


use Eccube\Event\TemplateEvent;
use Plugin\KokokaraSelect\Service\TwigRenderService\builder\base\ContentBlockBuilderInterface;
use Plugin\KokokaraSelect\Service\TwigRenderService\builder\EachChildContentBlockBuilder;
use Plugin\KokokaraSelect\Service\TwigRenderService\builder\EachContentBlockBuilder;
use Plugin\KokokaraSelect\Service\TwigRenderService\builder\InsertContentBlockBuilder;
use Twig_TemplateWrapper;

/**
 * Class TwigRenderService
 *
 * @version 3.7.0
 */
class TwigRenderService
{

    /** @var string */
    private $pluginName;

    /** @var string */
    private $supportBlockId;

    /** @var TemplateEvent */
    private $templateEvent;

    /** @var ContentBlockBuilderInterface[] */
    private $contentBlocks;

    private $twig;

    private $supportBlockIdKey;

    private $templateNameKey;

    private $controlScriptNameKey;

    private $ContentBlocksNameKey;

    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
        $this->contentBlocks = [];
    }

    /**
     * 初期化
     *
     * @param TemplateEvent $event
     * @return TwigRenderService
     */
    public function initRenderService(TemplateEvent $event)
    {
        $this->templateEvent = $event;

        // ContentBlock用のKey生成
        $nameSpaces = explode("\\", __NAMESPACE__);
        $this->pluginName = $nameSpaces[1];
        $this->supportBlockId = $this->pluginName . "_root";

        $this->supportBlockIdKey = $this->pluginName . 'support_block_id';
        $this->templateNameKey = $this->pluginName . 'template_name';
        $this->controlScriptNameKey = $this->pluginName . 'control_script_name';
        $this->ContentBlocksNameKey = $this->pluginName . 'ContentBlocks';

        return $this;
    }

    /**
     * テンプレート挿入用Builder返却
     *
     * @return InsertContentBlockBuilder
     */
    public function insertBuilder()
    {
        $builder = new InsertContentBlockBuilder();

        $this->contentBlocks[] = $builder;

        return $builder;
    }

    /**
     * Each用Builder
     *
     * @return EachContentBlockBuilder
     */
    public function eachBuilder()
    {
        $builder = new EachContentBlockBuilder();

        $this->contentBlocks[] = $builder;

        return $builder;
    }

    /**
     * Each Child用Builder
     *
     * @return EachChildContentBlockBuilder
     */
    public function eachChildBuilder()
    {
        $builder = new EachChildContentBlockBuilder();
        return $builder;
    }

    /**
     * テンプレート読み込み
     *
     * @param $template
     * @param array $params
     * @param bool $render
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function readTemplate($template, $params = [], $render = true)
    {

        if ($render) {
            /** @var Twig_TemplateWrapper $twigTemplate */
            $twigTemplate = $this->twig->load($template);

            return $twigTemplate->render($params);
        }

        return $this->twig->getLoader()->getSourceContext($template)->getCode();
    }

    /**
     * テンプレート拡張用Snippet追加[通常]
     *
     * @param null $templateName
     * @param null $scriptName
     * @param bool $debug
     */
    public function addSupportSnippet($templateName = null, $scriptName = null, $debug = false)
    {
        $this->addSnippet($templateName, $scriptName, false, $debug);
    }

    /**
     * テンプレート拡張Snippet追加[低速]
     *
     * @param null $templateName
     * @param null $scriptName
     * @param bool $debug
     */
    public function addSupportSnippetSlow($templateName = null, $scriptName = null, $debug = false)
    {
        $this->addSnippet($templateName, $scriptName, true, $debug);
    }

    /**
     * テンプレート拡張用Snippet追加
     *
     * @param null $templateName
     * @param null $scriptName
     * @param bool $slow
     * @param bool $debug
     */
    private function addSnippet($templateName = null, $scriptName = null, $slow = false, $debug = false)
    {
        $this->templateEvent->setParameter($this->supportBlockIdKey, $this->supportBlockId);

        if (!is_null($templateName)) {
            // 共通テンプレート
            $this->templateEvent->setParameter($this->templateNameKey, $templateName);
        }

        if (!is_null($scriptName)) {
            // 共通スクリプト
            $this->templateEvent->setParameter($this->controlScriptNameKey, $scriptName);
        }

        $contentBlocks = [];
        /** @var ContentBlockBuilderInterface $contentBlock */
        foreach ($this->contentBlocks as $contentBlock) {
            $contentBlocks[] = $contentBlock->build();
        }

        // 重複しない名称を付与
        $this->templateEvent->setParameter($this->ContentBlocksNameKey, $contentBlocks);

        // Template 反映
        $this->templateEvent->addSnippet($this->getRenderSupportTwig($slow, $debug), false);
    }

    /**
     * @param bool $debug true:デバッグモード
     * @return string
     */
    private function getRenderSupportTwig($slow, $debug)
    {

        ob_start();
        require __DIR__ . "/Resource/twig_render_service.twig";
        $renderSupportTwig = ob_get_contents();
        ob_end_clean();

        if($slow) {
            // 実行タイミングを変更
            $renderSupportTwig = str_replace("window.addEventListener('DOMContentLoaded', function () {", "$(function () {", $renderSupportTwig);
        }

        if ($debug) {
            // デバッグモードの場合は一時コードを消さない
            $renderSupportTwig = str_replace('$("#{{ __support_block_id__ }}").remove();', "", $renderSupportTwig);
        }

        // Key情報置換
        $renderSupportTwig = str_replace('__support_block_id__', $this->supportBlockIdKey, $renderSupportTwig);
        $renderSupportTwig = str_replace('__template_name__', $this->templateNameKey, $renderSupportTwig);
        $renderSupportTwig = str_replace('__control_script_name__', $this->controlScriptNameKey, $renderSupportTwig);
        $renderSupportTwig = str_replace('__ContentBlocks__', $this->ContentBlocksNameKey, $renderSupportTwig);

        return $renderSupportTwig;
    }
}
