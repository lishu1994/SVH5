<?php
include_once("../config/init.php");
require_once "jssdk.php";
$uri = $_POST['uri'];
wechatLog($uri);
$jssdk = new JSSDK(APP_ID, SECRET, $uri);
$signPackage = $jssdk->GetSignPackage();
echo json_encode($signPackage);

function wechatLog($log){
    $file = '/var/log/nginx/wechat.log';
    if(is_array($log))
        $log = print_r($log, true);
    file_put_contents($file, $log.PHP_EOL, FILE_APPEND);
}

?>
