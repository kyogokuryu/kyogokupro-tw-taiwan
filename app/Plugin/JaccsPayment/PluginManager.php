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

namespace Plugin\JaccsPayment;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Layout;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Entity\Payment;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\PageRepository;
use Eccube\Repository\PaymentRepository;
use Plugin\JaccsPayment\Entity\PaymentStatus;
use Plugin\JaccsPayment\Repository\PaymentStatusRepository;
use Plugin\JaccsPayment\Service\Method\JaccsPayment;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class PluginManager
 */
class PluginManager extends AbstractPluginManager
{
    private $urls = [
        'jaccs_examination_complete' => [
            'name' => '商品購入/ご注文完了/アトディーネ審査中',
            'url' => 'jaccs_examination_complete',
            'filename' => '@JaccsPayment/default/jaccs_examination_complete',
        ],
        'jaccs_ng' => [
            'name' => '商品購入/アトディーネ審査NG',
            'url' => 'jaccs_ng',
            'filename' => '@JaccsPayment/default/jaccs_ng',
        ],
        'jaccs_error' => [
            'name' => '商品購入/アトディーネ登録エラー',
            'url' => 'jaccs_error',
            'filename' => '@JaccsPayment/default/jaccs_error',
        ],
    ];

    /**
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        $this->createPayment($container);
        $this->addJasccPaymentStatus($container);
        foreach ($this->urls as $data) {
            $em = $container->get('doctrine.orm.entity_manager');
            $this->createPage($em, $container, $data['name'], $data['url'], $data['filename']);
        }

        $imageFile = $container->getParameter('eccube_save_image_dir').'/jaccs_default_468x64.gif';

        $fs = new Filesystem();
        if (!$fs->exists($imageFile)) {
            $fs->copy($container->getParameter('plugin_realdir').'/JaccsPayment/Resource/assets/img/jaccs_default_468x64.gif', $imageFile);
        }
    }

    /**
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');

        // ページを削除
        foreach ($this->urls as $data) {
            $this->removePage($em, $data['url']);
        }
    }

    /**
     * @param ContainerInterface $container
     */
    private function createPayment(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $paymentRepository = $container->get(PaymentRepository::class);

        $Payment = $paymentRepository->findOneBy(['method_class' => JaccsPayment::class]);
        if ($Payment) {
            return;
        }

        $Payment = $paymentRepository->findOneBy([], ['sort_no' => 'DESC']);
        $sortNo = $Payment ? $Payment->getSortNo() + 1 : 1;

        $Payment = new Payment();
        $Payment->setCharge(0);
        $Payment->setSortNo($sortNo);
        $Payment->setVisible(true);
        $Payment->setMethod('アトディーネ:後払い決済(コンビニエンスストア・銀行)'); // todo nameでいいんじゃないか
        $Payment->setMethodClass(JaccsPayment::class);
        $Payment->setRuleMin(1);
        $Payment->setRuleMax(55000);

        $entityManager->persist($Payment);
        $entityManager->flush($Payment);
    }

    /**
     * @param ContainerInterface $container
     */
    protected function addJasccPaymentStatus(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $allStatus = $container->get(PaymentStatusRepository::class)->findAllArray();

        if (!array_key_exists(PaymentStatus::JACCS_ORDER_PRE_END, $allStatus)) {
            $sortNo = 1;

            $addStatus = new PaymentStatus();
            $addStatus->setId(PaymentStatus::JACCS_ORDER_PRE_END);
            $addStatus->setName('アトディーネ審査OK');
            $addStatus->setSortNo($sortNo);
            $entityManager->persist($addStatus);

            $addStatus = new PaymentStatus();
            $addStatus->setId(PaymentStatus::JACCS_ORDER_PENDING);
            $addStatus->setName('アトディーネ審査中');
            $addStatus->setSortNo(++$sortNo);
            $entityManager->persist($addStatus);

            $addStatus = new PaymentStatus();
            $addStatus->setId(PaymentStatus::JACCS_ORDER_NG);
            $addStatus->setName('アトディーネ審査NG');
            $addStatus->setSortNo(++$sortNo);
            $entityManager->persist($addStatus);

            $addStatus = new PaymentStatus();
            $addStatus->setId(PaymentStatus::JACCS_ORDER_ERROR);
            $addStatus->setName('アトディーネ登録エラー');
            $addStatus->setSortNo(++$sortNo);
            $entityManager->persist($addStatus);

            $addStatus = new PaymentStatus();
            $addStatus->setId(PaymentStatus::JACCS_ORDER_PENDING_MANUAL);
            $addStatus->setName('アトディーネ審査保留');
            $addStatus->setSortNo(++$sortNo);
            $entityManager->persist($addStatus);

            $addStatus = new PaymentStatus();
            $addStatus->setId(PaymentStatus::JACCS_ORDER_NOW_ORDER_NG);
            $addStatus->setName('アトディーネ即時審査NG');
            $addStatus->setSortNo(++$sortNo);
            $entityManager->persist($addStatus);

            $addStatus = new PaymentStatus();
            $addStatus->setId(PaymentStatus::JACCS_ORDER_CANCEL);
            $addStatus->setName('アトディーネ取引キャンセル');
            $addStatus->setSortNo(++$sortNo);
            $entityManager->persist($addStatus);

            $entityManager->flush();
        }
    }

    /**
     * @param EntityManagerInterface $em
     * @param ContainerInterface $container
     * @param $name
     * @param $url
     * @param $filename
     */
    protected function createPage(EntityManagerInterface $em, ContainerInterface $container, $name, $url, $filename)
    {
        if (!$container->get(PageRepository::class)->findOneBy(['url' => $url])) {
            $Page = new Page();
            $Page->setEditType(Page::EDIT_TYPE_DEFAULT);
            $Page->setName($name);
            $Page->setUrl($url);
            $Page->setFileName($filename);

            // DB登録
            $em->persist($Page);
            $em->flush($Page);
            $Layout = $em->find(Layout::class, Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);
            $PageLayout = new PageLayout();
            $PageLayout->setPage($Page)
                ->setPageId($Page->getId())
                ->setLayout($Layout)
                ->setLayoutId($Layout->getId())
                ->setSortNo(0);
            $em->persist($PageLayout);
            $em->flush($PageLayout);
        }
    }

    /**
     * @param EntityManagerInterface $em
     * @param $url
     */
    protected function removePage(EntityManagerInterface $em, $url)
    {
        $Page = $em->getRepository(Page::class)->findOneBy(['url' => $url]);

        if (!$Page) {
            return;
        }
        foreach ($Page->getPageLayouts() as $PageLayout) {
            $em->remove($PageLayout);
            $em->flush($PageLayout);
        }

        $em->remove($Page);
        $em->flush($Page);
    }
}
