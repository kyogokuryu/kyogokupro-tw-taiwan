<?php

namespace Customize\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
use Eccube\Annotation as Eccube;
use Eccube\Entity\Product;
use Eccube\Entity\Master\SaleType;

/**
 * @EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait
{
    public static function get_prime_product_id(){
         return 522;
    }

    public static function get_prime_light_product_id(){
        return  814;
    }

    public static function get_all_prime_ids(){
        return [self::get_prime_product_id(), self::get_prime_light_product_id()];
    }
    /**
    * @var integer
    * @ORM\Column(name="regular_product_id", type="integer", nullable=true, options={"unsigned"=true})
    * @Eccube\FormAppend(
    *  auto_render=true,
    *  type="\Symfony\Component\Form\Extension\Core\Type\IntegerType",
    *  options={
    *   "required": false,
    *   "label":"関連定期商品ID"
    *  }
    * )
    */
    private $regular_product_id;

    /**
    * @var integer
    * @ORM\Column(name="owner_product_id", type="integer", nullable=true, options={"unsigned"=true})
    * @Eccube\FormAppend(
    *  auto_render=true,
    *  type="\Symfony\Component\Form\Extension\Core\Type\IntegerType",
    *  options={
    *   "required": false,
    *   "label":"関連ダイヤモンドメンバー限定商品ID"
    *  }
    * )
    */
    private $owner_product_id;

    /**
    * @var integer
    * @ORM\Column(name="owner_rank", type="integer", nullable=true, options={"unsigned"=true, "default"=0})
    */
    private $owner_rank;

    /**
     * @var RegularProduct
     *
     * @ORM\OneToOne(targetEntity="Eccube\Entity\Product")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="owner_product_id", referencedColumnName="id")
     * })
     */
    private $OwnerProduct;

    /**
    * @var string
    *
    * @ORM\Column(name="product_sub_name", type="string", nullable=true)
    */
    private $product_sub_name;

    /**
     * @var RegularProduct
     *
     * @ORM\oneToOne(targetEntity="Eccube\Entity\Product")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="regular_product_id", referencedColumnName="id")
     * })
     */
    private $RegularProduct;

/**
     * @return mixed
     */
    public function getRegularProductId()
    {
        return $this->regular_product_id;
    }

    /**
     * @param $regular_product_id
     * @return $this
     */
    public function setRegularProductId($regular_product_id)
    {
        $this->regular_product_id = $regular_product_id;
        return $this;
    }

    public function getRegularProduct(){
        return $this->RegularProduct;
    }

    public function setRegularProduct($Product){
        $this->RegularProduct = $Product;

        return $this;
    }

    public function getRegularDiscount(){
        return $this->RegularProduct->price02;
    }

    public function getOwnerProductId(){
        return $this->owner_product_id;
    }
    public function getOwnerProduct(){
        return $this->OwnerProduct;
    }

    public function setOwnerProductId($val){
        $this->owner_product_id = $val;
    }
    public function setOwnerProduct($Product){
        $this->OwnerProduct = $Product;

        return $this;
    }
    public function getOwnerRank(){
        return $this->owner_rank;
    }

    public function setOwnerRank($val){
        $this->owner_rank = $val;
    }

    public function setProductSubName($val){
        $this->product_sub_name = $val;
    }

    public function getProductSubName(){
        return $this->product_sub_name;
    }

    public function isAllPrimeProduct(){
        if(in_array($this->id, [self::get_prime_product_id(), self::get_prime_light_product_id()])){
            return true;
        }
        return false;
    }


}   
