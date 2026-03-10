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

namespace Eccube\Controller\Mypage;

use Eccube\Controller\AbstractController;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Front\EntryType;
use Eccube\Repository\CustomerRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class ChangeController extends AbstractController
{
    /**
     * @var TokenStorage
     */
    protected $tokenStorage;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var EncoderFactoryInterface
     */
    protected $encoderFactory;

    public function __construct(
        CustomerRepository $customerRepository,
        EncoderFactoryInterface $encoderFactory,
        TokenStorageInterface $tokenStorage
    ) {
        $this->customerRepository = $customerRepository;
        $this->encoderFactory = $encoderFactory;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * 会員情報編集画面.
     *
     * @Route("/mypage/change", name="mypage_change")
     * @Template("Mypage/change.twig")
     */
    public function index(Request $request)
    {
        //サロンIDに該当するユーザーの判定 20220510 kikuzawa
        if ($request->isXmlHttpRequest()) {
            $id = $_POST['salon_id'];
            $salonCustomer = $this->customerRepository->find($id);
            if($salonCustomer) {
                if($salonCustomer['company_name']){
                    $salon_name = mb_substr($salonCustomer['company_name'], 0, 1).'*****'.mb_substr($salonCustomer['company_name'], -1, 1);
                }
                else{
                    $salon_name = '紹介特典ID確認できました';
                }
            }
            else{
                $salon_name = '存在しない紹介特典IDです';
            }

            return $this->json([
                'salon_name' => $salon_name,
            ]);
            exit();
        }

        $Customer = $this->getUser();
        $LoginCustomer = clone $Customer;
        $this->entityManager->detach($LoginCustomer);

        $previous_password = $Customer->getPassword();
        $Customer->setPassword($this->eccubeConfig['eccube_default_password']);

        //紹介特典IDの有無を確認 20220801 kikuzawa
        $previous_salon_id = $Customer->getSalonId();

        /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
        $builder = $this->formFactory->createBuilder(EntryType::class, $Customer);

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Customer' => $Customer,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_CHANGE_INDEX_INITIALIZE, $event);

        /* @var $form \Symfony\Component\Form\FormInterface */
        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            log_info('会員編集開始');

            if ($Customer->getPassword() === $this->eccubeConfig['eccube_default_password']) {
                $Customer->setPassword($previous_password);
            } else {
                $encoder = $this->encoderFactory->getEncoder($Customer);
                if ($Customer->getSalt() === null) {
                    $Customer->setSalt($encoder->createSalt());
                }
                $Customer->setPassword(
                    $encoder->encodePassword($Customer->getPassword(), $Customer->getSalt())
                );
            }

            //紹介特典IDを新規登録した場合はポイント付与 20220801 kikuzawa
            if(empty($previous_salon_id) && $Customer->getSalonId()){
                $addPoint = $Customer->getPoint() + 500;
                $Customer->setPoint($addPoint);
            }

            $this->entityManager->flush();

            log_info('会員編集完了');

            $event = new EventArgs(
                [
                    'form' => $form,
                    'Customer' => $Customer,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_CHANGE_INDEX_COMPLETE, $event);

            //購入フローから強制的に会員登録へ遷移した場合は元の購入ページへリダイレクト 20220225 kikuzawa
            if($_SESSION['back_to_shopping'] == true){
                $_SESSION['back_to_shopping'] = '';
                return $this->redirect($this->generateUrl('shopping'));
            }
            else{
                return $this->redirect($this->generateUrl('mypage_change_complete'));
            }
        }

        $this->tokenStorage->getToken()->setUser($LoginCustomer);

        //サロンidは変更不可のため入力済みの場合はフォームから除外 20220603 kikuzawa
        $salon_selected_id = false;
        if($previous_salon_id) $salon_selected_id = $previous_salon_id;

        return [
            'form' => $form->createView(),
            'salon_selected_id' => $salon_selected_id,//20220603 kikuzawa
            'is_buy_ready' => $Customer->isBuyReady()
        ];
    }

    /**
     * 会員情報編集完了画面.
     *
     * @Route("/mypage/change_complete", name="mypage_change_complete")
     * @Template("Mypage/change_complete.twig")
     */
    public function complete(Request $request)
    {
        return [];
    }
}
