<?php

/**
 * Created by PhpStorm.
 * User: 立波
 * Date: 2016/3/11 0011
 * Time: 上午 9:35
 */
use Common\Controller\BaseController;

class BuyAction extends BaseController
{

	public function __construct ()
	{
		parent::__construct ();
		$this->guid = $_SESSION['user'][0]['u_guid'];
	}

	public function middcart ()
	{
		$idstr = canpointCommon::encrypt ($_GET['id']);
		$url   = "/shop/buy/cart/id/".$idstr;
		header ("location:$url");
	}

	/**
	 * 确定订单
	 */
	public function cart ()
	{
		$_GET['id'] = canpointCommon::decrypt ($_GET['id']);
		$id         = explode (',', $_GET['id']);

		$data = $this->_checkGoods ($id);
		if (!$data['goods'])
		{
			exit('订单错误');
		}
		foreach ($data['goods'] as $v)
		{
			$gid[] = $v['goods_id'];
		}
		$data['tkn']  = $_SESSION['tkn'] = rand (1000, 9999);
		$data['id']   = join (',', $gid);
		$data['addr'] = D ('Canpoint')->getAddrByGuid ($this->guid);
		$this->assign ('data', $data);
		$this->display ('shop.buy.cart');
	}

	public function saveAddr ()
	{
		$name = trim ($_POST['u_name']);
		$addr = trim ($_POST['u_addr']);
		$tel  = (int)$_POST['u_tel'];
		if (!$this->guid)
		{
			$re['msg'] = "请登录";
			echo json_encode ($re);
			exit;
		}
		if (!$name)
		{
			$re['msg'] = "请填写收货人";
			echo json_encode ($re);
			exit;
		}
		if (!$addr)
		{
			$re['msg'] = "请填写收货地址";
			echo json_encode ($re);
			exit;
		}
		if (!$tel || !$this->_istel ($tel))
		{
			$re['msg'] = "请填写手机号";
			echo json_encode ($re);
			exit;
		}
		D ('Canpoint')->updateAddr ($this->guid, $name, $addr, $tel);
		$re['msg']     = "地址保存成功";
		$re['success'] = 1;
		echo json_encode ($re);
		exit;
	}


