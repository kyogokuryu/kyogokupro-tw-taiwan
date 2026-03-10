<?php
/*
* Plugin Name : uc_slide
*/

namespace Plugin\uc_slide\Controller;

use Eccube\Controller\AbstractController;
use Plugin\uc_slide\Entity\uc_slideConfig;
use Plugin\uc_slide\Entity\uc_slideData;
use Plugin\uc_slide\Form\Type\uc_slideConfigType;
use Plugin\uc_slide\Repository\uc_slideConfigRepository;
use Plugin\uc_slide\Form\Type\uc_slideDataType;
use Plugin\uc_slide\Repository\uc_slideDataRepository;
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
     * @Route("/%eccube_admin_route%/uc_slide/config", name="uc_slide_admin_config")
     * @Template("@uc_slide/admin/config.twig")
     *
     * @param Request $request
     * @param uc_slideConfigRepository $configRepository
     *
     * @return array
     */
    public function index(Request $request, uc_slideConfigRepository $configRepository, uc_slideDataRepository $DataRepository, ContainerInterface $container)
    {
        $Config = $configRepository->get();
        $Images = $DataRepository->getImages();

        $form = $this->createForm(uc_slideConfigType::class, $Config);
        $form->handleRequest($request);

        // delete
        if($request->get('delete')){
            $id = $request->get('delete');
            $Config = $DataRepository->get($id);
            $this->entityManager->remove($Config);
            $this->entityManager->flush($Config);

            log_info('uc_slide delete', ['status' => 'Success']);
            $this->addSuccess('uc_slide.admin.setting.delete.complete', 'admin');

            return $this->redirectToRoute('uc_slide_admin_config');
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

            log_info('uc_slide config', ['status' => 'Success']);
            $this->addSuccess('uc_slide.admin.setting.save.complete', 'admin');

            return $this->redirectToRoute('uc_slide_admin_config');
        }

        return [
            'Config' => $Config,
            'Images' => $Images,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/uc_slide/edit", name="uc_slide_admin_edit")
     * @Template("@uc_slide/admin/edit.twig")
     *
     * @param Request $request
     * @param uc_slideDataRepository $configRepository
     *
     * @return array
     */
    public function edit(Request $request, uc_slideDataRepository $DataRepository)
    {
        $Config = $DataRepository->get();

        // idを取得
        $id = $request->get('id');

        if (is_null($id)) {
            // 空のエンティティを作成.
            $Config = new uc_slideData();
            // 新しいIDのセット、ブラウザのフォームバグ回避、保存時には使用しない
            // $Config->setId(0);
        } else {
            // idのデータを取得
            $Config = $DataRepository->get($id);
        }

        $form = $this->createForm(uc_slideDataType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            
            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            log_info('uc_slide edit', ['status' => 'Success']);
            $this->addSuccess('uc_slide.admin.setting.save.complete', 'admin');

            return $this->redirectToRoute('uc_slide_admin_config');
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
        $blockFileName = 'uc_slide_block';
        $templateDir = $container->getParameter('eccube_theme_front_dir');
        $originBlock = __DIR__.'/../Resource/template/Block/'.$blockFileName.'_'.$block_type.'.twig';

        // ブロックファイルを上書きする
        $file = new Filesystem();
        $file->copy($originBlock, $templateDir.'/Block/'.$blockFileName.'.twig', true);
    }
}
