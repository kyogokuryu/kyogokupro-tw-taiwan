<?php

namespace Plugin\Ranking\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Eccube\Annotation\EntityExtension;

/**
 * Config
 *
 * @ORM\Table(name="plg_ranking_config")
 * @ORM\Entity(repositoryClass="Plugin\Ranking\Repository\ConfigRepository")
 */
class Config
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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    // 対象期間
    /**
     * @var integer
     *
     * @ORM\Column(name="target_period", type="integer", nullable=false, options={"default": 1,"comment": "対象期間(1:前日, 2:過去1週間, 3:過去2週間, 4:過去3週間, 5:過去1ヶ月)"})
     */
    private $target_period;
    /**
     * @return integer
     */
    public function getTargetPeriod()
    {
        return $this->target_period;
    }
    /**
     * @param integer $target_period
     *
     * @return $this;
     */
    public function setTargetPeriod($target_period)
    {
        $this->target_period = $target_period;

        return $this;
    }
    
    // スライダー自動再生
    /**
     * @var integer
     *
     * @ORM\Column(name="slider_auto_play", type="boolean", options={"default": 1,"comment": "スライダー再生(1:自動, 0:手動)"})
     */
    private $slider_auto_play;
    /**
     * @return boolean
     */
    public function getSliderAutoPlay()
    {
        return $this->slider_auto_play;
    }
    /**
     * @param boolean $slider_auto_play
     *
     * @return $this;
     */
    public function setSliderAutoPlay($slider_auto_play)
    {
        $this->slider_auto_play = $slider_auto_play;
        
        return $this;
    }

    // スライダーデザイン
    /**
     * @var integer
     *
     * @ORM\Column(name="slider_design", type="integer", nullable=false, options={"default": 1,"comment": "対象期間(1:タイプ1, 2:タイプ2, 3:タイプ3, 4:タイプ4, 5:タイプ5)"})
     */
    private $slider_design;
    /**
     * @return integer
     */
    public function getSliderDesign()
    {
        return $this->slider_design;
    }
    /**
     * @param integer $slider_design
     *
     * @return $this;
     */
    public function setSliderDesign($slider_design)
    {
        $this->slider_design = $slider_design;

        return $this;
    }

    // 枠1
    /**
     * @var boolean
     *
     * @ORM\Column(name="frame1_type", type="boolean", options={"default": 0,"comment": "枠1設定区分(0:自動, 1:手動)"})
     */
    private $frame1_type;
    /**
     * @return boolean
     */
    public function getframe1Type()
    {
        return $this->frame1_type;
    }
    /**
     * @param boolean $frame1_type
     *
     * @return $this;
     */
    public function setframe1Type($frame1_type)
    {
        $this->frame1_type = $frame1_type;

        return $this;
    }
    /**
     * @var int
     *
     * @ORM\Column(name="frame1_value", type="integer", nullable=true, options={"unsigned": true,"default": 1,"comment": "枠1値(自動ランキング順位 or 商品ID)"})
     */
    private $frame1_value;
    /**
     * @return int
     */
    public function getframe1Value()
    {
        return $this->frame1_value;
    }
    /**
     * @param int $frame1_value
     *
     * @return $this;
     */
    public function setframe1Value($frame1_value)
    {
        $this->frame1_value = $frame1_value;

        return $this;
    }

    // 枠2
    /**
     * @var boolean
     *
     * @ORM\Column(name="frame2_type", type="boolean", options={"default": 0,"comment": "枠2設定区分(0:自動, 1:手動)"})
     */
    private $frame2_type;
    /**
     * @return boolean
     */
    public function getframe2Type()
    {
        return $this->frame2_type;
    }
    /**
     * @param boolean $frame2_type
     *
     * @return $this;
     */
    public function setframe2Type($frame2_type)
    {
        $this->frame2_type = $frame2_type;

        return $this;
    }
    /**
     * @var int
     *
     * @ORM\Column(name="frame2_value", type="integer", nullable=true, options={"unsigned": true,"default": 2,"comment": "枠2値(自動ランキング順位 or 商品ID)"})
     */
    private $frame2_value;
    /**
     * @return int
     */
    public function getframe2Value()
    {
        return $this->frame2_value;
    }
    /**
     * @param int $frame2_value
     *
     * @return $this;
     */
    public function setframe2Value($frame2_value)
    {
        $this->frame2_value = $frame2_value;

        return $this;
    }

    // 枠3
    /**
     * @var boolean
     *
     * @ORM\Column(name="frame3_type", type="boolean", options={"default": 0,"comment": "枠3設定区分(0:自動, 1:手動)"})
     */
    private $frame3_type;
    /**
     * @return boolean
     */
    public function getframe3Type()
    {
        return $this->frame3_type;
    }
    /**
     * @param boolean $frame3_type
     *
     * @return $this;
     */
    public function setframe3Type($frame3_type)
    {
        $this->frame3_type = $frame3_type;

        return $this;
    }
    /**
     * @var int
     *
     * @ORM\Column(name="frame3_value", type="integer", nullable=true, options={"unsigned": true,"default": 3,"comment": "枠3値(自動ランキング順位 or 商品ID)"})
     */
    private $frame3_value;
    /**
     * @return int
     */
    public function getframe3Value()
    {
        return $this->frame3_value;
    }
    /**
     * @param int $frame3_value
     *
     * @return $this;
     */
    public function setframe3Value($frame3_value)
    {
        $this->frame3_value = $frame3_value;

        return $this;
    }

    // 枠4
    /**
     * @var boolean
     *
     * @ORM\Column(name="frame4_type", type="boolean", options={"default": 0,"comment": "枠4設定区分(0:自動, 1:手動)"})
     */
    private $frame4_type;
    /**
     * @return boolean
     */
    public function getframe4Type()
    {
        return $this->frame4_type;
    }
    /**
     * @param boolean $frame4_type
     *
     * @return $this;
     */
    public function setframe4Type($frame4_type)
    {
        $this->frame4_type = $frame4_type;

        return $this;
    }
    /**
     * @var int
     *
     * @ORM\Column(name="frame4_value", type="integer", nullable=true, options={"unsigned": true,"default": 4,"comment": "枠4値(自動ランキング順位 or 商品ID)"})
     */
    private $frame4_value;
    /**
     * @return int
     */
    public function getframe4Value()
    {
        return $this->frame4_value;
    }
    /**
     * @param int $frame4_value
     *
     * @return $this;
     */
    public function setframe4Value($frame4_value)
    {
        $this->frame4_value = $frame4_value;

        return $this;
    }

    // 枠5
    /**
     * @var boolean
     *
     * @ORM\Column(name="frame5_type", type="boolean", options={"default": 0,"comment": "枠5設定区分(0:自動, 1:手動)"})
     */
    private $frame5_type;
    /**
     * @return boolean
     */
    public function getframe5Type()
    {
        return $this->frame5_type;
    }
    /**
     * @param boolean $frame5_type
     *
     * @return $this;
     */
    public function setframe5Type($frame5_type)
    {
        $this->frame5_type = $frame5_type;

        return $this;
    }
    /**
     * @var int
     *
     * @ORM\Column(name="frame5_value", type="integer", nullable=true, options={"unsigned": true,"default": 5,"comment": "枠5値(自動ランキング順位 or 商品ID)"})
     */
    private $frame5_value;
    /**
     * @return int
     */
    public function getframe5Value()
    {
        return $this->frame5_value;
    }
    /**
     * @param int $frame5_value
     *
     * @return $this;
     */
    public function setframe5Value($frame5_value)
    {
        $this->frame5_value = $frame5_value;

        return $this;
    }

    // 枠6
    /**
     * @var boolean
     *
     * @ORM\Column(name="frame6_type", type="boolean", options={"default": 0,"comment": "枠6設定区分(0:自動, 1:手動)"})
     */
    private $frame6_type;
    /**
     * @return boolean
     */
    public function getframe6Type()
    {
        return $this->frame6_type;
    }
    /**
     * @param boolean $frame6_type
     *
     * @return $this;
     */
    public function setframe6Type($frame6_type)
    {
        $this->frame6_type = $frame6_type;

        return $this;
    }
    /**
     * @var int
     *
     * @ORM\Column(name="frame6_value", type="integer", nullable=true, options={"unsigned": true,"default": 6,"comment": "枠6値(自動ランキング順位 or 商品ID)"})
     */
    private $frame6_value;
    /**
     * @return int
     */
    public function getframe6Value()
    {
        return $this->frame6_value;
    }
    /**
     * @param int $frame6_value
     *
     * @return $this;
     */
    public function setframe6Value($frame6_value)
    {
        $this->frame6_value = $frame6_value;

        return $this;
    }

    // 枠7
    /**
     * @var boolean
     *
     * @ORM\Column(name="frame7_type", type="boolean", options={"default": 0,"comment": "枠7設定区分(0:自動, 1:手動)"})
     */
    private $frame7_type;
    /**
     * @return boolean
     */
    public function getframe7Type()
    {
        return $this->frame7_type;
    }
    /**
     * @param boolean $frame7_type
     *
     * @return $this;
     */
    public function setframe7Type($frame7_type)
    {
        $this->frame7_type = $frame7_type;

        return $this;
    }
    /**
     * @var int
     *
     * @ORM\Column(name="frame7_value", type="integer", nullable=true, options={"unsigned": true,"default": 7,"comment": "枠7値(自動ランキング順位 or 商品ID)"})
     */
    private $frame7_value;
    /**
     * @return int
     */
    public function getframe7Value()
    {
        return $this->frame7_value;
    }
    /**
     * @param int $frame7_value
     *
     * @return $this;
     */
    public function setframe7Value($frame7_value)
    {
        $this->frame7_value = $frame7_value;

        return $this;
    }

    // 枠8
    /**
     * @var boolean
     *
     * @ORM\Column(name="frame8_type", type="boolean", options={"default": 0,"comment": "枠8設定区分(0:自動, 1:手動)"})
     */
    private $frame8_type;
    /**
     * @return boolean
     */
    public function getframe8Type()
    {
        return $this->frame8_type;
    }
    /**
     * @param boolean $frame8_type
     *
     * @return $this;
     */
    public function setframe8Type($frame8_type)
    {
        $this->frame8_type = $frame8_type;

        return $this;
    }
    /**
     * @var int
     *
     * @ORM\Column(name="frame8_value", type="integer", nullable=true, options={"unsigned": true,"default": 8,"comment": "枠8値(自動ランキング順位 or 商品ID)"})
     */
    private $frame8_value;
    /**
     * @return int
     */
    public function getframe8Value()
    {
        return $this->frame8_value;
    }
    /**
     * @param int $frame8_value
     *
     * @return $this;
     */
    public function setframe8Value($frame8_value)
    {
        $this->frame8_value = $frame8_value;

        return $this;
    }

    // 枠9
    /**
     * @var boolean
     *
     * @ORM\Column(name="frame9_type", type="boolean", options={"default": 0,"comment": "枠9設定区分(0:自動, 1:手動)"})
     */
    private $frame9_type;
    /**
     * @return boolean
     */
    public function getframe9Type()
    {
        return $this->frame9_type;
    }
    /**
     * @param boolean $frame9_type
     *
     * @return $this;
     */
    public function setframe9Type($frame9_type)
    {
        $this->frame9_type = $frame9_type;

        return $this;
    }
    /**
     * @var int
     *
     * @ORM\Column(name="frame9_value", type="integer", nullable=true, options={"unsigned": true,"default": 9,"comment": "枠9値(自動ランキング順位 or 商品ID)"})
     */
    private $frame9_value;
    /**
     * @return int
     */
    public function getframe9Value()
    {
        return $this->frame9_value;
    }
    /**
     * @param int $frame9_value
     *
     * @return $this;
     */
    public function setframe9Value($frame9_value)
    {
        $this->frame9_value = $frame9_value;

        return $this;
    }

    // 枠10
    /**
     * @var boolean
     *
     * @ORM\Column(name="frame10_type", type="boolean", options={"default": 0,"comment": "枠10設定区分(0:自動, 1:手動)"})
     */
    private $frame10_type;
    /**
     * @return boolean
     */
    public function getframe10Type()
    {
        return $this->frame10_type;
    }
    /**
     * @param boolean $frame10_type
     *
     * @return $this;
     */
    public function setframe10Type($frame10_type)
    {
        $this->frame10_type = $frame10_type;

        return $this;
    }
    /**
     * @var int
     *
     * @ORM\Column(name="frame10_value", type="integer", nullable=true, options={"unsigned": true,"default": 10,"comment": "枠10値(自動ランキング順位 or 商品ID)"})
     */
    private $frame10_value;
    /**
     * @return int
     */
    public function getframe10Value()
    {
        return $this->frame10_value;
    }
    /**
     * @param int $frame10_value
     *
     * @return $this;
     */
    public function setframe10Value($frame10_value)
    {
        $this->frame10_value = $frame10_value;

        return $this;
    }

}
