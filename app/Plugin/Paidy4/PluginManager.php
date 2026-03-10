<?php

namespace Plugin\Paidy4;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Delivery;
use Eccube\Entity\Payment;
use Eccube\Entity\PaymentOption;
use Eccube\Repository\PaymentRepository;
use Eccube\Repository\DeliveryRepository;
use Plugin\Paidy4\Entity\Config;
use Plugin\Paidy4\Service\Method\Paidy;

class PluginManager extends AbstractPluginManager
{

    /**
     * PluginManager constructor.
     */
    public function __construct()
    {
        $this->origin_dir = __DIR__ . '/Resource/copy';
        $this->target_dir = __DIR__ . '/../../../html/template/default/assets/img/paidy';
    }

    public function install(array $config, ContainerInterface $container)
    {
        // リソースファイルのコピー
        $this->copyAssets();
    }

    public function enable(array $config, ContainerInterface $container)
    {
        $this->createPaidy($container);
        $this->createPlgPaidyConfig($container);
    }

    public function uninstall(array $config, ContainerInterface $container)
    {
        // リソースファイルの削除
        $this->removeAssets();
    }

    public function disable(array $config, ContainerInterface $container)
    {
        $this->hidePaidy($container);
    }

    private function createPaidy(ContainerInterface $container)
    {
        $entityManage = $container->get('doctrine.orm.entity_manager');
        //$paymentRepository = $container->get(PaymentRepository::class);
        $entityManager = $container->get('doctrine')->getManager();
        $paymentRepository = $entityManager->getRepository(Payment::class);

        $Payment = $paymentRepository->findOneBy(['method_class' => Paidy::class]);
        if ($Payment) {
            if ($Payment->isVisible() == false) {
                // 支払い方法「Paidy」非表示→表示
                $Payment->setVisible(true);
            }
            return $Payment;
        }

        $Payment = $paymentRepository->findOneBy([], ['sort_no' => 'DESC']);
        $sortNo = $Payment ? $Payment->getSortNo() + 1 : 1;

        // Paidy
        $Payment = new Payment();
        $Payment->setCharge(0);
        $Payment->setSortNo($sortNo);
        $Payment->setVisible(true);
        $Payment->setMethod('あと払い（ペイディ）');
        $Payment->setMethodClass(Paidy::class);
        $Payment->setRuleMin(0);

        $entityManage->persist($Payment);
        $entityManage->flush($Payment);

        // 各配送方法に登録
        //$deliveryRepository = $container->get(DeliveryRepository::class);
        $deliveryRepository = $entityManager->getRepository(Delivery::class);
        $Deliveries = $deliveryRepository->findAll();
        foreach ($Deliveries as $Delivery) {
            $PaymentOption = new PaymentOption();
            $PaymentOption->setDelivery($Delivery);
            $PaymentOption->setDeliveryId($Delivery->getId());
            $PaymentOption->setPayment($Payment);
            $PaymentOption->setPaymentId($Payment->getId());

            $entityManage->persist($PaymentOption);
            $entityManage->flush($PaymentOption);
        }

        return $Payment;
    }

    /**
     * create table plg_paidy_config
     *
     * @param ContainerInterface $container
     */
    public function createPlgPaidyConfig(ContainerInterface $container)
    {
        $entityManage = $container->get('doctrine.orm.entity_manager');
        $Config = $entityManage->find(Config::class, 1);
        if ($Config) {
            return;
        }

        // タイトル、注文受付メール タイトル初期値
        $eccubeConfig = new EccubeConfig($container);
        $title = $eccubeConfig['paidy']['title'];

        // 注文受付メール 説明文初期値
        $mailDescriptionText = <<<__EOS__
1ヶ月分のご利用金額は、翌月1日に確定し、3日までにメールとSMSでご利用明細とお支払い方法をお知らせします。
10日までにコンビニか銀行振込でお支払いください。
口座振替によるお支払いも可能で、引き落とし日は毎月12日（金融機関の休業日の場合は翌営業日）です。
1月及び5月請求分の引き落とし日は20日（金融機関の休業日の場合は翌営業日）になる場合がございます。

よくあるご質問: https://paidy.com/faq
__EOS__;

        // Webhookリクエスト元IP初期値
        $webhookRequestIp = '';

        // Paidy決済の説明初期値
        $descriptionText = <<<__EOS__
<a href="https://paidy.com/consumer" target="_blank"><img src="https://download.paidy.com/CO-banner-728x90.png" style="margin-bottom:15px;" alt="あと払い（ペイディ）"></a>
<li style="line-height:1;margin:-3px 0px;">クレジットカード不要。</li>
<li style="line-height:1;margin:-3px 0px;">メールアドレスと携帯番号の簡単決済。</li>
<li style="line-height:1;margin:-3px 0px;">複数回の決済もまとめて翌月10日にお支払いいただけます。</li>
<li style="line-height:1;margin:-3px 0px;">3回あと払いも可能＊Paidyアプリからの申し込み 詳細は<a href="https://paidy.com/payments/" target="_blank">こちら</a></li>
__EOS__;

        // 動作設定
        $Config = new Config();
        $Config->setPublicKey('');
        $Config->setSecretKey('');
        $Config->setLogoUrl('');
        $Config->setPaymentMethod($title);
        $Config->setStoreName('');
        $Config->setMailTitle($title);
        $Config->setMailDescriptionText($mailDescriptionText);
        $Config->setChargeType(1);          // Authorizeのみ
        $Config->setWebhookRequestIp($webhookRequestIp);
        $Config->setDescriptionDisp(true);  // 表示する
        $Config->setDescriptionText($descriptionText);
        $Config->setLinkDisp(true);         // 表示する

        $entityManage->persist($Config);
        $entityManage->flush($Config);
    }

    /**
     * hide paidy
     *
     * @param ContainerInterface $container
     */
    private function hidePaidy(ContainerInterface $container)
    {
        $entityManage = $container->get('doctrine.orm.entity_manager');
        //$paymentRepository = $container->get(PaymentRepository::class);
        $entityManager = $container->get('doctrine')->getManager();
        $paymentRepository = $entityManager->getRepository(Payment::class);
        $Payment = $paymentRepository->findOneBy(['method_class' => Paidy::class]);

        // 支払い方法「Paidy」表示→非表示
        $Payment->setVisible(false);
        $entityManage->persist($Payment);
        $entityManage->flush($Payment);
    }

    /**
     * ファイルをコピー
     */
    private function copyAssets()
    {
        $file = new Filesystem();

        $file->mkdir($this->target_dir);
        $file->mirror($this->origin_dir, $this->target_dir);
    }

    /**
     * コピーしたファイルを削除
     */
    private function removeAssets()
    {
        $file = new Filesystem();

        $file->remove($this->target_dir);
    }
}
