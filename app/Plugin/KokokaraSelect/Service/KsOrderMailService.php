<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/05/30
 */

namespace Plugin\KokokaraSelect\Service;


use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Eccube\Repository\MailTemplateRepository;
use Eccube\Service\MailService;

class KsOrderMailService
{

    use ConfigHelperTrait;

    /** @var EccubeConfig */
    protected $eccubeConfig;

    /** @var MailTemplateRepository */
    protected $mailTemplateRepository;

    /** @var MailService */
    protected $mailService;

    /** @var \Twig_Environment */
    protected $twig;

    // 置き換え用KEY
    public const KOKOKARA_SELECT_ADD_MAIL_MSG = "__kokokara_select_select_order_items__";

    public function __construct(
        EccubeConfig $eccubeConfig,
        MailTemplateRepository $mailTemplateRepository,
        MailService $mailService,
        \Twig_Environment $twig
    )
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->mailService = $mailService;
        $this->twig = $twig;
    }

    /**
     * 選択商品情報を受注メールへ追加
     *
     * @param \Swift_Message $message
     * @param Order $order
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function addOrderMessage(\Swift_Message $message, Order $order)
    {

        $body = $message->getBody();

        if (strpos($body, KsOrderMailService::KOKOKARA_SELECT_ADD_MAIL_MSG) === false) {
            // 処理不要
            return;
        }

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_order_mail_template_id']);

        // HTMLテンプレートが存在する場合
        $htmlFileName = $this->mailService->getHtmlTemplate($MailTemplate->getFileName());

        if (!is_null($htmlFileName)) {

            // HTML置き換え
            $replaceHtmlMessage = $this->getMessageHtml($order);

            // HTMLメッセージ取得
            $multipart = $message->getChildren();
            if (count($multipart) > 0) {
                $htmlBody = $multipart[0]->getBody();

                $newHtmlBody = str_replace(
                    self::KOKOKARA_SELECT_ADD_MAIL_MSG,
                    $replaceHtmlMessage,
                    $htmlBody
                );

                $message->getChildren()[0]->setBody($newHtmlBody);
            }
        }

        // 通常メッセージのリプレース
        $newBody = $this->replaceMessage($order, $body);

        $message->setBody($newBody);
    }

    /**
     * TEXT版メール文言を選択商品メール追加文言へ変換
     *
     * @param Order $order
     * @param $body
     * @return string|string[]
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function replaceMessage(Order $order, $body)
    {
        $replaceMessage = $this->getMessage($order);

        return str_replace(
            self::KOKOKARA_SELECT_ADD_MAIL_MSG,
            $replaceMessage,
            $body
        );
    }

    /**
     * HTML版 選択商品メール追加メッセージ取得
     *
     * @param Order $order
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function getMessageHtml(Order $order)
    {
        // HTML版
        return $this->twig->render('@KokokaraSelect/default/Mail/add_order.html.twig', [
            'kokokara_select' => $this->getKokokaraSelectName(),
            'kokokara_select_direct_select' => $this->getKokokaraSelectDirectSelectName(),
            'Order' => $order,
        ]);
    }

    /**
     * TEXT版 選択商品メール追加メッセージ取得
     *
     * @param Order $order
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function getMessage(Order $order)
    {
        // TEXT版
        return $this->twig->render('@KokokaraSelect/default/Mail/add_order.twig', [
            'kokokara_select' => $this->getKokokaraSelectName(),
            'kokokara_select_direct_select' => $this->getKokokaraSelectDirectSelectName(),
            'Order' => $order,
        ]);
    }
}
