<?php

use Think\Model;

class GoodsModel extends Model {

    /**
     * 获取一个新的订单表
     */
    function get_neworder_id() {
        $orderlog = M('bill_order_info');

        $lastid = $orderlog->where("id>0")->limit(1)->order('id desc')->find();
        $this->log->info($lastid);
        canpointCommon::logDegub("goods_index_order:lastid=" . $lastid, 'Shop');
        $Card = "GP" . str_pad($lastid ['id'] + 1, 10, "0", STR_PAD_LEFT);
        $this->log->info($Card);
        canpointCommon::logDegub("goods_index_order:Card=" . $Card, 'Shop');
        return $Card;
    }

    /**
     * 获取货物详细
     * @param unknown $id
     * @return Ambigous <\Think\mixed, boolean, NULL, multitype:, unknown, mixed, string, object>
     */
    function get_goods_byid($id) {
        $goods = D('canpoint_goods.goods');
        $re = $goods->where(" goods_id =" . $id)->find();
        $re ['shop_price'] = (int) $re ['shop_price'];
        
        // $re['shop_price']= (int)$re['shop_price'];
        return $re;
    }

    /**
     * 该课订单如果生成切未付款则进行删除
     */
    public function has_Order_Bygid($guid, $goods_id) {
        $orderlog = M('bill_order_info');
        $sstlog = M('bill_order_plist');
        // 是否已经生成订单
        $orderNum = $orderlog->where("order_guid=$guid and order_type=4 and order_status=1 and order_keid=$goods_id and order_recycle=1")->getField("order_num");
        $this->log->info($orderNum);
        canpointCommon::logDegub("shop_index_order:ordernum_sql=" . $orderlog->getLastSql(), 'Shop');
        if ($orderNum) {
            
            // 更新订单的过期时间
            $sec_time = date("Y-m-d H:i:s", (time() + 3600 * 24));
            $order = array(
                'order_endtime' => $sec_time
                    // 有效期结束
            );
            $re = $orderlog->where("order_num='" . $orderNum . "'")->save($order);
            if ($re !== false) {
                return $orderNum;
            }
        } else {
            return '';
        }
    }

    /**
     * 订单表order_info的数组
     */
    public function add_ArrayOrderInfo($Card, $guid, $goods_info) {
        $orderlog = M('bill_order_info');
        $now_time = date("Y-m-d H:i:s");
        $sec_time = date("Y-m-d H:i:s", (time() + 3600 * 24));
        if ($goods_info ['goods_number']) {
            $order = array(
                'order_num' => $Card,
                // 订单编号
                'order_name' => $goods_info['goods_name'],
                // #商品名称
                'order_type' => 4,
                // 3实物
                'order_guid' => $guid,
                // 用户编号
                'order_mprice' => $goods_info ['shop_price'] / 100,
                // 市场总价格
                'order_price' => $goods_info ['shop_price'] / 100,
                // 销售价格
                'order_dou' => $goods_info ['shop_price'],
                // 全品豆
                'order_productnums' => 1,
                // 微课总数量
                'order_status' => 2,
                // 订单状态
                'order_haveexpire' => 1,
                // 是否会过期
                'order_begintime' => $now_time,
                // 有效期开始
                'order_endtime' => $sec_time,
                // 有效期结束
                'order_time_create' => $now_time,
                // 订单创建时间
                'order_keid' => $goods_info ['goods_id']
                    // 订单的视频id
            );
            $this->log->info($order);
            canpointCommon::logDegub("shop_index_order:orderinfo_arr=" . serialize($order), 'Shop');
            $newid = $orderlog->add($order);
            canpointCommon::logDegub("shop_index_order:add_orderinfo=" . $orderlog->getLastSql(), 'Shop');
            return $newid;
        } else {
            return false;
        }
    }

    /**
     * 添加到ECSHOP
     */
    public function add_ecshop($u_post, $goods_info, $order_userinfo, $order_Info) {
        $Morder_info = M('canpoint_goods.order_info');
        $order_info_data = array(
            "order_sn" => $order_Info['order_num'],
            "user_id" => $order_userinfo['u_guid'],
            "order_status" => 1,
            //订单的状态;0未确认,1确认,2已取消,3无效,4退货
            "shipping_status" => 0,
            //商品配送情况;0未发货,1已发货,2已收货,4退货
            "pay_status" => 2,
            //支付状态;0未付款;1付款中;2已付款
            "consignee" => $u_post['u_realname'],
            //收货人的姓名,用户页面填写,默认取值表user_address
            "address" => $u_post['u_address'],
            //收货人的详细地址,用户页面填写,默认取值于表user_address
            "zipcode" => $u_post['u_code'],
            //收货人的邮编,用户页面填写,默认取值于表user_address
            "goods_amount" => $order_Info['order_dou'],
            "money_paid" => $order_Info['order_dou'],
            "mobile" => $u_post['u_phone'],
            "tel" => $u_post['u_phone'],
            "to_buyer" => $order_userinfo['u_guid'] . "|" . $order_userinfo['u_nickname'],
            //商家给客户的留言,当该字段值时可以在订单查询看到
            "add_time" => time(),
                //"confirm_time" => time(),
                // "pay_time" => time(),
        );
        $order_id = $Morder_info->add($order_info_data);
        if ($order_id) {
            $Morder_goods = M('canpoint_goods.order_goods');
            $order_goods_data = array(
                "order_id" => $order_id,
                "goods_id" => $goods_info['goods_id'],
                "goods_name" => $goods_info['goods_name'],
                "goods_sn" => $goods_info['goods_sn'],
                "goods_number" => 1,
                "market_price" => $goods_info['market_price'],
                "goods_price" => $order_Info['order_dou'],
                "send_number" => 0,
                "is_real" => 1,
                "extension_code" => $goods_info['extension_code'],
                "parent_id" => 0,
                "is_gift" => 0,
            );
            $rec_id = $Morder_goods->add($order_goods_data);
        }
        #更新库存
        #goods
        $Mgoods = M('canpoint_goods.goods');
        //$re = $Mgoods->query("UPDATE canpoint_goods.qp_goods SET goods_number = goods_number -1 WHERE goods_id = " . $goods_info['goods_id']);
        $re = $Mgoods->where("goods_id=" . $goods_info['goods_id'])->setDec('goods_number', 1);
        // return $Mgoods->getLastSql();
        return $re;
    }

