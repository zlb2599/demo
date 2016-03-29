<?php

use Think\Model;

class PaymentModel extends Model {

    private $config;

    function _initialize() {
        $this->config = array(
            'alipay_partner' => C('ALIPAY_PARTNER'),
            'alipay_account' => C('ALIPAY_ACCOUNT'),
            'alipay_key' => C('ALIPAY_KEY'),
            'return_url' => '', //页面跳转同步通知页面路径
            'notify_url' => 'http://' . HOST_NAME . U('Pay/notify'), //服务器异步通知页面路径
        );
    }

    function get_payment_code($form) {
        $link = $form['bank_id'] == 'ALIPAY' ? $this->alipay_link($form) : $this->bank_link($form);
        return $link;
    }

    // 银联接连
    function bank_link($form) {
        $payment_notice = array(
            'money' => $form['money'],
            'deal_name' => $form['deal_name'],
            'bank_id' => $form['bank_id'],
            'notice_sn' => $form['notice_sn'],
        );
        $money = round($payment_notice['money'], 2);
        $payment_info = $this->config;
        $subject = $payment_notice['deal_name'];

        $data_return_url = $payment_info['return_url'];
        $data_notify_url = $payment_info['notify_url'];
        $service = 'create_direct_pay_by_user';
        /* 银行类型 */
        $bank_type = $payment_notice['bank_id'];

        $parameter = array(
            'service' => $service,
            'partner' => $payment_info['alipay_partner'],
            //'partner'           => ALIPAY_ID,
            '_input_charset' => 'utf-8',
            'notify_url' => $data_notify_url,
            'return_url' => $data_return_url,
            /* 业务参数 */
            'subject' => $subject,
            'out_trade_no' => $payment_notice['notice_sn'],
            'price' => $money,
            'quantity' => 1,
            'payment_type' => 1,
            /* 物流参数 */
            'logistics_type' => 'EXPRESS',
            'logistics_fee' => 0,
            'logistics_payment' => 'BUYER_PAY_AFTER_RECEIVE',
            'extend_param' => 'changjianghu',
            /* 买卖双方信息 */
            'seller_email' => $payment_info['alipay_account'],
            'defaultbank' => $bank_type,
            'payment' => 'bankPay'
        );
        $parameter = $this->argSort($parameter);
        $param = '';
        $sign = '';
        foreach ($parameter AS $key => $val) {
            $param .= "$key=" . urlencode($val) . "&";
            $sign .= "$key=$val&";
        }
        $param = substr($param, 0, -1);
        $sign = substr($sign, 0, -1) . $payment_info['alipay_key'];
        $sign_md5 = md5($sign);

        $payLinks = '<form target="_blank" action="https://www.alipay.com/cooperate/gateway.do?' . $param . '&sign=' . $sign_md5 . '&sign_type=MD5" id="jumplink" method="post">正在连接支付接口...</form>';
        $payLinks.='<script type="text/javascript">document.getElementById("jumplink").submit();</script>';
        return $payLinks;
    }

    // 支付宝链接
    function alipay_link($form) {
        $payment_notice = array(
            'money' => $form['money'],
            'deal_name' => $form['deal_name'],
            'bank_id' => $form['bank_id'],
            'notice_sn' => $form['notice_sn'],
        );
        $money = round($payment_notice['money'], 2);
        $payment_info = $this->config;
        $subject = $payment_notice['deal_name'];

        $data_return_url = $payment_info['return_url'];
        $data_notify_url = $payment_info['notify_url'];

        $parameter = array(
            'service' => 'create_direct_pay_by_user',
            'partner' => $payment_info['alipay_partner'],
            //'partner'           => ALIPAY_ID,
            '_input_charset' => 'utf-8',
            'notify_url' => $data_notify_url,
            'return_url' => $data_return_url,
            /* 业务参数 */
            'subject' => $subject,
            'out_trade_no' => $payment_notice['notice_sn'],
            'price' => $money,
            'quantity' => 1,
            'payment_type' => 1,
            /* 物流参数 */
            'logistics_type' => 'EXPRESS',
            'logistics_fee' => 0,
            'logistics_payment' => 'BUYER_PAY_AFTER_RECEIVE',
            'extend_param' => 'changjianghu',
            /* 买卖双方信息 */
            'seller_email' => $payment_info['alipay_account']
        );
        // print_r($parameter);exit;
        $parameter = $this->argSort($parameter);
        $param = '';
        $sign = '';
        foreach ($parameter AS $key => $val) {
            $param .= "$key=" . urlencode($val) . "&";
            $sign .= "$key=$val&";
        }
        $param = substr($param, 0, -1);
        $sign = substr($sign, 0, -1) . $payment_info['alipay_key'];
        $sign_md5 = md5($sign);

        $payLinks = '<form action="https://www.alipay.com/cooperate/gateway.do?' . $param . '&sign=' . $sign_md5 . '&sign_type=MD5" id="jumplink" method="post">正在连接支付接口...</form>';
        $payLinks.='<script type="text/javascript">document.getElementById("jumplink").submit();</script>';

        return $payLinks;
    }

