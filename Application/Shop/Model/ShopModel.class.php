<?php

use Think\Model;

class ShopModel extends Model {
    /*
     * 字段sum值
     */

    public function fieldsum($where, $field) {
        $this->log->info('input args:' . func_num_args() . json_encode(func_get_args()));
        M("zi_ke")->where($where)->sum($field);
    }

    /*
     * 最新订单号
     */

    public function getCardByLastId() {
        $orderlog = M('bill_order_info');

        $lastid = $orderlog->where("id>0")->limit(1)->order('id desc')->find();
        $this->log->info($lastid);
        canpointCommon::logDegub("shop_index_order:lastid=" . $lastid, 'Shop');
        $Card = "CP" . str_pad($lastid['id'] + 1, 10, "0", STR_PAD_LEFT);
        $this->log->info($Card);
        canpointCommon::logDegub("shop_index_order:Card=" . $Card, 'Shop');
        return $Card;
    }

    /*
     * 该课订单如果生成切未付款则进行删除
     */

    public function hasOrderByKid($guid, $keid) {
        $this->log->info( 'input args:' . func_num_args() . json_encode( func_get_args() ) );
        $orderlog = M('bill_order_info');
        $sstlog = M('bill_order_plist');
        //是否已经生成订单
        $orderNum = $orderlog->where("order_guid=$guid and order_type=1 and order_status=1 and order_keid=$keid")->getField("order_num");
        $this->log->info($orderNum);
        canpointCommon::logDegub("shop_index_order:ordernum_sql=" . $orderlog->getLastSql(), 'Shop');
        if ($orderNum) {
            //            //删除该订单
            //            $re = $orderlog->where("order_num='" . $orderNum . "' and order_guid=$guid")->delete();
            //            canpointCommon::logDegub("shop_index_order:delete_orderinfp_sql=" . $orderlog->getLastSql(), 'Shop');
            //            if ($re) {
            //                $retu = $sstlog->where("plist_ordernum='" . $orderNum . "'")->delete();
            //                canpointCommon::logDegub("shop_index_order:delete_orderplist_sql=" . $orderlog->getLastSql(), 'Shop');
            //            }
            //更新订单的过期时间
            $sec_time = date("Y-m-d H:i:s", (time() + 3600 * 24));
            $order = array(
                'order_endtime' => $sec_time,
                    #有效期结束
            );
            $re = $orderlog->where("order_num='" . $orderNum . "'")->save($order);
            if ($re !== false) {
                return $orderNum;
            }
        } else {
            return '';
        }
    }

    /*
     * 获取视频相关字段
     * @param $keid 视频id,$field 字段
     * @return array
     */

    public function getFieldByKid($keid, $field) {
        $this->log->info( 'input args:' . func_num_args() . json_encode( func_get_args() ) );
        $keList = M("zi_ke")->where("id=" . $keid)->field($field)->find();
        canpointCommon::logDegub("shop_index_order:find_ke=" . serialize($keList), 'Shop');
        return $keList;
    }

    /*
     * 订单表order_info的数组
     */

    public function getArrayOrderInfo($Card, $guid, $keList, $fpoint, $rpoint) {
        $this->log->info( 'input args:' . func_num_args() . json_encode( func_get_args() ) );
        $orderlog = M('bill_order_info');
        $now_time = date("Y-m-d H:i:s");
        $sec_time = date("Y-m-d H:i:s", (time() + 3600 * 24));

        $order = array(
            'order_num' => $Card,
            #订单编号
            'order_name' => $keList['title'],
            ##套餐的名称
            'order_type' => 1,
            #类型2 套餐
            'order_guid' => $guid,
            #用户编号
            'order_mprice' => $keList['x_price'],
            #市场总价格
            'order_price' => $keList['x_price'],
            #销售价格
            'order_fpoint' => $fpoint,
            #全品券
            'order_rpoint' => $rpoint,
            #全品币
            'order_productnums' => 1,
            #微课总数量
            'order_status' => 1,
            #订单状态
            'order_haveexpire' => 1,
            #是否会过期
            'order_begintime' => $now_time,
            #有效期开始
            'order_endtime' => $sec_time,
            #有效期结束
            'order_time_create' => $now_time,
            #订单创建时间
            'order_keid' => $keList['id'],
                #订单的视频id
        );
        canpointCommon::logDegub("shop_index_order:orderinfo_arr=" . serialize($order), 'Shop');
        $newid = $orderlog->add($order);
        canpointCommon::logDegub("shop_index_order:add_orderinfo=" . $orderlog->getLastSql(), 'Shop');
        return $newid;
    }