    /*
     * 更新ec订单的物流信息
     */

    public function updEcOrder($u_post) {
        $Morder_info = M('canpoint_goods.order_info');
        $order_info_data = array(
            "consignee" => $u_post['u_realname'],
            //收货人的姓名,用户页面填写,默认取值表user_address
            "address" => $u_post['u_address'],
            //收货人的详细地址,用户页面填写,默认取值于表user_address
            "zipcode" => $u_post['u_code'],
            //收货人的邮编,用户页面填写,默认取值于表user_address
            "mobile" => $u_post['u_phone'],
            "order_status" => 1,
            //订单的状态;0未确认,1确认,2已取消,3无效,4退货
            "shipping_status" => 0,
            //商品配送情况;0未发货,1已发货,2已收货,4退货
            "pay_status" => 2,
            //支付状态;0未付款;1付款中;2已付款
            "confirm_time" => time(),
            "pay_time" => time(),
        );
        $re = $Morder_info->where("order_sn='" . $u_post['u_formCard'] . "'")->save($order_info_data);
        return $re;
    }

    /**
     * 收获地址
     * @param $guid
     * @return bool|int
     */
    public function getAddressByGuid($guid) {
        if (!is_numeric($guid) || strlen($guid) != 9) {
            return 0;
        }
        $sql = "select * from canpoint.qp_u_address where guid={$guid} order by addtime desc limit 1";
        $result = $this->query($sql);
        return $result ? $result[0] : false;
    }

    public function addAddress($address) {
        if (!is_array($address) || !$address) {
            return 0;
        }
        if (!is_numeric($address['guid']) || strlen($address['guid']) != 9) {
            return 0;
        }
        if (!$address['name'] || !$address['address'] || !is_numeric($address['tel']) || strlen($address['tel']) != 11) {
            return 0;
        }
        $address['addtime'] = time();

        $sql = $this->_getInsertSql($address, 'canpoint.qp_u_address');
        $result = $this->execute($sql);
        return $result ? 1 : 0;
    }

    /**
     * 更新收货地址
     * @param $address
     * @return int
     */
    public function updateAddressByGuid($address) {
        if (!is_array($address) || !$address) {
            return 0;
        }
        if (!is_numeric($address['guid']) || strlen($address['guid']) != 9) {
            return 0;
        }
        if (!$address['name'] || !$address['address'] || !is_numeric($address['tel']) || strlen($address['tel']) != 11) {
            return 0;
        }
        $where = " where guid={$address['guid']}";
        $set = " set name='{$address['name']}',tel={$address['tel']},address='{$address['address']}', addtime=" . time();
        $sql = "update canpoint.qp_u_address " . $set . $where . " limit 1";
        $result = $this->execute($sql);
        return $result ? 1 : 0;
    }

    public function getUserByGuid($guid) {
        if (!is_numeric($guid) || strlen($guid) != 9) {
            return false;
        }
        $table_index = substr($guid, -2);

        $ds = $this->table("canpoint_user.qp_user_" . $table_index)->where("u_guid = {$guid}")->select();
        return $ds ? $ds[0] : false;
    }

    /**
     * 更新商品数量
     * @param $id
     * @param $num
     * @return int
     */
    public function updateGoodsNum($id, $num) {
        if (!is_numeric($id) || !is_numeric($num)) {
            return 0;
        }
        $sql = "update canpoint_goods.qp_goods set goods_number=goods_number+{$num} WHERE goods_id={$id} limit 1";
        $result = $this->execute($sql);
        return $result ? 1 : 0;
    }

    /**
     * 扣全品豆
     * @param $guid
     * @param $douNum
     * @return int
     */
    public function updateQdouByGuid($guid, $douNum) {
        if (!is_numeric($guid) || strlen($guid) != 9) {
            return 0;
        }
        if (!is_numeric($douNum)) {
            return 0;
        }
        $table_index = substr($guid, -2);
        $sql = "update canpoint_user.qp_user_{$table_index} set u_qdou=u_qdou-{$douNum} where u_guid={$guid} limit 1";
        $result = $this->execute($sql);
        return $result ? 1 : 0;
    }

    public function addCoinLog($coinlog) {
        if (!is_array($coinlog) || !$coinlog) {
            return 0;
        }
        $sql = $this->_getInsertSql($coinlog, "canpoint.qp_u_coinlog");
        $result = $this->execute($sql);
        return $result ? 1 : 0;
    }

    /**
     * 获取插入sql
     * @Author: ZLB
     * @param $data //数据
     * @param $table //表名
     * @return string
     */
    private function _getInsertSql($data, $table) {
        $this->log->info('input args:' . func_num_args() . json_encode(func_get_args()));

        $key = array_keys($data);
        $value = array_values($data);
        $key_str = ' (`' . join('`,`', $key) . '`)';
        $value_str = '("' . join('","', $value) . '")';
        $sql = 'insert into ' . $table . $key_str . ' value ' . $value_str;
        $this->log->info($this->getLastSql());
        return $sql;
    }

}

?>