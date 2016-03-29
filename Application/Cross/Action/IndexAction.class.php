<?php

use Common\Controller\BaseController;
use Think\Crypt\Driver\Des;

class IndexAction extends BaseController {

    public function login() {
    // G('begin');
        $username = I('post.user');

        $password = get_passwd(I('post.pwd'));
        $checkbox = I('post.checkbox');
        $token = I('post.token');

        if ($token != session_id()) {
            session_destroy();
            session_id($token);
            session_start();
        }

        $stu = D("Study");
        $t = $stu->get_by_uid_pass($username, $password);  //用户名密码匹配
      
//        G("end");
//        echo G('begin','end').'s';
//匹配成功，可以登录
        if ($t) {

            $stu->where("id=" . $t[0]['id'])->setField('u_lastlogin', date('Y-m-d H:i:s'));
            /* 活动期间连续登录 */
            $this->active_login($t[0]['id']);
            /* 活动部分结束 */
            /* 存储session开始 */
            $_SESSION['user'] = $t;  //数组用户信息
            $_SESSION['CP_LOGIN_USER_DID'] = $t[0]['id']; //用户登陆后,用户的数字编号，取id
            $_SESSION['CP_LOGIN_USER_ID'] = $t[0]['u_id']; //用户登录帐号
            $_SESSION['CP_LOGIN_USER_NICK'] = $t[0]['u_nickname'] ? $t[0]['u_nickname'] : $t[0]['u_name'];  //用户昵称，如果昵称为空，取姓名
            $_SESSION['CP_LOGIN_USER_TYPE'] = $t[0]['u_type']; //用户类型 1.学生  2.老师  3.家长
            $_SESSION['CP_LOGIN_USER_GRADE'] = $t[0]['u_nianji']; //用户（学生，家长）年级
            $_SESSION['CP_LOGIN_USER_COURSE'] = $t[0]['u_course']; //用户任教学科(老师)
            /* 存储session结束 */

            $_COOKIE['CP_LOGIN_USER_DID'] = Des::encrypt($t[0]['id'], '123456'); //加密后的用户数字编号
//登录正确的返回信息
            $aa = array('status' => 'y', 'user' => $_SESSION['user'][0]['u_id'], 'type' => $_SESSION['user'][0]['u_type']);

            if ($checkbox == 1) {  //是否记住密码判断
                setcookie("getway", $password, time() + 3600 * 24 * 30, "/");
                setcookie("type", $t[0]['u_type'], time() + 3600 * 24 * 30, "/");
                set_cookie("r_uname", $t[0]['u_id'], time() + 3600 * 24 * 30, "/");
//set_cookie("_user_login", get_login_cookie_hash_xt($t[0]['u_id'], $password, "", 0, false), time() + 3600 * 24 * 30, "/", "canpoint.net");
            } else {
// set_cookie("_user_login", get_login_cookie_hash_xt($t[0]['u_id'], $password, "", 0, false), time() + 3600 * 24, "/", "canpoint.net");
                set_cookie("r_uname", $t[0]['u_id'], time() + 3600 * 24, "/");
            }
//            购物车记录信息 #cookie('car_n');$_COOKIE['car_n']
#header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
            canpointCommon::logDegub('cookie=' . $_COOKIE['car_n']);
            if ($_COOKIE['car_n']) {
                $decode = authcode($_COOKIE[car_n], 'DECODE');
                $car_n = json_decode(str_replace("\\", "", $decode), true);
//$car_n = json_decode($decode,true);
                $keorder = M('zi_keorder');
#  print_r($car_n);
                foreach ($car_n as $val) {
                    $findke = $keorder->where("ke_id='$val[id]' and user_id='" . $_SESSION[user][0][u_id] . "' and ke_stat = 1 ")->find();
                    if (!is_array($findke)) {
                        $car_date[ke_id] = $val[id];
                        $car_date[user_id] = $_SESSION['user'][0][u_id];
                        $car_date[ke_price] = $val[price];
                        $find = $keorder->where("ke_id='$val[id]' and user_id='" . $_SESSION[user][0][u_id] . "'")->find();
                        $car_date[ke_order] = $find ? 0 : $val[price];
                        $car_date[ke_rdate] = $val[rdate];
                        $car_date[ke_stat] = 1;
                        $car_date[ka_study] = 1;
                        $keorder->add($car_date);
                    }
                }
                setcookie('car_n', '', time() - 1, "/", ".canpoint.net");
            }
#直接更新购物车数量
            $car_count = M('zi_keorder')->where("user_id='" . $_SESSION[user][0][u_id] . "' and ke_stat = 1 ")->count();
            setcookie('car_count', $car_count, time() + 3600 * 24 * 2, "/", ".canpoint.net");

//存session到文件 eqa头部使用
            set_coin('login', '登录');
        } else {
            $aa = array('status' => 'n');
        }

        header('Access-Control-Allow-Origin:*');
        echo json_encode($aa);
    }

