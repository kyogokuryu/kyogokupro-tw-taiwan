<?php
namespace Customize\Service;

class IpBlock{

    private $list = [];
    private $list_file;
    private $remote_ip;

    public function __construct($list_file){
        $this->list_file = $list_file; //__DIR__ . "/../../../allow_ip.txt";
        $this->remote_ip = $_SERVER["REMOTE_ADDR"];

        $this->load_list();
    }

    private function load_list(){
        foreach(explode("\n", file_get_contents($this->list_file)) as $line){
            if(preg_match('/[0-9]+/', $line)){
                $this->list[] = trim($line);
            }
        }
    }

    private function check_ip($accept_limit){
        //$accept_limit = "203.0.113.0/24";  //制限IP
        $remote_ip = $this->remote_ip; //$_SERVER["REMOTE_ADDR"];  //アクセスIP
        $ips = explode("/", $accept_limit);
        if(count($ips) == 1){
            if( $remote_ip == $ips[0] ){
                return true;
            }
        }else{
            list($accept_limit_ip, $mask) = explode("/", $accept_limit);
            $accept_limit_long = ip2long($accept_limit_ip) >> (32 - $mask);
            $remote_long = ip2long($remote_ip) >> (32 - $mask);

            if ($accept_limit_long == $remote_long) {
                return true;//echo "acceptable";
            } 
        }
        return false;
    }

    public function is_allow(){
        foreach($this->list as $ip){
            if( $this->check_ip($ip) ){
                return true;
            }
        }
        return false;
    }
}