<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Controller\Admin\Content;

use Eccube\Controller\AbstractController;
use Eccube\Repository\PageCountDownRepository;
use Eccube\Form\Type\Admin\PageCountdownType;
use Eccube\Util\CacheUtil;
use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use function Couchbase\defaultDecoder;

class PageCountdownController extends AbstractController
{
    /**
     * @var PageCountdownRepository
     */
    protected $pageCountdownRepository;

    /**
     * NewsController constructor.
     *
     * @param PageCountdownRepository $pageCountDownRepository
     */
    public function __construct(PageCountdownRepository $pageCountdownRepository)
    {
        $this->pageCountdownRepository = $pageCountdownRepository;
    }

    /**
     * 新着情報を登録・編集する。
     *
     * @Route("/%eccube_admin_route%/countdown", name="admin_content_countdown")
     * @Route("/%eccube_admin_route%/countdown/new", name="admin_countdown_new")
     * @Route("/%eccube_admin_route%/countdown/{id}/edit", requirements={"id" = "\d+"}, name="admin_countdown_edit")
     * @Template("@admin/Content/page_countdown_edit.twig")
     *
     * @param Request $request
     * @param null $id
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function edit(Request $request, $id = null, CacheUtil $cacheUtil, ManagerRegistry $managerRegistry)
    {
        $countdownSettingList = $this->pageCountdownRepository->getAll();

        if (count($countdownSettingList)>0) {
            $countdown = $countdownSettingList[0];
        } else {
            $countdown = new \Eccube\Entity\PageCountdown();
            $countdown->setCreatedAt(new \DateTime());
        }

        $builder = $this->formFactory
        ->createBuilder(PageCountDownType::class, $countdown);

        $form = $builder->getForm();
        $form->get('point')->setData($countdown->getPoint());
        $form->get('second')->setData($countdown->getSecond());
        $form->get('times')->setData($countdown->getTimes());
        $form->get('interval')->setData($countdown->getInterval());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $countdown->setUpdatedAt(new \DateTime());
                $this->pageCountdownRepository->save($countdown);
                $this->addSuccess('admin.common.save_complete', 'admin');
            } catch(\Exception $e) {
                throw $e;
            }
        }
       
        return [
            'form' => $form->createView(),
        ];
    }
}
