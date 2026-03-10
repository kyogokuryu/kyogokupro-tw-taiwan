<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/07/13
 */

namespace Plugin\PinpointSale\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Plugin\PinpointSale\Service\PlgConfigService\Entity\ConfigInterface;
use Plugin\PinpointSale\Service\PlgConfigService\Entity\ConfigTrait;

/**
 * Class PlgConfig
 * @package Plugin\PinpointSale\Entity
 *
 * @ORM\Table(name="plg_pinpoint_sale_config")
 * @ORM\Entity(repositoryClass="Plugin\PinpointSale\Repository\PlgConfigRepository")
 */
class PlgConfig extends AbstractEntity implements ConfigInterface
{
    use ConfigTrait;
}
