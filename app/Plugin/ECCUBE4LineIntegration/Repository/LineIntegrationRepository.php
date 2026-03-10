<?php

namespace Plugin\ECCUBE4LineIntegration\Repository;

use Eccube\Repository\AbstractRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Eccube\Common\Constant;
use Eccube\Util\StringUtil;
use Plugin\ECCUBE4LineIntegration\Entity\LineIntegration;

class LineIntegrationRepository extends AbstractRepository
{
    private $app;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LineIntegration::class);
    }

    function setApplication($app) {
        $this->app = $app;
    }

    /**
     * LINE通知を受け取るカスタマーIDの配列を取得する.
     */
    protected function getLineNotificationCustomerIds()
    {
        $lineNotificationCustomerIds = array();

        $lineIntegrations = $this->findBy(['line_notification_flg' => Constant::ENABLED]);

        foreach ($lineIntegrations as $lineIntegration) {
            $lineNotificationCustomerIds[] = $lineIntegration->getCustomerId();
        }

        return array_unique($lineNotificationCustomerIds);
    }

    public function getQueryBuilderBySearchData($searchData)
    {
        // LINE通知を受け取るカスタマーのIDを取得する
        $lineNotificationCustomerIds = $this->getLineNotificationCustomerIds();

        $cr = $this->app->getCustomerRepository();
        $qb = $cr->createQueryBuilder('c')
            ->select('c');

        // LINE通知受け取るカスタマーのみに絞る
        if (count($lineNotificationCustomerIds) > 0) {
            // LINE通知送付カスタマーがいれば対象カスタマーのみ対象とする
            $qb->andWhere($qb->expr()->in('c.id', $lineNotificationCustomerIds));
        } else {
            // LINE通知送付カスタマーがいなければ強制的に非表示にする条件を追加する
            $qb->andWhere('c.id < 0');
        }

        if (!empty($searchData['id'])) {
            $cleanId = preg_replace('/\s+|[　]+/u', '', $searchData['id']); // スペース除去
            if (preg_match('/^\d+$/', $cleanId)) {
                $qb->andWhere('c.id = :customer_id')->setParameter('customer_id', $cleanId);
            }
        }
        if (!empty($searchData['email'])) {
            $cleanEmail = preg_replace('/\s+|[　]+/u', '',
                $searchData['email']); // スペース除去
            $qb->andWhere('c.email LIKE :email')
                ->setParameter('email', '%' . $cleanEmail . '%');
        }
        if (!empty($searchData['name'])) {
            $cleanName = preg_replace('/\s+|[　]+/u', '',
                $searchData['name']); // スペース除去
            $qb->andWhere('CONCAT(c.name01, c.name02) LIKE :name OR CONCAT(c.kana01, 
            c.kana02) LIKE :kana')
                ->setParameter('name', '%' . $cleanName . '%')
                ->setParameter('kana', '%' . $cleanName . '%');
        }

        // Pref
        if (!empty($searchData['pref']) && $searchData['pref']) {
            $qb
                ->andWhere('c.Pref = :pref')
                ->setParameter('pref', $searchData['pref']->getId());
        }

        // sex
        if (!empty($searchData['sex']) && count($searchData['sex']) > 0) {
            $sexs = [];
            foreach ($searchData['sex'] as $sex) {
                $sexs[] = $sex->getId();
            }

            $qb->andWhere($qb->expr()->in('c.Sex', ':sexs'))
                ->setParameter('sexs', $sexs);
        }
        //birth
        if (!empty($searchData['birth_month']) && $searchData['birth_month']) {
            $qb
                ->andWhere('EXTRACT(MONTH FROM c.birth) = :birth_month')
                ->setParameter('birth_month', $searchData['birth_month']);
        }
        if (!empty($searchData['birth_start']) && $searchData['birth_start']) {
            $qb
                ->andWhere('c.birth >= :birth_start')
                ->setParameter('birth_start', $searchData['birth_start']);
        }
        if (!empty($searchData['birth_end']) && $searchData['birth_end']) {
            $date = clone $searchData['birth_end'];
            $date->modify('+1 days');
            $qb
                ->andWhere('c.birth < :birth_end')
                ->setParameter('birth_end', $date);
        }
        // buy_total
        if (isset($searchData['buy_total_start']) && StringUtil::isNotBlank
            ($searchData['buy_total_start'])) {
            $qb
                ->andWhere('c.buy_total >= :buy_total_start')
                ->setParameter('buy_total_start', $searchData['buy_total_start']);
        }
        if (isset($searchData['buy_total_end']) && StringUtil::isNotBlank
            ($searchData['buy_total_end'])) {
            $qb
                ->andWhere('c.buy_total <= :buy_total_end')
                ->setParameter('buy_total_end', $searchData['buy_total_end']);
        }
        // buy_times
        if (isset($searchData['buy_times_start']) && StringUtil::isNotBlank
            ($searchData['buy_times_start'])) {
            $qb
                ->andWhere('c.buy_times >= :buy_times_start')
                ->setParameter('buy_times_start', $searchData['buy_times_start']);
        }
        if (isset($searchData['buy_times_end']) && StringUtil::isNotBlank
            ($searchData['buy_times_end'])) {
            $qb
                ->andWhere('c.buy_times <= :buy_times_end')
                ->setParameter('buy_times_end', $searchData['buy_times_end']);
        }
        // create_date
        if (!empty($searchData['create_date_start']) && $searchData['create_date_start']) {
            $qb
                ->andWhere('c.create_date >= :create_date_start')
                ->setParameter('create_date_start', $searchData['create_date_start']);
        }
        if (!empty($searchData['create_date_end']) && $searchData['create_date_end']) {
            $date = clone $searchData['create_date_end'];
            $date->modify('+1 days');
            $qb
                ->andWhere('c.create_date < :create_date_end')
                ->setParameter('create_date_end', $date);
        }
        // update_date
        if (!empty($searchData['update_date_start']) && $searchData['update_date_start']) {
            $qb
                ->andWhere('c.update_date >= :update_date_start')
                ->setParameter('update_date_start', $searchData['update_date_start']);
        }
        if (!empty($searchData['update_date_end']) && $searchData['update_date_end']) {
            $date = clone $searchData['update_date_end'];
            $date->modify('+1 days');
            $qb
                ->andWhere('c.update_date < :update_date_end')
                ->setParameter('update_date_end', $date);
        }
        // last_buy
        if (!empty($searchData['last_buy_start']) && $searchData['last_buy_start']) {
            $qb
                ->andWhere('c.last_buy_date >= :last_buy_start')
                ->setParameter('last_buy_start', $searchData['last_buy_start']);
        }
        if (!empty($searchData['last_buy_end']) && $searchData['last_buy_end']) {
            $date = clone $searchData['last_buy_end'];
            $date->modify('+1 days');
            $qb
                ->andWhere('c.last_buy_date < :last_buy_end')
                ->setParameter('last_buy_end', $date);
        }

        // status
        if (!empty($searchData['customer_status']) &&
            count($searchData['customer_status']) > 0) {
            $qb
                ->andWhere($qb->expr()->in('c.Status', ':statuses'))
                ->setParameter('statuses', $searchData['customer_status']);
        }

        // buy_product_name
        if (isset($searchData['buy_product_name']) &&
            StringUtil::isNotBlank($searchData['buy_product_name'])) {
            $qb
                ->leftJoin('c.Orders', 'o')
                ->leftJoin('o.OrderItems', 'oi')
                ->andWhere('oi.product_name LIKE :buy_product_name OR oi.product_code LIKE :buy_product_name')
                ->setParameter('buy_product_name', '%'.$searchData['buy_product_name'].'%');
        }

        // Order By
        $qb->addOrderBy('c.id', 'ASC');

        return $qb;
    }

    public function getResultBySearchData($searchData)
    {
        $queryBuilder = $this->getQueryBuilderBySearchData($searchData);
        $customers = $queryBuilder->getQuery()->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        $targetCustomerIds = array();
        foreach ($customers as $customer) {
            $targetCustomerIds[] = $customer['id'];
        }

        $lineIntegrations = $this->findBy(['line_notification_flg' => Constant::ENABLED]);

        $targetLineIntegrations = array();
        foreach ($lineIntegrations as $lineIntegration) {
            if (in_array($lineIntegration->getCustomerId(), $targetCustomerIds)) {
                $targetLineIntegrations[] = $lineIntegration;
            }
        }

        return $targetLineIntegrations;
    }

    public function deleteLineAssociation($lineIntegration)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            $em->remove($lineIntegration);
            $em->flush();

            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            return false;
        }

        return true;
    }
}
