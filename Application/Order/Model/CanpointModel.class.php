<?php

/**
 * Created by PhpStorm.
 * User: 立波
 * Date: 2016/3/15 0015
 * Time: 下午 2:37
 */
use Think\Model;

class CanpointModel extends Model
{
	public function __construct ()
	{
		parent::__construct ();
		$this->DB = "canpoint";
	}

	/**
	 * 获取订单信息
	 * @param $id
	 * @param $guid
	 * @return bool
	 */
	public function getOrderByID ($id, $guid)
	{
		if (!is_numeric ($id))
		{
			return false;
		}
		$sql = "select * from {$this->DB}.qp_bill_order_info where id={$id} and order_guid={$guid}  limit 1";
		$this->log->info ($sql);
		$re = $this->query ($sql);
		return $re ? $re[0] : false;
	}
}