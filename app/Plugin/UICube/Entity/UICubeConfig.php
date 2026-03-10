<?php
/*
* Plugin Name : UICube
*/

namespace Plugin\UICube\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * UICubeConfig
 *
 * @ORM\Table(name="plg_ui_cube")
 * @ORM\Entity(repositoryClass="Plugin\UICube\Repository\UICubeRepository")
 */
class UICubeConfig extends AbstractEntity
{
  /**
   * @var int
   *
   * @ORM\Column(name="id", type="integer", options={"unsigned":true})
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="IDENTITY")
   */
  private $id;

  /**
   * Get id.
   *
   * @return int
   */
  public function getId()
  {
    return $this->id;
  }
}
