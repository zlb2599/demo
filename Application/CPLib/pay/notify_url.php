<?php

/* *
 * 功能：支付宝服务器异步通知页面
 * 版本：3.3
 * 日期：2012-07-23
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。


 * ************************页面功能说明*************************
 * 创建该页面文件时，请留心该页面文件中无任何HTML代码及空格。
 * 该页面不能在本机电脑测试，请到服务器上做测试。请确保外部可以访问该页面。
 * 该页面调试工具请使用写文本函数logResult，该函数已被默认关闭，见alipay_notify_class.php中的函数verifyNotify
 * 如果没有收到该页面返回的 success 信息，支付宝会在24小时内按一定的时间策略重发通知
 */

require_once("alipay.config.php");
require_once("lib/alipay_notify.class.php");

//计算得出通知验证结果
$alipayNotify = new AlipayNotify($alipay_config);
$verify_result = $alipayNotify->verifyNotify();

if ($verify_result) {//验证成功
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //请在这里加上商户的业务逻辑程序代
    //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
    //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
    //商户订单号
    $out_trade_no = $_POST['out_trade_no'];

    //支付宝交易号
    $trade_no = $_POST['trade_no'];

    //交易状态
    $trade_status = $_POST['trade_status'];
    $table_index = substr($is_pay['memberid'], -2);
    $study = D("canpoint_user.user_" . $table_index);

    if ($_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS') {
        //判断该笔订单是否在商户网站中已经做过处理
        //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
        //如果有做过处理，不执行商户的业务程序
        if ($is_pay['payorno'] == 1) {
            $pay_db->where("orderId='" . $info['out_trade_no'] . "'")->save($pay_order);
            $num = $info['total_fee'];  //充值的金额
            $oldmny = $study->where("u_guid='" . $is_pay['memberid'] . "'")->getField('u_rpoint');  //老金额
			//优惠活动
            if (!empty($is_pay['bonus'])) {
            	$num += $is_pay['bonus'];
            }
            $mony['u_rpoint'] = $oldmny + $num;  //充值后新金额
            canpointCommon::logDegub("recharge_new_rpoint=" . $mony['u_rpoint']);
            if ($study->where("u_guid='" . $is_pay['memberid'] . "'")->save($mony)) {
                canpointCommon::logDegub("recharge_user_sql=" . $study->getLastSql());

//                $data['uid'] = $is_pay['memberid'];
//                $data['uname'] = $is_pay['memberid'];
//                $data['action'] = "chongzhi1";
//                $data['note'] = "充值";
//                $data['val'] = $num;
//                $data['flag'] = 3;
//                $data['date'] = date("Y-m-d H:i:s", time());
//                $log = M("u_coinlog");
//                $log->add($data);

                //请不要修改或删除
                $_SESSION['now_rpoint'] = $oldmny + $num;
                //修改红包状态
                $active_model = new ActiveModel();
                $active_model->updateStatus($is_pay['bonus_id'], 2, $is_pay['id']);
                $notify_yz = 1;
            } else {
                canpointCommon::logError("User failed recharge Please check recharge_user_sql");
            }
        }
    } else {
        //echo '已支付';
        canpointCommon::logError("recharge_mesg_Paid trade_status=" . $_GET['trade_status']);
    }
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
} else {
    //验证失败
    // echo "fail";
    canpointCommon::logError("recharge_mesg=Verify failure");
    //调试用，写文本函数记录程序运行情况是否正常
    //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
}
?>