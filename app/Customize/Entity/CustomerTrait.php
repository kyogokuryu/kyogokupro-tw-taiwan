<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Customer")
 */
trait CustomerTrait
{


    public static $login_point_list = [1, 5, 3, 5, 5, 10, 10];

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=false, options="{default:0}")
     */
    private $prime_member = 0;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=false, options="{default:0}")
     */
    private $pre_order_discount_price = 0;


    //会員情報にサロン(親ユーザー)と紐づける項目追加 20220510 kikuzawa
    /**
     * @var int
     *
     * @ORM\Column(name="salon_id", type="integer", nullable=true, options={"unsigned":true})
     */
    private $Salon_id;

    /**
     * @var string
     *
     * @ORM\Column(name="financial", type="string", length=255, nullable=true)
     */
    private $Financial;

    /**
     * @var string
     *
     * @ORM\Column(name="branch", type="string", length=255, nullable=true)
     */
    private $Branch;

    /**
     * @var string
     *
     * @ORM\Column(name="account_type", type="integer", nullable=true, options={"unsigned":true})
     */
    private $Account_type;

    /**
     * @var string
     *
     * @ORM\Column(name="account_number", type="string", length=255, nullable=true)
     */
    private $Account_number;

    /**
     * @var string
     *
     * @ORM\Column(name="account_name", type="string", length=255, nullable=true)
     */
    private $Account_name;
    //end 会員情報にサロン(親ユーザー)と紐づける項目追加 20220510 kikuzawa
    
    /**
     *  登録をどこからしたのか  0:サイト新規登録 1:店頭 2:管理サイト
     *
     * @var integer
     * @ORM\Column(name="entry_type", type="integer", nullable=true, options={"default":0,"unsigned":true})
     *
     */
    private $entry_type;
    /**
     * オーナーズクラブランク 0:一般 1:シルバー 2:ゴールド 3:プラチナ 4:ダイヤモンド
     *
     * @var integer
     *
     * @ORM\Column(name="owner_rank", type="integer", nullable=true, options={"default":0,"unsigned":true})
     */
    private $owner_rank;


    /**
     * @var integer
     *
     * @ORM\Column(name="owner_val", type="integer", nullable=true, options={"default":0,"unsigned":true})
     */
    private $owner_val;

    /**
     * @var integer
     *
     * @ORM\Column(name="owner_next_val", type="integer", nullable=true, options={"default":0,"unsigned":true})
     */
    private $owner_next_val;


    /**
     * @var integer
     *
     * @ORM\Column(name="login_point_day", type="integer", nullable=true, options={"default":0,"unsigned":true})
     */
    private $login_point_day;

    /**
     * @var date
     *
     * @ORM\Column(name="last_login_date", type="date", nullable=true)
     */
    private $last_login_date;
    /** 
    *   @return date|null
    */
    public function getLastLoginDate(){
        return $this->last_login_date;
    }

    /**
     * @param date
     * @return CustomerTrait
     */
    public function setLastLoginDate($last_login_date){
        $this->last_login_date = $last_login_date; 
        return $this;
    }

    /**
     * @return integer
     */
    public function getLoginPointDay(){
        return $this->login_point_day;
    }

    /**
     * @param integer
     * @return CustomerTrait
     */
    public function setLoginPointDay($login_point_day)
    {
        $this->login_point_day = $login_point_day;
        return $this;
    }

    /**
     *  @return bool
     */
    public function isLoginPoint(){

        if( $this->last_login_date && $this->last_login_date->format('Y-m-d') == date('Y-m-d') ){
            return false;
        }

        return true;
    }


    public function calcOwnerRank(){
        if($this->owner_val >= 150000){
            return 3;
        }elseif($this->owner_val >= 100000){
            return 2;
        }elseif($this->owner_val >= 30000){
            return 1;
        }
        return 0;
    }

    public function getOwnerPoint(){
        if($this->owner_val >= 150000){
            return 4;
        }elseif($this->owner_val >= 100000){
            return 3;
        }elseif($this->owner_val >= 30000){
            return 2;
        }
        return 1;
    }

    /**
     */
    public function getActiveLoginPointDay(){
        if($this->last_login_date){
            if($this->isLoginPoint()){
                if( $this->last_login_date->format('Y-m-d') == date('Y-m-d', strtotime('-1 day')) ){
                    if($this->login_point_day == 7){
                        return 1;
                    }else{
                        return $this->login_point_day + 1 ;
                    }
                }
            }else{
                
                return $this->login_point_day;
            }
        }
        return 1;
    }

    /**
     */
    public function getLoginPoint($day=null){
        $day = $day ?: $this->getActiveLoginPointDay();
        return self::$login_point_list[ ($day - 1) % 7];
    }

    /**
     */
    public function getLoginDayList(){

        if($this->isLoginPoint()){
            $login_point_day = $this->login_point_day;
            if( $this->last_login_date && $this->last_login_date->format('Y-m-d') == date('Y-m-d', strtotime('-1 day')) ){
                // 継続中
                $login_point_day = $this->login_point_day % 7;
            }else{
                // 新規
                $login_point_day = 0;
            }
        }else{            
            $login_point_day = $this->login_point_day - 1;        
        }

        $days = [];
        for($i=0; $i<7; $i++){
            $days[] =["active"=>($login_point_day - $i) >= 0, "today"=>($login_point_day - $i) == 0 ,"point"=>self::$login_point_list[$i], "day"=>date('n/j', strtotime("-" . ($login_point_day - $i) . "day"))];
        }    
        return $days;        

    }


    /**
     * @return integer
     */
    public function getOwnerRank(){
        return $this->owner_rank;
    }

    /**
     * @param integer|null 
     * @return CustomerTrait
     */
    public function setOwnerRank($val){
        $this->owner_rank = $val;
        return $this;
    }

    /**
     * @return integer
     */
    public function getOwnerVal(){
        return $this->owner_val;
    }

    /**
     * @param integer|null 
     * @return CustomerTrait
     */
    public function setOwnerVal($val){
        $this->owner_val = $val;
        return $this;
    }
    /**
     * @return integer
     */
    public function getOwnerNextVal(){
        return $this->owner_next_val;
    }

    /**
     * @param integer|null 
     * @return CustomerTrait
     */
    public function setOwnerNextVal($val){
        $this->owner_next_val = $val;
        return $this;
    }
    /**
     * @return integer
     */
    public function getEntryType(){
        return $this->entry_type;
    }

    /**
     *
     * @param integer|null 
     * @return CustomerTrait
     */
    public function setEntryType($val){
        $this->entry_type = $val;
        return $this;
    }


    /**
     * @return integer|null
     */
    public function getPrimeMember()
    {
        return $this->prime_member;
    }

    /**
     * @param integer|null $prime_number
     * @return CustomerTrait
     */
    public function setPrimeMember($prime_member)
    {
        $this->prime_member = $prime_member;
        return $this;
    }


    public function setPreOrderDiscountPrice($val){
        $this->pre_order_discount_price = $val;
        return $this;
    }

    public function getPreOrderDiscountPrice(){
        return $this->pre_order_discount_price;
    }

    //会員情報にサロン(親ユーザー)と紐づける項目追加 20220510 kikuzawa
    /**
     * Set salon_id
     *
     * @param int|null $salon_id
     *
     * @return Customer
     */
    public function setSalonId($salon_id = null)
    {
        $this->Salon_id = $salon_id;

        return $this;
    }

    /**
     * Get salon_id
     *
     * @return int|null
     */
    public function getSalonId()
    {
        return $this->Salon_id;
    }

    /**
     * Set financial
     *
     * @param string|null $financial
     *
     * @return Customer
     */
    public function setFinancial($financial = null)
    {
        $this->Financial = $financial;

        return $this;
    }

    /**
     * Get financial
     *
     * @return string|null
     */
    public function getFinancial()
    {
        return $this->Financial;
    }

    /**
     * Set branch
     *
     * @param string|null $branch
     *
     * @return Customer
     */
    public function setBranch($branch = null)
    {
        $this->Branch = $branch;

        return $this;
    }

    /**
     * Get branch
     *
     * @return string|null
     */
    public function getBranch()
    {
        return $this->Branch;
    }

    /**
     * Set account_type
     *
     * @param int|null $account_type
     *
     * @return Customer
     */
    public function setAccountType($account_type)
    {
        $this->Account_type = $account_type;

        return $this;
    }

    /**
     * Get account_type
     *
     * @return int|null
     */
    public function getAccountType()
    {
        return $this->Account_type;
    }

    /**
     * Set account_number
     *
     * @param string|null $account_number
     *
     * @return Customer
     */
    public function setAccountNumber($account_number = null)
    {
        $this->Account_number = $account_number;

        return $this;
    }

    /**
     * Get account_number
     *
     * @return string|null
     */
    public function getAccountNumber()
    {
        return $this->Account_number;
    }

    /**
     * Set account_name
     *
     * @param string|null $account_name
     *
     * @return Customer
     */
    public function setAccountName($account_name = null)
    {
        $this->Account_name = $account_name;

        return $this;
    }

    /**
     * Get account_name
     *
     * @return string|null
     */
    public function getAccountName()
    {
        return $this->Account_name;
    }
    //end 会員情報にサロン(親ユーザー)と紐づける項目追加 20220510 kikuzawa
}