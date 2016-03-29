<?php

use Think\Model;

class OrderModel extends Model {
    /*
     * 待付款总数
     * @param $guid
     * @return int
     */

    public function obligation($guid) {
        $this->log->info($guid);
        $where = "order_guid='" . $guid . "' and order_status=1 and order_recycle=1";
        $count = M("bill_order_info")->where($where)->count();
        return $count;
    }

    /*
     * 获取单条订单信息
     * @param $order_id
     * @return array
     */

    public function getOrder($order_id,$guid) {
        $this->log->info( 'input args:' . func_num_args() . json_encode( func_get_args() ) );
        $info = M("bill_order_info")->where("id=$order_id and order_guid=$guid")->find();
        return $info;
    }

    /*
     * 通过订单编号获取订单下的视频
     * @param $order_num
     * @return array
     */

    public function getKeByOnum($order_num, $field) {
        $this->log->info( 'input args:' . func_num_args() . json_encode( func_get_args() ) );
        $array = M("bill_order_plist")->where("plist_ordernum='$order_num'")->field($field)->select();
        return $array;
        //return M("bill_order_plist")->getLastSql();
    }

    /*
     * 视频id字符串获取数组
     * @param $order_num
     * @return array
     */

    public function getKeById($kidstr, $field) {
        $this->log->info( 'input args:' . func_num_args() . json_encode( func_get_args() ) );
        $array = M("zi_ke")->where("id in ($kidstr)")->field($field)->select();
        return $array;
        //return M("bill_order_plist")->getLastSql();
    }

    /*
     * 订单下视频总数
     * @param $order_num
     * @return int
     */

    public function getKeTotal($order_num) {
        $this->log->info($order_num);
        $total = M("bill_order_plist")->where("plist_ordernum='$order_num'")->count();
        return $total;
        //return M("bill_order_plist")->getLastSql();
    }

    /*
     * 根据学段获取所有学科
     * @param $cate_01
     * @return Array
     */

    public function getXueKe($cate_01) {
        $this->log->info($cate_01);
        $arr = M("zi_xueduan")->where("x_type='$cate_01'")->order("sort asc")->select();
        foreach ($arr as $key => $v) {
            $count = M("zi_ke")->where("cate_02=" . $v['id'])->count();
            if (!$count) {
                unset($arr[$key]);
            }
        }
        //return M("zi_xueduan")->getLastSql();
        return $arr;
    }

    /**
     * 获取不同尺寸图片
     * @Author: ZLB
     * @param $url
     * @param $size  282x184  432x282 194x125
     * @return mixed
     */
    public function getimgURL($url, $size) {
        $arr = explode('/', $url);
        $str = end($arr);
        $re = str_replace('.', "_" . $size . ".", $str);
        return str_replace($str, $re, $url);
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
        $this->log->info($rearea);
        return $rearea[x_ke];
    }

    /*
     * -------------------
     * get_nianji 获取年级
     * -------------------
     * @param  id
     * @return field
     */

    public function get_nianji($id) {
        $A_area = M("zi_nianji");
        $rearea = $A_area->find($id);
        if($id==13){
            $aa="<a href='http://e.canpoint.net/grade/g/12.html'>".$rearea['nianji']."</a>";
        }elseif ($id==13) {
            $aa="<a href='http://e.canpoint.net/grade/grade/list3.html'>".$rearea['nianji']."</a>";
        }else{
            $aa="<a href=http://e.canpoint.net/grade/g/$id.html>".$rearea['nianji']."</a>";
        }
        $this->log->info($aa);
        //return $rearea['nianji'];
        return $aa;
    }

    /*
     * -------------------
     * get_xueduan 获取学段
     * -------------------
     * @param  id
     * @return field
     */

    public function get_xd($id) {
        $arr = array('1' =>"小学",'2' => "初中",'3' => '高中');
        return $arr[$id];
    }

}
