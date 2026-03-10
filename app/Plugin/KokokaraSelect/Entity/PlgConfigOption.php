<?php
/**
 * Copyright(c) 2020 SYSTEM_KD
 * Date: 2020/06/06
 */

namespace Plugin\KokokaraSelect\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Plugin\KokokaraSelect\Service\PlgConfigService\Entity\ConfigOptionInterface;
use Plugin\KokokaraSelect\Service\PlgConfigService\Entity\ConfigOptionTrait;

/**
 * Class PlgConfigOption
 * @package Plugin\KokokaraSelect\Entity
 *
 * @ORM\Table(name="plg_kokokara_select_config_option")
 * @ORM\Entity(repositoryClass="Plugin\KokokaraSelect\Repository\PlgConfigOptionRepository")
 */
class PlgConfigOption extends AbstractEntity implements ConfigOptionInterface
{
    use ConfigOptionTrait;
}
