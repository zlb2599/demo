<?php

/*
 * 判断终端访问设备,目前分：PC 和  (ipad,iphone,android) 两种
 * haifeng
 * 2015-03-24
 */

function isMobile ()
{
	$cb = new canpointBrowser();
	return $cb->checkIsMobile ();
	//return false;
}

function IP ($ip = '', $file = 'UTFWry.dat')
{

	$_ip = array ();
	if (isset($_ip [$ip]))
	{
		return $_ip [$ip];
	}
	else
	{
		// import ("ORG.Net.IpLocsation");
		$iplocation          = new canpointIpLocation($file);
		$location            = $iplocation->getlocation ($ip);
		$location['country'] = iconv ("GB2312", "UTF-8", $location['country']);
		$location['area']    = iconv ("GB2312", "UTF-8", $location['area']);
		$_ip [$ip]           = $location ['country'].$location ['area'];
	}
	return $_ip [$ip];
}

//全品豆，券的添加
function set_coin ($type, $log, $is_t = TRUE, $fid = 0, $v = 0)
{
	if (empty($_SESSION['user'][0]))
	{
		return false;
	}
	else
	{
		//$id=$_SESSION['user'][0]['id'];
		if (!$_SESSION['user'][0]['u_level'])
		{
			$_SESSION['user'][0]['u_level'] = 0;
		}
		$score = new canpointScore();
		$re    = $score->addscore_defaulf (get_level ($_SESSION['user'][0]['u_xuefen']), $_SESSION['user'][0]['u_guid'], $_SESSION['user'][0]['u_guid'], $type, $log, $is_t, $fid, $v);

		return $re;
	}
}

function get_level ($fen)
{
	if ($fen >= 0 && $fen <= 200)
	{
		return 0;
	}
	if ($fen >= 201 && $fen <= 500)
	{
		return 90;
	}
	if ($fen >= 501 && $fen <= 1000)
	{
		return 9;
	}
	if ($fen >= 1001 && $fen <= 2000)
	{
		return 80;
	}
	if ($fen >= 2001 && $fen <= 3000)
	{
		return 8;
	}
	if ($fen >= 3001 && $fen <= 4000)
	{
		return 70;
	}
	if ($fen >= 4001 && $fen <= 6000)
	{
		return 7;
	}
	if ($fen >= 6001 && $fen <= 8000)
	{
		return 60;
	}
	if ($fen >= 8001 && $fen <= 10000)
	{
		return 6;
	}
	if ($fen >= 10001 && $fen <= 13000)
	{
		return 50;
	}
	if ($fen >= 13001 && $fen <= 16000)
	{
		return 5;
	}
	if ($fen >= 16001 && $fen <= 19000)
	{
		return 40;
	}
	if ($fen >= 19001 && $fen <= 23000)
	{
		return 4;
	}
	if ($fen >= 23001 && $fen <= 27000)
	{
		return 30;
	}
	if ($fen >= 27001 && $fen <= 31000)
	{
		return 3;
	}
	if ($fen >= 31001 && $fen <= 36000)
	{
		return 20;
	}
	if ($fen >= 36001 && $fen <= 41000)
	{
		return 2;
	}
	if ($fen >= 41001 && $fen <= 47000)
	{
		return 10;
	}
	if ($fen >= 47001 && $fen <= 55000)
	{
		return 1;
	}
	if ($fen >= 55001)
	{
		return 100;
	}
}

/**
 * post 接口数据
 * @param unknown $url
 * @param unknown $post
 * @return mixed
 */
function curl_post ($post, $url = 'http://my.canpoint.net/Notification/Api/api_set_notific/')
{

	$options = array (
		CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => false, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $post,
	);

	$ch = curl_init ($url);

	curl_setopt_array ($ch, $options);
	$result = curl_exec ($ch);

	curl_close ($ch);
	return $result;
}


/**
 * @param string $time
 * @return bool|string
 * 对时间进行优化
 */
function Timeoptimized ($timse)
{

	if (!$time)
	{
		return false;
	}

	if (strlen ($time) > 18)
	{
		$str    = substr ($time, 5);
		$newstr = substr ($str, 0, -3);
		return $newstr;
	}
	else
	{
		$str = substr ($time, 5);
		return $str;
	}
}


/*** 微信开发涉及到内容 开始 random()函数 上层********/
/**
 * 获取当前页面完整URL地址
 */
