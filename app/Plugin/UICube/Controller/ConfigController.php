<?php
/*
* Plugin Name : UICube
*/

namespace Plugin\UICube\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Common\Constant;
use Eccube\Entity\Plugin;
use Eccube\Repository\PluginRepository;
use Plugin\UICube\Form\Type\ConfigType;
use Plugin\UICube\Repository\UICubeRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigController.
 */
class ConfigController extends AbstractController
{
    /**
     * @Route("/%eccube_admin_route%/ui_cube/config", name="ui_cube_admin_config")
     * @Template("@UICube/admin/config.twig")
     *
     * @param Request $request
     * @param UICubeRepository $configRepository
     *
     * @return array
     */
    public function index(Request $request, UICubeRepository $configRepository, ContainerInterface $container)
    {
        $Config = $configRepository->get();
        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $name = $data['name'];
            $code = $data['code'];
            $version = '1.1.1';

            $code_lower = $this->to_underscore($code);
            $pluginRealDir = $container->getParameter('plugin_realdir');
            $pluginDir = $pluginRealDir.'/'.$code;
            $plgFileList = $this->getFileList($code, $code_lower);
            $selected_plugin = $this->getSelectedPlugin($data['choised_plugin']);

            $Plugin = $container->get(PluginRepository::class)->findOneBy(['code' => $code]);
            if ($Plugin) {
                $this->addError('ui_cube.admin.config.save.error', 'admin');
                return [
                    'form' => $form->createView(),
                ];
            }
            foreach ($plgFileList as $plg) {
                $file_path = __DIR__.'/../Resource/content/'.$selected_plugin.$plg['filePath'].'/'.$plg['fileNameBefore'];
                if( $pluginFileBefore = @file_get_contents($file_path) ){
                    $from = '/\[code\]/';
                    $pluginFileAfter = preg_replace($from, $code, $pluginFileBefore);
                    $from = '/\[code_name\]/';
                    $pluginFileAfter = preg_replace($from, $name, $pluginFileAfter);
                    $from = '/\[code_version\]/';
                    $pluginFileAfter = preg_replace($from, $version, $pluginFileAfter);
                    $from = '/\[code_lower\]/';
                    $pluginFileAfter = preg_replace($from, mb_strtolower($code_lower), $pluginFileAfter);

                    $file = new Filesystem();
                    $file->mkdir($pluginDir.$plg['filePath']);
                    file_put_contents($pluginDir.$plg['filePath'].'/'.$plg['fileNameAfter'], $pluginFileAfter);
                }
            }

            $this->createPlugin($data, $container);

            log_info('Test config', ['status' => 'Success']);
            $this->addSuccess('ui_cube.admin.config.save.complete', 'admin');

            return $this->redirectToRoute('ui_cube_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * プラグインを作成する.
     *
     * @param $data
     * @param ContainerInterface $container
     *
     */
    private function createPlugin($data, ContainerInterface $container) {
        $Plugin = new Plugin();
        $Plugin->setName($data['name']);
        $Plugin->setCode($data['code']);
        $Plugin->setVersion('1.1.0');
        $Plugin->setSource(0);

        $em = $container->get('doctrine.orm.entity_manager');
        $em->persist($Plugin);
        $em->flush($Plugin);
    }

    /**
     * キャメルタイプをスネークタイプに変換して返す.
     *
     * @param $input
     *
     */
    private function to_underscore($input) {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    /**
     * ファイルリストを返す.
     *
     */
    private function getFileList($code, $code_lower) {
        $fileList = array(
            //-----------------------------------------------------------------
            // composer,LICENSE
            //-----------------------------------------------------------------
            'composer' => array(
                'filePath' => '',
                'fileNameBefore' => 'composer.json',
                'fileNameAfter' => 'composer.json',
            ),
            'license' => array(
                'filePath' => '',
                'fileNameBefore' => 'LICENSE',
                'fileNameAfter' => 'LICENSE',
            ),

            //-----------------------------------------------------------------
            // PluginManager
            //-----------------------------------------------------------------
            'pluginmanager' => array(
                'filePath' => '',
                'fileNameBefore' => 'PluginManager.php',
                'fileNameAfter' => 'PluginManager.php',
            ),

            //-----------------------------------------------------------------
            // Controller
            //-----------------------------------------------------------------
            'controller_config' => array(
                'filePath' => '/Controller',
                'fileNameBefore' => 'ConfigController.php',
                'fileNameAfter' => 'ConfigController.php',
            ),
            'controller_block' => array(
                'filePath' => '/Controller',
                'fileNameBefore' => 'BlockController.php',
                'fileNameAfter' => 'BlockController.php',
            ),


            //-----------------------------------------------------------------
            // Entiry
            //-----------------------------------------------------------------
            'entity' => array(
                'filePath' => '/Entity',
                'fileNameBefore' => 'Config.php',
                'fileNameAfter' => $code.'Config.php',
            ),
            'entity_image' => array(
                'filePath' => '/Entity',
                'fileNameBefore' => 'Data.php',
                'fileNameAfter' => $code.'Data.php',
            ),


            //-----------------------------------------------------------------
            // Form
            //-----------------------------------------------------------------
            'form' => array(
                'filePath' => '/Form/Type',
                'fileNameBefore' => 'ConfigType.php',
                'fileNameAfter' => $code.'ConfigType.php',
            ),
            'form_data' => array(
                'filePath' => '/Form/Type',
                'fileNameBefore' => 'DataType.php',
                'fileNameAfter' => $code.'DataType.php',
            ),
            'form_block' => array(
                'filePath' => '/Form/Type',
                'fileNameBefore' => 'BlockType.php',
                'fileNameAfter' => $code.'BlockType.php',
            ),


            //-----------------------------------------------------------------
            // Repository
            //-----------------------------------------------------------------
            'repository' => array(
                'filePath' => '/Repository',
                'fileNameBefore' => 'ConfigRepository.php',
                'fileNameAfter' => $code.'ConfigRepository.php',
            ),
            'repository_image' => array(
                'filePath' => '/Repository',
                'fileNameBefore' => 'DataRepository.php',
                'fileNameAfter' => $code.'DataRepository.php',
            ),

            //-----------------------------------------------------------------
            // Resource
            //-----------------------------------------------------------------
            'resource_config' => array(
                'filePath' => '/Resource/config',
                'fileNameBefore' => 'services.yaml',
                'fileNameAfter' => 'services.yaml',
            ),
            'resource_locale' => array(
                'filePath' => '/Resource/locale',
                'fileNameBefore' => 'messages.ja.yaml',
                'fileNameAfter' => 'messages.ja.yaml',
            ),
            'resource_admin_config' => array(
                'filePath' => '/Resource/template/admin',
                'fileNameBefore' => 'config.twig',
                'fileNameAfter' => 'config.twig',
            ),
            'resource_admin_edit' => array(
                'filePath' => '/Resource/template/admin',
                'fileNameBefore' => 'edit.twig',
                'fileNameAfter' => 'edit.twig',
            ),
            'resource_block_0' => array(
                'filePath' => '/Resource/template/Block',
                'fileNameBefore' => 'block_0.twig',
                'fileNameAfter' => $code_lower.'_block_0.twig',
            ),
            'resource_block_1' => array(
                'filePath' => '/Resource/template/Block',
                'fileNameBefore' => 'block_1.twig',
                'fileNameAfter' => $code_lower.'_block_1.twig',
            ),
            'resource_block_2' => array(
                'filePath' => '/Resource/template/Block',
                'fileNameBefore' => 'block_2.twig',
                'fileNameAfter' => $code_lower.'_block_2.twig',
            ),
            'resource_block_3' => array(
                'filePath' => '/Resource/template/Block',
                'fileNameBefore' => 'block_3.twig',
                'fileNameAfter' => $code_lower.'_block_3.twig',
            ),
            'resource_block_4' => array(
                'filePath' => '/Resource/template/Block',
                'fileNameBefore' => 'block_4.twig',
                'fileNameAfter' => $code_lower.'_block_4.twig',
            ),


            //-----------------------------------------------------------------
            // Event
            //-----------------------------------------------------------------
        );

        return $fileList;
    }

    /**
     * プラグイン名を返す.
     *
     * @param $selectedPlugin
     *
     */
    private function getSelectedPlugin($selectedPlugin) {
        $plugins = [
            'header','footer','shopguide','itemnew','itemrank','image','slider','menu','news'
        ];

        return $plugins[$selectedPlugin];
    }
}
