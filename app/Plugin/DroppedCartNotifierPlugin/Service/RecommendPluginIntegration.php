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

namespace Plugin\DroppedCartNotifierPlugin\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\Common\Persistence\Mapping\MappingException;
use ReflectionException;
use Exception;

class RecommendPluginIntegration
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    public function __construct(EntityManagerInterface $em = null)
    {
        $this->entityManager = $em;
    }

    /**
     * おすすめ商品管理プラグインが導入されているかを返す
     *
     * @return bool
     */
    public function isEnabledRecommendedPlugin()
    {
        return !is_null($this->getRecommendedItems());
    }

    /**
     * おすすめ商品を取得する
     *
     * @return array|null RecommendProductの配列。おすすめ商品プラグインが未導入時はnullを返す
     */
    public function getRecommendedItems()
    {
        try {
            $recommendProductRepository = $this->entityManager->getRepository("Plugin\Recommend4\Entity\RecommendProduct");
            return $recommendProductRepository->getRecommendList();
        } catch (ReflectionException | MappingException $e) {     // プラグインが未導入のとき発生
            return null;
        } catch (TableNotFoundException | PDOException $e) {      // テーブルが存在しないとき発生
            return null;
        } catch (Exception $e) {                                  // その他、全例外を捕捉
            log_warning("[DroppedCartNotifierPlugin] 例外" . get_class($e) . ": " . $e->getMessage());
            return null;
        }
    }
}
