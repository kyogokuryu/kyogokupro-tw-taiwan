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

use Doctrine\ORM\EntityManager;
use Eccube\Entity\Order;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\OrderRepository;
use Plugin\JaccsPayment\Entity\Config;
use Plugin\JaccsPayment\Entity\History;
use Plugin\JaccsPayment\Entity\PaymentStatus;
use Plugin\JaccsPayment\Entity\ShippingRequest;
use Plugin\JaccsPayment\Form\Type\Admin\PaymentStatusType;
use Plugin\JaccsPayment\Form\Type\Admin\ShippingRequestType;
use Plugin\JaccsPayment\Lib\Inc;
use Plugin\JaccsPayment\Lib\Xml\Getauthori;
use Plugin\JaccsPayment\Repository\ConfigRepository;
use Plugin\JaccsPayment\Repository\HistoryRepository;
use Plugin\JaccsPayment\Repository\PaymentStatusRepository;
use Plugin\JaccsPayment\Repository\ReOrderRepository;
use Plugin\JaccsPayment\Service\Method\JaccsPayment;
use Plugin\JaccsPayment\Util\GetauthoriBatch;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class JaccsPaymentEvent implements EventSubscriberInterface
{
    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var PaymentStatusRepository
     */
    protected $paymentStatusRepository;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var HistoryRepository
     */
    protected $historyRepository;

    /**
     * @var ReOrderRepository
     */
    protected $reOrderRepository;

    /**
     * @var GetauthoriBatch
     */
    protected $getauthoriBatch;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * JaccsPaymentEvent constructor.
     * @param OrderRepository $orderRepository
     * @param PaymentStatusRepository $paymentStatusRepository
     * @param ConfigRepository $configRepository
     * @param HistoryRepository $historyRepository
     * @param ReOrderRepository $reOrderRepository
     * @param GetauthoriBatch $getauthoriBatch
     * @param Router $router
     * @param EntityManager $entityManager
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(
        OrderRepository $orderRepository,
        PaymentStatusRepository $paymentStatusRepository,
        ConfigRepository $configRepository,
        HistoryRepository $historyRepository,
        ReOrderRepository $reOrderRepository,
        GetauthoriBatch $getauthoriBatch,
        Router $router,
        EntityManager $entityManager,
        FormFactoryInterface $formFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->paymentStatusRepository = $paymentStatusRepository;
        $this->configRepository = $configRepository;
        $this->historyRepository = $historyRepository;
        $this->reOrderRepository = $reOrderRepository;
        $this->getauthoriBatch = $getauthoriBatch;
        $this->router = $router;
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Mypage/index.twig' => 'onMypageIndex',
            'Mypage/history.twig' => 'onMypageHistory',
            'Shopping/index.twig' => 'onDefaultShoppingIndex',
            'Shopping/confirm.twig' => 'onDefaultShoppingConfirm',
            '@admin/Order/edit.twig' => 'onAdminOrderEditTwig',
            '@admin/Order/index.twig' => 'onAdminOrderIndex',
       //     '@admin/index.twig' => 'onAdminIndex',
            EccubeEvents::ADMIN_ORDER_EDIT_INDEX_INITIALIZE => 'onAdminOrderEditIndexInitalize',
            EccubeEvents::ADMIN_ORDER_INDEX_INITIALIZE => 'onAdminIndexInitalize',
            EccubeEvents::ADMIN_ORDER_INDEX_SEARCH => 'onAdminIndexSearch',
            '@JaccsPayment/default/jaccs_error.twig' => 'onJaccsPaymentJaccsErrorTwig',
        ];
    }

    /**
     * @param TemplateEvent $event
     */
    public function onMypageIndex(TemplateEvent $event)
    {
        $source = $event->getSource();

        $oldB = '<dd>{{ Order.CustomerOrderStatus }}</dd>';
        $reStr = '<dd>{% if Order.isJaccsPayment %}{{ Order.CustomerJacccsStatus }}{% else %}{{ Order.CustomerOrderStatus }}{% endif %}</dd>';

        $source = str_replace($oldB, $reStr, $source);

        $event->setSource($source);
    }

    /**
     * @param TemplateEvent $event
     */
    public function onMypageHistory(TemplateEvent $event)
    {
        $source = $event->getSource();

        $oldB = '<dd>{{ Order.CustomerOrderStatus }}</dd>';
        $reStr = '<dd>{% if Order.isJaccsPayment %}{{ Order.CustomerJacccsStatus }}{% else %}{{ Order.CustomerOrderStatus }}{% endif %}</dd>';

        $source = str_replace($oldB, $reStr, $source);

        $event->setSource($source);
    }

    /**
     * @param TemplateEvent $event
     */
    public function onAdminIndex(TemplateEvent $event)
    {
        $source = $event->getSource();

        $oldB = '</div><!-- /.c-primaryCol -->';
        $reStr = '
        <div class="row">
            <div class="col mb-4">
                <div id="shop-statistical" class="card rounded border-0 h-100">
                    <div class="card-header">
                        <div class="d-inline-block" data-toggle="tooltip" data-placement="top"
                             title="Tooltip">
                            <span class="card-title">{{ \'jaccs_payment.admin.index.title\'|trans }}</span>
                            <i class="fa fa-question-circle fa-lg ml-1" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="d-block p-3 border border-top-0 border-left-0 border-right-0">
                            <div class="row align-items-center">
                                <div class="col-2 align-middle text-center">
                                    <i class="fa fa-inbox fa-2x text-secondary" aria-hidden="true"></i>
                                </div>
                                <div class="col p-0">
                                    <span class="align-middle">{{ \'jaccs_payment.admin.index.1\'|trans }}</span>
                                </div>
                                <div class="col-auto text-right align-middle">
                                    <span class="h4 align-middle font-weight-normal">{{ jaccsStatusCount[20002]|number_format }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="d-block p-3 border border-top-0 border-left-0 border-right-0">
                            {# TODO: Link, Number of products handled #}
                            <div class="row align-items-center">
                                <div class="col-2 align-middle text-center">
                                    <i class="fa fa-inbox fa-2x text-secondary" aria-hidden="true"></i>
                                </div>
                                <div class="col p-0">
                                    <span class="align-middle">{{ \'jaccs_payment.admin.index.2\'|trans }}</span>
                                </div>
                                <div class="col-auto text-right align-middle">
                                    <span class="h4 align-middle font-weight-normal">{{ jaccsStatusCount[20003]|number_format }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="d-block p-3 border border-top-0 border-left-0 border-right-0">
                            <div class="row align-items-center">
                                <div class="col-2 align-middle text-center">
                                    <i class="fa fa-inbox fa-2x text-secondary" aria-hidden="true"></i>
                                </div>
                                <div class="col p-0">
                                    <span class="align-middle">{{ \'jaccs_payment.admin.index.3\'|trans }}</span>
                                </div>
                                <div class="col-auto text-check align-middle">
                                    <span class="h4 align-middle font-weight-normal">{{ jaccsStatusCount[20005]|number_format }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="d-block p-3 border border-top-0 border-left-0 border-right-0">
                            <div class="row align-items-center">
                                <div class="col-2 align-middle text-center">
                                    <i class="fa fa-inbox fa-2x text-secondary" aria-hidden="true"></i>
                                </div>
                                <div class="col p-0">
                                    <span class="align-middle">{{ \'jaccs_payment.admin.index.4\'|trans }}</span>
                                </div>
                                <div class="col-auto text-check align-middle">
                                    <span class="h4 align-middle font-weight-normal">{{ jaccsStatusCount[20006]|number_format }}</span>
                                </div>
                            </div>
                        </div>
                        {% if jaccsConfig.batch_type != 1 %}
                        <div class="d-block p-3 border border-top-0 border-left-0 border-right-0">
                        
                            <button class="btn btn-ec-regular" type="button" onclick=\'window.open("{{ url(\'jaccs_payment_admin_batch\', {\'mode\': \'start\'}) }}", "batch", "height=800, width=800, top=10, left=10, toolbar=no, menubar=no, scrollbars=no, resizable=no,location=no, status=no")\'>
                                <i class="fa mr-1 text-secondary"></i><span>{{ \'jaccs_payment.admin.index.batch\'|trans }}</span>
                            </button>
                        </div>
                        {% endif %}
                    </div>
                </div><!-- /#shop-statistical -->
            </div>
            <div class="col mb-4">
            </div>
        </div>
        </div>';

        $source = str_replace($oldB, $reStr, $source);

        $event->setSource($source);

        $allStatus = $this->paymentStatusRepository->findBy([
            'id' => [PaymentStatus::JACCS_ORDER_PRE_END, PaymentStatus::JACCS_ORDER_PENDING, PaymentStatus::JACCS_ORDER_NG, PaymentStatus::JACCS_ORDER_ERROR, PaymentStatus::JACCS_ORDER_PENDING_MANUAL],
        ]);

        $jaccsStatusCount = [];

        if (count($allStatus)) {
            $data = $this->orderRepository->createQueryBuilder('o')
                ->select('count(o.id) con, IDENTITY(o.JaccsPaymentPaymentStatus) status')
                ->groupBy('o.JaccsPaymentPaymentStatus')
                ->where('o.JaccsPaymentPaymentStatus in (:status)')
                ->setParameter('status', $allStatus)
                ->getQuery()
                ->getArrayResult();

            foreach ($data as $item) {
                $jaccsStatusCount[$item['status']] = $item['con'];
            }

            foreach ($allStatus as $data) {
                if (!array_key_exists($data->getId(), $jaccsStatusCount)) {
                    $jaccsStatusCount[$data->getId()] = 0;
                }
            }
        }

        $event->setParameter('jaccsStatusCount', $jaccsStatusCount);

        $config = $this->configRepository->get();
        if (!$config) {
            $config = new Config();
            $config->setBatchType(1);
        }
        $event->setParameter('jaccsConfig', $config);
    }

    /**
     * @param TemplateEvent $event
     */
    public function onDefaultShoppingConfirm(TemplateEvent $event)
    {
        $source = $event->getSource();

        $oldB = "<form id=\"shopping-form\" method=\"post\" action=\"{{ url('shopping_checkout') }}\">";
        $reStar = "<form id=\"shopping-form\" method=\"post\" action=\"{{ url('shopping_checkout') }}\">
    <input type=\"hidden\" id=\"fraudbuster\" name=\"fraudbuster\" />
    <script type=\"text/javascript\" language=\"javascript\" src=\"".Inc::fraudbuster_js_url.'"></script>
';

        $source = str_replace($oldB, $reStar, $source);

        $event->setSource($source);
    }

    /**
     * @param TemplateEvent $event
     */
    public function onDefaultShoppingIndex(TemplateEvent $event)
    {
        $source = $event->getSource();

        $oldB = '{% if Payment.payment_image is not null %}';
        $reStar = "
{% if Payment.method_class == 'Plugin\\\JaccsPayment\\\Service\\\Method\\\JaccsPayment' %}
    <a href=\"http://c.atodene.jp/rule/\" target=\"_blank\"><img src=\"{{ asset('jaccs_default_468x64.gif', 'save_image') }}\" alt=\"後払い決済サービス「アトディーネ」\" /></a><br />
    ジャックス・ペイメント・ソリューションズ株式会社が提供する後払い決済サービスです。<br />
    購入商品の到着を確認してから、コンビニエンスストア・金融機関で後払いできる安心・簡単な決済方法です。<br />
    請求書は、商品とは別に郵送されますので、発行から14日以内にお支払ください。<br />
    <br />
    アトディーネ決済手数料：<span style=\"font-weight:bold;\">{{ Payment.charge|number_format }} 円（税込）</span><br />
    {% if Payment.rule_max %}
    ご利用限度額：<span style=\"color:#ff0000;\">累計残高で{{ Payment.rule_max|number_format }} 円（税込）迄（他店舗含む）</span><br />
    {% endif %}
    <br />
    <span style=\"color:#ff0000;\">お客様は上記バナーをクリックし「注意事項」及び「個人情報の取扱いについて」に記載の内容をご確認・ご承認の上、<br />
    本サービスのお申し込みを行うものとします。<br />
    ※ご承認いただけない場合は本サービスのお申し込みをいただけませんので、ご了承ください。<br />
    支払い伝票は後日にて発送させていただきます、商品とは同梱で発送されませんのであらかじめご了承ください。<br />
    アトディーネ:後払い決済による商品返品は出来かねますのでご注意ください。
    </span>
    <br />
    <br />
    ※以下の場合サービスをご利用いただけません。予めご了承ください。<br />
    ・郵便局留め・運送会社営業所留め（営業所での引き取り）<br />
    ・商品の転送<br />
    ・コンビニ店頭での受け渡し<br />
    ※ご本人様確認のため、ご連絡させて頂くことがございます。予めご了承ください。
{% endif %}
{% if Payment.payment_image is not null %}";

        $source = str_replace($oldB, $reStar, $source);

        $event->setSource($source);
    }

    /**
     * @param TemplateEvent $event
     */
    protected function reHtmlOrderEditMain(TemplateEvent $event)
    {

        $source = $event->getSource();

        $oldB = "{{ 'admin.order.orderer'|trans }}";
        $reStr = "
                {% if isJaccs %}
                                アトディーネ</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class=\"collapse show ec-cardCollapse\" id=\"jasscInfo\">
                    <div class=\"card-body\">
                        {% if reHistoryData %}
                            {% if Order.JaccsPaymentPaymentStatus %}
                                <div class=\"row mb-2\">
                                    <div class=\"col-3\">
                                        <div class=\"d-inline-block\"><span>決済ステータス</span></div>
                                    </div>
                                    <div class=\"col\">
                                        {{ Order.JaccsPaymentPaymentStatus.name }}
                                    </div>
                                </div>
                            {% endif %}
                            {% if reHistoryData.transaction_id %}
                                <div class=\"row mb-2\">
                                    <div class=\"col-3\">
                                        <div class=\"d-inline-block\"><span>お問い合わせ番号</span></div>
                                    </div>
                                    <div class=\"col\">
                                        {{ reHistoryData.transaction_id }}
                                    </div>
                                </div>
                            {% endif %}
                            {% if reHistoryDataDetail.auto_authoriresult %}
                                <div class=\"row mb-2\">
                                    <div class=\"col-3\">
                                        <div class=\"d-inline-block\"><span>自動審査結果</span></div>
                                    </div>
                                    <div class=\"col\">
                                        {{ reHistoryDataDetail.auto_authoriresult }}
                                    </div>
                                </div>
                            {% endif %}
                            {% if reHistoryDataDetail.manual_authoriresult %}
                                <div class=\"row mb-2\">
                                    <div class=\"col-3\">
                                        <div class=\"d-inline-block\"><span>目視審査結果</span></div>
                                    </div>
                                    <div class=\"col\">
                                        {{ reHistoryDataDetail.manual_authoriresult }}
                                    </div>
                                </div>
                            {% endif %}
                            {% if reHistoryDataDetail.manual_authorireasons|length %}
                                <div class=\"row mb-2\">
                                    <div class=\"col-3\">
                                        <div class=\"d-inline-block\"><span>目視審査結果理由</span></div>
                                    </div>
                                    <div class=\"col\">
                                        {% for manual_authorireason in reHistoryDataDetail.manual_authorireasons %}
                                            {{ manual_authorireason }}<br/>
                                        {% endfor %}
                                    </div>
                                </div>
                            {% endif %}
                        {% endif %}
                        
                        {% if jacssError|length %}
                            <div class=\"row mb-2\">
                                <div class=\"col-3\">
                                    <div class=\"d-inline-block\"><span>アトディーネ登録エラー</span></div>
                                </div>
                                <div class=\"col\">
                                    {% for error in jacssError %}
                                        {{ error }}<br/>
                                    {% endfor %}
                                </div>
                            </div>
                        {% endif %}
                        
                        {% if (reHistoryData and reHistoryData.create_date) %}
                            <div class=\"row mb-2\">
                                <div class=\"col-3\">
                                    <div class=\"d-inline-block\"><span>通信時間</span></div>
                                </div>
                                <div class=\"col\">
                                    {{ reHistoryData.create_date|date_min }}
                                </div>
                            </div>
                        {% endif %}
                       
                        <div class=\"row mb-2\">
                            <div class=\"col-3\">
                                <div class=\"d-inline-block\"><span>操作</span></div>
                            </div>
                            <div class=\"col\">
                                {% if isSendTransaction %}
                                    <a class=\"btn-normal\" href=\"javascript:;\" id=\"sendTransaction\">取引通信</a>
                                {% endif %}
                                {% if isSendGetauthori %}
                                    <a class=\"btn-normal\" href=\"javascript:;\" id=\"sendChange\">取引変更</a>
                                    <a class=\"btn-normal\" href=\"javascript:;\" id=\"sendGetauthori\">審査結果を取る</a>
                                    <a class=\"btn-normal\" href=\"javascript:;\" id=\"sendCancel\">取引キャンセル</a>
                                {% endif %}
                            </div>
                            
                            <script type=\"text/javascript\">
                                $(function() {
                                    $(\"#sendTransaction\").click(function() {
                                        $(\"input[name='mode']\").val('sendTransaction');
                                        $(\"#sendTransaction\").unbind();
                                        $(\"#sendTransaction\").click(function() {
                                            alert('通信中ため少々待ちください。');
                                            return false;
                                        });
                                        $(\"#form1\").submit();
                                        return false;
                                    });
                                    
                                    $(\"#sendGetauthori\").click(function() {
                                        $(\"input[name='mode']\").val('sendGetauthori');
                                        $(\"#sendGetauthori\").unbind();
                                        $(\"#sendGetauthori\").click(function() {
                                            alert('通信中ため少々待ちください。');
                                            return false;
                                        });
                                        $(\"#form1\").submit();
                                        return false;
                                    });
                                    
                                    $(\"#sendChange\").click(function() {
                                        $(\"input[name='mode']\").val('sendChange');
                                        $(\"#sendChange\").unbind();
                                        $(\"#sendChange\").click(function() {
                                            alert('通信中ため少々待ちください。');
                                            return false;
                                        });
                                        $(\"#form1\").submit();
                                        return false;
                                    });
                                    
                                    $(\"#sendCancel\").click(function() {
                                    
                                        if (confirm('取引をキャンセルしてもよろしいでしょうか?')) {
                                            $(\"input[name='mode']\").val('sendCancel');
                                            $(\"#sendCancel\").unbind();
                                            $(\"#sendCancel\").click(function() {
                                                alert('通信中ため少々待ちください。');
                                                return false;
                                            });
                                            $(\"#form1\").submit();
                                        }
                                        
                                        return false;
                                    });
                                });
                            </script>
                        </div>
                    </div>
                </div>
            </div>
           
            {% if Order.JaccsPaymentPaymentStatus and Order.JaccsPaymentPaymentStatus.id == 20001 %}
                <div class=\"card rounded border-0 mb-4 h-adr\">
                    <!-- 出荷情報 -->
                    <div class=\"card-header\">
                        <div class=\"row\">
                            <div class=\"col-8\">
                               <div class=\"d-inline-block\" data-tooltip=\"true\" data-placement=\"top\"
                                title=\"Tooltip\">
                                    <span class=\"card-title\">{{ 'jaccs_payment.admin.order.shipping_shippingrequest'|trans }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <script>
                        $(function() {
                            $('#jaccsShippingRequestModalButton').on('click', function() {
                            
                                $('.jaccs_shipping').hide();
                                $('#jaccsShippingRequestModalMess').html('処理中。');
                            
                                $.ajax({
                                    url: '{{ url('jaccs_payment_shipping_request') }}',
                                    type: 'POST',
                                    dataType: 'json',
                                    data: {
                                        'delivery_slip_no': $('#admin_jaccs_shipping_request_delivery_slip_no').val(),
                                        'delivery_company_code': $('#admin_jaccs_shipping_request_delivery_company_code').val(),
                                        'invoice_date': $('#admin_jaccs_shipping_request_invoice_date').val(),
                                        'order_id': {{ Order.id }}
                                    }
                                }).done(function(data) { 
                                    $('#jaccsShippingRequestModalMess').html('');
                                    if (data.status == 'ok') {
                                        window.location.href='{{ url('admin_order_edit', {id: Order.id}) }}';
                                    } else {
                                        //$('#jaccsShippingRequestModalMess').html(JSON.stringify(data));
                                       
                                        var message = '';
                                        
                                        if (data.status != 'form_error') {
                                            message += 'アトディーネ通信エラー:';
                                        }
                                       
                                        for (var i in data.detail) {
                                            message += i + ':' + data.detail[i];
                                        }
                                        
                                        $('#jaccsShippingRequestModalMess').html(message);
                                    }
                                    $('.jaccs_shipping').show();    
                                }).fail(function(data) {
                                    alert('jaccs shipping request failed.');
                                    $('#jaccsShippingRequestModalMess').html('');
                                    $('.jaccs_shipping').show();
                                });
                            });
                            
                            $('#jaccsShippingRequestCancelModalButton').on('click', function() {
                            
                                $('.jaccs_shipping').hide();
                                $('#jaccsShippingRequestModalMess').html('処理中。');
                            
                                $.ajax({
                                    url: '{{ url('jaccs_payment_shipping_request_cancel') }}',
                                    type: 'POST',
                                    dataType: 'json',
                                    data: {
                                        'order_id': {{ Order.id }}
                                    }
                                }).done(function(data) {
                                    $('#jaccsShippingRequestModalMess').html('');
                                    if (data.status == 'ok') {
                                        window.location.href='{{ url('admin_order_edit', {id: Order.id}) }}';
                                    } else {
                                        //$('#jaccsShippingRequestModalMess').html(JSON.stringify(data));
                                        
                                        var message = '';
                                        
                                        if (data.status != 'form_error') {
                                            message += 'アトディーネ通信エラー:';
                                        }
                                       
                                        for (var i in data.detail) {
                                            message += i + ':' + data.detail[i];
                                        }
                                        
                                        $('#jaccsShippingRequestModalMess').html(message);
                                    }
                                    
                                    $('.jaccs_shipping').show();
                                    
                                }).fail(function(data) {
                                    alert('jaccs shipping request failed.');
                                    $('#jaccsShippingRequestModalMess').html('');
                                    $('.jaccs_shipping').show();
                                });
                            });
                        });
                    </script>
                        
                    <div class=\"collapse show ec-cardCollapse\" id=\"jaccs_shipping_shippingrequest\">
                        <div class=\"card-body\">
                        
                            {% for JaccsShippingRequest in Order.JaccsShippingRequests %}
                                <div class=\"row mb-2\">
                                    <div class=\"col-3\">
                                        <div class=\"d-inline-block\"><span>お問合せ番号</span></div>
                                    </div>
                                    <div class=\"col\">
                                        {{ JaccsShippingRequest.transaction_id }}
                                    </div>
                                </div>
                                <div class=\"row mb-2\">
                                    <div class=\"col-3\">
                                        <div class=\"d-inline-block\"><span>運送会社コード</span></div>
                                    </div>
                                    <div class=\"col\">
                                        {{ jaccsShippingCompanyCode[JaccsShippingRequest.delivery_company_code] }}
                                    </div>
                                </div>
                                <div class=\"row mb-2\">
                                    <div class=\"col-3\">
                                        <div class=\"d-inline-block\"><span>配送伝票番号</span></div>
                                    </div>
                                    <div class=\"col\">
                                        {{ JaccsShippingRequest.delivery_slip_no }}
                                    </div>
                                </div>
                                <div class=\"row mb-2\">
                                    <div class=\"col-3\">
                                        <div class=\"d-inline-block\"><span>請求書発行日</span></div>
                                    </div>
                                    <div class=\"col\">
                                        {{ JaccsShippingRequest.invoice_date|date_day }}
                                    </div>
                                </div>
                            {% endfor %}
                        
                            <div class=\"row mb-3\">
                                <div class=\"col-6\">
                                    <a class=\"btn btn-ec-regular mr-2 add\" data-toggle=\"modal\" data-target=\"#addJaccsShippingRequest\">{{ 'jaccs_payment.admin.order.shipping_shippingrequest'|trans }}</a>
                                    <div class=\"modal fade\" id=\"addJaccsShippingRequest\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"addProduct\" aria-hidden=\"true\">
                                        <div class=\"modal-dialog modal-lg\" role=\"document\">
                                            <div class=\"modal-content\">
                                                <div class=\"modal-header\">
                                                    <h5 class=\"modal-title\">{{ 'jaccs_payment.admin.order.shipping_shippingrequest'|trans }}</h5>
                                                    <button class=\"close\" type=\"button\" data-dismiss=\"modal\" aria-label=\"Close\"><span aria-hidden=\"true\">×</span></button>
                                                </div>
                                                <div class=\"modal-body\">
                                                    配送伝票番号: {{ form_widget(jaccsShippingRequestModalForm.delivery_slip_no) }}
                                                    運送会社: {{ form_widget(jaccsShippingRequestModalForm.delivery_company_code) }}
                                                    請求書発行日: {{ form_widget(jaccsShippingRequestModalForm.invoice_date) }}
                                                    
                                                    <button type=\"button\" id=\"jaccsShippingRequestModalButton\" class=\"btn btn-ec-conversion px-5 mb-4 mt-2 jaccs_shipping\">{{ 'jaccs_payment.admin.order.shipping_shippingrequest_btn'|trans }}</button>
                                                    <button type=\"button\" id=\"jaccsShippingRequestCancelModalButton\" class=\"btn btn-ec-conversion px-5 mb-4 mt-2 jaccs_shipping\">{{ 'jaccs_payment.admin.order.shipping_shippingrequest_cancel'|trans }}</button>
                                                    <div id=\"jaccsShippingRequestModalMess\"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>      
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            {% endif %}
            
            <div class=\"card rounded border-0 mb-4\">
                <div class=\"card-header\">
                    <div class=\"row\">
                        <div class=\"col-2\">
                            <div class=\"d-inline-block\" data-tooltip=\"true\" data-placement=\"top\" title=\"Tooltip\"><span class=\"card-title\">
                            {% endif %}{{ 'admin.order.orderer'|trans }}";

        $source = str_replace($oldB, $reStr, $source);


        $event->setSource($source);

        $pas = $event->getParameters();

        // 商品検索フォーム
        $builder = $this->formFactory->createBuilder(ShippingRequestType::class);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (\Symfony\Component\Form\FormEvent $event) use($pas) {
            /** @var $order \Eccube\Entity\Order */
            $order = $pas['Order'];
            $reqs = $order->getJaccsShippingRequests();
            if (!is_null($reqs) && count($reqs)) {
                /** @var $req \Plugin\JaccsPayment\Entity\ShippingRequest */
                $req = $reqs[0];
                $req->getDeliveryCompanyCode();

                $arrData = [
                    'delivery_slip_no' => $req->getDeliverySlipNo(),
                    'delivery_company_code' => $req->getDeliveryCompanyCode(),
                ];

                if ($req->getInvoiceDate()) {
                    $arrData['invoice_date'] = $req->getInvoiceDate()->format('Y/m/d');
                }

                $event->setData($arrData);
            }
        });

        $jaccsShippingRequestModalForm = $builder->getForm();

        $pas = array_merge($pas, [
            'jaccsShippingRequestModalForm' => $jaccsShippingRequestModalForm->createView(),
            'jaccsShippingCompanyCode' => ShippingRequest::$DeliverCompanyCode,
        ]);

        $event->setParameters($pas);
    }

    /**
     * @param TemplateEvent $event
     */
    public function reHtmlOrderEditCustomer(TemplateEvent $event)
    {
        $source = $event->getSource();

        $oldB = '<div class="collapse {{ id ? \'\' : \'show\' }} ec-cardCollapse h-adr" id="ordererInfo">';
        $reStr = '<div class="collapse {{ id ? \'\' : \'show\' }} ec-cardCollapse h-adr" id="ordererInfo">
{% if isJaccs and jaccsCustomerError|length %}
    <div class="card-body">
        <p class="form-text font-weight-bold text-danger mb-0">
            {% for error in jaccsCustomerError %}
                {{ error }}<br/>
            {% endfor %}
        </p>
    </div>
{% endif %}';

        $source = str_replace($oldB, $reStr, $source);

        $event->setSource($source);
    }

    /**
     * @param TemplateEvent $event
     */
    public function reHtmlOrderEditShip(TemplateEvent $event)
    {
        $source = $event->getSource();

        $oldB = '<div class="collapse show ec-cardCollapse" id="shippingInfo">';
        $reStr = '<div class="collapse show ec-cardCollapse" id="shippingInfo">
{% if isJaccs and jaccsShipError|length %}
    <div class="card-body">
        <p class="form-text font-weight-bold text-danger mb-0">
            {% for error in jaccsShipError %}
                {{ error }}<br/>
            {% endfor %}
        </p>
    </div>
{% endif %}';

        $source = str_replace($oldB, $reStr, $source);

        $event->setSource($source);
    }

    /**
     * @param TemplateEvent $event
     */
    public function reHtmlOrderEditDetail(TemplateEvent $event)
    {
        $source = $event->getSource();

        $oldB = '<div class="collapse show ec-cardCollapse" id="orderItem">';
        $reStr = '<div class="collapse show ec-cardCollapse" id="orderItem">
{% if isJaccs and jaccsDetailsError|length %}
    <div class="card-body">
        <p class="form-text font-weight-bold text-danger mb-0">
            {% for error in jaccsDetailsError %}
                {{ error }}<br/>
            {% endfor %}
        </p>
    </div>
{% endif %}';

        $source = str_replace($oldB, $reStr, $source);

        $event->setSource($source);
    }

    /**
     * @param TemplateEvent $event
     */
    public function onAdminOrderIndex(TemplateEvent $event)
    {
        $source = $event->getSource();

        $oldB = '<div class="row mb-2">';
        $reStr = '
                <div class="row mb-2">
                    <div class="col">
                        <div class="form-row">
                            <div class="col-12">
                                <p class="col-form-label">{{ \'searchorder.label.jaccs_payment.payment_status\'|trans }}</p>
                                {{ form_widget(searchForm.jaccs_payment_payment_status, {\'attr\': {\'class\': \'form-check form-check-inline\'}}) }}
                                {{ form_errors(searchForm.jaccs_payment_payment_status) }}
                            </div>
                        </div>
                    </div>
                </div>
<div class="row mb-2">';

        $limit = 1;
        $source = str_replace($oldB, $reStr, $source, $limit);

        $oldB = '<th class="border-top-0 pt-2 pb-2 text-center">{{ \'admin.order.order_status\'|trans }}</th>';
        $reStr = '<th class="border-top-0 pt-2 pb-2 text-center">{{ \'admin.order.order_status\'|trans }}</th>';

        $source = str_replace($oldB, $reStr, $source);

        $oldB = '<span class="badge badge-ec-blue" style="background-color: #fff; color: {{ Order.OrderStatusColor }}; border-color: {{ Order.OrderStatusColor }}">{{ Order.OrderStatus }}</span>';
        $reStr = '<span class="badge badge-ec-blue" style="background-color: #fff; color: {{ Order.OrderStatusColor }}; border-color: {{ Order.OrderStatusColor }}">{{ Order.OrderStatus }}</span></td>';
        // <td class="align-middle text-center">
        // <span>{{ Order.JaccsPaymentPaymentStatus }}</span>';
        $source = str_replace($oldB, $reStr, $source);

        $event->setSource($source);
    }

    /**
     * @param TemplateEvent $event
     */
    public function onAdminOrderEditTwig(TemplateEvent $event)
    {
        $this->reHtmlOrderEditMain($event);
        $this->reHtmlOrderEditCustomer($event);
        $this->reHtmlOrderEditShip($event);
        $this->reHtmlOrderEditDetail($event);

        /** @var $reHistoryData History */
        /** @var $errorHistory History */
        $isJaccs = false;
        $reHistoryData = null;
        $reHistoryDataDetail = [
            'result' => '',
            'auto_authoriresult' => '',
            'manual_authoriresult' => '',
            'manual_authorireasons' => [],
        ];

        $errorHistory = null;
        $jacssError = [];
        $jaccsDetailsError = [];
        $jaccsCustomerError = [];
        $jaccsShipError = [];

        /** @var $Order Order */
        $Order = $event->getParameter('Order');

        if ($Order->getPayment() && $Order->getPayment()->getMethodClass() == JaccsPayment::class) {
            $isJaccs = true;

            if ($Order->getId()) {
                $reOrder = $this->reOrderRepository->getReEditData($Order);
                if ($reOrder) {
                    $errHistory = $reOrder->getHistory();
                    if ($errHistory) {
                        $reHistoryData = $errHistory;
                        $data = unserialize($errHistory->getItem());
                        $objErrors = $data->getErrors()->getErrors();
                        if ($objErrors && count($objErrors)) {
                            $itemVer = $this->getItemVerifiction();
                            foreach ($objErrors as $objError) {
                                $errorMess = sprintf('※%s(code:%s point:%s)',
                                    $objError->getErrorMessage(),
                                    $objError->getErrorCode(),
                                    $objError->getErrorPoint());

                                //ポイント(タグ名)によりエラーを分ける
                                switch ($objError->getErrorPoint()) {
                                    case 'service':
                                        $jacssError[] = $errorMess;
                                        break;
                                    case 'billedAmount':
                                        $jaccsDetailsError[] = $errorMess;
                                        break;
                                    case array_key_exists($objError->getErrorPoint(), $itemVer['customer']):
                                        $jaccsCustomerError[] = $errorMess;
                                        break;
                                    case array_key_exists($objError->getErrorPoint(), $itemVer['ship']):
                                        $jaccsShipError[] = $errorMess;
                                        break;
                                    case array_key_exists($objError->getErrorPoint(), $itemVer['detail']):
                                        $jaccsDetailsError[] = $errorMess;
                                        break;
                                    default:
                                        $jacssError[] = $errorMess;
                                        break;
                                }
                            }
                        } else {
                            $jacssError[] = '※通信ログのご確認をお願いいたします。';
                        }
                    } else {
                        $reHistoryData = $this->historyRepository->getReHistory($Order);
                    }
                } else {
                    $reHistoryData = $this->historyRepository->getReHistory($Order);
                }
            }
        }

        if ($reHistoryData == null) {
            $isSendTransaction = true;
            $isSendGetauthori = false;
        } else {
            if ($reHistoryData->getItem()) {
                $detail = unserialize($reHistoryData->getItem());
                $reHistoryDataDetail['result'] = $detail->getResult();
                if ($reHistoryDataDetail['result'] == 'OK') {
                    if (method_exists($detail, 'getTransactionInfo') && method_exists($detail->getTransactionInfo(), 'getAutoAuthoriresult')) {
                        //自動審査結果
                        $reHistoryDataDetail['auto_authoriresult'] = $detail->getTransactionInfo()->getAutoAuthoriresult();
                    }

                    if ($detail instanceof Getauthori\Response) {//与信審査結果取得により
                        //目視審査結果
                        $reHistoryDataDetail['manual_authoriresult'] = $detail->getTransactionInfo()->getManualAuthoriresult();

                        if ($detail->getTransactionInfo()->getManualAuthoriresult()) {
                            //目視審査結果理由
                            $reHistoryDataDetail['manual_authorireasons'] = $detail->getTransactionInfo()->getManualAuthorireasons()->getManualAuthorireason();
                        }
                    }
                }
            }

            list($isSendTransaction, $isSendGetauthori) = $this->GetTransactionGetauthoriCheck($reHistoryData, $reHistoryDataDetail);
        }

        $pas = $event->getParameters();

        $pas = array_merge($pas, [
            'isJaccs' => $isJaccs,
            'reHistoryData' => $reHistoryData,
            'reHistoryDataDetail' => $reHistoryDataDetail,
            'errorHistory' => $errorHistory,
            'jacssError' => $jacssError,
            'jaccsDetailsError' => $jaccsDetailsError,
            'jaccsCustomerError' => $jaccsCustomerError,
            'jaccsShipError' => $jaccsShipError,
            'isSendGetauthori' => $isSendGetauthori,
            'isSendTransaction' => $isSendTransaction,
        ]);

        $event->setParameters($pas);
    }

    /**
     * @param History $reHistoryData
     * @param null $reHistoryDataDetail
     *
     * @return array
     */
    protected function GetTransactionGetauthoriCheck(History $reHistoryData, $reHistoryDataDetail = null)
    {
        if ($reHistoryDataDetail == null) {
            $reHistoryDataDetail = [
                'result' => '',
            ];

            if ($reHistoryData->getItem()) {
                $detail = unserialize($reHistoryData->getItem());
                $reHistoryDataDetail['result'] = $detail->getResult();
            }
        }

        $isSendTransaction = false;
        $isSendGetauthori = false;

        if ($reHistoryData->getOrder()->getJaccsPaymentPaymentStatus() && $reHistoryData->getOrder()->getJaccsPaymentPaymentStatus()->getId() == PaymentStatus::JACCS_ORDER_CANCEL) {
            $isSendTransaction = true;
            $isSendGetauthori = false;
        } else {
            $sendTransactionStarus = [PaymentStatus::JACCS_ORDER_ERROR, PaymentStatus::JACCS_ORDER_CANCEL];

            if (!$reHistoryData) {
                $isSendTransaction = true;
            } elseif (!$reHistoryData->getOrder()->getJaccsPaymentPaymentStatus() || in_array($reHistoryData->getOrder()->getJaccsPaymentPaymentStatus()->getId(), $sendTransactionStarus)) {
                $isSendTransaction = true;
            } else {
                $isSendGetauthori = true;
            }
        }

        return [$isSendTransaction, $isSendGetauthori];
    }

    protected $verification = [];

    /**
     * @return array
     */
    protected function getItemVerifiction()
    {
        if (!count($this->verification)) {
            $this->verification['customer'] = [
                'name' => 21,
                'kanaName' => 25,
                'zip' => 8,
                'address' => 55,
                'companyName' => 30,
                'tel' => 15,
                'email' => 100,
                'billedAmount' => 6,
                'service' => 2,
            ];

            $this->verification['ship'] = [
                'shipName' => 21,
                'shipKananame' => 25,
                'shipZip' => 8,
                'shipAddress' => 55,
                'shipCompanyName' => 30,
                'shipTel' => 15,
            ];

            $this->verification['detail'] = [
                'goods' => 150,
                'goodsPrice' => 6,
                'goodsAmount' => 5,
            ];
        }

        return $this->verification;
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @throws Lib\JaccsException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onAdminOrderEditIndexInitalize(EventArgs $eventArgs)
    {
        $mode = $eventArgs->getRequest()->get('mode');

        /** @var $Order Order */
        $Order = $eventArgs->getArgument('TargetOrder');
        if (!$Order || !$Order->getPayment() || $Order->getPayment()->getMethodClass() != JaccsPayment::class) {
            return;
        }

        $config = $this->configRepository->get();
        if (!$config) {
            return;
        }

        //---form項目追加
        /** @var $builder \Symfony\Component\Form\FormBuilder */
        $builder = $eventArgs->getArgument('builder');
        /*
        $builder->add('JaccsShippingRequests', CollectionType::class, [
            'entry_type' => ShippingRequestType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'prototype' => true,
        ]);
        */


        //---操作

        /** @var $reHistoryData History */
        $reHistoryData = $this->historyRepository->getReHistory($Order);
        if ($reHistoryData) {
            list($isSendTransaction, $isSendGetauthori) = $this->GetTransactionGetauthoriCheck($reHistoryData);
        } else {
            $isSendGetauthori = false;
            $isSendTransaction = true;
        }

        if ($mode == 'sendGetauthori' || $mode == 'sendTransaction' || $mode == 'sendChange' || $mode == 'sendCancel') {
            $status = 0;

            if ($mode == 'sendGetauthori' && $isSendGetauthori) {
                $status = $this->getauthoriBatch->Getauthor($config, $Order, $reHistoryData->getTransactionId(), true);
            } elseif ($mode == 'sendTransaction' && $isSendTransaction) {
                $status = $this->getauthoriBatch->Transaction($config, $Order);
            } elseif ($mode == 'sendChange' && $isSendGetauthori) {
                $status = $this->getauthoriBatch->Modifytransaction($config, $Order, $reHistoryData->getTransactionId());
            } elseif ($mode == 'sendCancel' && !$isSendTransaction) {
                $status = $this->getauthoriBatch->Cancel($config, $Order, $reHistoryData->getTransactionId());
            }

            if ($status == 2) {
                $eventArgs->getRequest()->getSession()->getFlashBag()->add('eccube.admin.error', 'アトディーネ取引エラー発生しました。');
            } else {
                $eventArgs->getRequest()->getSession()->getFlashBag()->add('eccube.admin.info', 'アトディーネ取引成功。');
            }

            $eventArgs->setResponse(new RedirectResponse($this->router->generate('admin_order_edit', ['id' => $Order->getId()])));
        }
    }

    /**
     * @param EventArgs $eventArgs
     */
    public function onAdminIndexInitalize(EventArgs $eventArgs)
    {
        /** @var $builder \Symfony\Component\Form\FormBuilder */
        $builder = $eventArgs->getArgument('builder');
        $builder->add('jaccs_payment_payment_status', PaymentStatusType::class, [
            'label' => 'searchorder.label.jaccs_payment.payment_status',
            'expanded' => true,
            'multiple' => true,
        ]);
    }

    /**
     * @param EventArgs $eventArgs
     */
    public function onAdminIndexSearch(EventArgs $eventArgs)
    {
        /** @var $qb \Doctrine\ORM\QueryBuilder */
        $qb = $eventArgs->getArgument('qb');
        $searchData = $eventArgs->getArgument('searchData');

        if (!empty($searchData['jaccs_payment_payment_status']) && count($searchData['jaccs_payment_payment_status'])) {
            $qb->andWhere($qb->expr()->in('o.JaccsPaymentPaymentStatus', ':jaccs_payment_payment_status'))
                ->setParameter('jaccs_payment_payment_status', $searchData['jaccs_payment_payment_status']);
        }
    }

    public function onJaccsPaymentJaccsErrorTwig(TemplateEvent $event)
    {
        $JaccsPaymentError = [];

        $Order = $event->getParameter('Order');

        if ($Order->getId()) {
            $reOrder = $this->reOrderRepository->getReEditData($Order);

            if ($reOrder) {
                $errHistory = $reOrder->getHistory();

                if ($errHistory) {
                    $reHistoryData = $errHistory;
                    $data = unserialize($errHistory->getItem());
                    $objErrors = $data->getErrors()->getErrors();

                    if ($objErrors && count($objErrors)) {
                        foreach ($objErrors as $objError) {
                            $errorMess = sprintf('※%s(code:%s point:%s)',
                                $objError->getErrorMessage(),
                                $objError->getErrorCode(),
                                $objError->getErrorPoint());

                            $JaccsPaymentError[] = $errorMess;
                        }
                    }
                }
            }
        }

        $event->setParameter('JaccsPaymentError', $JaccsPaymentError);
    }
}
