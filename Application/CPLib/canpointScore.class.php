<?php

class canpointScore {

//    $lev=array(
//        '1'=>array(
//           'name'=>'普通用户' 
//        ),
//        
//    );
//    用户id uid
//    用户名字 uname
//    用户行为  action
//    行为说明  note
//    学分/豆值   val
//    类型  flag
//    附属id   fid


    function addscore($uid, $uname, $action, $note, $val, $flag, $fid = 0) {
        if (!empty($uid)) {
            $log = M("u_coinlog");
            $table = "user_" . substr($uname, -2);
            $stu = M("canpoint_user.$table");
            //$stu = M("u_study");
            $data = array(
                'uid' => $uid,
                'uname' => $uname,
                'action' => $action,
                'note' => $note,
                'val' => $val,
                'flag' => $flag,
                'date' => date("Y-m-d H:i:s")
            );
            $re = $log->add($data);
            if ($re) {
                $info = $stu->where("u_guid='" . $uname . "'")->field("u_xuefen,u_qdou,u_type")->find();
                $xuefen = $info['u_xuefen'];
                $qdou = $info['u_qdou'];
                if ($flag == 1) {  //学分
                    $d = array("u_xuefen" => $xuefen + $val);
                    $stu->where("u_guid='" . $uname . "'")->save($d);
                    canpointCommon::logDegub("xuefensql=".$stu->getLastSql());
                } elseif ($flag == 2) {
                    $d = array("u_qdou" => $qdou + $val);
                    $stu->where("u_guid='" . $uname . "'")->save($d);
                    canpointCommon::logDegub("dousql=".$stu->getLastSql());

                    //插入变更记录 qp_p_dou 
                    $doulog = M("p_qdou");
                    $data1 = array(
                        "type" => $note,
                        "u_leixing" => $info['u_type'],
                        'u_id' => $uid,
                        'u_name' => $uname,
                        "p_point" => $val,
                        "p_oldpoint" => $info['u_qdou'],
                        "p_newpoint" => (int) $info['u_qdou'] + $val,
                        "p_cr" => "系统",
                        "u_rdate" => date("Y-m-d H:i:s", time()),
                        "u_beizhu" => $note
                    );
                    $res = $doulog->add($data1);
                    return $res;
                }
            }
        }
    }

