<?php

namespace Plugin\A8SalesTag4\Entity;

use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Plugin\A8SalesTag4\Entity\Config', false)) {
    /**
     * Config
     *
     * @ORM\Table(name="plg_a8_sales_tag4_config")
     * @ORM\Entity(repositoryClass="Plugin\A8SalesTag4\Repository\ConfigRepository")
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
         * @var string
         *
         * @ORM\Column(name="eid", type="string", length=255, nullable=true)
         */
        private $eid;

        /**
         * @var string
         *
         * @ORM\Column(name="pids", type="string", length=255, nullable=true)
         */
        private $pids;

        /**
         * @var string
         *
         * @ORM\Column(name="is_enabled_crossdomain", type="boolean", nullable=true)
         */
        private $is_enabled_crossdomain;

        /**
         * @return int
         */
        public function getId()
        {
            return $this->id;
        }

        /**
         * @return string
         */
        public function getEid()
        {
            return $this->eid;
        }

        /**
         * @param string $eid
         *
         * @return $this;
         */
        public function setEid($eid)
        {
            $this->eid = $eid;

            return $this;
        }

        /**
         * @return string
         */
        public function getPids()
        {
            return $this->pids;
        }

        /**
         * @param string $pids
         *
         * @return $this;
         */
        public function setPids($pids)
        {
            $this->pids = $pids;

            return $this;
        }

        /**
         * @return boolean
         */
        public function getIsEnabledCrossDomain()
        {
            return $this->is_enabled_crossdomain;
        }

        /**
         * @param boolean $is_enabled_crossdomain
         *
         * @return $this;
         */
        public function setIsEnabledCrossDomain($is_enabled_crossdomain)
        {
            $this->is_enabled_crossdomain = $is_enabled_crossdomain;

            return $this;
        }
    }
}
