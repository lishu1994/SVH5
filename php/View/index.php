<?php
//error_reporting(E_ALL ^ E_NOTICE);
include_once("../config/init.php");
include_once("../jsapi/jssdk.php");
use Db\ReturnStr as ReturnStr;
use Dao\SqlDao as SqlDao;

$service = $_GET['service'];
$ret = new ReturnStr();
$dao = new SqlDao();

$userMod = array(
    "Id"=>0,
    "openid"=>"",
    "nickname"=>"",
    "headpic"=>"",
    "phonenum"=>"",
    "name"=>"",
    "accuracy"=>0,
    "rightCount"=>0,
    "wrongCount"=>0,
    "allCount"=>0,
    "time"=>0,
    "createTime"=>0,
    "nextTime"=>0,
    "timelineshare"=>0,
    "firshare"=>0,
);
$scoreMod = array(
    "Id"=>0,
    "user_id"=>0,
    "code_id"=>0,
    "rightCount"=>0,
    "wrongCount"=>0,
    "time"=>0,
    "realTime"=>0,
    "allCount"=>0,
    "createTime"=>0
);


switch ($service) {
    case 'phpinfo':
        echo phpinfo();
        break;
    case 'jsapi':
        $url = $_POST['uri'];
        $jssdk = new JSSDK(APP_ID, SECRET, $url);
        $signPackage = $jssdk->GetSignPackage();
        $ret->setData($signPackage);
        echo $ret->toString();
        break;
    case 'login':
        $code = $_POST['code'];
        $tokenUrl="https://api.weixin.qq.com/sns/oauth2/access_token";
        $tokenParams = array(
            "appid"=>APP_ID,
            "secret"=>SECRET,
            "code"=>$code,
            "grant_type"=>"authorization_code");
        $tokenHtml = http($tokenUrl,$tokenParams,'POST');
        $tokenRtn = json_decode($tokenHtml);
        $openid = $tokenRtn->{'openid'};//$_POST['openid'];

        if($tokenRtn->{'errcode'}){
            $ret->setCode(1001);
            $ret->setMsg("错误登陆。正在重新登陆");
            echo $ret->toString();
            break;
        }
        $userCount = $dao->count('user_info',"`openid`='{$openid}'");
        if($userCount==0){
            $userUrl="https://api.weixin.qq.com/sns/userinfo";
            $userParams = array("access_token"=>$tokenRtn->{"access_token"},"openid"=>$tokenRtn->{"openid"},"lang"=>"zh_CN");
            $userHtml = http($userUrl,$userParams,'POST');
            $userRtn = json_decode($userHtml);
            $nickname = base64_encode($userRtn->{'nickname'}); //$_POST['nickname'];
            $headpic = $userRtn->{'headimgurl'};//$_POST['headpic'];
            if($userRtn->{'errcode'}){
                $ret->setCode(1001);
                $ret->setMsg("错误登陆。正在重新登陆");
                echo $ret->toString();
                break;
            }
            $userMod['nickname']=$nickname;
            $userMod['headpic']=$headpic;
            $userMod['openid']=$openid;
            $userMod['createTime']=time();
            $id = $dao->insert('user_info',$userMod);
        }
        $user = $dao->select('user_info',"`openid`='{$openid}'");
        $user = $user[0];
        $_SESSION['user'] = $user;
        $retData = array(
            "nickname"=>base64_decode($user['nickname']),
            "headpic"=>$user['headpic'],
            "state"=>$user['nextTime']!=0?($user['nextTime']<time()?0:1):0,
            "countDown"=>$user['nextTime']!=0?($user['nextTime']<time()?0:$user['nextTime']-time()):0
        );
        $ret->setData($retData);
        echo $ret->toString();
        break;
    case 'getCode':
        $user = $_SESSION['user'];
        if(!$user){
            $ret->setCode(1);
            $ret->setMsg("非法操作！");
            echo $ret->toString();
            break;
        }
        if($user['nextTime']>time()){
            $ret->setCode(1);
            $ret->setMsg("时间还没到！");
            echo $ret->toString();
            break;
        }
        $json = file_get_contents("../config/code.json");
        $json = json_decode($json);
        $scoreList = $dao->select("score_log","`user_id`={$user['id']}");
        $codeList = array();
        if($scoreList){
            if(count($scoreList)>count($json)){
                $newScoreList = array();
                foreach ($scoreList as $key=>$value){
                    $count =0;
                    foreach ($scoreList as $ck=>$cv){
                        if($cv==$value){
                            $count++;
                        }
                    }
                    if(count($scoreList)/count($json)<$count){
                        $newScoreList[] = $value;
                    }
                }
                $scoreList = $newScoreList;
            }
            foreach($json as $key=>$value){
                $used = false;
                foreach ($scoreList as $sk=>$sv){
                    if($sv['code_id']==$value->{'id'}){
                        $used=true;
                        break;
                    }
                }
                if($user){
                    continue;
                }
                $codeList[] = $value;
            }
        }
        if(count($codeList)==0){
            $codeList = $json;
        }
        $code = $codeList[mt_rand(0,count($codeList)-1)];
        $_SESSION['codeUid'] = uniqid();
        $_SESSION['timeUid'] = uniqid();
        setRedis($_SESSION['codeUid'],json_encode($code));
        setRedis($_SESSION['timeUid'],time());
        $ret->setData($code->{'rows'});
        echo $ret->toString();
        break;
    case 'upload':
        $user = $_SESSION['user'];
        if(!$user){
            $ret->setCode(1);
            $ret->setMsg("非法操作！");
            echo $ret->toString();
            break;
        }
        $answer = $_POST['answer'];

        if($_SESSION['codeUid']==null){
            $ret->setCode(1);
            $ret->setMsg("非法操作！");
            echo $ret->toString();
            break;
        }
        $code = json_decode(getRedis($_SESSION['codeUid']));
        clearRedis($_SESSION['codeUid']);
        $_SESSION['codeUid']=null;
        $codeAnswer = $code->{'right'};

        if($_SESSION['timeUid']==null){
            $ret->setCode(1);
            $ret->setMsg("非法操作！");
            echo $ret->toString();
            break;
        }
        $beginTime = getRedis($_SESSION['timeUid']);
        clearRedis($_SESSION['timeUid']);
        $_SESSION['timeUid']=null;
        //答题耗时
        $time = time()-$beginTime;
        $realTime = $time;
        //正确数
        $rightCount = 0;
        //错误数
        $wrongCount= 0;


        //print_r($answer);
        //print_r($codeAnswer);

        $userAnswerRet = array_fill(0, count($codeAnswer), 0);
        if(count($answer)>5){
            $wrongCount = count($answer);
            $userAnswerRet =array_fill(0, count($codeAnswer), -1);
        }else{
            foreach ($answer as $key => $value) {
                if($codeAnswer[$value]==0){
                    $userAnswerRet[$value] = 1;
                    $rightCount++;
                }else{
                    $userAnswerRet[$value] = -1;
                    $wrongCount++;
                }
            }

            $answerCount = 0;
            foreach ($codeAnswer as $key => $value) {
                if($value==0){
                    $answerCount++;
                }
            }
        }

        // foreach ($codeAnswer as $key=>$value){
        //     $isRight = false;
        //     foreach ($answer as $uk=>$uv){
        //         if($uv==$value){
        //             $isRight=true;
        //             break;
        //         }
        //     }
        //     if($isRight){
        //         $rightCount++;
        //     }else{
        //         $wrongCount++;
        //     }
        // }

        $scoreMod['createTime'] = time();
        $scoreMod['time'] = $time;
        $scoreMod['user_id'] = $user['id'];
        $scoreMod['allCount'] = $answerCount;
        $scoreMod['rightCount'] = $rightCount;
        $scoreMod['wrongCount'] = $wrongCount;
        $scoreMod['realTime']=$realTime;
        $scoreMod['code_id'] = $code->{'id'};
        $dao->insert('score_log',$scoreMod);
        $usermb = $dao->getById("user_info",$user['id']);
        $usermb['rightCount']=intval($usermb['rightCount'])+$rightCount;
        $usermb['wrongCount']=intval($usermb['wrongCount'])+$wrongCount;
        $usermb['allCount']=intval($usermb['allCount'])+$answerCount;
        $usermb['time']= intval($usermb['time'])+$time;
        $usermb['accuracy'] = (intval($usermb['rightCount'])/(intval($usermb['wrongCount'])+intval($usermb['allCount'])))*10000;
        $usermb['nextTime']=time()+30;
        $dao->update('user_info',"`id`={$user['id']}",$usermb);
        $_SESSION['user'] = $user;
        $retData = array(
            "allCount"=>$answerCount,
            "rightCount"=>$rightCount,
            "wrongCount"=>$wrongCount,
            "time"=>$time,
            "answer"=>$userAnswerRet
        );
        $ret->setData($retData);
        echo $ret->toString();
        break;
    case 'ranking':
        $ranking = $dao->select('user_info',' 1=1 ',' limit 0,50','order by `rightCount` desc, `time`,`wrongCount`,`id` ');
        $user = $_SESSION['user'];
        if(!$user){
            $ret->setCode(1);
            $ret->setMsg("非法操作！");
            echo $ret->toString();
            break;
        }
        $retData = array();
        foreach ($ranking as $key=>$value){
            $retData[] = array(
                "nickname"=>base64_decode($value['nickname']),
                "headpic"=>$value['headpic'],
                "accuracy"=>$value['accuracy'],
                "rightCount"=>$value['rightCount'],
                "time"=>$value['time']
            );
        }
        $mine = $dao->getById("user_info",$user['id']);
        $mine = array(
            "nickname"=>base64_decode($mine['nickname']),
            "headpic"=>$mine['headpic'],
            "accuracy"=>$mine['accuracy'],
            "rightCount"=>$mine['rightCount'],
            "time"=>$mine['time']
        );
        $arrData = array("ranking"=>$retData,"mine"=>$mine);
        $ret->setData($arrData);
        echo $ret->toString();
        break;
    case 'share':
        $user = $_SESSION['user'];
        if(!$user){
            $ret->setCode(1);
            $ret->setMsg("非法操作！");
            echo $ret->toString();
            break;
        }
        $user = $dao->getById("user_info",$user['id']);
        $usermb = array();
        switch ($_GET['type']){
            case "1":
                $usermb['timelineshare'] = $user['timelineshare']+1;
                break;
            case "0":
                $usermb['firshare'] = $user['firshare']+1;
                break;
        }
        $dao->update('user_info',"`id`={$user['id']}",$usermb);
        break;
    default:
        # code...
        break;
}
