<?php
/**
 * Created by PhpStorm.
 * User: wepromo
 * Date: 2016/4/17
 * Time: 13:44
 */


defined('API_ROOT') || define('API_ROOT', dirname(__FILE__) . '/..' );
# defined('APP_ID') || define('APP_ID', "wx5c0a17d77fb1e5dc" );
defined('APP_ID') || define('APP_ID','wxda9fa1c738a65500');
# defined('SECRET') || define('SECRET', "2acb5e2894cafaf81f780cdd297b37ea" );
defined('SECRET') || define('SECRET', "89f850b7d31c61479df3d11d3a5c34b6");
defined('QINIUKEY') || define('QINIUKEY', "2ks9ft9ts9I3Yus4zNxAw3hV8OldscaQwWDGCHjk" );
defined('QINIUSECRET') || define('QINIUSECRET', "aTi8HBmh-hxgJVYfmPMmvh7v0U45ux_IkXZttiJV" );

session_set_cookie_params(3600, "/", null, false, TRUE);
session_start();
if (is_file(API_ROOT . "/config/config.php")) {
    C(require_once(API_ROOT . '/config/config.php'));
}
require_once  API_ROOT . '/Qiniu/functions.php';

if (!function_exists("__autoload")) {
    function __autoload($class_name) {
        if (is_file(API_ROOT . '/' . str_replace('\\','/',$class_name) . '.php')) {
            include_once(API_ROOT . '/' . str_replace('\\','/',$class_name) . '.php');
        }else{
            dump(API_ROOT . '/' . str_replace('\\','/',$class_name) . '.php');
        }
    }
}

function QiNiuUpFile($bsfile,$name){
    $auth = new \Qiniu\Auth(QINIUKEY, QINIUSECRET);
    $bucket = "jianshen";
    $token = $auth->uploadToken($bucket);
    $ret = http("http://upload.qiniu.com/",array("key"=>$name,"token"=>$token,"file"=>$bsfile),"POST",array(),true);
    return $ret;
}

function getRedis($key = ""){
    $redis = new Redis();
    $redis->connect("localhost",6379);
    $redis->auth("9@y&ab#115.)A!Ok");
    if($key===""){
        return false;
    }
    if(!$redis->exists($key)){
        return false;
    }
    $ret = $redis->get($key);
    $redis->close();
    return $ret;
}

function clearRedis($key=""){
    $redis = new Redis();
    $redis->connect("localhost",6379);
    $redis->auth("9@y&ab#115.)A!Ok");
    if($key===""){
        return false;
    }
    if(!$redis->exists($key)){
        return false;
    }
    $ret = $redis->delete($key);
    $redis->close();
    return $ret;
}

function setRedis($key="",$val = ""){
    $redis = new Redis();
    $redis->connect("localhost");
    $redis->auth("9@y&ab#115.)A!Ok");
    if($key===""){
        return false;
    }
    $ret = $redis->setex($key,1800,$val);
    $redis->close();
    return $ret;
}

/**
 * 发送HTTP请求方法，目前只支持CURL发送请求
 * @param  string $url    请求URL
 * @param  array  $params 请求参数
 * @param  string $method 请求方法GET/POST
 * @return array  $data   响应数据
 */
function http($url, $params, $method = 'GET', $header = array(), $multi = false) {
    $opts = array(CURLOPT_TIMEOUT => 30, CURLOPT_RETURNTRANSFER => 1, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_HTTPHEADER => $header);

    /* 根据请求类型设置特定参数 */
    switch(strtoupper($method)) {
        case 'GET' :
            $opts[CURLOPT_URL] = $url . '&' . http_build_query($params);
            //dump($opts[CURLOPT_URL]);
            break;
        case 'POST' :
            //判断是否传输文件
            $params = $multi ? $params : http_build_query($params);
            $opts[CURLOPT_URL] = $url;

            //dump($opts[CURLOPT_URL]);
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $params;
            break;
        default :
            throw new Exception('不支持的请求方式！');
    }

    /* 初始化并执行curl请求 */
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $data = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error)
        throw new Exception('请求发生错误：' . $error);
    return $data;
}
 
function toLogin($code){
    if(empty($code)){
        $url =  "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".APP_ID."&redirect_uri=".
            urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']) .
            "&response_type=code&scope=snsapi_base#wechat_redirect";
        header("Location: ".$url);
        exit;
    }
    $tokenUrl="https://api.weixin.qq.com/sns/oauth2/access_token";
    $tokenParams = array(
        "appid"=>APP_ID,
        "secret"=>SECRET,
        "code"=>$code,
        "grant_type"=>"authorization_code");
    $tokenHtml = http($tokenUrl,$tokenParams,'POST');
    $tokenRtn = json_decode($tokenHtml);
    $openId=$tokenRtn->{'openid'};
    $dao = new Dao\SqlDao();
    $user = $dao->select("user_info","`openid`='{$openId}'","");
    if(!$user){
        return null;
    }
    $user = $user[0];
    $ret = array("openid"=>$user['openid'],"nickname"=>$user['nickname'],"headpic"=>$user['headpic']);
    return $user;
}


