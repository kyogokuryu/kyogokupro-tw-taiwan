<?php

namespace Plugin\PinpointSale\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * PlgPinpointRepeat
 *
 * @ORM\Table(name="plg_pinpoint_repeat")
 * @ORM\Entity(repositoryClass="Plugin\PinpointSale\Repository\PinpointRepeatRepository")
 */
class PinpointRepeat extends \Eccube\Entity\AbstractEntity
{

    // 日曜
    const WEEK_0 = 1;

    // 月曜
    const WEEK_1 = 2;

    // 火曜
    const WEEK_2 = 4;

    // 水曜
    const WEEK_3 = 8;

    // 木曜
    const WEEK_4 = 16;

    // 金曜
    const WEEK_5 = 32;

    // 土曜
    const WEEK_6 = 64;

    /** @var int 繰り返しOFF */
    const REPEAT_OFF = 0;

    /** @var int 繰り返しON */
    const REPEAT_ON = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="start_time", type="string", length=256, nullable=true)
     */
    private $startTime;

    /**
     * @var string|null
     *
     * @ORM\Column(name="end_time", type="string", length=256, nullable=true)
     */
    private $endTime;

    /**
     * @var int|null
     *
     * @ORM\Column(name="week0", type="smallint", nullable=true)
     */
    private $week0;

    /**
     * @var int|null
     *
     * @ORM\Column(name="week1", type="smallint", nullable=true)
     */
    private $week1;

    /**
     * @var int|null
     *
     * @ORM\Column(name="week2", type="smallint", nullable=true)
     */
    private $week2;

    /**
     * @var int|null
     *
     * @ORM\Column(name="week3", type="smallint", nullable=true)
     */
    private $week3;

    /**
     * @var int|null
     *
     * @ORM\Column(name="week4", type="smallint", nullable=true)
     */
    private $week4;

    /**
     * @var int|null
     *
     * @ORM\Column(name="week5", type="smallint", nullable=true)
     */
    private $week5;

    /**
     * @var int|null
     *
     * @ORM\Column(name="week6", type="smallint", nullable=true)
     */
    private $week6;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\PinpointSale\Entity\Pinpoint", mappedBy="PinpointRepeat")
     */
    private $Pinpoints;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set startTime.
     *
     * @param string|null $startTime
     *
     * @return PinpointRepeat
     */
    public function setStartTime($startTime = null)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime.
     *
     * @return string|null
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Set endTime.
     *
     * @param string|null $endTime
     *
     * @return PinpointRepeat
     */
    public function setEndTime($endTime = null)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get endTime.
     *
     * @return string|null
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Set week0.
     *
     * @param int|null $week0
     *
     * @return PinpointRepeat
     */
    public function setWeek0($week0 = null)
    {
        $this->week0 = $week0;

        return $this;
    }

    /**
     * Get week0.
     *
     * @return int|null
     */
    public function getWeek0()
    {
        return $this->week0;
    }

    /**
     * Set week1.
     *
     * @param int|null $week1
     *
     * @return PinpointRepeat
     */
    public function setWeek1($week1 = null)
    {
        $this->week1 = $week1;

        return $this;
    }

    /**
     * Get week1.
     *
     * @return int|null
     */
    public function getWeek1()
    {
        return $this->week1;
    }

    /**
     * Set week2.
     *
     * @param int|null $week2
     *
     * @return PinpointRepeat
     */
    public function setWeek2($week2 = null)
    {
        $this->week2 = $week2;

        return $this;
    }

    /**
     * Get week2.
     *
     * @return int|null
     */
    public function getWeek2()
    {
        return $this->week2;
    }

    /**
     * Set week3.
     *
     * @param int|null $week3
     *
     * @return PinpointRepeat
     */
    public function setWeek3($week3 = null)
    {
        $this->week3 = $week3;

        return $this;
    }

    /**
     * Get week3.
     *
     * @return int|null
     */
    public function getWeek3()
    {
        return $this->week3;
    }

    /**
     * Set week4.
     *
     * @param int|null $week4
     *
     * @return PinpointRepeat
     */
    public function setWeek4($week4 = null)
    {
        $this->week4 = $week4;

        return $this;
    }

    /**
     * Get week4.
     *
     * @return int|null
     */
    public function getWeek4()
    {
        return $this->week4;
    }

    /**
     * Set week5.
     *
     * @param int|null $week5
     *
     * @return PinpointRepeat
     */
    public function setWeek5($week5 = null)
    {
        $this->week5 = $week5;

        return $this;
    }

    /**
     * Get week5.
     *
     * @return int|null
     */
    public function getWeek5()
    {
        return $this->week5;
    }

    /**
     * Set week6.
     *
     * @param int|null $week6
     *
     * @return PinpointRepeat
     */
    public function setWeek6($week6 = null)
    {
        $this->week6 = $week6;

        return $this;
    }

    /**
     * Get week6.
     *
     * @return int|null
     */
    public function getWeek6()
    {
        return $this->week6;
    }

    /**
     * 有効な曜日のindex返却
     *
     * @return array
     */
    public function getActiveWeeks()
    {
        $activeWeeks = [];

        for ($i = 0; $i <= 6; $i++) {

            $method = 'getWeek' . $i;

            if($this->{$method}() == 1) {
                $activeWeeks[$i] = 1;
            }
        }

        return $activeWeeks;
    }

    /**
     * @return Collection
     */
    public function getPinpoints()
    {
        return $this->Pinpoints;
    }

    /**
     * @param Collection $Pinpoints
     * @return PinpointRepeat
     */
    public function setPinpoints(Collection $Pinpoints)
    {
        $this->Pinpoints = $Pinpoints;
        return $this;
    }
}
