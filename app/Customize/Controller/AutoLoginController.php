<?php

/**
 * 自動登入 Controller
 * 
 * SalesDash 註冊完成後，透過一次性 token 自動登入用戶。
 * 
 * 流程：
 * 1. SalesDash registerCustomer API 註冊成功後，生成 random token
 *    寫入 dtb_customer.reset_key + reset_expire（60秒後過期）
 * 2. 前端跳轉到 /auto_login?token=xxx
 * 3. 此 Controller 驗證 token → 自動登入 → 清除 token → 跳轉 /feed/
 * 
 * 安全性：
 * - Token 有效期僅 60 秒
 * - 使用後立即清除（一次性）
 * - 只對 customer_status_id = 2（本會員）有效
 * - 使用 EC-CUBE 標準的 UsernamePasswordToken 建立 session
 * 
 * 建立日期: 2026-04-16
 * 建立者: SalesDash Auto-Login Integration
 */

namespace Customize\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Repository\CustomerRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class AutoLoginController extends AbstractController
{
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    public function __construct(
        CustomerRepository $customerRepository,
        TokenStorageInterface $tokenStorage
    ) {
        $this->customerRepository = $customerRepository;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * 一次性 token 自動登入
     * 
     * @Route("/cm_auto_login", name="auto_login")
     */
    public function autoLogin(Request $request)
    {
        $token = $request->query->get('token');
        $redirect = $request->query->get('redirect', '/feed/');

        // Validate token parameter
        if (empty($token) || !preg_match('/^[a-zA-Z0-9]+$/', $token)) {
            log_info('[AutoLogin] Invalid or missing token');
            return new RedirectResponse('/mypage/login');
        }

        // Find customer by reset_key with expiry check
        try {
            $Customer = $this->customerRepository->createQueryBuilder('c')
                ->where('c.reset_key = :reset_key')
                ->andWhere('c.Status = :status')
                ->andWhere('c.reset_expire >= :now')
                ->setParameter('reset_key', $token)
                ->setParameter('status', CustomerStatus::REGULAR)
                ->setParameter('now', new \DateTime())
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (\Exception $e) {
            log_info('[AutoLogin] DB error: ' . $e->getMessage());
            return new RedirectResponse('/mypage/login');
        }

        if (!$Customer) {
            log_info('[AutoLogin] Token not found or expired: ' . substr($token, 0, 8) . '...');
            return new RedirectResponse('/mypage/login');
        }

        // Clear the token immediately (one-time use)
        $Customer->setResetKey(null);
        $Customer->setResetExpire(null);
        $this->entityManager->persist($Customer);
        $this->entityManager->flush();

        // Establish login session (same pattern as EntryController::activate)
        $securityToken = new UsernamePasswordToken($Customer, null, 'customer', ['ROLE_USER']);
        $this->tokenStorage->setToken($securityToken);
        $request->getSession()->migrate(true);

        log_info('[AutoLogin] SUCCESS: customer_id=' . $Customer->getId() . ', email=' . $Customer->getEmail());

        // Sanitize redirect URL (only allow relative paths starting with /)
        if (!preg_match('/^\/[a-zA-Z0-9_\-\/]*$/', $redirect)) {
            $redirect = '/feed/';
        }

        return new RedirectResponse($redirect);
    }
}
