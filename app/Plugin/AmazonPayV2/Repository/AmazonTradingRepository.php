<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.3   |
    |              on 2021-09-17 16:10:28              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
namespace Plugin\AmazonPayV2\Repository;use Doctrine\ORM\EntityRepository;class AmazonTradingRepository extends EntityRepository{public $config;public function setConfig(array $config){$this->config = $config;}}