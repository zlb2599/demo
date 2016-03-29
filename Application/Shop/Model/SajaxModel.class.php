<?php

use Think\Model;

class SajaxModel extends Model {
    /*
     * 订单某一个字段获取
     * @param $order 订单号,$field 字段
     * @reutrn string
     */

    public function getOneFieldByOrderid($Card, $field) {
        $this->log->info($Card.",".$field);
        $orderlog = M('bill_order_info');
        if (!$field) {
            return false;
        } else {
            $orderList = $orderlog->where("order_num='" . $Card . "'")->getField($field);
            $this->log->info($orderList);
            canpointCommon::logDegub("shop_index_ajaxpay:orderStat=" . $orderStat, 'Shop');
            return $orderList;
        }
    }

    /*
     * 用户相关字段获取
     * @param $guid $field
     * @reutrn array
     */

    public function getUserByGuid($guid, $field = '') {
        $this->log->info($guid.",".$field);
        $tablename = "user_" . substr($guid, -2);
        $table = "canpoint_user." . $tablename;
        $this->log->info($table);
        $User = M($table);
        if ($field == '') {
            $List = $User->where("u_guid='" . $guid . "'")->find();
        } else {
            $List = $User->where("u_guid='" . $guid . "'")->field($field)->find();
        }
        canpointCommon::logDegub("shop_index_ajaxpay:find_user=" . serialize($List), 'Shop');
        return $List;
    }

    /*
     * u_coinlog财富增减记录
     * @param $guid用户guid,$action行为,$note行为描述,$val增减的值,$flag 1.学分  2.豆 .3币，4券,$type 增加1  减少2
     */

    public function addCoinlog($guid, $action, $note, $val, $flag, $type) {
        $this->log->info( 'input args:' . func_num_args() . json_encode( func_get_args() ) );
        $data = array('uid' => $guid,
            'uname' => $guid,
            'action' => 'goumai',
            'note' => '购买',
            'flag' => $flag,
            'date' => date("Y-m-d H:i:s")
        );
        if ($type == 1) {
            $data['val'] = '-' . $val;
        } elseif ($type == 2) {
            $data['val'] = $val;
        }

        $re = M("u_coinlog")->add($data);
        if ($re) {
            $info = $this->getUserByGuid($guid, 'u_type,u_guid,u_name,u_qdou,u_fpoint,u_rpoint');
            $rpoint = array();
            $rpoint['type'] = "购买微课";
            $rpoint['u_leixing'] = $info['u_type'];
            $rpoint['u_id'] = $info['u_guid'];
            $rpoint['u_name'] = $info['u_name'];
            $rpoint['p_point'] = $val;
            $rpoint['p_cr'] = "购买";
            $rpoint['u_rdate'] = date("Y-m-d H:i:s");
            $rpoint['u_beizhu'] = "用户[" . $info['u_name'] . "(" . $info['u_guid'] . "购买";
            if ($flag == 2) {
                $Rse = M('p_qdou');
                $rpoint['p_oldpoint'] = $info['u_qdou'];
                if ($type == 1) {
                    $rpoint['p_newpoint'] = $info['u_qdou'] - $val;
                } elseif ($type == 2) {
                    $rpoint['p_newpoint'] = $info['u_qdou'] + $val;
                }
            } elseif ($flag == 3) {
                $Rse = M('p_rpoint');
                $rpoint['p_oldpoint'] = $info['u_rpoint'];
                if ($type == 1) {
                    $rpoint['p_newpoint'] = $info['u_rpoint'] - $val;
                } elseif ($type == 2) {
                    $rpoint['p_newpoint'] = $info['u_rpoint'] + $val;
                }
            } elseif ($flag == 4) {
                $Rse = M('p_fpoint');
                $rpoint['p_oldpoint'] = $info['u_fpoint'];
                if ($type == 1) {
                    $rpoint['p_newpoint'] = $info['u_fpoint'] - $val;
                } elseif ($type == 2) {
                    $rpoint['p_newpoint'] = $info['u_fpoint'] + $val;
                }
            }
            $Rse->add($rpoint);
            return true;
        } else {
            return false;
        }
    }

    /*
     * 用户更新字段
     * @param $guid $data
     * @reutrn bool
     */

    public function updateUser($guid, $data) {
        $tablename = "user_" . substr($guid, -2);
        $table = "canpoint_user." . $tablename;
        $User = M($table);
        $re = $User->where("u_guid=$guid")->save($data);
        if ($re) {
            return true;
        } else {
            return false;
        }
    }

}
