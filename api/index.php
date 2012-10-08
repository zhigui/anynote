<?php
session_start();
require 'Slim/Slim.php';
require_once ( "./Bcms.class.php" ) ;
date_default_timezone_set('Asia/Shanghai');
$app = new Slim();
$accessKey="";
$secretKey="";
$host = 'bcms.api.duapp.com';

//用户注册
$app->post('/register/', 'userRegister');
$app->post('/login/', 'login');
$app->get('/logout/', 'logout');
//$app->get('/check/:email', 'checkUser');

$app->post('/newnote/', authorize('0'), 'createNote');
$app->put('/editnote/', authorize('0'), 'editNote');
$app->delete('/delnote/:note_id', authorize('0'), 'deleteNote');

 
$app->get('/notes/:page', authorize('0'), 'getNoteList');

//页数，然后是用逗号分隔的tag
$app->get('/tag/:tags/:page', authorize('0'), 'getNoteByTags');

$app->get('/showtag/', authorize('0'), 'getTags');

$app->get('/test/', authorize('0'), 'justtest');
 

 

$app->run();

function deleteNote($note_id){
    $user_id=$_SESSION['user']->user_id;
    $sql = "DELETE notes.*, tagmap.* FROM notes, tagmap WHERE notes.note_id={$note_id} AND notes.user_id={$user_id} AND notes.note_id=tagmap.note_id AND notes.user_id=tagmap.user_id";
    //$del_note_sql="DELETE  FROM notes WHERE note_id={$note_id} AND user_id={$user_id} ";
    //$del_tag_sql="DELETE  FROM tagmap WHERE note_id={$note_id} AND user_id={$user_id} ";
    try {
        $db = getConnection();
        $db->query($sql);
        //print_r(mysql_affected_rows());
        //$db->query($del_tag_sql);
        $info['success']=true;
        echo json_encode($info); 
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }

}
//UPDATE  `anynote`.`notes` SET  `user_id` =  '2', `note_content` =  'dgdf' WHERE  `notes`.`note_id` =14;
function editNote(){
    $user_id=$_SESSION['user']->user_id;
    $request = Slim::getInstance()->request();
    $note = json_decode($request->getBody());
    $sql = "UPDATE notes SET  note_title=:title , note_content=:content , note_tag=:tag , note_lastmod=:timenow  WHERE note_id=:note_id AND user_id=:user_id ";
    $tags=preg_replace( "/,{2,}|,*$/", "", $note->tag);
    $nowtime=time();
    $note_id= $note->id;
    try {

        $db = getConnection();
        $stmt = $db->query("set names utf8");
        //在进行数据库修改之前，先获得原来的标签
        $tag_result= $db->query("SELECT note_tag FROM  notes WHERE note_id={$note_id} AND user_id={$user_id} ");
        $tagobj=$tag_result->fetchAll(PDO::FETCH_OBJ);
        $oldtag=explode(',',$tagobj[0]->note_tag);


        $stmt = $db->prepare($sql);  
        $stmt->bindParam("user_id", $user_id);
        $stmt->bindParam("note_id", $note->id);
        $stmt->bindParam("title", $note->title);
        $stmt->bindParam("content", $note->content);
        $stmt->bindParam("timenow", $nowtime);
        $stmt->bindParam("tag", $tags);
        $stmt->execute();
        

        $tagarr=explode(',', $tags);

        
        //出现在数组一中，数组2中没有的就是被删除的。
        $deltag=array_diff($oldtag, $tagarr);
        #print_r($deltag);
        if($deltag){
        $delsql="DELETE tagmap.* FROM tagmap, tags ";
        $delsql.="WHERE (tags.name='".join("' or tags.name='", $deltag)."' )  ";
        $delsql.="AND tagmap.user_id={$user_id} AND tagmap.note_id={$note_id} AND tagmap.tag_id=tags.tag_id";       
        $db->query($delsql);
        }

        //出现在数组2中，数组1中没有的就是增加的。
        $addtag=array_diff($tagarr, $oldtag);
        #print_r($addtag);
        foreach ($addtag as $tag) {
                    $result = $db->query("SELECT tag_id FROM  tags WHERE name='{$tag}' ");
            $id=$result->fetchAll(PDO::FETCH_OBJ);

            if($id){
                $result = $db->query("SELECT id FROM  tagmap WHERE note_id={$note_id} AND  tag_id={$id[0]->tag_id} ");
                $rid=$result->fetchAll(PDO::FETCH_OBJ);
                //print_r($id);
                if(!$rid){
                $sql="INSERT INTO tagmap (note_id, tag_id, user_id) VALUES ({$note_id}, {$id[0]->tag_id}, {$user_id} )";
                //echo $sql;
                $db->query($sql);
                }
            }else{
                $db->query("INSERT INTO tags (name) VALUES( '{$tag}' )");
                $id= $db->lastInsertId();
                $db->query("INSERT INTO tagmap (note_id, tag_id, user_id) VALUES({$note_id}, {$id}, {$user_id} )");
            }
        }
         
        $db=null;
        $info['success']=true;
        echo json_encode($note); 
    
    } catch(PDOException $e) {
        //error_log($e->getMessage(), 3, '/var/tmp/php.log');
        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }

}


