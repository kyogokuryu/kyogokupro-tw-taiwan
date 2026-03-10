<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.3   |
    |              on 2021-09-17 16:10:28              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
namespace Plugin\AmazonPayV2\Entity;use Eccube\Annotation\EntityExtension;use Doctrine\ORM\Mapping as ORM;
/**
* @EntityExtension("Eccube\Entity\Order")
*/
trait OrderTrait{public function getAmazonPayV2SumAuthoriAmount(){goto TDt7P;vRE24:mh3f2:goto FoOKS;TDt7P:$sumAuthoriAmount = 0;goto nixNX;FoOKS:return $sumAuthoriAmount;goto RBG9g;nixNX:foreach ($this->AmazonPayV2AmazonTradings as $AmazonTrading) {$sumAuthoriAmount += $AmazonTrading->getAuthoriAmount();gs4O0:}goto vRE24;RBG9g:}public function getAmazonPayV2SumCaptureAmount(){goto Z05j8;gICQ5:Xikow:goto RUup4;RUup4:return $sumCaptureAmount;goto TSQq5;Z05j8:$sumCaptureAmount = 0;goto dEsKS;dEsKS:foreach ($this->AmazonPayV2AmazonTradings as $AmazonTrading) {$sumCaptureAmount += $AmazonTrading->getCaptureAmount();t1qT8:}goto gICQ5;TSQq5:}    
/**
* @var string
* 
* @ORM\Column(name="amazonpay_v2_charge_permission_id", type="string", length=255, nullable=true)
*/
private $amazonpay_v2_charge_permission_id;    
/**
* @var integer
* 
* @ORM\Column(name="amazonpay_v2_billable_amount", type="integer", nullable=true)
*/
private $amazonpay_v2_billable_amount;    
/**
* @var AmazonStatus
* @ORM\ManyToOne(targetEntity="Plugin\AmazonPayV2\Entity\Master\AmazonStatus")
* @ORM\JoinColumns({
*   @ORM\JoinColumn(name="amazonpay_v2_amazon_status_id", referencedColumnName="id")
* })
*/
private $AmazonPayV2AmazonStatus;    
/**
* @var \Doctrine\Common\Collections\Collection
* 
* @ORM\OneToMany(targetEntity="Plugin\AmazonPayV2\Entity\AmazonTrading", mappedBy="Order", cascade={"persist", "remove"})
*/
private $AmazonPayV2AmazonTradings;    
/**
* @var string
* @ORM\Column(name="amazonpay_v2_session_temp", type="text", length=36777215, nullable=true)
*/
private $amazonpay_v2_session_temp;public function setAmazonPayV2ChargePermissionId($AmazonPayV2ChargePermissionId){$this->amazonpay_v2_charge_permission_id = $AmazonPayV2ChargePermissionId;return $this;}public function getAmazonPayV2ChargePermissionId(){return $this->amazonpay_v2_charge_permission_id;}public function setAmazonPayV2BillableAmount($amazonpayV2BillableAmount){$this->amazonpay_v2_billable_amount = $amazonpayV2BillableAmount;return $this;}public function getAmazonPayV2BillableAmount(){return $this->amazonpay_v2_billable_amount;}public function setAmazonPayV2AmazonStatus(\Plugin\AmazonPayV2\Entity\Master\AmazonStatus $AmazonPayV2AmazonStatus){$this->AmazonPayV2AmazonStatus = $AmazonPayV2AmazonStatus;return $this;}public function getAmazonPayV2AmazonStatus(){return $this->AmazonPayV2AmazonStatus;}public function addAmazonPayV2AmazonTrading(\Plugin\AmazonPayV2\Entity\AmazonTrading $AmazonPayV2AmazonTrading){$this->AmazonPayV2AmazonTradings[] = $AmazonPayV2AmazonTrading;return $this;}public function clearAmazonPayV2AmazonTradings(){$this->AmazonPayV2AmazonTradings->clear();return $this;}public function getAmazonPayV2AmazonTradings(){return $this->AmazonPayV2AmazonTradings;}public function setAmazonPayV2SessionTemp($AmazonPayV2SessionTemp){$this->amazonpay_v2_session_temp = $AmazonPayV2SessionTemp;return $this;}public function getAmazonPayV2SessionTemp(){return $this->amazonpay_v2_session_temp;}}