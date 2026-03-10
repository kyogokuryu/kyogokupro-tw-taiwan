<?php
/*
* Plugin Name : uc_slide_cbd
*/

namespace Plugin\uc_slide_cbd\Controller;

use Eccube\Controller\AbstractController;
use Plugin\uc_slide_cbd\Entity\uc_slide_cbdConfig;
use Plugin\uc_slide_cbd\Entity\uc_slide_cbdData;
use Plugin\uc_slide_cbd\Form\Type\uc_slide_cbdConfigType;
use Plugin\uc_slide_cbd\Repository\uc_slide_cbdConfigRepository;
use Plugin\uc_slide_cbd\Form\Type\uc_slide_cbdDataType;
use Plugin\uc_slide_cbd\Repository\uc_slide_cbdDataRepository;
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
     * @Route("/%eccube_admin_route%/uc_slide_cbd/config", name="uc_slide_cbd_admin_config")
     * @Template("@uc_slide_cbd/admin/config.twig")
     *
     * @param Request $request
     * @param uc_slide_cbdConfigRepository $configRepository
     *
     * @return array
     */
    public function index(Request $request, uc_slide_cbdConfigRepository $configRepository, uc_slide_cbdDataRepository $DataRepository, ContainerInterface $container)
    {
        $Config = $configRepository->get();
        $Images = $DataRepository->getImages();

        $form = $this->createForm(uc_slide_cbdConfigType::class, $Config);
        $form->handleRequest($request);

        // delete
        if($request->get('delete')){
            $id = $request->get('delete');
            $Config = $DataRepository->get($id);
            $this->entityManager->remove($Config);
            $this->entityManager->flush($Config);

            log_info('uc_slide_cbd delete', ['status' => 'Success']);
            $this->addSuccess('uc_slide_cbd.admin.setting.delete.complete', 'admin');

            return $this->redirectToRoute('uc_slide_cbd_admin_config');
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

            log_info('uc_slide_cbd config', ['status' => 'Success']);
            $this->addSuccess('uc_slide_cbd.admin.setting.save.complete', 'admin');

            return $this->redirectToRoute('uc_slide_cbd_admin_config');
        }

        return [
            'Config' => $Config,
            'Images' => $Images,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/uc_slide_cbd/edit", name="uc_slide_cbd_admin_edit")
     * @Template("@uc_slide_cbd/admin/edit.twig")
     *
     * @param Request $request
     * @param uc_slide_cbdDataRepository $configRepository
     *
     * @return array
     */
    public function edit(Request $request, uc_slide_cbdDataRepository $DataRepository)
    {
        $Config = $DataRepository->get();

        // idを取得
        $id = $request->get('id');

        if (is_null($id)) {
            // 空のエンティティを作成.
            $Config = new uc_slide_cbdData();
            // 新しいIDのセット、ブラウザのフォームバグ回避、保存時には使用しない
            // $Config->setId(0);
        } else {
            // idのデータを取得
            $Config = $DataRepository->get($id);
        }

        $form = $this->createForm(uc_slide_cbdDataType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            
            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            log_info('uc_slide_cbd edit', ['status' => 'Success']);
            $this->addSuccess('uc_slide_cbd.admin.setting.save.complete', 'admin');

            return $this->redirectToRoute('uc_slide_cbd_admin_config');
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
        $blockFileName = 'uc_slide_cbd_block';
        $templateDir = $container->getParameter('eccube_theme_front_dir');
        $originBlock = __DIR__.'/../Resource/template/Block/'.$blockFileName.'_'.$block_type.'.twig';

        // ブロックファイルを上書きする
        $file = new Filesystem();
        $file->copy($originBlock, $templateDir.'/Block/'.$blockFileName.'.twig', true);
    }
}