    /* ---------------------------
     * 2015-1-1至2015-1-31老用户连续登录3天赠送全品券 
     * $uid 老用户的uid
      --------------------------- */

    public function active_login($uid) {

        /* 活动期间连续登录 */
        $now = intval(time());
        $start = intval(strtotime("2015-1-1"));
        $end = intval(strtotime("2015-2-1"));

//        //在活动期间
        if ($now >= $start && $now < $end) {
            $Stu = D("Study");
            $Alogin = M("active_login");
            $Afp = M("active_fpoint");
            $info = $Stu->get_by_id($uid);
            $rdate = strtotime($info['u_rdate']);  //注册时间
//是否为老用户
            if ($rdate < $start) {
//更新或添加active_login表数据
//如果active_login表有数据则登录过，进行修改，若没有数据则表示没有登陆过，进行添加数据
                $one = $Alogin->where("uid=$uid")->find();
                if ($one['id']) { //数据存在 则更新
//最后一次登录和现在时间比较
                    $differ = intval(time()) - intval($one['lastdate']);
                    if ($differ > 24 * 3600 && $differ <= 48 * 3600) {
//count统计加1，更新时间
                        $Alogin->where("uid=$uid")->setInc('days', 1);
                        $Alogin->where("uid=$uid")->setField('lastdate', time());
                    } elseif ($differ > 48 * 3600) {
//count统计清0+1，更新时间
                        $Alogin->where("uid=$uid")->setField('days', 1);
                        $Alogin->where("uid=$uid")->setField('lastdate', time());
                    } elseif ($differ < 24 * 3600) {
                        $Alogin->where("uid=$uid")->setField('lastdate', time());
                    }

//取连续登录天数
                    $days = $Alogin->where("uid=$uid")->getField("days");
//是否已连续3天
                    if ($days == 3) {
//判断是否领取过 连续登录3天的全品券
                        $fcount = $Afp->where("uid=$uid and action='login3'")->count();
                        if (!$fcount) {

                            $Stu->where("id=$uid")->setInc('u_fpoint', 300); //老用户连续登录3天新增300全品券
                            $Afp->add(array('uid' => $uid, 'point' => 300, 'action' => 'login3', 'note' => '连续登录达三天', 'date' => time()));
                            $uname = $Stu->where("id=$uid")->getField("u_id");
                            M("u_coinlog")->add(array('uid' => $uid, 'uname' => $uname, 'action' => 'login3', 'note' => '连续登录达三天', 'val' => 300, 'flag' => 4, 'date' => date('Y-m-d', time())));
                        }
                    }
                } else { //无数据 首次登录  添加active_login数据
                    $Stu->where("id=$uid")->setInc('u_fpoint', 300); //老用户首次登录新增300全品券
                    $Afp->add(array('uid' => $uid, 'point' => 300, 'action' => 'first', 'note' => '老用户首次登录', 'date' => time()));
                    $Alogin->add(array('days' => 1, 'uid' => $uid, 'lastdate' => time()));
                    $uname = $Stu->where("id=$uid")->getField("u_id");
                    M("u_coinlog")->add(array('uid' => $uid, 'uname' => $uname, 'action' => 'first', 'note' => '老用户首次登录', 'val' => 300, 'flag' => 4, 'date' => date('Y-m-d', time())));
                }
            }
        }
        /* 活动连续登录结束 */
    }

    /*  -------------------------------------------
     * 登录验证码显示
     * @param string $top_login_ver  头部登录验证码id
     * @param string $login_ver  登录页面验证码id
      ------------------------------------------- */