function createNote(){
    $user_id=$_SESSION['user']->user_id;
    $request = Slim::getInstance()->request();
    $note = json_decode($request->getBody());
    $sql = "INSERT INTO notes (user_id, note_title, note_content, note_tag, note_lastmod) VALUES (:user_id, :title, :content,  :tag, ".time()." )";
    $tags=preg_replace( "/,{2,}|,*$/", "", $note->tag);
    
    try {

        $db = getConnection();
        $stmt = $db->query("set names utf8");
        $stmt = $db->prepare($sql);  
        $stmt->bindParam("user_id", $user_id);
        $stmt->bindParam("title", $note->title);
        $stmt->bindParam("content", $note->content);
        $stmt->bindParam("tag", $tags);
        $stmt->execute();
        $note_id= $db->lastInsertId();
        $note->id=$note_id;
        $temtime=time();
        $note->lastmod=$temtime;
        $note->date=date("Y-m-d G:i:s", $temtime);

        $tagarr=explode(',', $tags);
        //echo $note_id;
        
        foreach ($tagarr as $tag) {
            $result = $db->query("SELECT tag_id FROM  tags WHERE name='{$tag}' ");
            $id=$result->fetchAll(PDO::FETCH_OBJ);

            if($id){
                $result = $db->query("SELECT id FROM  tagmap WHERE note_id={$note_id} AND  tag_id={$id[0]->tag_id} ");
                $rid=$result->fetchAll(PDO::FETCH_OBJ);
                //print_r($id);
                if(!$rid){
                $sql="INSERT INTO tagmap (note_id, tag_id, user_id) VALUES ({$note_id}, {$id[0]->tag_id}, {$user_id} )";
                //echo $sql;
                $db->query($sql);
                }
            }else{
                $db->query("INSERT INTO tags (name) VALUES( '{$tag}' )");
                $id= $db->lastInsertId();
                $db->query("INSERT INTO tagmap (note_id, tag_id, user_id) VALUES({$note_id}, {$id}, {$user_id} )");
            }
        }
         
        $db=null;
        $info['success']=true;
        echo json_encode($note); 
    
    } catch(PDOException $e) {
        error_log($e->getMessage(), 3, '/var/tmp/php.log');
        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }

}

