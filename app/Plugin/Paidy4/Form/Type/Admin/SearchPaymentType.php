<?php

namespace Plugin\Paidy4\Form\Type\Admin;

use Eccube\Repository\Master\OrderStatusRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\OrderStatus;
use Plugin\Paidy4\Util as Paidy4;

class SearchPaymentType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * RepeatedPasswordType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        OrderStatusRepository $orderStatusRepository
    )
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->orderStatusRepository = $orderStatusRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $pluginUtil = new Paidy4\PluginUtil($this->eccubeConfig);
        $builder
            ->add('OrderStatuses', ChoiceType::class, [
                'choices'  => $this->getOrderStatuses(),
                'multiple' => true,
                'expanded' => true,
            ])

            ->add('paidy_status', ChoiceType::class, [
                'choices' => array_flip($pluginUtil->getPaidyStatus()),
                'expanded' => true,
                'multiple' => true,
            ])

            ->add('payment_status', ChoiceType::class, [
                'choices' => array_flip($pluginUtil->getPaidyBulkPaymentStatus()),
                'placeholder' => '-',
                'required' => false,
                'expanded' => false,
                'multiple' => false
            ])

            // 受注ID・注文者名・注文者（フリガナ）・注文者会社名
            ->add('multi', TextType::class, [
                'label' => 'admin.order.multi_search_label',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                ],
            ])
            ;
    }

    private function getOrderStatuses()
    {
        $arrOrderStatuses = [];
        $arrTmpOrderStatuses = $this->orderStatusRepository->findAllArray();
        foreach ($arrTmpOrderStatuses as $arrTmp) {
            // 「購入処理中」と「決済処理中」は表示させないため
            if ($arrTmp['id'] != OrderStatus::PENDING && $arrTmp['id'] != OrderStatus::PROCESSING) {
                $arrOrderStatuses[$arrTmp['name']] = $arrTmp['id'];
            }
        }

        return $arrOrderStatuses;
    }
}
