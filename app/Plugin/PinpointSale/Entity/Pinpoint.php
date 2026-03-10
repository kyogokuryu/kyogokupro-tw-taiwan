<?php

namespace Plugin\PinpointSale\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Form\FormInterface;

if (!class_exists('\Plugin\PinpointSale\Entity\Pinpoint')) {
    /**
     * PlgPinpoint
     *
     * @ORM\Table(name="plg_pinpoint")
     * @ORM\Entity(repositoryClass="Plugin\PinpointSale\Repository\PinpointRepository")
     */
    class Pinpoint extends \Eccube\Entity\AbstractEntity
    {

        // 割引　価格
        const TYPE_PRICE = 1;

        // 割引　割引率
        const TYPE_RATE = 2;

        // 割引　共通（割引率）
        const TYPE_COMMON = 3;

        public function __construct()
        {
            $this->ProductPinpoints = new ArrayCollection();
        }

        /**
         * @var int
         *
         * @ORM\Column(name="id", type="integer")
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        private $id;

        /**
         * @var int
         *
         * @ORM\Column(name="sale_type", type="smallint", nullable=false)
         */
        private $saleType;

        /**
         * @var string|null
         *
         * @ORM\Column(name="sale_price", type="decimal", precision=12, scale=2, nullable=true)
         */
        private $salePrice;

        /**
         * @var int|null
         *
         * @ORM\Column(name="sale_rate", type="integer", nullable=true)
         */
        private $saleRate;

        /**
         * @var \DateTime|null
         *
         * @ORM\Column(name="start_time", type="datetime", nullable=true)
         */
        private $startTime;

        /**
         * @var \DateTime|null
         *
         * @ORM\Column(name="end_time", type="datetime", nullable=true)
         */
        private $endTime;

        /**
         * @var string|null
         *
         * @ORM\Column(name="name", type="string", length=255, nullable=true)
         */
        private $name;

        /**
         * @var int
         *
         * @ORM\Column(name="sort_no", type="integer", nullable=false, options={"default":0})
         */
        private $sortNo;

        /**
         * @var PinpointRepeat
         *
         * @ORM\ManyToOne(targetEntity="Plugin\PinpointSale\Entity\PinpointRepeat", inversedBy="Pinpoints", cascade={"persist"})
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="repeat_id", referencedColumnName="id")
         * })
         */
        private $PinpointRepeat;

        /**
         * @var \Doctrine\Common\Collections\Collection
         *
         * @ORM\OneToMany(targetEntity="Plugin\PinpointSale\Entity\ProductPinpoint", mappedBy="Pinpoint")
         */
        private $ProductPinpoints;

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
         * Set saleType.
         *
         * @param int $saleType
         *
         * @return Pinpoint
         */
        public function setSaleType($saleType)
        {
            $this->saleType = $saleType;

            return $this;
        }

        /**
         * Get saleType.
         *
         * @return int
         */
        public function getSaleType()
        {
            return $this->saleType;
        }

        /**
         * Set salePrice.
         *
         * @param string|null $salePrice
         *
         * @return Pinpoint
         */
        public function setSalePrice($salePrice = null)
        {
            $this->salePrice = $salePrice;

            return $this;
        }

        /**
         * Get salePrice.
         *
         * @return string|null
         */
        public function getSalePrice()
        {
            return $this->salePrice;
        }

        /**
         * Set saleRate.
         *
         * @param int|null $saleRate
         *
         * @return Pinpoint
         */
        public function setSaleRate($saleRate = null)
        {
            $this->saleRate = $saleRate;

            return $this;
        }

        /**
         * Get saleRate.
         *
         * @return int|null
         */
        public function getSaleRate()
        {
            return $this->saleRate;
        }

        /**
         * Set startTime.
         *
         * @param \DateTime|null $startTime
         *
         * @return Pinpoint
         */
        public function setStartTime($startTime = null)
        {
            $this->startTime = $startTime;

            return $this;
        }

        /**
         * Get startTime.
         *
         * @return \DateTime|null
         */
        public function getStartTime()
        {
            return $this->startTime;
        }

        /**
         * Set endTime.
         *
         * @param \DateTime|null $endTime
         *
         * @return Pinpoint
         */
        public function setEndTime($endTime = null)
        {
            $this->endTime = $endTime;

            return $this;
        }

        /**
         * Get endTime.
         *
         * @return \DateTime|null
         */
        public function getEndTime()
        {
            return $this->endTime;
        }

        /**
         * @return string|null
         */
        public function getName()
        {
            return $this->name;
        }

        /**
         * @param string|null $name
         * @return Pinpoint
         */
        public function setName(?string $name)
        {
            $this->name = $name;
            return $this;
        }

        /**
         * @return int
         */
        public function getSortNo()
        {
            return $this->sortNo;
        }

        /**
         * @param int $sortNo
         * @return Pinpoint
         */
        public function setSortNo(int $sortNo)
        {
            $this->sortNo = $sortNo;
            return $this;
        }

        /**
         * @return PinpointRepeat
         */
        public function getPinpointRepeat()
        {
            return $this->PinpointRepeat;
        }

        /**
         * @param PinpointRepeat $PinpointRepeat
         * @return Pinpoint
         */
        public function setPinpointRepeat(?PinpointRepeat $PinpointRepeat)
        {
            $this->PinpointRepeat = $PinpointRepeat;
            return $this;
        }

        /**
         * repeat copy
         *
         * @param FormInterface|null $pinpointRepeatForm
         */
        public function copyPinpointRepeat(?FormInterface $pinpointRepeatForm)
        {

            if (is_null($pinpointRepeatForm)) {
                // 繰り返しがない場合はNULL設定
                $this->setPinpointRepeat(null);
                return;
            }

            $pinpoints = new ArrayCollection();
            $pinpoints->add($this);

            $pinpointRepeat = new PinpointRepeat();
            $pinpointRepeat
                ->setStartTime($pinpointRepeatForm->get('start_time')->getData())
                ->setEndTime($pinpointRepeatForm->get('end_time')->getData())
                ->setPinpoints($pinpoints);

            $checks = $pinpointRepeatForm->get('week_check')->getData();

            $checkSum = 0;
            foreach ($checks as $value) {
                $checkSum += $value;
            }

            $checkList = [
                'Week0' => PinpointRepeat::WEEK_0,
                'Week1' => PinpointRepeat::WEEK_1,
                'Week2' => PinpointRepeat::WEEK_2,
                'Week3' => PinpointRepeat::WEEK_3,
                'Week4' => PinpointRepeat::WEEK_4,
                'Week5' => PinpointRepeat::WEEK_5,
                'Week6' => PinpointRepeat::WEEK_6,
            ];

            foreach ($checkList as $key => $item) {

                $method = 'set' . $key;
                if($checkSum & $item) {
                    $value = 1;
                } else {
                    $value = 0;
                }
                $pinpointRepeat->{$method}($value);
            }

            $this->setPinpointRepeat($pinpointRepeat);
        }

        /**
         * @return \Doctrine\Common\Collections\Collection
         */
        public function getProductPinpoints()
        {
            return $this->ProductPinpoints;
        }

        /**
         * @param \Doctrine\Common\Collections\Collection $ProductPinpoints
         * @return Pinpoint
         */
        public function setProductPinpoints(\Doctrine\Common\Collections\Collection $ProductPinpoints)
        {
            $this->ProductPinpoints = $ProductPinpoints;
            return $this;
        }

        /**
         * リピート判定
         *
         * @return bool true:リピートON
         */
        public function isPinpointRepeat()
        {
            if ($this->PinpointRepeat) {
                return true;
            }
            return false;
        }

        /**
         * 共通設定表示情報取得
         *
         * @return string
         */
        public function getViewName()
        {

            if ($this->isPinpointRepeat()) {
                $viewName = sprintf('%s (期間：%s ~ %s, 割引率：%d％, 繰り返し設定有り)',
                    $this->name,
                    $this->getStartTime()->format('Y/m/d H:i'),
                    $this->getEndTime()->format('Y/m/d H:i'),
                    $this->getSaleRate()
                );

            } else {
                $viewName = sprintf('%s (セール期間：%s ~ %s, 割引率：%d％)',
                    $this->name,
                    $this->getStartTime()->format('Y/m/d'),
                    $this->getEndTime()->format('Y/m/d'),
                    $this->getSaleRate()
                );
            }

            return $viewName;
        }

        /**
         * ProductClass の存在チェック
         *
         * @param $productClass
         * @return bool
         */
        public function hasProductClass($productClass)
        {
            $criteria = Criteria::create()
                ->where(Criteria::expr()->eq('ProductClass', $productClass))
                ->setFirstResult(0)
                ->setMaxResults(1);

            return $this->ProductPinpoints->matching($criteria)->count() > 0;
        }

        /**
         * Copy
         *
         * @param FormInterface $pinpointForm
         */
        public function copySetting(FormInterface $pinpointForm)
        {
            $this
                ->setSaleType($pinpointForm->get('sale_type')->getData())
                ->setSalePrice($pinpointForm->get('salePrice')->getData())
                ->setSaleRate($pinpointForm->get('saleRate')->getData())
                ->setStartTime($pinpointForm->get('start_time')->getData())
                ->setEndTime($pinpointForm->get('end_time')->getData())
                ->setSortNo(0)
                ->copyPinpointRepeat($pinpointForm->get('PinpointRepeat'));
        }

        /**
         * 販売種別共通判定
         *
         * @return bool true:共通
         */
        public function isSaleTypeCommon()
        {
            if (self::TYPE_COMMON == $this->saleType) {
                return true;
            }

            return false;
        }
    }
}
