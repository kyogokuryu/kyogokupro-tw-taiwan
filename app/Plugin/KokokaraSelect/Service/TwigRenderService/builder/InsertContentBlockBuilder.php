<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/03/21
 */

namespace Plugin\KokokaraSelect\Service\TwigRenderService\builder;



use Plugin\KokokaraSelect\Service\TwigRenderService\builder\base\ContentBlockBuilder;
use Plugin\KokokaraSelect\Service\TwigRenderService\Content\ContentBlock;

/**
 * テンプレート挿入用Builder
 *
 * Class InsertContentBlockBuilder
 */
class InsertContentBlockBuilder extends ContentBlockBuilder
{

    public function __construct()
    {
        $this->contentBlock = new ContentBlock();
    }

    /**
     * 挿入テンプレート設定
     *
     * @param $template
     * @param bool $include
     * @return $this
     */
    public function setTemplate($template, $include = true)
    {
        $this->contentBlock->setInsertTemplate($template, $include);
        return $this;
    }

    /**
     * 挿入スクリプト設定
     *
     * @param $scriptPath
     * @param bool $include
     * @return $this
     */
    public function setScript($scriptPath, $include = true)
    {
        $this->contentBlock->setInsertScript($scriptPath, $include);
        return $this;
    }

    /**
     * @param string $targetId
     * @return $this
     */
    public function setTargetId($targetId)
    {
        $this->contentBlock->setTargetId($targetId);
        return $this;
    }

    /**
     * @return $this jQuery after()
     */
    public function setInsertModeAfter()
    {
        $this->contentBlock->setInsertMode(ContentBlock::INSERT_AFTER);
        return $this;
    }

    /**
     * @return $this jQuery append()
     */
    public function setInsertModeAppend()
    {
        $this->contentBlock->setInsertMode(ContentBlock::INSERT_APPEND);
        return $this;
    }

    /**
     * @return $this jQuery wrap()
     */
    public function setInsertModeWrap()
    {
        $this->contentBlock->setInsertMode(ContentBlock::INSERT_WRAP);
        return $this;
    }

    /**
     * @return $this jQuery replaceWith()
     */
    public function setInsertModeReplaceWith()
    {
        $this->contentBlock->setInsertMode(ContentBlock::INSERT_REPLACE);
        return $this;
    }

    /**
     * @return $this jQuery remove()
     */
    public function setInsertModeRemove()
    {
        $this->contentBlock->setInsertMode(ContentBlock::INSERT_REMOVE);
        return $this;
    }

    /**
     * @return $this JQuery prepend()
     */
    public function setInsertModePrepend()
    {
        $this->contentBlock->setInsertMode(ContentBlock::INSERT_PREPEND);
        return $this;
    }

    /**
     * @return $this JQuery before()
     */
    public function setInsertModeBefore()
    {
        $this->contentBlock->setInsertMode(ContentBlock::INSERT_BEFORE);
        return $this;
    }

    /**
     * @return $this Jquery none;
     */
    public function setInsertModeNone()
    {
        $this->contentBlock->setInsertMode(ContentBlock::INSERT_DIRECT);
        return $this;
    }
}
