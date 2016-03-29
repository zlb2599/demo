<?php

namespace Common\Controller;

use Think\Controller;

/* * *********************************************************************************
 * Copyright (c) 2005-2014
 * All rights reserved.
 *
 * Author:gaohaifeng
 * Editor:$LastChangedBy: haifeng $
 * Email:haifeng@hnusoft.com
 * Version:$Id: IndexAction.class.php 57 2014-11-18 05:06:49Z haifeng $
 * Description:基础类
 * session中：
 * $_SESSION['CP_LOGIN_USER_DID'] //用户登录后，用户的数字编号，取id
 * $_SESSION['CP_LOGIN_USER_ID']  //用户登录帐号
 * $_SESSION['CP_LOGIN_USER_NICK']//用户昵称，如果昵称为空，取姓名
 * $_SESSION['CP_LOGIN_USER_TYPE']//用户类型
 * $_SESSION['CP_LOGIN_USER_GRADE']//如果是学生或家长，年级
 * $_SESSION['CP_LOGIN_USER_COURSE']//如果是老师，科目
 * 
 * cookie中：
 * CP_LOGIN_USER_DID 加密的数字编号
 * ********************************************************************************* */

class BaseController extends Controller {

    private $_USER_GUID = 0;
    public $table_name = '';

    public $log;
    public function _initialize() {

        //parent::__construct();
        
        header("Content-type:text/html;charset=utf-8");
        if ($_SESSION['user'][0]['u_guid']) {
            $table_index = substr($_SESSION['user'][0]['u_guid'], -2);
            $this->table_name = "user_" . $table_index;
        } else {
            $url_method = $_SERVER["REQUEST_URI"];
            $http = $_SERVER['HTTP_HOST']; //获取当前域名  
            $Url = "http://" . $http . "/" . $url_method;
            //  / -    + _ 
            $new_Url = str_replace('+', '_', str_replace('/', '-', base64_encode($Url)));
            echo "<script>window.location.href='http://e.canpoint.net/login/index/index/url/".$new_Url. "'</script>";
        }


        //1.检测是否登录
        $this->initUserLoginHeader();

        $this->checkMicroMe();

        //2.
        $this->assign('keyword', '老师名/课程名');
        //4.日志
        $this->log = \Logger::getLogger(__CLASS__);
    }

    /**
     * 检测是否登录，如果登录，初始化header
     * Author：haifeng
     * date：2014-12-09
     */
    public function initUserLoginHeader() {

        $sessionSid_in_cookie = $_COOKIE['CP_LOGIN_USER_SID'];
        if (!empty($sessionSid_in_cookie)) {
            if ($sessionSid_in_cookie != session_id()) {
                session_destroy();
                session_id($sessionSid_in_cookie);
                session_start();
                ##登录增加全品豆功能
                set_coin('login', '登录');
            }
        }

        if (!empty($_SESSION['user'][0])) {
            
            $this->assign('islogin', '');
            $this->assign('nologin', 'none');

            $guid=$_SESSION['user'][0]['u_guid'];
            $uimgarr=getCanpointUser(array($guid));
            $_SESSION['user'][0]['avatar_file_url']=$uimgarr[$guid]['uimg'];
            
            $this->assign('u_guid', $_SESSION['user'][0]['u_guid']);
            $this->assign('u_nickname', $_SESSION['user'][0]['u_nickname']);
            $this->assign('avatar_file_url', $_SESSION['user'][0]['avatar_file_url']);
        	if( empty($_SESSION['user'][0]['avatar_file_url']) )
            {
            	$this->assign( 'avatar_file_url', $this->getUserimg( $_SESSION['user'][0]['u_nianji'], $_SESSION['user'][0]['u_sex'] ) );
            }
            
        } else {

            $this->assign('islogin', 'none');
            $this->assign('nologin', '');
        }

        //当前页面url,向页面输出，目的是登录后返回
        $host_self = $_SERVER['HTTP_HOST']; //获取当前域名 
        $request_url = $_SERVER["REQUEST_URI"];
        $pattern = '/^\/login|\/register/';
        if (preg_match($pattern, $request_url)) {
            $request_url = '';
        }
        $return_url = 'http://' . $host_self . $request_url;
        $return_url = str_replace('+', '_', str_replace('/', '-', base64_encode($return_url)));
        $this->assign('return_url', $return_url);

        $this->_USER_GUID = $_SESSION['user'][0]['u_guid'];
        $this->assign('cp_guid', $this->_USER_GUID);
        $this->assign('cp_guid_right_num_length1', substr($this->_USER_GUID, -1));

        //输出sessionid
        $this->assign('phpsessionid', session_id());
        $this->assign('timestamp', time() . rand(1000, 9999));

        //2. 通用部分用户信息
        $m_fans = D('Money/Money');
        $ds_userinfo = $m_fans->getCanpointUser($this->_USER_GUID);

        $this->assign('u_user_img', $ds_userinfo['avatar_file_url']); //用户头像
        $this->assign('u_nick_name', $ds_userinfo['u_nickname']); //用户昵称
        $this->assign('u_user_fpoint', $ds_userinfo['u_fpoint']); //用户全品券
        $this->assign('u_user_rpoint', $ds_userinfo['u_rpoint']); //用户全品币
        $this->assign('u_user_qdou', $ds_userinfo['u_qdou']); //用户全品豆
        //登录和未登录下的链接地址，向Ta提问，发私信，送礼物
        $url_ask = "http://eqa.canpoint.net/askid-" . $this->_USER_GUID;
        $url_sendletter = "http://eqa.canpoint.net/msgid-" . $this->_USER_GUID;
        $url_sendgift = "http://my.canpoint.net/user/exchange/shou_gift/u_nickname/{$ds_userinfo['u_nickname']}/u_guid/" . $this->_USER_GUID . ",";
        $url_login = "http://e.canpoint.net/login/index/index/url/";

        $url_ask = $url_login . str_replace('+', '_', str_replace('/', '-', base64_encode($url_ask)));
        $url_sendletter = $url_login . str_replace('+', '_', str_replace('/', '-', base64_encode($url_sendletter)));
        $url_sendgift = $url_login . str_replace('+', '_', str_replace('/', '-', base64_encode($url_sendgift)));
        $this->assign('url_ask', $url_ask);
        $this->assign('url_sendletter', $url_sendletter);
        $this->assign('url_sendgift', $url_sendgift);
    }

