<?php

/*
 * Project Name: ダウンロードコンテンツ販売 プラグイン for 4.0
 * Copyright(c) 2019 Kenji Nakanishi. All Rights Reserved.
 *
 * https://www.facebook.com/web.kenji.nakanishi
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\SheebDlc\Form\Type\Admin;

use Eccube\Entity\Master\OrderStatus;
use Eccube\Repository\Master\OrderStatusRepository;
use Plugin\SheebDlc\Entity\Config;
use Plugin\SheebDlc\PluginManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigType extends AbstractType
{
    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;

    /**
     * ConfigType constructor.
     * @param OrderStatusRepository $orderStatusRepository
     */
    public function __construct(OrderStatusRepository $orderStatusRepository)
    {
        $this->orderStatusRepository = $orderStatusRepository;
    }

    /**
     * Build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $orderStatusChoices = (function() {
            $result = [];
            $ignores = [OrderStatus::CANCEL, OrderStatus::RETURNED];
            $statuses = $this->orderStatusRepository->findAll();
            /* @var $status OrderStatus */
            foreach ($statuses as $status) {
                if (in_array($status->getId(), $ignores)) {
                    continue;
                }
                $result[$status->getName()] = $status;
            }
            return $result;
        })();
        
        $builder
            ->add('available_order_status', ChoiceType::class, [
                'label' => 'sheeb.dlc.admin.config.order_status',
                'choices' => $orderStatusChoices,
                'multiple' => true,
                'expanded' => true,
                'required' => true,
            ])
            ->add('mode', ChoiceType::class, [
                'label' => 'sheeb.dlc.admin.config.mode',
//                'choice_label' => 'name',
                'choices' => [
                    "sheeb.dlc.admin.config.mode.local" => Config::MODE_LOCAL,
                    "sheeb.dlc.admin.config.mode.google_drive" => Config::MODE_GOOGLE_DRIVE
                ],
                'empty_data' => Config::MODE_LOCAL,
                'multiple' => false,
                'expanded' => true,
                'required' => true,
            ])
        ;
        
         $builder->get('available_order_status')
             ->addModelTransformer(new CallbackTransformer(
                 // DB(カンマ区切り) => ロジック(OrderStatusのList)
                 function ($string) {
                     return $this->orderStatusRepository->findBy([
                         'id' => explode(PluginManager::SEPARATOR, $string)
                     ]);
                 },
                 // ロジック(OrderStatusのList) => DB(カンマ区切り)
                 function ($OrderStatusList) {
                     $order_status_ids = array_reduce($OrderStatusList, function ($reduced, OrderStatus $OrderStatus) {
                         $reduced[] = $OrderStatus->getId();
                         return $reduced;
                     });

                     if (empty($order_status_ids)) {
                         return '';
                     }
                     
                     return implode(PluginManager::SEPARATOR, $order_status_ids);
                 }
             ))
         ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    function getBlockPrefix()
    {
        return 'dlc_config';
    }
}
