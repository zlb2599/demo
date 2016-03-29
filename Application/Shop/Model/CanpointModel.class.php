<?php

/**
 * Created by PhpStorm.
 * User: 立波
 * Date: 2016/3/11 0011
 * Time: 上午 10:59
 */
use Think\Model;

class CanpointModel extends Model {

    public function __construct() {
        parent::__construct();
        $this->DB = "canpoint";
    }

    public function addCanpointOrder($order) {
        $this->log->info('input args:' . func_num_args() . json_encode(func_get_args()));

        if (!is_array($order) || !$order) {
            return false;
        }
        if (!$order['order_num'] || !$order['order_name']) {
            return false;
        }
        if (!is_numeric($order['order_guid']) || strlen($order['order_guid']) != 9) {
            return false;
        }
        if (!is_numeric($order['order_type']) || !is_numeric($order['order_mprice']) || !is_numeric($order['order_price'])) {
            return false;
        }
        if (!is_numeric($order['order_productnums']) || !is_numeric($order['order_status']) || !is_numeric($order['order_haveexpire'])) {
            return false;
        }
        $data_array['order_num'] = $order['order_num'];
        $data_array['order_name'] = $order['order_name'];
        $data_array['order_guid'] = (int) $order['order_guid'];
        $data_array['order_type'] = (int) $order['order_type'];
        $data_array['order_mprice'] = $order['order_mprice'];
        $data_array['order_price'] = $order['order_price'];
        $data_array['order_productnums'] = (int) $order['order_productnums'];
        $data_array['order_status'] = (int) $order['order_status'];
        $data_array['order_haveexpire'] = (int) $order['order_haveexpire'];
        $data_array['order_begintime'] = date('Y-m-d H:i:s');
        $data_array['order_endtime'] = date('Y-m-d H:i:s', time() + 3600 * 24);
        $data_array['order_time_create'] = date('Y-m-d H:i:s');
        $data_array['order_pay_way'] = $order['order_pay_way'];

        $sql = $this->_getInsertSql($data_array, $this->DB . ".qp_bill_order_info");
        $this->log->info($sql);
        $re = $this->execute($sql);
        return $re ? $this->getLastInsID() : 0;
    }

    /**
     * 订单编号
     * @return string
     */
    public function getNewID() {
        $sql = " select max(id) as id from {$this->DB2}.qp_bill_order_info limit 1";
        $this->log->info($sql);
        $re = $this->query($sql);
        $lastid = $re[0]['id'];
        $Card = "GP" . str_pad($lastid + 1, 10, "0", STR_PAD_LEFT);
        return $Card;
    }

    /**
     * 获取用户地址
     * @param $guid
     * @return bool
     */
    public function getAddrByGuid($guid) {
        $this->log->info('input args:' . func_num_args() . json_encode(func_get_args()));

        if (!is_numeric($guid) || strlen($guid) != 9) {
            return false;
        }
        $sql = "select * from {$this->DB}.qp_u_address where guid={$guid} limit 1";
        $this->log->info($sql);
        $result = $this->query($sql);
        return $result[0];
    }

    /**
     * 更新用户地址
     * @param $guid
     * @param $name
     * @param $addr
     * @param $tel
     * @return bool|int
     */
    public function updateAddr($guid, $name, $addr, $tel) {
        $this->log->info('input args:' . func_num_args() . json_encode(func_get_args()));

        if (!is_numeric($guid) || strlen($guid) != 9) {
            return false;
        }
        if (!$name || !$addr || !is_numeric($tel)) {
            return false;
        }
        $sql = "select * from {$this->DB}.qp_u_address where guid={$guid} limit 1";
        $r = $this->query($sql);
        if ($r) {
            $sql = "update {$this->DB}.qp_u_address set name='{$name}',address='{$addr}',tel={$tel},addtime=" . time() . "  where guid={$guid} limit 1";
        } else {
            $data['guid'] = $guid;
            $data['name'] = $name;
            $data['address'] = $addr;
            $data['tel'] = $tel;
            $data['addtime'] = time();

            $sql = $this->_getInsertSql($data, "{$this->DB}.qp_u_address");
        }
        $this->log->info($sql);
        $re = $this->execute($sql);
        return $re ? 1 : 0;
    }

