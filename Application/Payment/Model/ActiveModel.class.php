<?php
use Think\Model;
class ActiveModel extends Model{
		
	/**
	 * 获取可用红包列表
	 * @param unknown $uid
	 * @return 
	 */
	public function getHongBao($uid)
	{
		$uid = (int)$uid;
		if ($uid <=0) {
			return false;
		}
		$time = time();
		$res = M('canpoint_active.wechat_gift', 'qp_')->where("uid = $uid AND status=1 AND end_time>$time")->select();
		return $res;
	}
	
	public function getHongBaoById($id)
	{
		$id = (int)$id;
		if ($id <=0) {
			return false;
		}
		return M('canpoint_active.wechat_gift', 'qp_')->where("id = $id")->find();
	}
	
	/**
	 * 修改订单
	 */
	public function updateStatus($id, $status, $order_id)
	{
		$id = (int)$id; $order_id = (int)$order_id;
		
		if ($id <=0 || $order_id <=0) {
			return false;
		}
		
		$arrData = array(
			'status' =>$status,
			'update_time'=>time(),
			'order_id'=>$order_id
		);
		$res = M('canpoint_active.wechat_gift', 'qp_')->where("id = $id")->save($arrData);
		return $res;
	}
	
}