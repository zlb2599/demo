<?php

use Think\Model;

class ChangeModel extends Model {
    /*
     * 根据用户guid获取数据
     */

    public function getInfo($guid, $field) {
        $table_index = substr($guid, -2);
        $table_name = "user_" . $table_index;
        $list = M("canpoint_user." . $table_name)->where("u_guid='$guid'")->field($field)->find();
        canpointCommon::logDegub("change_getusersql=" . M("canpoint_user." . $table_name)->getLastSql(),'Change');
        //return M("canpoint_user." . $table_name)->getLastSql();
        return $list;
    }

    /*
     * 根据用户信息
     */

    public function updByguid($guid, $array) {
        $table_index = substr($guid, -2);
        $table_name = "user_" . $table_index;
        $re = M("canpoint_user." . $table_name)->where("u_guid='$guid'")->save($array);
        canpointCommon::logDegub("change_updsql=" . M("canpoint_user." . $table_name)->getLastSql(),'Change');
        return $re;
    }

    /*
     * u_coinlog表添加兑换的全品币和全品豆记录
     */

    public function addcoinlog($array) {
        $re = M("u_coinlog")->add($array);
        canpointCommon::logDegub("change_coinlog_addsql=" . M("u_coinlog")->getLastSql(),'Change');
        return $re;
    }

    /*
     * qp_change兑换记录
     */

    public function addchange($array) {
        $re = M("c_change")->add($array);
        return $re;
    }

}