function userState($user){
    $dao = new Dao\SqlDao();
    $state = $dao->select("user_state","`userid`={$user['id']} AND `type` < 3","");
    return $state;
}

function dayDifference($now=0,$last=0){
    $now = date('Y-m-d 01:01:01',$now);
    $last = date('Y-m-d 01:01:01',$last);
    $now = strtotime($now);
    $last = strtotime($last);
    $difference = round(abs($now - $last)/86400);
    return $difference+1;
}

function getCode($code){
    if(empty($code)){
        $url =  "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".APP_ID."&redirect_uri=".
            urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']) .
            "&response_type=code&scope=snsapi_userinfo#wechat_redirect";
        header("Location: ".$url);
        exit;
    }
}

function weixinCode($code){
    if(empty($code)){
        setcookie("weixinToCode_123",uniqid(time()));
        $url =  "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".APP_ID."&redirect_uri=".
            urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']) .
            "&response_type=code&scope=snsapi_userinfo#wechat_redirect";
        header("Location: ".$url);
        exit;
    }
}


function jsonToString($json){
    $ret="";
    if(gettype($json)=="array"){
        $ret = "[";
        for($i=0;$i<count($json);$i++){
            $ret .= "{";
            foreach ($json[$i] as $key=>$val){
                $ret.="\\\"".$key."\\\":\\\"".$val."\\\",";
            }
            $ret = substr($ret,0,strlen($ret)-1);
            $ret .="}";
        }
        $ret .= "]";
    }else{
        $ret = "{";
        foreach ($json as $key=>$val){
            $ret.="\\\"".$key."\\\":\\\"".$val."\\\",";
        }
        $ret = substr($ret,0,strlen($ret)-1);
        $ret .="}";
    }
    return $ret;
}
/**
 * 数据库操作函数
 * @return \mysqli
 */
function M() {
    $db = new Model();
    if (mysqli_connect_errno())
        throw_exception(mysqli_connect_error());
    return $db;
}

// 获取配置值
function C($name = null, $value = null) {
    //静态全局变量，后面的使用取值都是在 $)config数组取
    static $_config = array();
    // 无参数时获取所有
    if (empty($name))
        return $_config;
    // 优先执行设置获取或赋值
    if (is_string($name)) {
        if (!strpos($name, '.')) {
            $name = strtolower($name);
            if (is_null($value))
                return isset($_config[$name]) ? $_config[$name] : null;
            $_config[$name] = $value;
            return;
        }
        // 二维数组设置和获取支持
        $name = explode('.', $name);
        $name[0] = strtolower($name[0]);
        if (is_null($value))
            return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : null;
        $_config[$name[0]][$name[1]] = $value;
        return;
    }
    // 批量设置
    if (is_array($name)) {
        return $_config = array_merge($_config, array_change_key_case($name));
    }
    return null; // 避免非法参数
}

function ajaxReturn($data = null, $message = "", $status) {
    $ret = array();
    $ret["data"] = $data;
    $ret["message"] = $message;
    $ret["status"] = $status;
    echo json_encode($ret);
    die();
}


//调试数组
function _dump($var) {
    if (C("debug"))
        dump($var);
}

// 浏览器友好的变量输出
function dump($var, $echo = true, $label = null, $strict = true) {
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    if (!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        } else {
            $output = $label . print_r($var, true);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace("/\]\=\>\n(\s+)/m", '] => ', $output);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }
    }
    if ($echo) {
        echo($output);
        return null;
    }
    else
        return $output;
}

/**
 * 调试输出
 * @param type $msg
 */
function _debug($msg) {
    if (C("debug"))
        echo "$msg<br />";
}

function _log($filename, $msg) {
    $time = date("Y-m-d H:i:s");
    $msg = "[$time]\n$msg\r\n";
    if (C("log")) {
        $fd = fopen($filename, "a+");
        fwrite($fd, $msg);
        fclose($fd);
    }
}

/**
 * 日志记录
 * @param type $str
 */
function L($msg) {
    $time = date("Y-m-d H:i:s");
    $clientIP = $_SERVER['REMOTE_ADDR'];
    $msg = "[$time $clientIP] $msg\r\n";
    $log_file = C("LOGFILE");
    _log($log_file, $msg);
}
