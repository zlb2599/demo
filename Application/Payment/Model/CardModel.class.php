<?php

use Think\Model;

class CardModel extends Model {
    /*
     * 卡错误统计加1
     * @param $guid
     * @reutrn bool
     */

    public function updErrorNum($guid) {
        $cardError = M("card_error_num");
        $re = $cardError->where("u_guid=$guid")->setInc("num");
        canpointCommon::logDegub("payment_pay_updErrorNum=" . $cardError->getLastSql());
        if ($re) {
            $retu = $cardError->where("u_guid=$guid")->setField("time", time());
            return $retu;
        }
    }

    /*
     * 添加卡错误统计 
     * @param $guid
     * @reutrn bool
     */

    public function addErrorNum($guid) {
        $cardError = M("card_error_num");
        $array = array('num' => 1, 'u_guid' => $guid, 'time' => time());
        $re = $cardError->add($array);
        if ($re) {
            canpointCommon::logDegub("payment_pay_addErrorNum=" . $cardError->getLastSql());
        } else {
            canpointCommon::logDegub("addErrorNum sql error Please check payment_pay_addErrorNum sql");
        }
        return $re;
    }

    /*
     * 获取用户信息
     * @param $guid
     * @return Array
     */

    public function getUserByGuid($guid, $table) {
        $list = M("canpoint_user." . $table)->where("u_guid=$guid")->find();
        canpointCommon::logDegub("payment_pay_getuser=" . M("canpoint_user." . $table)->getLastSql());
        return $list;
    }

    /*
     * 获取用户输错记录
     * @param $guid
     * @return Array
     */

    public function getErrorList($guid) {
        $list = M("card_error_num")->where("u_guid=$guid")->find();
        canpointCommon::logDegub("payment_pay_getErrorList=" . M("card_error_num")->getLastSql());
        return $list;
    }

    /*
     * 卡批次是否存在
     * @param $k_part 卡批次
     * @reutrn Array
     */

    public function getKaList($k_part) {
        $list = M('ka_alist')->where("k_part='$k_part' and k_ff ='1'")->select();
        canpointCommon::logDegub("payment_pay_getKalist_sql=" . M('ka_alist')->getLastSql());
        return $list;
    }

    /*
     * 旧卡—根据卡号获取该条记录
     * @param $k_part 卡批次   $pcard 卡号
     * @reutrn Array
     */

    public function getListByCard($k_part, $pcard) {
        $list = M($k_part)->where("k_no='" . $pcard . "' and ka_stat='2'")->find();
        canpointCommon::logDegub("payment_pay_getListByCard_sql=" . M($k_part)->getLastSql());
        return $list;
    }

    /*
     * 卡激活日志
     * @param 
     * @reutrn 
     */

    public function addjihuolog($card, $price, $guid, $nickname, $ip) {
        $NSQ = M('ka_jihuolog');

        $data = array();
        $data['k_id'] = $card;
        $data['k_type'] = 2;
        $data['k_mianzi'] = $price;
        $data['k_jifen'] = null;
        $data['k_userid'] = $guid;
        $data['k_uname'] = $guid;
        $data['k_ip'] = $ip;
        $data['ka_diqu'] = null;
        $data['ka_agen1'] = 'quanpin';
        $data['ka_agen2'] = 'quanpin';
        $data['ka_fdiqu'] = IP($ip);
        $data['ka_rdate'] = date("Y-m-d H:i:s");
        $re = $NSQ->add($data);
        canpointCommon::logDegub("guid_payment_pay_addjihuolog=" . $NSQ->getLastSql(), 'Payment');
        return $re;
    }

    /*
     * 后台券记录
     * @param 
     * @reutrn 
     */

    public function addfpoint($guid, $price, $fpoint, $nickname) {
        $Rse = D('p_fpoint');
        $rpoint = array();
        $rpoint['type'] = "官网充值";
        $rpoint['u_leixing'] = "1";
        $rpoint['u_id'] = $guid;
        $rpoint['u_name'] = $nickname;
        $rpoint['p_point'] = null;
        $rpoint['p_oldpoint'] = $price;
        $rpoint['p_newpoint'] = $price + $fpoint;
        $rpoint['p_cr'] = "系统赠送卡";
        $rpoint['u_rdate'] = date("Y-m-d H:i:s");
        $rpoint['u_beizhu'] = "官网充值用户[" . $nickname . "(" . $guid . ")]";
        $re = $Rse->add($rpoint);
        canpointCommon::logDegub("guid_payment_table_fpoint_add=" . $Rse->getLastSql(), 'Payment');
        return $re;
    }

    /*
     * u_coin记录
     */

    public function addcoin($guid,$price) {
        $data1['uid'] = $guid;
        $data1['uname'] = $guid;
        $data1['action'] = "chongzhi";
        $data1['note'] = "充值";
        $data1['val'] = $price;
        $data1['flag'] = 4;
        $data1['date'] = date("Y-m-d H:i:s");
        $log = M("u_coinlog");
        $re=$log->add($data1);
        canpointCommon::logDegub("guid_payment_table_coinlog_add=" . $log->getLastSql(), 'Payment');
        return $re;
    }

}
