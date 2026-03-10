<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.3   |
    |              on 2021-09-17 16:10:28              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
namespace Plugin\AmazonPayV2\Repository;use Doctrine\ORM\EntityRepository;class AmazonOrderRepository extends EntityRepository{public $config;protected $app;public function setApplication($app){$this->app = $app;}public function setConfig(array $config){$this->config = $config;}public function getAmazonOrderByOrderDataForAdmin($Orders){goto PFXwv;PFXwv:$AmazonOrders = [];goto HMsHv;GbUew:return $AmazonOrders;goto p3Efj;IXzPk:MvdpU:goto GbUew;HMsHv:foreach ($Orders as $Order) {goto mZPvv;uLpG4:nuQhm:goto i7inv;r0Rtq:if (empty($AmazonOrder)) {goto FAYWI;}goto LWR2M;LWR2M:$AmazonOrders[] = $AmazonOrder[0];goto PS2A7;mZPvv:$AmazonOrder = $this->findby(['Order' => $Order]);goto r0Rtq;PS2A7:FAYWI:goto uLpG4;i7inv:}goto IXzPk;p3Efj:}}