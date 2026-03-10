<?php
/*
* Plugin Name : [code]
*/

namespace Plugin\[code]\Controller;

use Eccube\Controller\AbstractController;
use Plugin\[code]\Entity\[code]Config;
use Plugin\[code]\Entity\[code]Data;
use Plugin\[code]\Form\Type\[code]ConfigType;
use Plugin\[code]\Repository\[code]ConfigRepository;
use Plugin\[code]\Form\Type\[code]DataType;
use Plugin\[code]\Repository\[code]DataRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ConfigController.
 */
class ConfigController extends AbstractController
{
    /**
     * @Route("/%eccube_admin_route%/[code_lower]/config", name="[code_lower]_admin_config")
     * @Template("@[code]/admin/config.twig")
     *
     * @param Request $request
     * @param [code]ConfigRepository $configRepository
     *
     * @return array
     */
    public function index(Request $request, [code]ConfigRepository $configRepository, [code]DataRepository $DataRepository, ContainerInterface $container)
    {
        $Config = $configRepository->get();
        $Images = $DataRepository->getImages();

        $form = $this->createForm([code]ConfigType::class, $Config);
        $form->handleRequest($request);

        // delete
        if($request->get('delete')){
            $id = $request->get('delete');
            $Config = $DataRepository->get($id);
            $this->entityManager->remove($Config);
            $this->entityManager->flush($Config);

            log_info('[code] delete', ['status' => 'Success']);
            $this->addSuccess('[code_lower].admin.setting.delete.complete', 'admin');

            return $this->redirectToRoute('[code_lower]_admin_config');
        }

        // config save
        if ($form->isSubmitted() && $form->isValid()) {
            $block_type = 0;
            $Config = $form->getData();

            if ($Config['block_type'] != $block_type) {
                // $this->copyBlock($container, $Config['block_type']);
            }

            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            log_info('[code] config', ['status' => 'Success']);
            $this->addSuccess('[code_lower].admin.setting.save.complete', 'admin');

            return $this->redirectToRoute('[code_lower]_admin_config');
        }

        return [
            'Config' => $Config,
            'Images' => $Images,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/[code_lower]/edit", name="[code_lower]_admin_edit")
     * @Template("@[code]/admin/edit.twig")
     *
     * @param Request $request
     * @param [code]DataRepository $configRepository
     *
     * @return array
     */
    public function edit(Request $request, [code]DataRepository $DataRepository)
    {
        $Config = $DataRepository->get();

        // idを取得
        $id = $request->get('id');

        if (is_null($id)) {
            // 空のエンティティを作成.
            $Config = new [code]Data();
            // 新しいIDのセット、ブラウザのフォームバグ回避、保存時には使用しない
            // $Config->setId(0);
        } else {
            // idのデータを取得
            $Config = $DataRepository->get($id);
        }

        $form = $this->createForm([code]DataType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            
            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            log_info('[code] edit', ['status' => 'Success']);
            $this->addSuccess('[code_lower].admin.setting.save.complete', 'admin');

            return $this->redirectToRoute('[code_lower]_admin_config');
        }

        return [
            'Image' => $Config,
            'form' => $form->createView(),
        ];
    }

    /**
     * Copy block template.
     *
     * @param ContainerInterface $container
     * @param block_type int
     */
    private function copyBlock(ContainerInterface $container, $block_type)
    {
        $blockFileName = '[code_lower]_block';
        $templateDir = $container->getParameter('eccube_theme_front_dir');
        $originBlock = __DIR__.'/../Resource/template/Block/'.$blockFileName.'_'.$block_type.'.twig';

        // ブロックファイルを上書きする
        $file = new Filesystem();
        $file->copy($originBlock, $templateDir.'/Block/'.$blockFileName.'.twig', true);
    }
}