	/*
     * 得到用户默认头像
     */
	public function getUserimg($nj = '', $sex = '', $size = 'mid') 
	{
        
		if( $sex == '男')
		{
			$sex = 'm';
		}
		else
		{
			$sex = 'f';
		}
		
		if (empty($nj)) {
            if (empty($sex)) {
                return 'http://100095755.u.canpoint.netstyle_img/default_img/tyf.jpg';
            }
            return 'http://100095755.u.canpoint.netstyle_img/default_img/ty' . $sex . '.jpg';
        }
        if (empty($sex) || $sex == 'on') {
            return 'http://100095755.u.canpoint.netstyle_img/default_img/tyf.jpg';
        }
        if ($nj >= 10) {
            $nj = 'gz';
        } else if ($nj < 3) {
            $nj = 3;
        }
        return 'http://100095755.u.canpoint.net' . 'style_img/default_img/' . $nj . $sex . '.jpg';
        
    }
    
    

    
    
    
    public function logDegub($msg, $ext = "") {
        $logFile = "/home/canpoint/www_v3/u.canpoint.net/Application/Runtime/Logs/Debug-" . date('Y-m-d') . '.txt' . $ext;
        //$msg = date ( '[Y-m-d H:i:s]' ) . '	' . $msg . '	{in file:' . $_SERVER ['REQUEST_URI'] . "}\r\n";
        $msg = date('[Y-m-d H:i:s]') . '	' . $msg . "\r\n";
        file_put_contents($logFile, $msg, FILE_APPEND);
    }

    /*
     * 得到网址中的用户数字编号
     */

    public function getUserGUID() {
        return $this->_USER_GUID;
    }

    /**
     * 得到登录用户信息
     * 如果没有登录，返回null
     * Author：haifeng
     * date：2014-11-25
     */
    public function getLoginUserInfo() {
        if (!isset($_SESSION['CP_LOGIN_USER_DID']) || !isset($_SESSION['CP_LOGIN_USER_ID'])) {
            return null;
        } else {
            $array_user = array();
            $array_user['u_did'] = $_SESSION['CP_LOGIN_USER_DID'];
            $array_user['u_id'] = $_SESSION['CP_LOGIN_USER_ID'];
            $array_user['u_nick'] = $_SESSION['CP_LOGIN_USER_NICK'];
            $array_user['u_type'] = $_SESSION['CP_LOGIN_USER_TYPE'];
            $array_user['u_grade'] = $_SESSION['CP_LOGIN_USER_GRADE'];
            $array_user['u_course'] = $_SESSION['CP_LOGIN_USER_COURSE'];
            return $array_user;
        }
    }

    /**
     * 验证是否微信浏览器
     */
    public function checkMicroMe()
    {
        if( false!==strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
            //微信浏览器 获取签名
            $data = getSignature();
            $this->assign('weixin_data',$data);
        }
    }

    

}