    public function verify() {
        ob_clean();
        header("Content-type:image/png");
        $verify = I('get.id');

        canpointCommon::logDegub('111 $verify=' . $verify, '.g');
        canpointImagev2::buildImageVerify(4, 1, 'png', 50, 30, $verify);
    }

    /**
     * 关键字联想
     * Author：haifeng
     * date：2014-12-01
     */
    function getkwd() {
        header('Access-Control-Allow-Origin:*');
//1.接受参数
        canpointCommon::logDegub('getkwd');
        $keywd = $_REQUEST['keywd'];
        $keywd = trim($keywd);
        if ($keywd == '') {
            return;
        }

        $kwd = D('Search');
        $result = $kwd->getSearchKeyWord($keywd);
        echo json_encode($result);


        /*
          $s = array();
          $s['a'] = '111';
          $s['b'] = '222';
          echo json_encode( $s );
         */
    }
    
    /*  -------------------------------------------
     * 登录验证码校验
     * @param string $top_login_ver  头部登录验证码id
     * @param string $login_ver  登录页面验证码id
      ------------------------------------------- */

    public function findchk_code() {

        $code = md5(I("post.param"));
        $verify = I('get.id');
        $token = I('get.token');

        canpointCommon::logDegub('111$token=' . $token, '.g');
        canpointCommon::logDegub('222$verify=' . $verify, '.g');
        canpointCommon::logDegub('333$code=' . $code, '.g');
        canpointCommon::logDegub('444 session_id=' . session_id(), '.g');

        if ($token != session_id()) {
            session_destroy();
            session_id($token);
            session_start();
        }


        canpointCommon::logDegub('555 session_id=' . session_id(), '.g');
        canpointCommon::logDegub('666 $code=' . $code, '.g');
        canpointCommon::logDegub('888 $_SESSION [$verify]=' . $_SESSION[$verify], '.g');
        if ($code == $_SESSION [$verify]) {
            $aa = array(
                'info' => '',
                'status' => 'y'
            );
        } else {
            $aa = array(
                'info' => "验证码输入错误！",
                'status' => 'n'
            );
        }
        header('Access-Control-Allow-Origin:*');
        echo json_encode($aa);
    }
    
    public function qq() {
        require_once("qq/API/qqConnectAPI.php");
        $qc = new QC();
        $qc->qq_login();
        echo $qc->qq_callback();
        echo $qc->get_openid();
    }
    
