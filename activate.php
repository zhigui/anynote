<?php
session_start();
require './api/Slim/Slim.php';

function get_rand_key( $length = 8 ) 
{
    $str = substr(md5(time()), 0, $length);
    return $str;
}

if(!empty($_GET['email']) && !empty($_GET['key'])) {
    if(checkUser($_GET['email'],$_GET['key'])){
        $newkey=get_rand_key(20);
        $email=$_GET['email'];
        $key=$_GET['key'];
        $sql="UPDATE  users SET user_activation_key = :newkey, user_actived  = '1' WHERE user_email=:email AND user_activation_key=:key AND user_actived='0' ";
        $db = getConnection();
        $check= $db->prepare($sql);
        $check->bindParam("email", $email);
        $check->bindParam("key", $key);
        $check->bindParam("newkey", $newkey);
        $result=$check->execute();

        $db = null;
        if($result){
            echo '<center><h3>恭喜，激活成功！<a href="#">点击登录</a></h3></center>';
        }else{
            echo '<center><h3>激活失败，请联系管理员！</h3></center>';
        }
    }else {
        echo '<center><h3>验证身份失败，非法请求！</h3></center>';
    }

}else{
        echo '<center><h3>非法请求！</h3></center>';
}


function checkUser($email,$key){
    $presql="SELECT user_email FROM users  WHERE user_email=:email AND user_activation_key=:key AND user_actived=0 ";
    $db = getConnection();
    $check= $db->query("set names utf8");
    $check= $db->prepare($presql);
    $check->bindParam("email", $email);
    $check->bindParam("key", $key);
    $check->execute();
    $result=$check->fetchAll(PDO::FETCH_OBJ);
    //print_r($result);
    $db = null;
    if($result) {
        return true;
    } else {
        return false;
    }
    
 
}

function getConnection() {
  $dbhost="localhost";
  $dbuser="root";
  $dbpass="";
  $dbname="anynote";
  $dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);  
  $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  return $dbh;
}

?>