<?php
/**
 * Created by PhpStorm.
 * User: xyliv
 * Date: 2016/7/14
 * Time: 11:42
 */
include_once("../config/init.php");
use Dao\SqlDao as SqlDao;
$page = $_GET['page']?$_GET['page']:1;
$length = 100;
$time = $_GET['time']?$_GET['time']:date('Y-m-d',time());
$today = strtotime($time);
$tomorrow = strtotime("+1 day",$today);
$str = "SELECT ".
       "  sl.`user_id`, ".
       "  Sum(sl.`rightCount`) `rightCount`, ".
       "  Sum(sl.`wrongCount`) `wrongCount`, ".
       "  Sum(sl.`allCount`) `allCount`, ".
       "  Sum(sl.`time`) `time`, ".
       "  CONVERT((Sum(sl.`rightCount`)/(Sum(sl.`wrongCount`)+Sum(sl.`allCount`))*10000),SIGNED ) as `accuracy`, ".
       "  ui.`nickname`, ".
       "  ui.`headpic`, ".
       "  ui.`firshare`, ".
       "  ui.`timelineshare` ".
       "FROM  ".
       "  `score_log`  sl  ".
       "LEFT JOIN ".
       "  `user_info` ui  ".
       "ON ".
       "  sl.`user_id` = ui.`id` ".
       "WHERE  sl.`createTime`<{$tomorrow} and ui.`createTime`<{$tomorrow}  ".
       "GROUP BY ".
       "  `user_id` ".
       "ORDER BY `rightCount` desc,`time`,`user_id` ".
       "LIMIT ".(intval($page)-1)*$length.",".$length;
$sql = new SqlDao();
$ret = $sql->getQuery($str);
echo "<input type='date' value='{$time}' id='time'/>";
echo "<br/>";
echo "<input type='button' value='提交' id='sub' />";
echo "<br/>";
echo "<table style='text-align: center' cellpadding='1' cellspacing='1' bgcolor='black'>";
echo "<thead>";
echo "<tr><th width='50'>排名</th><th width='100'>用户id</th><th width='100'>总正确数</th><th width='100'>总错误数</th><th width='100'>题目数</th><th width='100'>用时（s）</th><th width='100'>正确率</th><th width='300'>用户昵称</th><th width='100'>头像</th><th width='100'>会话分享次数</th><th width='100'>朋友圈分享次数</th></tr>";
echo "</thead>";
echo "<tbody>";
foreach ($ret as $key => $value){
    $nickname = base64_decode($value['nickname']);
    $accuracy = ($value['accuracy']/100)."%";
    $ranking = ($key+($page-1)*$length)+1;
    echo "<tr><td>{$ranking}</td><td>{$value['user_id']}</td><td>{$value['rightCount']}</td><td>{$value['wrongCount']}</td><td>{$value['allCount']}</td><td>{$value['time']} s</td><td>{$accuracy}</td><td>{$nickname}</td><td><img width='50px' src='{$value['headpic']}' /></td><td>{$value['firshare']}</td><td>{$value['timelineshare']}</td></tr>";
}
echo "</tbody>";
echo "</table>";
$count = $sql->count('user_info'," `allCount`<>0 and `createTime`<{$tomorrow}");
echo "<br/>";
echo "共".$count."个用户";
echo "<br/>";
if($count>$length){
    for ($i=0;$i<$count/$length;$i++){
        $pageNum =$i+1;
        if($page==$pageNum){
            echo "<span>{$pageNum}</span> ";
        }else{
            echo "<a href='#' onclick='return page({$pageNum})'>{$pageNum}</a> ";
        }

    }
}else{
    if ($page!=1){
        header("Location: admin.php?page=1&time=".$time);
    }
    echo "<span>1</span>";
}
?>
<style>
    td,th{
        background-color: #FFF;
    }
</style>
<script>
    window.onload=(function(){
        document.getElementById('sub').onclick = function () {
            location.href = location.pathname+"?page=1&time="+document.getElementById("time").value;
        };
    });

    function page(index){
        location.href = location.pathname+"?page="+index+"&time="+document.getElementById("time").value;
    }
</script>
