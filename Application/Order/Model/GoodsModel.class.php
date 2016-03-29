<?php
/**
 * Created by PhpStorm.
 * User: 立波
 * Date: 2016/3/15 0015
 * Time: 下午 2:42
 */
use Think\Model;

class GoodsModel extends Model
{
	public function __construct ()
	{
		parent::__construct ();
		$this->DB = "canpoint_goods";
	}

	/**
	 * 获取订单中商品信息
	 * @param $id
	 * @return bool|mixed
	 */
	public function getOrderByID ($id)
	{
		if (!$id)
		{
			return false;
		}
		$sql = "select * from {$this->DB}.qp_order_info where order_sn='{$id}' ";
		$this->log->info ($sql);
		$re = $this->query ($sql);
		return $re[0];
	}

	/**
	 * 获取商品信息
	 * @param $id
	 * @return bool
	 */
	public function getResByID ($id)
	{
		$this->log->info ('input args:'.func_num_args ().json_encode (func_get_args ()));
		if (!is_array ($id) && !is_numeric ($id))
		{
			return false;
		}
		if (is_array ($id))
		{
			foreach ($id as $v)
			{
				if (!is_numeric ($v))
				{
					return false;
				}
				$where = " where goods_id in (".join (',', $id).")";
				$limit = " limit ".count ($id);
			}
		}
		else
		{
			$where = " where goods_id={$id}";
			$limit = " limit 1";
		}
		$sql = "select * from {$this->DB}.qp_goods {$where} {$limit}";
		$this->log->info ($sql);
		$result = $this->query ($sql);
		foreach ($result as $v)
		{
			if ($v['goods_thumb'])
			{
				$v['goods_thumb'] = "http://adm.canpoint.net/goods/".$v['goods_thumb'];
			}
			$re[$v['goods_id']] = $v;
		}
		return $re;
	}

	/**
	 * 获取订单商品
	 * @param $id
	 * @param $ipage
	 * @param $ipageSize
	 * @return bool|mixed
	 */
	public function getGoodsByOrderID ($id, $ipage, $ipageSize)
	{
		$this->log->info ('input args:'.func_num_args ().json_encode (func_get_args ()));
		if (!$id || !is_numeric ($ipage) || !is_numeric ($ipageSize))
		{
			return false;
		}
		$where       = "where order_id={$id}";
		$limit_start = ($ipage - 1) * $ipageSize;
		$limit       = " limit {$limit_start},{$ipageSize}";
//		$sql         = "select * from {$this->DB}.qp_order_goods {$where} {$limit}";
                $sql         = "select * from {$this->DB}.qp_order_goods {$where}";
		$this->log->info ($sql);
		$re = $this->query ($sql);
		foreach ($re as $v)
		{
			$r[$v['goods_id']] = $v;
		}
		return $r;

	}

}