	/**
	 * 生成订单
	 */
	public function order ()
	{
		$id        = explode (',', $_POST['id']);
		$pay_style = $_POST['pay_style'] ? 1 : 0;

		$this->log->info ($_POST);
		if (!$this->guid)
		{
			$re['msg'] = "请登录";
			echo json_encode ($re);
			exit;
		}
		if ($_SESSION['tkn'] != $_POST['tkn'])
		{
			$re['msg'] = "禁止重复提交";
			$re['tkn'] = $_SESSION['tkn'] = rand (1000, 9999);
			echo json_encode ($re);
			exit;
		}
		$re['tkn'] = $_SESSION['tkn'] = rand (1000, 9999);
		$data      = $this->_checkGoods ($id);
		if (!$data['goods'])
		{
			$re['msg'] = "订单错误";
			echo json_encode ($re);
			exit;
		}
		//收货人信息
		$addrData = D ('Canpoint')->getAddrByGuid ($this->guid);
		if (!$addrData)
		{
			$re['msg'] = "请填写收货信息";
			echo json_encode ($re);
			exit;
		}

		foreach ($data['goods'] as $v)
		{
			if (!$v['goods_number'])
			{
				$re['msg'] = $v['goods_name']."库存不足";
				echo json_encode ($re);
				exit;
			}
			$orderDatail[$v['goods_id']]["goods_id"]       = $v['goods_id'];
			$orderDatail[$v['goods_id']]["goods_name"]     = $v['goods_name'];
			$orderDatail[$v['goods_id']]["goods_sn"]       = $v['goods_sn'];
			$orderDatail[$v['goods_id']]["market_price"]   = $v['market_price'];
			$orderDatail[$v['goods_id']]["goods_price"]    = $v['shop_price'];
			$orderDatail[$v['goods_id']]["extension_code"] = $v['extension_code'];

			$gid[] = $v['goods_id'];
		}
		$m     = D ('Res');
		$newID = D ('Canpoint')->getNewID ();

		//生成订单
		$order['order_num']         = $newID;
		$order['order_name']        = '2016年高考猜题活动资料';
		$order['order_type']        = 5;
		$order['order_guid']        = $this->guid;
		$order['order_mprice']      = $data['ori_price'];
		$order['order_price']       = $data['net_price'];
		$order['order_productnums'] = count ($data['goods']);
		$order['order_status']      = 1;
		$order['order_haveexpire']  = 1;
		if ($pay_style == 1)
		{
			$order['order_pay_way'] = 3;
		}
		elseif ($pay_style == 0)
		{
			$order['order_pay_way'] = 2;
		}

		//记录订单信息
		$result = D ('Canpoint')->addCanpointOrder ($order);
		if ($result)
		{
			//同步到ECshop
			$ec['order_sn']     = $newID;
			$ec['user_id']      = $this->guid;
			$ec['goods_amount'] = $data['net_price'];
			$ec['consignee']    = $addrData['name'];
			$ec['address']      = $addrData['address'];
			$ec['tel']          = $addrData['tel'];
			$ec['to_buyer']     = $this->guid."|".$_SESSION['user'][0]['u_nickname'];

			$ec_id = $m->addEC_order ($ec);
			//记录订单中商品信息
			foreach ($orderDatail as $v)
			{
				$v['order_id'] = $ec_id;

				$m->addOrderDetail ($v);
			}
			//修改库存
			$m->updateGoodsNum ($gid);

			$re['success'] = 1;
			$encrynewID    = canpointCommon::encrypt ($newID);
			$re['url']     = $pay_style ? 'http://pay.canpoint.net/shop/buy/wx_pay/id/'.$encrynewID : 'http://pay.canpoint.net/shop/buy/pay/id/'.$encrynewID;
			echo json_encode ($re);

			//添加取消订单任务
			$task['task_class']      = 1001;
			$task['task_name']       = '取消订单-'.$newID;
			$task['task_time_begin'] = date ('Y-m-d H:i:s', time () + 3600);
			$task['task_data']       = 'http://pay.canpoint.net/shop/buy/cancelOrder/id/'.$newID;
			$task['task_person']     = $this->guid;
			$task['task_count']      = 1;
			$task['task_interval']   = 1;
			addTaskSimple ($task);
			exit;
		}
	}

	/**
	 * 支付宝支付
	 */
	public function pay ()
	{
		$id    = canpointCommon::decrypt ($_GET['id']);
		$order = D ('Canpoint')->getOrderByID ($id);

		if (!$order)
		{
			exit('订单不存在');
		}

		//支付类型
		$payment_type = "1";
		//服务器异步通知页面路径
		// $notify_url = "http://pay.canpoint.net/payment/recharge/post_payarr";
		//需http://格式的完整路径，不能加?id=123这类自定义参数
		//页面跳转同步通知页面路径
		// $return_url = "http://pay.canpoint.net/payment/recharge/get_payarr";
		//需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/
		//卖家支付宝帐户
		$seller_email = 'canpointedu@163.com';  #2015-12-09
		//$seller_email = 'canpointbook@126.com';
		//商户订单号//必填
		$out_trade_no = $order['order_num'];
		//订单名称//必填
		$subject = '全品学堂订单支付';

		//付款金额
		$total_fee = $order['order_price'];
		//测试用
		//$total_fee = 0.01;
		$body = '';
		//商品展示地址
		$show_url = '';
		//需以http://开头的完整路径，例如：http://www.xxx.com/myorder.html
		//防钓鱼时间戳
		$anti_phishing_key = "";
		//若要使用请调用类文件submit中的query_timestamp函数
		//客户端的IP地址
		$exter_invoke_ip = "";
		//非局域网的外网IP地址，如：221.0.0.1
		$notify_url = "http://pay.canpoint.net/shop/buy/post_payarr";
		$return_url = "http://pay.canpoint.net/shop/buy/get_payarr";
		require_once './Application/CPLib/pay/alipayapi.php';
	}

