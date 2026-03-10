<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\OrderItem")
 */
trait OrderItemTrait
{

    /**
    * @var integer
    * @ORM\Column(name="review_point", type="integer", nullable=true, options={"unsigned"=true, "default"=0})
    */
    private $review_point;
    
    /**
    * @var integer
    * @ORM\Column(name="review_id", type="integer", nullable=true, options={"unsigned"=true, "default"=0})
    */
    private $review_id;


    /**
     * @return integer
     */
    public function getReviewPoint(){
        return $this->review_point;
    }

    public function setReviewPoint($val){
        $this->review_point = $val;
        return $this;
    }

    /**
     * @return integer
     */
    public function getReviewId(){
        return $this->review_id;
    }

    /**
     *  @return 
     */
    public function setReviewId($id){
        $this->review_id = $id;
        return $this;
    }
}