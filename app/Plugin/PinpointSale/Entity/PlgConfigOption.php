<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/07/13
 */

namespace Plugin\PinpointSale\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Plugin\PinpointSale\Service\PlgConfigService\Entity\ConfigOptionInterface;
use Plugin\PinpointSale\Service\PlgConfigService\Entity\ConfigOptionTrait;

/**
 * Class PlgConfigOption
 * @package Plugin\PinpointSale\Entity
 *
 * @ORM\Table(name="plg_pinpoint_sale_config_option")
 * @ORM\Entity(repositoryClass="Plugin\PinpointSale\Repository\PlgConfigOptionRepository")
 */
class PlgConfigOption extends AbstractEntity implements ConfigOptionInterface
{
    use ConfigOptionTrait;
}