    /* 结果 */

    function notify($request) {
        $return_res = array(
            'info' => '',
            'status' => false,
        );
        $payment = $this->config;

        $request = $this->argSort($request);
        /* 检查数字签名是否正确 */
        $isSign = $this->getSignVeryfy($request);
        if (!$isSign) {//签名验证失败
            $return_res['info'] = '签名验证失败';
            return $return_res;
        }
        if ($request['trade_status'] == 'TRADE_SUCCESS' || $request['trade_status'] == 'TRADE_FINISHED' || $request['trade_status'] == 'WAIT_SELLER_SEND_GOODS' || $request['trade_status'] == 'WAIT_BUYER_CONFIRM_GOODS') {

            $return_res['status'] = true;
        }
        return $return_res;
    }

    // 获取返回时的签名验证结果
    function getSignVeryfy($para_temp) {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);
        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);

        $isSgin = false;
        $isSgin = $this->md5Verify($prestr, $para_temp['sign'], $this->config['alipay_key']);
        return $isSgin;
    }

    // 验证签名
    function md5Verify($prestr, $sign, $key) {
        $prestr = $prestr . $key;
        $mysgin = md5($prestr);
        if ($mysgin == $sign) {
            return true;
        } else {
            return false;
        }
    }

    // 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
    function createLinkstring($para) {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg.=$key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    // 除去数组中的空值和签名参数
    function paraFilter($para) {
        $para_filter = array();
        while (list ($key, $val) = each($para)) {
            if ($key == "sign" || $key == "sign_type" || $val == "")
                continue;
            else
                $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    // 对数组排序
    function argSort($para) {
        ksort($para);
        reset($para);
        return $para;
    }

    /*
     * ---------------------------
     * 通过uid 获取guid
     * ---------------------------
     * @param uid
     * @return guid
     */

    public function getGuidByUid($uid) {
        if (empty($uid))
            return false;
        return M('canpoint_user.user_guiduid', 'qp_')->where("uid='$uid'")->getField('guid');
    }
    
     /*
     * --------------------
     * @手机检测号段和位数 参数 手机号
     * --------------------
     * @param phone
     * @return ture or false
     */
    public function isTphone($phone) {
        #如果传进来的不是数字 返回false
        if (is_numeric($phone)) {
            $pattern = '/^13\d{9}|15[0|1|2|3|5|6|7|8|9]\d{8}|18\d{9}|17[0|6|7|8]\d{8}$/';
            if (strlen($phone) == 11 && preg_match($pattern, $phone)) {
                return true;
            }
        }
        return false;
    }
    
      /*
     * ----------------------
     * @检测是否是邮箱   参数 邮箱
     * ----------------------
     * @param $email
     * @return  true or false
     */
    public function isEmail($email) {
        //检测
        return $this->checkEmail($email);
    }
    
    /*
     * --------------------
     * 检测email的正确性
     * --------------------
     * @param email
     * @return true or false
     */
    public function checkEmail($email) {
        if (strstr($email, '@') && strstr($email, '.')  && strlen($email)<50 ) {
            //if(eregi("^([_a-z0-9]+([._a-z0-9-]+)*)@([a-z0-9]{1,}(.[a-z0-9-]{1,})*.[a-z]{2,3})$", $email))
            if (preg_match('/^[a-z0-9_\-]+(\.[_a-z0-9\-]+)*@([_a-z0-9\-]+\.)+([a-z]{2}|aero|arpa|biz|com|coop|edu|gov|info|int|jobs|mil|museum|name|nato|net|org|pro|travel)$/', $email)) {
                return true;
            }
        }
        return false;
    }

}