	/**
	 * 支付宝回调地址
	 */
	public function post_payarr ()
	{
		$data  = $_POST;
		$m     = D ('Canpoint');
		$order = $m->getOrderByOrderNum ($data['out_trade_no']);
		require_once ("./Application/CPLib/pay/alipay.config.php");
		require_once ("./Application/CPLib/pay/lib/alipay_notify.class.php");
		//计算得出通知验证结果
		$alipayNotify  = new AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyNotify ();
		$this->log->info ($verify_result);
		if ($verify_result)
		{
			//验证成功
			if ($_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS')
			{
				if ($order['order_status'] == 1)
				{
					//学堂 支付状态
					$m->updateOrderPayStatus ($data['out_trade_no'], $data['trade_no']);
					//ECshop 支付状态
					D ('Res')->updateOrderPayStatus ($data['out_trade_no']);
				}
			}
		}
	}

	/**
	 * 支付宝支付完成
	 */
	public function get_payarr ()
	{
		$data = $_GET;
		$this->log->info ($data);
		$m     = D ('Canpoint');
		$order = $m->getOrderByOrderNum ($data['out_trade_no']);
		$this->log->info ($order);
		require_once ("./Application/CPLib/pay/alipay.config.php");
		require_once ("./Application/CPLib/pay/lib/alipay_notify.class.php");
		//计算得出通知验证结果
		$alipayNotify  = new AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyReturn ();
		$this->log->info ($verify_result);
		if ($verify_result)
		{//验证成功
			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			//请在这里加上商户的业务逻辑程序代码
			//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
			//获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
			//商户订单号
			$out_trade_no = $_GET['out_trade_no'];
			//支付宝交易号
			$trade_no = $_GET['trade_no'];
			//交易状态
			$trade_status = $_GET['trade_status'];
			if ($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS')
			{
				//判断该笔订单是否在商户网站中已经做过处理
				//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
				//如果有做过处理，不执行商户的业务程序
				if ($order['order_status'] == 1)
				{
					//学堂 支付状态
					$m->updateOrderPayStatus ($data['out_trade_no'], $data['trade_no']);
					//ECshop 支付状态
					D ('Res')->updateOrderPayStatus ($data['out_trade_no']);
				}
			}
			$this->order_suc ($out_trade_no);
		}
		$this->order_err ($out_trade_no);
	}

	/**
	 * 订单支付成功
	 * @param $out_trade_no
	 */
	public function order_suc ($out_trade_no)
	{
		$this->log->info ($out_trade_no);
		$data['order'] = D ('Res')->getOrderByID ($out_trade_no);
		if ($data['order'])
		{
			$order               = D ('Canpoint')->getOrderByID ($out_trade_no);
			$data['order']['id'] = $order['id'];
		}
		$this->assign ('data', $data);
		$this->display ("shop.buy.success");
	}

	/**
	 * 支付失败
	 * @param $out_trade_no
	 */
	public function order_err ($out_trade_no)
	{
		$data['order'] = D ('Res')->getOrderByID ($out_trade_no);
		if ($data['order'])
		{
			$order               = D ('Canpoint')->getOrderByID ($out_trade_no);
			$data['order']['id'] = $order['id'];
		}
		$this->assign ('data', $data);
		$this->display ("shop.buy.error");
	}

	/**
	 * 微信支付
	 */
	public function wx_pay ()
	{
		require_once "Application/CPLib/wxpay/lib/WxPay.Api.php";
		require_once "Application/CPLib/wxpay/WxPay.NativePay.php";
		require_once 'Application/CPLib/wxpay/log.php';
		$Card  = canpointCommon::decrypt ($_GET['id']);
		$order = D ('Canpoint')->getOrderByID ($Card);
		//测试
		//        $order['order_price'] = 0.01;
		//$order['order_price']* 100;
		//        $aa = sprintf("%.2f", $order['order_price']);
		$notify = new NativePay();
		$input  = new WxPayUnifiedOrder();


		$input->SetBody ("全品学堂高考猜题支付");
		$input->SetOut_trade_no ($order['order_num']);
		$input->SetTotal_fee ($order['order_price'] * 100);
		$input->SetTime_start (date ("YmdHis"));
		$input->SetTime_expire (date ("YmdHis", time () + 6000));
		$input->SetGoods_tag ("全品学堂高考猜题支付");
		$input->SetNotify_url ("http://pay.canpoint.net/shop/buy/notify");
		$input->SetTrade_type ("NATIVE");
		$input->SetProduct_id ("12345678");

		$result = $notify->GetPayUrl ($input);
		$url2   = $result["code_url"];

		$this->assign ('price', $order['order_price']);
		$this->assign ('ordernum', $order['order_num']);
		$this->assign ('url2', $url2);
		$this->display ('shop.buy.wxpay');
	}

	/**
	 * 微信回调
	 * @throws WxPayException
	 */
	public function notify ()
	{
		$simple  = file_get_contents ("php://input");
		$xml_msg = json_decode (json_encode ((array)simplexml_load_string ($simple, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		file_put_contents ('/alidata1/www_v3/pay.canpoint.net/Application/Runtime/'.date ("Y-m-d").'.txt', $xml_msg['result_code']."\n", FILE_APPEND);
		$this->log->info ($xml_msg);
		if ($xml_msg['result_code'] == 'SUCCESS')
		{
			require_once "Application/CPLib/wxpay/lib/WxPay.Api.php";
			require_once "Application/CPLib/wxpay/lib/WxPay.Data.php";
			$out_trade_no = $xml_msg['out_trade_no'];
			$this->log->info ($out_trade_no);
			if (isset($out_trade_no) && $out_trade_no != "")
			{
				$input = new WxPayOrderQuery();
				$input->SetOut_trade_no ($out_trade_no);
				$wx_order = WxPayApi::orderQuery ($input);
				$this->log->info ($wx_order);
				if ($wx_order['err_code'] == 'ORDERNOTEXIST')
				{
					exit('订单不存在');
				}

				if ($wx_order['trade_state'] == 'SUCCESS')
				{
					$m     = D ('Canpoint');
					$order = $m->getOrderByOrderNum ($out_trade_no);
					if ($order['order_status'] == 1)
					{
						//学堂 支付状态
						$m->updateOrderPayStatus ($out_trade_no, $wx_order['transaction_id']);
						//ECshop 支付状态
						D ('Res')->updateOrderPayStatus ($out_trade_no);
					}
				}
			}
			$this->order_suc ($out_trade_no);
		}
	}

	/*
	 * 微信根据订单查询是否成功
	 */

	public function wx_notify ()
	{
		// require_once "./Application/CPLib/wxpay/native_notify.php";
		/* 订单状态 */
		require_once "Application/CPLib/wxpay/lib/WxPay.Api.php";
		require_once "Application/CPLib/wxpay/lib/WxPay.Data.php";
		$Card = $_POST['ordernum'];
		$this->log->info ($Card);
		if (isset($Card) && $Card != "")
		{
			$out_trade_no = $Card;

			$input = new WxPayOrderQuery();
			$input->SetOut_trade_no ($out_trade_no);
			//            print_r(WxPayApi::orderQuery($input));
			$order = WxPayApi::orderQuery ($input);
		}
		$this->log->info ($order['trade_state']);
		echo $order['trade_state'];
	}

	/*
	 * 微信成功页面
	 */

	public function wx_success ()
	{
		require_once "Application/CPLib/wxpay/lib/WxPay.Api.php";
		require_once "Application/CPLib/wxpay/lib/WxPay.Data.php";
		$Card = $_GET['ordernum'];
		if (isset($Card) && $Card != "")
		{
			$out_trade_no = $Card;
			$input        = new WxPayOrderQuery();
			$input->SetOut_trade_no ($out_trade_no);
			$order = WxPayApi::orderQuery ($input);
			$this->log->info ($order);

			if ($order['err_code'] == 'ORDERNOTEXIST')
			{
				exit('订单不存在');
			}

			if ($order['trade_state'] == 'SUCCESS')
			{
				$data['order']['order_sn']     = $Card;
				$data['order']['goods_amount'] = $order['total_fee'] * 0.01;
				$order                         = D ('Canpoint')->getOrderByID ($out_trade_no);
				$data['order']['id']           = $order['id'];
				$this->assign ('data', $data);
				$this->display ("shop.buy.success");
			}
		}
		else
		{
			exit('订单不存在');
		}
	}

	/**
	 * 取消订单
	 */
	public function cancelOrder ()
	{
		$id    = $_GET['id'];
		$m     = D ('Canpoint');
		$order = $m->getOrderByOrderNum ($id);
		if ($order['order_status'] == 1)
		{
			if (strtotime ($order['order_begintime']) < time () - 3600)
			{
				$mEC = D ('Res');
				//订单状态
				$mEC->updateOrderStatus ($id);
				//商品数量
				$goodsID = $mEC->getGoodsIDByOrderID ($id);

				$mEC->updateGoodsNumInc ($goodsID);
			}
		}
	}

	/**
	 * 验证手机号
	 * @param $tel
	 * @return int
	 */
	private function _istel ($tel)
	{
		return preg_match ('/^[1][0-9]{10}$/', $tel);
	}

	private function _getDiscount ($num)
	{
		if ($num == 2)
		{
			return 0.9;
		}
		elseif ($num == 3)
		{
			return 0.85;
		}
		elseif ($num >= 4)
		{
			return 0.8;
		}
		else
		{
			return 1;
		}
	}

	/**
	 * 赠品
	 * @param $id
	 * @return bool
	 */
	public function getGift ($id)
	{
		$gift = $this->giftList ();
		return $gift[$id] ? $gift[$id] : array ();
	}

	/**
	 * @return mixed
	 */
	public function giftList ()
	{
		$gift[81] = array (83); //语文
		$gift[82] = array (85); //理数
		$gift[86] = array (90); //文数
		$gift[87] = array (84); //英语
		$gift[88] = array (91, 92, 93); //理综
		$gift[89] = array (94, 95, 96); //文综
		return $gift;
	}

	public function _checkGoods ($id)
	{
		//商品类型
		$type = array (
			29, 30
		);
		$m    = D ('Res');
		//活动商品
		$list = $m->getGoodsList ($type);
		//试卷
		$goods_a = array ();
		//考题
		$goods_b = array ();
		//赠送商品数
		$n = 0;
		foreach ($id as $v)
		{
			if (!$list[$v])
			{
				return false;;
			}

			if ($list[$v]['cat_id'] == 29)
			{
				$goods_a[] = $v;
				$n++;
			}
			else
			{
				$goods_b[] = $v;
			}
		}
		$ship = array ();
		foreach ($goods_a as $v)
		{
			$gift = $this->getGift ($v);
			if ($gift && !in_array ($v, $ship))
			{
				$ship[] = $v;
				$same   = array_values (array_intersect ($goods_b, $gift));
				if (!$same)
				{
					$goods_b[] = $gift[0];
					$free[]    = $gift[0];
				}
				elseif ($gift[0])
				{
					$free[] = $same[0];
				}
			}
		}
		$goods_id = array_merge ($goods_a, $goods_b);
		//订单总价
		$data['ori_price'] = 0;
		//实付价格
		$data['net_price'] = 0;
		foreach ($goods_id as $v)
		{
			$data['goods'][$v] = $list[$v];
			if (!in_array ($v, $free))
			{
				$data['ori_price'] += $list[$v]['shop_price'];
			}
			else
			{
				$data['goods'][$v]['shop_price'] = 0;
			}
		}
		//付款总价
		$data['net_price'] = $data['ori_price'];
		//付款打折价
		$data['net_price'] = $data['net_price'] * $this->_getDiscount (count ($data['goods']) - $n);


		return $data;
	}

}