    function decscore($uid, $uname, $action, $note, $val, $flag, $fid = 0) {
        $log = M("u_coinlog");
        $table = "user_" . substr($uname, -2);
        $stu = M("canpoint_user.$table");
        // $stu = M("u_study");
        $data = array(
            'uid' => $uid,
            'uname' => $uname,
            'action' => $action,
            'note' => $note,
            'val' => $val,
            'flag' => $flag,
            'date' => date("Y-m-d H:i:s")
        );
        $re = $log->add($data);
        if ($re) {
            $info = $stu->where("u_guid='" . $uname . "'")->field("u_xuefen,u_qdou,u_type")->find();
            $xuefen = $info['u_xuefen'];
            $qdou = $info['u_qdou'];
            if ($flag == 1) {  //学分
                $d = array("u_xuefen" => $xuefen + $val);
                $stu->where("u_guid='" . $uname . "'")->save($d);
            } elseif ($flag == 2) {
                $d = array("u_qdou" => $qdou + $val);
                $stu->where("u_guid='" . $uname . "'")->save($d);

                //插入变更记录 qp_p_dou 
                $doulog = M("p_qdou");
                $data1 = array(
                    "type" => $note,
                    "u_leixing" => $info['u_type'],
                    'u_id' => $uid,
                    'u_name' => $uname,
                    "p_point" => $val,
                    "p_oldpoint" => $info['u_qdou'],
                    "p_newpoint" => (int) $info['u_qdou'] + $val,
                    "p_cr" => "系统",
                    "u_rdate" => date("Y-m-d H:i:s", time()),
                    "u_beizhu" => $note
                );
                $res = $doulog->add($data1);
                return $res;
            }
        }
    }

//   用户等级 $level
//   用户id $uid
//   行为 $action
//   描述 $note
//   加/减  TRUE=默认加  false=减  $f_type
//   加分类型，现有两种 0=加积分，1=加豆，12=积分豆全加 $sorce_addtype
    //附属id
    //附属值
    /**
     * 
     * @param unknown $level 等级
     * @param unknown $uid 用户ID
     * @param unknown $uname 用户名
     * @param unknown $action 行为
     * @param unknown $note 描述
     * @param string $f_type 加/减  TRUE=默认加  false=减  $f_type
     * @param number $fid 
     * @param number $v
     */
    function addscore_defaulf($level, $uid, $uname, $action, $note, $f_type = 'TRUE', $fid = 0, $v = 0) {

        //行为对应的学分值
        $actarr = array(
            'regist' => 50,
            'login' => 5, //每天加一次
            'comment' => 3,
            'replay' => 3,
            'buy' => 5,
            'ask' => 3,
            'share' => 0,
            'invite' => 5,
            'adopt'=>5,
            'isadopt'=>10,
        );

        //不同等级全品豆奖励情况
        $darr = array(
            '0' => array(
                'name' => "普通会员",
                'level' => 0, //会员等级
                'login' => 20, //登录奖励豆
                'comment' => 3, //评论奖励
                'replay' => 3, //回复奖励
                'ask' => 0, //提问奖励
                'share' =>3, //分享奖励
                'invite' => 10, //邀请奖励
                'siding1' => 10, //1-50区间订单金额奖励
                'siding2' => 15, //51-100区间订单金额奖励
                'siding3' => 20, //101-150区间订单金额奖励
                'siding4' => 25, //151-200区间订单金额奖励
                'siding5' => 30   //200以上订单金额奖励
            ),
            '90' => array(
                'name' => "从九品",
                'level' => 90,
                'login' => 20,
                'comment' => 3, //评论奖励
                'replay' => 3,
                'ask' => 0,
                'share' => 3,
                'invite' => 10,
                'siding1' => 10,
                'siding2' => 15,
                'siding3' => 20,
                'siding4' => 25,
                'siding5' => 30
            ),
            '9' => array(
                'name' => "九品",
                'level' => 9,
                'login' => 20,
                'comment' => 3, //评论奖励
                'replay' => 3,
                'ask' => 0,
                'share' => 3,
                'invite' => 10,
                'siding1' => 10,
                'siding2' => 15,
                'siding3' => 20,
                'siding4' => 25,
                'siding5' => 30
            ),
            '80' => array(
                'name' => "从八品",
                'level' => 80,
                'login' => 20,
                'comment' => 3, //评论奖励
                'replay' => 3,
                'ask' => 0,
                'share' => 3,
                'invite' => 10,
                'siding1' => 10,
                'siding2' => 15,
                'siding3' => 20,
                'siding4' => 25,
                'siding5' => 30
            ),
            '8' => array(
                'name' => "八品",
                'level' => 8,
                'login' => 20,
                'comment' => 3, //评论奖励
                'replay' => 3,
                'ask' => 0,
                'share' => 3,
                'invite' => 10,
                'siding1' => 10,
                'siding2' => 15,
                'siding3' => 20,
                'siding4' => 25,
                'siding5' => 30
            ),
            '70' => array(
                'name' => "从七品",
                'level' => 70,
                'login' => 30,
                'comment' => 4, //评论奖励
                'replay' => 4,
                'ask' => 0,
                'share' => 4,
                'invite' => 15,
                'siding1' => 15,
                'siding2' => 20,
                'siding3' => 25,
                'siding4' => 30,
                'siding5' => 35
            ),
            '7' => array(
                'name' => "七品",
                'level' => 7,
                'login' => 30,
                'comment' => 4,
                'replay' => 4,
                'ask' => 0,
                'share' => 4,
                'invite' => 15,
                'siding1' => 15,
                'siding2' => 20,
                'siding3' => 25,
                'siding4' => 30,
                'siding5' => 35
            ),
            '60' => array(
                'name' => "从六品",
                'level' => 60,
                'login' => 30,
                'comment' => 4,
                'replay' => 4,
                'ask' => 0,
                'share' => 4,
                'invite' => 15,
                'siding1' => 15,
                'siding2' => 20,
                'siding3' => 25,
                'siding4' => 30,
                'siding5' => 35
            ),
            '6' => array(
                'name' => "六品",
                'level' => 6,
                'login' => 30,
                'comment' => 4,
                'replay' => 4,
                'ask' => 0,
                'share' => 4,
                'invite' => 15,
                'siding1' => 15,
                'siding2' => 20,
                'siding3' => 25,
                'siding4' => 30,
                'siding5' => 35
            ),
            '50' => array(
                'name' => "从五品",
                'level' => 50,
                'login' => 40,
                'comment' => 5,
                'replay' => 5,
                'ask' => 0,
                'share' => 5,
                'invite' => 20,
                'siding1' => 20,
                'siding2' => 25,
                'siding3' => 30,
                'siding4' => 35,
                'siding5' => 40
            ),
            '5' => array(
                'name' => "五品",
                'level' => 5,
                'login' => 40,
                'comment' => 5,
                'replay' => 5,
                'ask' => 0,
                'share' => 5,
                'invite' => 20,
                'siding1' => 20,
                'siding2' => 25,
                'siding3' => 30,
                'siding4' => 35,
                'siding5' => 40
            ),
            '40' => array(
                'name' => "从四品",
                'level' => 40,
                'login' => 40,
                'comment' => 5,
                'replay' => 5,
                'ask' => 0,
                'share' => 5,
                'invite' => 20,
                'siding1' => 20,
                'siding2' => 25,
                'siding3' => 30,
                'siding4' => 35,
                'siding5' => 40
            ),
            '4' => array(
                'name' => "四品",
                'level' => 4,
                'login' => 40,
                'comment' => 5,
                'replay' => 5,
                'ask' => 0,
                'share' => 5,
                'invite' => 20,
                'siding1' => 20,
                'siding2' => 25,
                'siding3' => 30,
                'siding4' => 35,
                'siding5' => 40
            ),
            '30' => array(
                'name' => "从三品",
                'level' => 30,
                'login' => 50,
                'comment' => 6,
                'replay' => 6,
                'ask' => 0,
                'share' => 6,
                'invite' => 25,
                'siding1' => 25,
                'siding2' => 30,
                'siding3' => 35,
                'siding4' => 40,
                'siding5' => 45
            ),
            '3' => array(
                'name' => "三品",
                'level' => 3,
                'login' => 50,
                'comment' => 6,
                'replay' => 6,
                'ask' => 0,
                'share' => 6,
                'invite' => 25,
                'siding1' => 25,
                'siding2' => 30,
                'siding3' => 35,
                'siding4' => 40,
                'siding5' => 45
            ),
            '20' => array(
                'name' => "从二品",
                'level' => 20,
                'login' => 60,
                'comment' => 7,
                'replay' => 7,
                'ask' => 0,
                'share' => 7,
                'invite' => 30,
                'siding1' => 25,
                'siding2' => 30,
                'siding3' => 35,
                'siding4' => 40,
                'siding5' => 45
            ),
            '2' => array(
                'name' => "二品",
                'level' => 2,
                'login' => 60,
                'comment' => 7,
                'replay' => 7,
                'ask' => 0,
                'share' => 7,
                'invite' => 30,
                'siding1' => 25,
                'siding2' => 30,
                'siding3' => 35,
                'siding4' => 40,
                'siding5' => 45
            ),
            '10' => array(
                'name' => "从一品",
                'level' => 10,
                'login' => 70,
                'comment' => 8,
                'replay' => 8,
                'ask' => 0,
                'share' => 8,
                'invite' => 35,
                'siding1' => 30,
                'siding2' => 35,
                'siding3' => 40,
                'siding4' => 45,
                'siding5' => 50
            ),
            '1' => array(
                'name' => "一品",
                'level' => 1,
                'login' => 80,
                'comment' => 9,
                'replay' => 9,
                'ask' => 0,
                'share' => 9,
                'invite' => 40,
                'siding1' => 35,
                'siding2' => 40,
                'siding3' => 45,
                'siding4' => 50,
                'siding5' => 55
            ),
            '100' => array(
                'name' => "全品",
                'level' => 100,
                'login' => 100,
                'comment' => 10,
                'replay' => 10,
                'ask' => 0,
                'share' => 10,
                'invite' => 50,
                'siding1' => 40,
                'siding2' => 45,
                'siding3' => 50,
                'siding4' => 55,
                'siding5' => 60
            ),
        );
        //  print_r($darr);
        $log = M("u_coinlog");
        $act = M('u_action');
        $time = date("Y-m-d");
        $totime='%Y-%m-%d';

        if ($f_type) {
            $count = $log->where(" uname='" . $uname . "' and flag=2 and action='" . $action . "' and  DATE_FORMAT(date, '".$totime."')='" . $time . "'")->count();  //当天该用户的行为次数
            if ($action == 'login') {
                if (!$count) {
                    //$re = $act->where("level=$level")->field("login")->find();
                    //$login_dou = $re['login'];  //该会员等级下奖励的豆
                    $login_dou = $darr[$level][$action];
                    //执行添加分,豆
                    $this->addscore($uid, $uname, $action, $note, $actarr[$action], 1);  //添加学分
                    $this->addscore($uid, $uname, $action, $note, $login_dou, 2);  //添加豆
                }
            } elseif ($action == 'regist') {  //注册
                $this->addscore($uid, $uname, $action, $note, $actarr[$action], 1);  //添加学分
                $this->addscore($uid, $uname, $action, $note, 100, 2);  //添加豆
            } elseif ($action == 'comment' || $action == 'replay') { //回复
//                $re = $act->where("level=$level")->field("comment")->find();  //评论、回复赠送豆相同
//                $replay_dou = $re['comment'];  //该会员等级下奖励的豆
                $replay_dou = $darr[$level][$action];

                if ($count < 10 && $action == 'replay') {
                    $wuser = M('wecenter.Answer', 'aws_');
                    $cou = $wuser->where("question_id=$fid")->count();
                    if ($cou <= 5) {  //问题的前五位获2倍豆
                        $this->addscore($uid, $uname, $action, $note, $actarr[$action], 1);  //添加学分
                        $this->addscore($uid, $uname, $action, $note, $replay_dou * 2, 2);  //添加豆
                    } else {
                        $this->addscore($uid, $uname, $action, $note, $actarr[$action], 1);  //添加学分
                        $this->addscore($uid, $uname, $action, $note, $replay_dou, 2);  //添加豆
                    }
                }


                if ($count < 5 && $action == 'comment') {

                    $this->addscore($uid, $uname, $action, $note, $actarr[$action], 1);  //添加学分
                }
            } elseif ($action == 'ask') {  //提问和分享分,豆相同
//                $re = $act->where("level=$level")->field("ask,share")->find();  //该等级下的豆
//                $ask_dou = $re['ask'];  //分，豆相同
                $ask_dou = $darr[$level][$action];
                if ($count < 10) {
                    $this->addscore($uid, $uname, $action, $note, $actarr[$action], 1);  //添加学分
                    $this->addscore($uid, $uname, $action, $note, $ask_dou, 2);  //添加豆
                }
            }elseif($action == 'share'){
                $ask_dou = $darr[$level][$action];
                if ($count < 5) {
                    $this->addscore($uid, $uname, $action, $note, $ask_dou, 2);  //添加豆
                }
            }elseif ($action == 'buy') {

                if (1 <= $v && $v <= 50) {
                    $buy_dou = $darr[$level]['siding1'];
                } elseif (50 < $v && $v <= 100) {
                    $buy_dou = $darr[$level]['siding2'];
                } elseif (100 < $v && $v <= 150) {
                    $buy_dou = $darr[$level]['siding3'];
                } elseif (150 < $v && $v <= 200) {
                    $buy_dou = $darr[$level]['siding4'];
                } elseif (200 < $v) {
                    $buy_dou = $darr[$level]['siding5'];
                }
                if ($count < 2 ) {
                    $this->addscore($uid, $uname, $action, $note, $actarr[$action], 1);  //添加学分
                    $this->addscore($uid, $uname, $action, $note, $buy_dou, 2);  //添加豆
                }

            } elseif($action == 'adopt'){
                if($count<2){
                    $this->addscore($uid, $uname, $action, $note, $actarr[$action], 1);
                }
            }elseif($action == 'isadopt'){
                if($count<1){
                    $this->addscore($uid, $uname, $action, $note, $actarr[$action], 1);
                }
            }
        } else {  //减
            $actarr = array(
                'comment' => '-5'
            );
            $fen = $actarr[$action];
            if ($action == 'comment') {
                $this->decscore($uid, $uname, $action, $note, $fen, 1);  //减少学分
            }
        }
    }

}
