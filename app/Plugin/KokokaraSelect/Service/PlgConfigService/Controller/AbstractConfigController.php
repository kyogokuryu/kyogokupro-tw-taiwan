<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/05/05
 */

namespace Plugin\KokokaraSelect\Service\PlgConfigService\Controller;


use Eccube\Controller\AbstractController;
use Plugin\KokokaraSelect\Service\PlgConfigService\ConfigHelper;
use Plugin\KokokaraSelect\Service\PlgConfigService\ConfigService;
use Plugin\KokokaraSelect\Service\PlgConfigService\Entity\ConfigInterface;
use Plugin\KokokaraSelect\Service\PlgConfigService\Entity\ConfigOptionInterface;
use Plugin\KokokaraSelect\Service\PlgConfigService\Repository\AbstractConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

abstract class AbstractConfigController extends AbstractController
{

    /** @var AbstractConfigRepository */
    protected $configRepository;

    /** @var ConfigHelper */
    protected $configHelper;

    /** @var ConfigService  */
    protected $configService;

    public function __construct(
        ConfigService $configService,
        ConfigHelper $configHelper
    )
    {
        $this->configService = $configService;
        $this->configHelper = $configHelper;
    }

    /**
     * @Route("/%eccube_admin_route%/xxxxx/config", name="xxxxx_admin_config")
     * @Template("@KokokaraSelect/admin/config.twig")
     *
     * @param Request $request
     * @return mixed
     */
    abstract public function index(Request $request);

    public function configControl(Request $request)
    {
        $Configs = $this->configService->getAllOrderGroup();

        $configTypeClass = $this->configHelper->getConfigTypeClassName();

        /** @var FormBuilderInterface $builder */
        $builder = $this->formFactory->createBuilder($configTypeClass, $Configs);
        $form = $builder->getForm();

        if ('POST' === $request->getMethod()) {

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                $Configs = $form->get('plgConfigs');

                // 設定情報登録
                /** @var FormBuilderInterface $configForm */
                foreach ($Configs as $configForm) {

                    /** @var ConfigInterface $config */
                    $config = $configForm->getData();

                    if(ConfigHelper::TYPE_OPTIONS == $config->getConfigType()) {

                        // 現状のデータをクリア
                        $configOptions = $this->configService->getOptions($config);
                        foreach ($configOptions as $configOption) {
                            $config->removeConfigOption($configOption);
                            $this->entityManager->remove($configOption);
                        }
                        $this->entityManager->persist($config);
                        $this->entityManager->flush();

                        // 新規設定
                        $formOptions = $configForm->get('optionsValue')->getData();
                        foreach ($formOptions as $formOption) {
                            /** @var ConfigOptionInterface $configOption */
                            $plgConfigOptionEntityClass = $this->configHelper->getEntityPlgConfigOption();
                            $newConfigOption = new $plgConfigOptionEntityClass;
                            $newConfigOption->setValue($formOption);
                            $newConfigOption->setPlgConfig($config);

                            $config->addConfigOptions($newConfigOption);
                        }

                        $this->entityManager->persist($config);
                    }

                    $this->entityManager->persist($config);
                }
                $this->entityManager->flush();
                $this->addSuccess($this->configHelper->getSnakePlgName() . '.admin.config_save', 'admin');

                return $this->redirectToRoute($request->attributes->get('_route'));
            }
        }

        $configTitle = $this->translator->trans(
            $this->configHelper->getSnakePlgName() . '.admin.config_title');

        $configSubTitle = $this->translator->trans(
            $this->configHelper->getSnakePlgName() . '.admin.config_sub_title');

        $configBack = $this->translator->trans(
            $this->configHelper->getSnakePlgName() . '.admin.config_back');

        return [
            'form' => $form->createView(),
            'config_title' => $configTitle,
            'config_sub_title' => $configSubTitle,
            'config_back' => $configBack,
            'TYPE_STRING' => ConfigHelper::TYPE_STRING,
            'TYPE_CHOICE' => ConfigHelper::TYPE_CHOICE,
            'TYPE_BOOL' => ConfigHelper::TYPE_BOOL,
            'TYPE_OPTIONS' => ConfigHelper::TYPE_OPTIONS,
            'SETTING_GROUPS' => $this->configHelper->getSettingGroups()
        ];
    }
}
