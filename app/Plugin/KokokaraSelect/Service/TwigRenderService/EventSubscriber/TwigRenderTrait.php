<?php


namespace Plugin\KokokaraSelect\Service\TwigRenderService\EventSubscriber;


use Eccube\Event\TemplateEvent;
use Plugin\KokokaraSelect\Service\TwigRenderService\builder\InsertContentBlockBuilder;
use Plugin\KokokaraSelect\Service\TwigRenderService\TwigRenderService;

trait TwigRenderTrait
{

    /** @var TwigRenderService */
    protected $twigRenderService;

    /** @var TwigRenderHelper */
    protected $twigRenderHelper;

    /**
     * @param TwigRenderService $twigRenderService
     * @required
     */
    public function setTwigRenderService(TwigRenderService $twigRenderService)
    {
        $this->twigRenderService = $twigRenderService;
    }

    /**
     * @param TwigRenderHelper $twigRenderHelper
     * @required
     */
    public function setTwigRenderHelper(TwigRenderHelper $twigRenderHelper)
    {
        $this->twigRenderHelper = $twigRenderHelper;
    }

    /**
     * 初期処理
     *
     * @param TemplateEvent $event
     */
    public function initRenderService(TemplateEvent $event)
    {
        $this->twigRenderService->initRenderService($event);
    }

    /**
     * InsertBuilder生成
     *
     * @return InsertContentBlockBuilder
     */
    public function createInsertBuilder()
    {
        return $this->twigRenderService->insertBuilder();
    }

    /**
     * 描画追加
     *
     * @param null $templateName
     * @param null $scriptName
     * @param bool $debug
     */
    public function addTwigRenderSnippet($templateName = null, $scriptName = null, $debug = false)
    {
        $this->twigRenderService->addSupportSnippet($templateName, $scriptName, $debug);
    }

    /**
     * 描画追加[低速]
     *
     * @param null $templateName
     * @param null $scriptName
     * @param bool $debug
     */
    public function addTwigRenderSnippetSlow($templateName = null, $scriptName = null, $debug = false)
    {
        $this->twigRenderService->addSupportSnippetSlow($templateName, $scriptName, $debug);
    }
}
