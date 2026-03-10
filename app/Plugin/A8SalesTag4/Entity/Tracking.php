<?php

namespace Plugin\A8SalesTag4\Entity;

use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Plugin\A8SalesTag4\Entity\Tracking', false)) {
    /**
     * Config
     *
     * @ORM\Table(name="plg_a8_sales_tag4_tracking")
     * @ORM\Entity(repositoryClass="Plugin\A8SalesTag4\Repository\TrackingRepository")
     */
    class Tracking 
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
    }
}