function get_url ()
{
	$sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
	$php_self     = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
	$path_info    = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
	$relate_url   = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
	return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
}

/**
 * 获取信息
 */
function curl_get ($url)
{

	$oCurl = curl_init ();
	if (stripos ($url, "https://") !== FALSE)
	{
		curl_setopt ($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt ($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
	}
	curl_setopt ($oCurl, CURLOPT_URL, $url);
	curl_setopt ($oCurl, CURLOPT_RETURNTRANSFER, 1);
	$sContent = curl_exec ($oCurl);
	$aStatus  = curl_getinfo ($oCurl);
	curl_close ($oCurl);
	if (intval ($aStatus["http_code"]) == 200)
	{
		if (preg_match ('/^\xEF\xBB\xBF/', $sContent))
		{
			$sContent = substr ($sContent, 3);
		}
		return json_decode ($sContent, true);
	}
	else
	{
		return false;
	}
}

function random ($length)
{
	$hash  = '';
	$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';   // 指定要返回的字符串
	$max   = strlen ($chars) - 1;
	mt_srand ((double)microtime () * 1000000);
	for ($i = 0; $i < $length; $i++)
	{
		$hash .= $chars[mt_rand (0, $max)];
	}

	return $hash;
}

/**
 * 生成微信签名算法
 */
function getSignature ()
{
	$noncestr    = random (16);
	$timestamp   = time ();
	$url         = get_url ();
	$url         = explode ('#', $url);
	$url         = $url[0];
	$ticket_name = 'wechat_Ticket';
	$openId      = S ('openId');
	if (!$ticket = S ($ticket_name))
	{
		$res = curl_get ('http://weixin.canpoint.net/home/index/getTicket');
		if ($res['errNo'] == 0)
		{
			$expire = $res['result']['expires_in'] ? intval ($res['result']['expires_in']) - 100 : 3600;
			$ticket = $res['result']['ticket'];
			$openId = $res['result']['openid'];
			S ($ticket_name, $ticket, $expire);  //设置缓存
			S ('openId', $openId, $expire); //
		}
	}
	//对所有待签名参数按照字段名的ASCII 码从小到大排序拼成string1
	$arr = array ('jsapi_ticket='.$ticket, 'noncestr='.$noncestr, 'timestamp='.$timestamp, 'url='.$url);
	sort ($arr);
	$string1 = implode ('&', $arr);
	//对string1作sha1加密
	$signature = sha1 ($string1);
	return array ('nonceStr' => $noncestr, 'timestamp' => $timestamp, 'appId' => $openId, 'signature' => $signature);
}

/***微信开发涉及到内容 end ***/

/***
 * 添加非定期任务
 * @param int $task_class 任务类, 举例：1001
 * @param string $task_name 任务名称
 * @param datetime $task_time_begin 任务开始执行时间, 举例：2015-10-10 12:00:00
 * @param string $task_data 任务数据, $task_class=1001时,$task_data为url。举例：http://xxx.xxx.com/
 * @param string $task_person 任务添加人
 * @param int $task_count 任务执行次数
 * @param int $task_interval 每次任务的间隔时间, 单位：秒
 */
function addTaskSimple ($post)
{
	$url                 = 'http://api.canpoint.net/index/Quartz/addTask';
	$post['task_iscron'] = 0;
	$res                 = post ($url, $post);
	return $res;
}

/**
 * post请求
 * @param string $param 请求url
 * @param array $param 请求参数
 */
function post ($url, $post)
{
	$post = !empty($post) ? formatParam ($post) : '';
	$ch   = curl_init ();
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($ch, CURLOPT_BINARYTRANSFER, 1);
	curl_setopt ($ch, CURLOPT_POST, 1);
	curl_setopt ($ch, CURLOPT_POSTFIELDS, $post);// data是数组格式
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 10);//链接超时
	curl_setopt ($ch, CURLOPT_TIMEOUT, 30); //curl最长执行时间
	curl_setopt ($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)');
	$result = curl_exec ($ch);
	curl_close ($ch);
	return $result;
}
/**
 * 参数格式化
 * @param array $arr
 * @return string
 */
function formatParam ($arr)
{
	$return = '';
	foreach ($arr as $key => $value)
	{
		if (is_array ($value))
		{
			$value = serialize ($value);
		}
		$return .= $key.'='.$value.'&';
	}
	$return = rtrim ($return, '&');
	return $return;
}

