<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/06
 */

namespace Plugin\KokokaraSelect\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Plugin\KokokaraSelect\Service\PlgConfigService\Entity\ConfigInterface;
use Plugin\KokokaraSelect\Service\PlgConfigService\Entity\ConfigTrait;

/**
 * Class PlgConfig
 * @package Plugin\KokokaraSelect\Entity
 *
 * @ORM\Table(name="plg_kokokara_select_config")
 * @ORM\Entity(repositoryClass="Plugin\KokokaraSelect\Repository\PlgConfigRepository")
 */
class PlgConfig extends AbstractEntity implements ConfigInterface
{
    use ConfigTrait;
}