    /**
     * 添加订单
     * @param $order =array(
     *  'order_num'=>'';//订单号string
     *  'order_name'=>'';//订单名称string
     *  'order_type'=>'';//类型int
     *  'order_mprice'=>'';//市场价int
     *  'order_price'=>'';//实际价格int
     *  'order_productnums'=>'';//商品数量int
     *  'order_status'=>'';//状态int
     *  'order_haveexpire'=>'';//是否会过期int
     * )
     * @return bool|int
     */
    public function addOrder($order) {
        $this->log->info('input args:' . func_num_args() . json_encode(func_get_args()));

        if (!is_array($order) || !$order) {
            return false;
        }
        if (!$order['order_num'] || !$order['order_name']) {
            return false;
        }
        if (!is_numeric($order['order_guid']) || strlen($order['order_guid']) != 9) {
            return false;
        }
        if (!is_numeric($order['order_type']) || !is_numeric($order['order_mprice']) || !is_numeric($order['order_price'])) {
            return false;
        }
        if (!is_numeric($order['order_productnums']) || !is_numeric($order['order_status']) || !is_numeric($order['order_haveexpire'])) {
            return false;
        }
        $data_array['order_num'] = $order['order_num'];
        $data_array['order_name'] = $order['order_name'];
        $data_array['order_guid'] = (int) $order['order_guid'];
        $data_array['order_type'] = (int) $order['order_type'];
        $data_array['order_mprice'] = (int) $order['order_mprice'];
        $data_array['order_price'] = (int) $order['order_price'];
        $data_array['order_productnums'] = (int) $order['order_productnums'];
        $data_array['order_status'] = (int) $order['order_status'];
        $data_array['order_haveexpire'] = (int) $order['order_haveexpire'];
        $data_array['order_begintime'] = date('Y-m-d H:i:s');
        $data_array['order_endtime'] = date('Y-m-d H:i:s', time() + 3600 * 24);
        $data_array['order_time_create'] = date('Y-m-d H:i:s');

        $sql = $this->_getInsertSql($data_array, $this->DB . ".qp_bill_order_info");
        $this->log->info($sql);
        $re = $this->execute($sql);
        return $re ? $this->getLastInsID() : 0;
    }

    /**
     * 获取订单信息
     * @param $id
     * @return bool
     */
    public function getOrderByID($id) {
        if (!$id) {
            return false;
        }
        $sql = "select * from {$this->DB}.qp_bill_order_info where order_num='{$id}' limit 1";
        $this->log->info($sql);
        $re = $this->query($sql);
        return $re ? $re[0] : false;
    }

    public function getOrderByOrderNum($id) {
        if (!$id) {
            return false;
        }
        $sql = "select * from {$this->DB}.qp_bill_order_info where order_num='{$id}' limit 1";
        $this->log->info($sql);
        $re = $this->query($sql);
        return $re ? $re[0] : false;
    }

    /**
     * 支付状态
     * @param $id
     * @return bool|int
     */
    public function updateOrderPayStatus($id,$third='') {
        if (!$id) {
            return false;
        }
        $sql = "update {$this->DB}.qp_bill_order_info set order_status=2,order_time_pay='" . date('Y-m-d H:i:s') . "',order_third_num='".$third."' where order_num='{$id}' limit 1";
        $this->log->info($sql);
        $re = $this->execute($sql);
        return $re ? 1 : 0;
    }

    /**
     * 获取插入sql
     * @Author: ZLB
     * @param $data //数据
     * @param $table //表名
     * @return string
     */
    private function _getInsertSql($data, $table) {

        $key = array_keys($data);
        $value = array_values($data);
        $key_str = ' (`' . join('`,`', $key) . '`)';
        $value_str = '("' . join('","', $value) . '")';
        $sql = 'insert into ' . $table . $key_str . ' value ' . $value_str;
        return $sql;
    }

}