     public function get_qq() {
        require_once("qq/API/qqConnectAPI.php");
        $qc = new QC();
        $acs = $qc->qq_callback();
        $oid = $qc->get_openid();
        $qc = new QC($acs, $oid);
//		
        //  $qc->qq_callback();

        $arr = $qc->get_user_info();
        // $arr[open_id]=$qc->get_openid();
        $arr[open_id] = $oid;
        #$userid = "cp" . rand(100000, 99999999);
        $db = D('u_study');
        $u_db = D('u_third');
        $user = $u_db->where("open_id='$arr[open_id]'")->find();
        #$ck_user = $db->where("u_id='$userid'")->find();
        do {
            $userid = "cp" . rand(100000, 99999999);
            $ck_user = $db->where("u_id='$userid'")->find();
        } while ($ck_user);

        $time = date('Y-m-d H:i:s', time());
        if ($user) {
            $user_id = $u_db->where("open_id='$arr[open_id]'")->getField('u_id');
            $date[u_lastlogin] = $time;
            $update = $db->where("u_id='$user_id'")->save($date);
            $db->where("u_id='$user_id'")->setInc('u_login');    //增加登陆次数
            // $db->where("u_id='$user_id'")->setInc('u_qdou', 10);   //增加豆
            $ses = $db->where("u_id='$user_id'")->select();
            $_SESSION['user'] = $ses;
            /* 活动期间连续登录 */
            $this->active_login($ses['0']['id']); 
            /* 活动部分 */
            
        } else {
            $nice_count=$db->where("u_nickname='$arr[nickname]'")->count();
            $arr[nickname]=$nice_count?'':$arr[nickname];
            $date = array(
                'u_id' => $userid,
                'u_pass' => 0,
                'u_nickname' => $arr[nickname],
                'u_lastlogin' => $time,
                'u_rdate' => $time,
                'u_verify' => 0,
                'u_vip' => "普通用户",
            );
            $date1 = array(
                'u_id' => $userid,
                'tag' => 1,
                'open_id' => $arr[open_id],
                'access_token' => $_SESSION[QC_userData][access_token]
            );

            $update1 = $db->add($date);
            $update2 = $u_db->add($date1);
            $db->where("u_id='$userid'")->setInc('u_login');
            // $db->where("u_id='$userid'")->setInc('u_qdou', 10);
            $ses = $db->where("u_id='$userid'")->select();
            $_SESSION['user'] = $ses;
            /* 活动期间新注册用户（包括被邀请用户）增加300元全品券 */
            $this->active_register($update1);
            /* 活动注册结束 */
        }
        $_SESSION['CP_LOGIN_USER_DID'] = $ses[0]['id']; //用户登陆后,用户的数字编号，取id
        $_SESSION['CP_LOGIN_USER_ID'] = $ses[0]['u_id']; //用户登录帐号
        $_SESSION['CP_LOGIN_USER_NICK'] = $ses[0]['u_nickname'] ? $t[0]['u_nickname'] : $t[0]['u_name'];  //用户昵称，如果昵称为空，取姓名
        $_SESSION['CP_LOGIN_USER_TYPE'] = $ses[0]['u_type']; //用户类型 1.学生  2.老师  3.家长
        $_SESSION['CP_LOGIN_USER_GRADE'] = $ses[0]['u_nianji']; //用户（学生，家长）年级
        $_SESSION['CP_LOGIN_USER_COURSE'] = $ses[0]['u_course']; //用户任教学科(老师)
        $_COOKIE['CP_LOGIN_USER_DID'] = Des::encrypt($ses[0]['id'], '123456'); //加密后的用户数字编号
        set_cookie("_user_login", get_login_cookie_hash_xt($ses[0]['u_id'], $password, "", 0, false), time() + 3600 * 24, "/", 'canpoint.net');
        set_cookie("r_uname", $ses[0]['u_id'], time() + 3600 * 24, "/", 'canpoint.net');
        
        $ase = $_SESSION['user'];
        set_cookie("r_uname", $ase[0][u_id], time() + 3600 * 24 * 7, "/", '');
        // set_cookie("_user_login", get_login_cookie_hash($ase[0][u_id], $passwd, $ase[0][u_salt], false), time() + 3600 * 24 * 7, "/", '');
        set_cookie("_user_login", get_login_cookie_hash_xt($ase[0][u_id], $ase[0][u_pass], "", 0, false), time() + 3600 * 24, "/", '');
        $_SESSION['form1'] = 1;

        if ($_COOKIE['car_n']) {
                $decode = authcode($_COOKIE[car_n], 'DECODE');
                $car_n = json_decode(str_replace("\\", "", $decode), true);
                //$car_n = json_decode($decode,true);
                $keorder = M('zi_keorder');
                #  print_r($car_n);
                foreach ($car_n as $val) {
                    $findke = $keorder->where("ke_id='$val[id]' and user_id='" . $_SESSION[user][0][u_id] . "' and ke_stat = 1 ")->find();
                    if (!is_array($findke)) {
                        $car_date[ke_id] = $val[id];
                        $car_date[user_id] = $_SESSION['user'][0][u_id];
                        $car_date[ke_price] = $val[price];
                        $find = $keorder->where("ke_id='$val[id]' and user_id='" . $_SESSION[user][0][u_id] . "'")->find();
                        $car_date[ke_order] = $find ? 0 : $val[price];
                        $car_date[ke_rdate] = $val[rdate];
                        $car_date[ke_stat] = 1;
                        $car_date[ka_study] = 1;
                        $keorder->add($car_date);
                    }
                }
                setcookie('car_n', '', time() - 1, "/", ".canpoint.net");
            }
            #直接更新购物车数量
            $car_count = M('zi_keorder')->where("user_id='" . $_SESSION[user][0][u_id] . "' and ke_stat = 1 ")->count();
            setcookie('car_count', $car_count, time() + 3600 * 24 * 2, "/", ".canpoint.net");

        //积分全品都增加
        set_coin('login', '登录');
        $this->redirect('/');
    }
}

?>