    /*
     * 订单表order_plist的数组
     */

    public function getArrayOrderPlist($Card, $guid, $keList) {
        $this->log->info( 'input args:' . func_num_args() . json_encode( func_get_args() ) );
        $sstlog = M('bill_order_plist');

        $data['plist_ordernum'] = $Card;
        $data['plist_guid'] = $guid;
        $data['plist_merchantid'] = 1;
        $data['plist_productid'] = $keList['id'];
        $data['plist_productname'] = $keList['title'];
        $data['plist_productimg'] = $keList['x_url'];
        $data['plist_mprice'] = $keList['x_price'];
        $data['plist_price'] = $keList['x_price'];
        $data['plist_nums'] = 1;
        $data['plist_price_total'] = $keList['x_price'];
        $data['plist_haveexpire'] = 1;
        $data['plist_isused'] = 1;
        $data['plist_type'] = 1;
        canpointCommon::logDegub("shop_index_order:orderplist_arr=" . serialize($data), 'Shop');
        $re = $sstlog->add($data);
        canpointCommon::logDegub("shop_index_order:add_orderplist=" . $sstlog->getLastSql(), 'Shop');
        return $re;
    }

    /*
     * 订单相关字段获取
     * @param $order 订单号,$field 字段
     * @reutrn array
     */

    public function getByOrderid($Card, $field = '') {
        $this->log->info( 'input args:' . func_num_args() . json_encode( func_get_args() ) );
        $orderlog = M('bill_order_info');
        if ($field == '') {
            $orderList = $orderlog->where("order_num='" . $Card . "'")->find();
        } else {
            $orderList = $orderlog->where("order_num='" . $Card . "'")->field($field)->find();
        }
        return $orderList;
    }

    /*
     * 订单某一个字段获取
     * @param $order 订单号,$field 字段
     * @reutrn string
     */

    public function getOneFieldByOrderid($order, $field) {
        $this->log->info( 'input args:' . func_num_args() . json_encode( func_get_args() ) );
        $orderlog = M('bill_order_info');
        if (!$field) {
            return false;
        } else {
            $orderList = $orderlog->where("order_num='" . $order . "'")->getField($field);
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
        $this->log->info( 'input args:' . func_num_args() . json_encode( func_get_args() ) );
        $tablename = "user_" . substr($guid, -2);
        $table = "canpoint_user." . $tablename;
        $this->log->info($table);
        $User = M($table);
        if ($field == '') {
            $List = $User->where("u_guid='" . $guid . "'")->find();
        } else {
            $List = $User->where("u_guid='" . $guid . "'")->field($field)->find();
        }
        canpointCommon::logDegub("shop_index_pay:find_user=" . serialize($List), 'Shop');
        return $List;
    }

    /*
     * 根据订单号获取视频
     * @param $orderid
     * @return Array
     */

    public function getKeByOrder($orderid) {
        $this->log->info($orderid);
        $Plist = M("bill_order_plist");
        $list = $Plist->where("plist_ordernum='" . $orderid . "'")->find();
        $kelist = $this->getFieldByKid($list['plist_productid'], 'id,cate_01,cate_02,x_url');


        $list['cate_01'] = $this->get_xd($kelist['cate_01']);
        $list['cate_02'] = $this->get_xk($kelist['cate_02']);
        $list['image'] = $this->getimgURL($kelist['x_url'], '200x134');
        $list['keid'] = canpointCommon::encrypt($kelist['id']);
        $this->log->info($list);

        return $list;
    }

    /*
     * -------------------
     * get_xueduan 获取学段
     * -------------------
     * @param  id
     * @return field
     */

    public function get_xd($id) {
        $arr = array(
            '1' => "小学",
            '2' => "初中",
            '3' => '高中'
        );
        return $arr[$id];
    }

    /*
     * -------------------
     * get_xueke 获取学科
     * -------------------
     * @param  id
     * @return field
     */

    public function get_xk($id) {
        $A_area = M("zi_xueduan");
        $rearea = $A_area->find($id);
        return $rearea[x_ke];
    }

    /**
     * 获取不同尺寸图片
     * @Author: ZLB
     * @param $url
     * @param $size 282x184  432x282 194x125
     * @return mixed
     */
    public function getimgURL($url, $size) {
        $arr = explode('/', $url);
        $str = end($arr);
        $re = str_replace('.', "_" . $size . ".", $str);
        return str_replace($str, $re, $url);
    }

}
