<?php

/*
 * Plugin Name: JoolenEntryOrderCompleted4
 *
 * Copyright(c) joolen inc. All Rights Reserved.
 *
 * https://www.joolen.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\JoolenEntryOrderCompleted4\Controller\Admin;

use Eccube\Controller\AbstractController;
use Eccube\Form\Validator\TwigLint;
use Eccube\Util\CacheUtil;
use Eccube\Util\StringUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Filesystem\Filesystem;

class ConfigController extends AbstractController
{
    /**
     * @Route("/%eccube_admin_route%/joolen_entry_order_completed4/config", name="joolen_entry_order_completed4_admin_config")
     * @Template("@JoolenEntryOrderCompleted4/admin/config.twig")
     */
    public function index(Request $request,Environment $twig, CacheUtil $cacheUtil)
    {
        $builder = $this->formFactory->createBuilder();
        $builder
            ->add('tpl_data', TextareaType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new TwigLint(),
                ],
            ]);

        $form = $builder->getForm();
        $source = $twig->getLoader()
            ->getSourceContext('JoolenEntryOrderCompleted4/index.twig')
            ->getCode();
        $form->get('tpl_data')->setData($source);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $templatePath = $this->getParameter('eccube_theme_front_dir');
            $filePath = $templatePath . '/JoolenEntryOrderCompleted4/index.twig';

            $fs = new Filesystem();
            $pageData = $form->get('tpl_data')->getData();

            $pageData = StringUtil::convertLineFeed($pageData);
            $fs->dumpFile($filePath, $pageData);
            $this->addSuccess('admin.common.save_complete', 'admin');

            // キャッシュの削除
            $cacheUtil->clearTwigCache();
            $cacheUtil->clearDoctrineCache();

            return $this->redirectToRoute('joolen_entry_order_completed4_admin_config');
        }

        $filePath = $this->getParameter('eccube_theme_front_dir') . '/JoolenEntryOrderCompleted4/index.twig';
        $projectDir = $this->getParameter('kernel.project_dir');
        $filePath = str_replace($projectDir.'/', '', $filePath);
        return [
            'form' => $form->createView(),
            'filePath' => $filePath,
        ];
    }
}