function getTags(){
    $user_id=$_SESSION['user']->user_id;
    $page=1;
    $count=30;
    $start=((int)$page-1)*$count;
    $sql='SELECT tags.tag_id id, tags.name name ,  COUNT( tags.tag_id ) count ';
    $sql.='FROM tagmap, notes, tags ';
    $sql.='WHERE tagmap.tag_id = tags.tag_id ';
    $sql.='AND notes.note_id = tagmap.note_id ';
    $sql.='AND tagmap.user_id=:uid ';
    $sql.='GROUP BY tags.tag_id ';
    $sql.=" LIMIT $start , $count ";
    //echo $sql;
    try {
        $db = getConnection();
        $stmt = $db->query("set names utf8");
        $stmt = $db->prepare($sql);  
        $stmt->bindParam("uid", $user_id); 
        $stmt->execute();
        $notes = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($notes); 
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }

}
function getNoteByTags($tags,$page=1 ){
    $user_id=$_SESSION['user']->user_id;
    $count=20;
    $start=((int)$page-1)*$count;

    $tagarr=explode(',', $tags);
    $sql='SELECT notes.note_id id, notes.note_date date, notes.note_title title, notes.note_content content, notes.note_tag tag, notes.note_lastmod lastmod ';
    $sql.='FROM tagmap, notes, tags ';
    $sql.='WHERE tagmap.tag_id = tags.tag_id ';
    $sql.='AND ( ';
    $sql.='tags.name ';
    $sql.='IN ( ';
    $sql.=" '".join("','", $tagarr)."' ";
    $sql.=') ';
    $sql.=') AND notes.note_id = tagmap.note_id ';
    $sql.='AND notes.user_id=:uid ';
    $sql.='GROUP BY notes.note_id ';
    $sql.='HAVING COUNT( notes.note_id ) ='.count($tagarr);
    $sql.=" LIMIT $start , $count ";
    //echo $sql;
    try {
        $db = getConnection();
        $stmt = $db->query("set names utf8");
        $stmt = $db->prepare($sql);  
        $stmt->bindParam("uid", $user_id); 
        $stmt->execute();
        $notes = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($notes); 
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }

}
function getNoteList($page){
    $user_id=$_SESSION['user']->user_id;
    $count=5;
    $start=((int)$page-1)*$count;
    $sql = "SELECT note_id id,  note_date date, note_title title, note_content content, note_tag tag, note_lastmod lastmod FROM notes  WHERE user_id=:uid ORDER BY note_date DESC  LIMIT $start , $count ";
    //$sql = "SELECT * FROM books WHERE id=:id";
    try {
        $db = getConnection();
        $stmt = $db->query("set names utf8");
        $stmt = $db->prepare($sql);  
        $stmt->bindParam("uid", $user_id);
         
        $stmt->execute();
        $notes = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($notes); 
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
}
function justtest(){
    $db = getConnection();
    $tag_result= $db->query("SELECT note_tag FROM  notes WHERE note_id=23 AND user_id=1 ");
    $tagobj=$tag_result->fetchAll(PDO::FETCH_OBJ);
    echo $tagobj[0]->note_tag;
}

function get_rand_key( $length = 8 ) 
{
    $str = substr(md5(time()), 0, $length);
    return $str;
}

function send_mail ( $queueName, $message, $address )
{
    global $accessKey, $secretKey, $host;
    $bcms = new Bcms ( $accessKey, $secretKey, $host ) ;
    $ret = $bcms->mail ( $queueName, $message, $address ) ;
    /*if ( false === $ret ) 
    {
        error_output ( 'WRONG, ' . __FUNCTION__ . ' ERROR!!!!!' ) ;
        error_output ( 'ERROR NUMBER: ' . $bcms->errno ( ) ) ;
        error_output ( 'ERROR MESSAGE: ' . $bcms->errmsg ( ) ) ;
        error_output ( 'REQUEST ID: ' . $bcms->getRequestId ( ) );
    }
    else
    {
        right_output ( 'SUCC, ' . __FUNCTION__ . ' OK!!!!!' ) ;
        right_output ( 'result: ' . print_r ( $ret, true ) ) ;
    }   */
    return $ret;
}

function checkUser($email){
    $presql="SELECT user_email FROM users WHERE user_email=:email";
    $db = getConnection();
    $check= $db->query("set names utf8");
    $check= $db->prepare($presql);
    $check->bindParam("email", $email);
    $check->execute();
    $result=$check->fetchAll(PDO::FETCH_OBJ);
    $db = null;
    if($result) {
        return true;
    } else {
        return false;
    }
    
 
}
function userRegister(){
    $request = Slim::getInstance()->request();
    $user = json_decode($request->getBody());
    $sql = "INSERT INTO users (user_email, user_password, user_nickname, user_activation_key) VALUES (:email, sha1(:password), :nickname,  :key)";
    try {
        #if(checkUser($user->email)){
        if (!checkUser($user->email)){
            $key=get_rand_key(20);
            $db = getConnection();
            $stmt = $db->query("set names utf8");
            $stmt = $db->prepare($sql);  
            $stmt->bindParam("email", $user->email);
            $stmt->bindParam("password", $user->password);
            $stmt->bindParam("nickname", $user->nickname);
            $stmt->bindParam("key", $key);
            $stmt->execute();
            $isok= $db->lastInsertId();
            $db = null;
            $result['success']=true;
            if($isok){
                $queueName="ff77d57dd256cd8cb65fd74e825d919b";
                $message=$user->nickname.", 您好，感谢您注册Anynote，请点击以下链接，激活您的账号： http://anynote.duapp.com/activate.php?email=".$user->email."&key=".$key;
                $address[0] =$user->email;
                $isok=send_mail($queueName, $message, $address);
                if($isok){
                   $result['text']="恭喜注册成功，请查收激活邮件，激活后才可以登陆！"; 
               }else{
                   $result['text']="激活邮件发送失败，请联系管理员激活 zhigui@126.com";
               }   
                
            }else{
                $result['success']=false;
                $result['text']="注册失败，请稍候再试！";    
            }
            echo json_encode($result); 
        }else{
            $result['success']=false;
            $result['text']="该Email已经被占用！";
            echo json_encode($result); 
        } 
    } catch(PDOException $e) {
        error_log($e->getMessage(), 3, '/var/tmp/php.log');
        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }

}
// api/index.php
/**
 * Quick and dirty login function with hard coded credentials (admin/admin)
 * This is just an example. Do not use this in a production environment
 */
function login() {
    $request = Slim::getInstance()->request();
    $user = json_decode($request->getBody());
    $sql="SELECT user_id, user_nickname, user_email,user_level FROM users WHERE user_email=:email AND user_password=sha1(:password)";
    $db = getConnection();
    $check= $db->query("set names utf8");
    $check= $db->prepare($sql);
    $check->bindParam("email", $_POST['email']);
    $check->bindParam("password", $_POST['password']);
    $check->execute();
    $result=$check->fetchAll(PDO::FETCH_OBJ);
    $db = null;
    $info['success']=true;
    if($result) {
        $_SESSION['user'] = $result[0];
        //print_r($_SESSION['user']);
        $info['text']="登录成功！";
 
    } else {
        $info['success']=false;
        $info['text']="用户名与密码的组合错误！";
        
    }
    echo json_encode($info); 
}

//用户安全退出，返回值{success: true,text: "成功安全退出"}
function logout(){
    unset($_SESSION);
    session_destroy();
    $info['success']=true;
    $info['text']="成功安全退出";
    echo json_encode($info);
}

/**
 * Authorise function, used as Slim Route Middlewear (http://www.slimframework.com/documentation/stable#routing-middleware)
 */

function authorize($role = "0") {
    return function () use ( $role ) {
        // Get the Slim framework object
        $app = Slim::getInstance();
        // First, check to see if the user is logged in at all
        if(!empty($_SESSION['user'])) {
            // Next, validate the role to make sure they can access the route
            // We will assume admin role can access everything
            if($_SESSION['user']->user_level == $role || 
                $_SESSION['user']->user_level == '10') {
                //User is logged in and has the correct permissions... Nice!
                return true;
            }
            else {
                // If a user is logged in, but doesn't have permissions, return 403
                $info['success']=false;
                $info['text']="权限不足！";
                $app->halt(403, json_encode($info));
            }
        }
        else {
            // If a user is not logged in at all, return a 401
            $info['success']=false;
            $info['text']="请登录！";
            $app->halt(401, json_encode($info));
        }
    };
}

function getEmployees() {

    if (isset($_GET['name'])) {
        return getEmployeesByName($_GET['name']);
    } else if (isset($_GET['modifiedSince'])) {
        return getModifiedEmployees($_GET['modifiedSince']);
    }

    $sql = "select e.id, e.firstName, e.lastName, e.title, count(r.id) reportCount " .
    		"from employee e left join employee r on r.managerId = e.id " .
    		"group by e.id order by e.lastName, e.firstName";
	try {
		$db = getConnection();
		$stmt = $db->query($sql);
		$employees = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;

        // Include support for JSONP requests
        if (!isset($_GET['callback'])) {
            echo json_encode($employees);
        } else {
            echo $_GET['callback'] . '(' . json_encode($employees) . ');';
        }

	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
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