<?php

namespace Eccube\Controller;


use Eccube\Controller\AbstractController;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Customize\Entity\AccessLog;

use Customize\Repository\AccessLogRepository;

class AccessLogController extends AbstractController
{

    /**
     * @var AccessLogRepository
     */
    protected $accessLogRepository;

    public function __construct(
        AccessLogRepository $accessLogRepository
    ) {
        $this->accessLogRepository = $accessLogRepository;
    }

    /**
     * アクセスログを取得
     *
     * @param Request $request
     *
     * @Route("/access_log", name="access_log")
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function postAccessLog(Request $request)
    {

        // try{
            $userId = $request->get('userId');
            if($userId == 0){
                $userId = null;
            }

            $path = $request->get('path');

            $accessLog = new AccessLog();
            $accessLog->setCustomerId($userId);
            $accessLog->setCreateDate(new \DateTime());
            $accessLog->setUpdateDate(new \DateTime()); 
            $accessLog->setPath($path);
            
            $this->entityManager->persist($accessLog);
            $this->entityManager->flush();

            return $this->json(['success' => true]);
        // }catch(Exception $e){
        //     return $this->json(['error' => $e]);
        // }
    }
}