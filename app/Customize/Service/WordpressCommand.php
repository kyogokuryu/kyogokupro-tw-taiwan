<?php
namespace Customize\Service;


class WordpressCommand{

    public static function cancel($user_id){
         // wp
        $param = [];
        $param[] = "user update ";
        $param[] = "eu" . $user_id;
        $param[] = "--role=um_kyogoku_under-25";
        $param[] = "--path=".__DIR__ . "/../../../academy";

        $cmd = __DIR__ . "/../../../academy/wp-cli.phar";
        $cmd = "/usr/bin/php7.3 " . $cmd . " " . implode(" ", $param);
        //var_dump($cmd);
        exec($cmd);


        $param = [];
        $param[] = "db query 'update user_date set ";
        $param[] = 'start_under25_date="' . date('Y-m-d') . '", nextClass="under-25" ';
        $param[] = sprintf('where ID in (select ID from wp_users where user_login="%s")' . "'", "eu" . $user_id);
        $param[] = "--path=".__DIR__ . "/../../../academy";

        $cmd = __DIR__ . "/../../../academy/wp-cli.phar";
        $cmd = "/usr/bin/php7.3 " . $cmd . " " . implode(" ", $param);
        //var_dump($cmd);
        exec($cmd);

        $param = [];
        $param[] = "db query 'insert into delete_users (userID, name, email, request_date, delete_date)value(";
        $param[] = sprintf('(select ID from wp_users where user_login="%s"),', "eu" . $user_id);
        $param[] = '"eu' . $user_id . '",';
        $param[] = sprintf('(select user_email from wp_users where user_login="%s"),', "eu" . $user_id);
        $param[] = sprintf('"%s",', date('Y-m-d'));//start_under25_date="' . date('Y-m-d') . '", nextClass="under-25" ';
        $param[] = sprintf('"%s")', date('Y-m-d', strtotime('+1 month'))) . "'";//where ID in (select ID from wp_users where user_login="%s")' . "'", "eu" . $user_id);
        $param[] = "--path=".__DIR__ . "/../../../academy";

        $cmd = __DIR__ . "/../../../academy/wp-cli.phar";
        $cmd = "/usr/bin/php7.3 " . $cmd . " " . implode(" ", $param);
        exec($cmd);
    }

    public static function create_user($order_id, $user_id, $email, $name01, $name02){

        logs('gmo_epsilon')->info('受注ID: '.$order_id.' entityManager OK');

        // wp
        $param = [];
        $param[] = "db query ";
        $param[] = '"update wp_users set user_email =';
        $param[] = "'time".time()."-duplicate@kyogokupro.com'";
        $param[] = "where user_email =";
        $param[] = "'". $email . "'";
        $param[] = '"';
        $param[] = "--path=".__DIR__ . "/../../../academy";
        $upcmd = __DIR__ . "/../../../academy/wp-cli.phar";
        $upcmd = "/usr/bin/php7.3 " . $upcmd . " " . implode(" ", $param);
        exec($upcmd);

        $param = [];
        $param[] = "user create ";
        $param[] = "eu" . $user_id;
        $param[] = $email; 
        $param[] = "--role=um_kyogoku_advance";
        $param[] = "--user_registered=". '"' . date('Y-m-d H:i:s') . '"';
        $param[] = "--first_name=" . '"' . $name01 . '"';
        $param[] = "--last_name=". '"' .  $name02 . '"';
        $param[] = "--send-email=1";
        $param[] = "--path=".__DIR__ . "/../../../academy";
        
        $cmd = __DIR__ . "/../../../academy/wp-cli.phar";
        $cmd = "/usr/bin/php7.3 " . $cmd . " " . implode(" ", $param);
        exec($cmd, $output, $return_var);
        $output_log = print_r($output, true);
        logs('gmo_epsilon')->info('Wordpress user:' . $output_log);

        $param = [];
        $param[] = "db query ";
        $param[] = '"update user_date set advance_days = 1000 where ID = ';
        $param[] = "(select ID from wp_users where user_login ='" . "eu" . $user_id . "')"; 
        $param[] = '"';
        $param[] = "--path=".__DIR__ . "/../../../academy";
        $upcmd = __DIR__ . "/../../../academy/wp-cli.phar";
        $upcmd = "/usr/bin/php7.3 " . $upcmd . " " . implode(" ", $param);
        exec($upcmd);

        logs('gmo_epsilon')->info('受注ID: '. $order_id .'create new achademy user. eu' . $user_id );

        if(isset($output[2])){
            return ["id"=>"eu".$user_id, "password"=>$output[2]];
        }
    }

}