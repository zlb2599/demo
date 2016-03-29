<?php
/**
 * ECShop
 * Created by PhpStorm.
 * User: 立波
 * Date: 2016/3/11 0011
 * Time: 上午 10:29
 */
use Think\Model;

class ResModel extends Model
{
	public function __construct ()
	{
		parent::__construct ();
		$this->DB = "canpoint_goods";
	}

	public function addEC_order ($data)
	{
		$this->log->info ('input args:'.func_num_args ().json_encode (func_get_args ()));

		if (!is_array ($data) || !$data)
		{
			return false;
		}
		if (!$data['order_sn'] || !$data['consignee'] || !$data['address'] || !is_numeric ($data['tel']))
		{
			return false;
		}
		if (!is_numeric ($data['user_id']) || strlen ($data['user_id']) != 9)
		{
			return false;
		}
		$data_array['order_sn']        = $data['order_sn'];
		$data_array['user_id']         = $data['user_id'];
		$data_array['to_buyer']        = $data['to_buyer'];
		$data_array['consignee']       = $data['consignee'];
		$data_array['address']         = $data['address'];
		$data_array['tel']             = $data['tel'];
		$data_array['goods_amount']    = (int)$data['goods_amount'];
		$data_array['order_status']    = 1;    //订单的状态;0未确认,1确认,2已取消,3无效,4退货
		$data_array['shipping_status'] = 0;    //商品配送情况;0未发货,1已发货,2已收货,4退货
		$data_array['pay_status']      = 0;    //支付状态;0未付款;1付款中;2已付款
		$data_array['add_time']        = time ();

		$sql = $this->_getInsertSql ($data_array, $this->DB.".qp_order_info");
		$this->log->info ($sql);

		$re = $this->execute ($sql);
		return $re ? $this->getLastInsID () : 0;
	}

	/**
	 * 获取订单中商品ID
	 * @param $id
	 * @return array|bool
	 */
	public function getGoodsByOrderID ($id)
	{
		if (!$id)
		{
			return false;
		}
		$sql = "select * from {$this->DB}.qp_order_goods where order_id='{$id}' ";
		$this->log->info ($sql);
		$result = $this->query ($sql);
		return $result;
	}

	/**
	 * 商品列表
	 * @param $type_id
	 * @return bool|mixed
	 */
	public function getGoodsList ($type_id)
	{
		if (!is_array ($type_id))
		{
			return false;
		}
		$sql = "select * from {$this->DB}.qp_goods where cat_id IN (".join (',', $type_id).") ";
		$this->log->info ($sql);
		$result = $this->query ($sql);
		foreach ($result as $v)
		{
			if ($v['goods_img'])
				$v['goods_img'] = "http://adm.canpoint.net/goods/".$v['goods_img'];
			$r[$v['goods_id']] = $v;
		}
		return $r;
	}

	/**
	 * 获取订单中商品信息
	 * @param $id
	 * @param $guid
	 * @return bool|mixed
	 */
	public function getOrderByID ($id, $guid = 0)
	{
		if (!$id || !is_numeric ($guid))
		{
			return false;
		}
		$where = " where order_sn='{$id}'";
		if ($guid)
		{
			$where .= " and user_id={$guid}";
		}
		$sql = "select * from {$this->DB}.qp_order_info  {$where} limit 1";
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
		$sql = "select * from {$this->DB}.qp_goods {$where} ORDER  BY  cat_id {$limit} ";
		$this->log->info ($sql);
		$result = $this->query ($sql);
		foreach ($result as $v)
		{
			$re[$v['goods_id']] = $v;
		}
		return $re;
	}

	/**
	 * 获取订单中商品信息
	 * @param $id
	 * @return bool|mixed
	 */
	public function getResByOrderID ($id)
	{
		if (!is_numeric ($id))
		{
			return false;
		}
		$sql = "select * from {$this->DB}.qp_order_goods where order_id='{$id}' ";
		$re  = $this->query ($sql);
		return $re;
	}

	/**
	 * 订单同步到ECshop
	 * @param $data
	 * @return bool|int|string
	 */
	public function addECshop ($data)
	{
		$this->log->info ('input args:'.func_num_args ().json_encode (func_get_args ()));

		if (!is_array ($data) || !$data)
		{
			return false;
		}
		if (!$data['order_sn'])
		{
			return false;
		}
		if (!is_numeric ($data['user_id']) || strlen ($data['user_id']) != 9)
		{
			return false;
		}
		if (!$data['consignee'] || !$data['address'] || !is_numeric ($data['mobile']))
		{
			return false;
		}
		$data_array['order_sn']        = $data['order_sn'];
		$data_array['user_id']         = $data['user_id'];
		$data_array['consignee']       = $data['consignee'];
		$data_array['address']         = $data['address'];
		$data_array['to_buyer']        = $data['to_buyer'];
		$data_array['zipcode']         = (int)$data['zipcode'];
		$data_array['mobile']          = (int)$data['mobile'];
		$data_array['goods_amount']    = (int)$data['goods_amount'];
		$data_array['order_status']    = 1;    //订单的状态;0未确认,1确认,2已取消,3无效,4退货
		$data_array['shipping_status'] = 0;    //商品配送情况;0未发货,1已发货,2已收货,4退货
		$data_array['pay_status']      = 0;    //支付状态;0未付款;1付款中;2已付款
		$data_array['add_time']        = time ();

		$sql = $this->_getInsertSql ($data_array, $this->DB.".qp_order_info");
		$this->log->info ($sql);

		$re = $this->execute ($sql);
		return $re ? $this->getLastInsID () : 0;

	}

	/**
	 * 订单商品详情
	 * @param $data =array(
	 * 'order_id'=>'',// 订单号
	 * 'goods_id'=>'',//商品ID
	 * 'market_price'=>'',//市场价
	 * 'goods_price'=>'',//实际价
	 * 'goods_name'=>'',//商品名称
	 * 'goods_sn'=>'',//商品SN
	 * )
	 * @return bool|int
	 */
	public function addOrderDetail ($data)
	{
		$this->log->info ('input args:'.func_num_args ().json_encode (func_get_args ()));

		if (!is_array ($data) || !$data)
		{
			return false;
		}
		if (!is_numeric ($data['order_id']) || !is_numeric ($data['goods_id']))
		{
			return false;
		}
		if (!is_numeric ($data['market_price']) || !is_numeric ($data['goods_price']))
		{
			return false;
		}
		if (!$data['goods_name'] || !$data['goods_sn'])
		{
			return false;
		}
		$data_array["order_id"]       = $data['order_id'];
		$data_array["goods_id"]       = $data['goods_id'];
		$data_array["goods_name"]     = $data['goods_name'];
		$data_array["goods_sn"]       = $data['goods_sn'];
		$data_array["market_price"]   = $data['market_price'];
		$data_array["goods_price"]    = $data['goods_price'];
		$data_array["extension_code"] = $data['extension_code'];
		$data_array["goods_number"]   = 1;
		$data_array["send_number"]    = 0;
		$data_array["is_real"]        = 1;
		$data_array["parent_id"]      = 0;
		$data_array["is_gift"]        = 0;

		$sql = $this->_getInsertSql ($data_array, $this->DB.".qp_order_goods");
		$this->log->info ($sql);

		$re = $this->execute ($sql);
		return $re ? 1 : 0;

	}

	/**
	 * 更新商品数量 自减1
	 * @param $id
	 * @return bool|int
	 */
	public function updateGoodsNum ($id)
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
		$sql = "update {$this->DB}.qp_goods set goods_number=goods_number-1 {$where} {limit}";
		$this->log->info ($sql);

		$re = $this->execute ($sql);
		return $re ? 1 : 0;
	}

	/**
	 * 修改商品数量 自增1
	 * @param $id
	 * @return bool|int
	 */
	public function updateGoodsNumInc ($id)
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
		$sql = "update {$this->DB}.qp_goods set goods_number=goods_number+1 {$where} {limit}";
		$this->log->info ($sql);

		$re = $this->execute ($sql);
		return $re ? 1 : 0;
	}

	/**
	 * 支付状态
	 * @param $id
	 * @return bool|int
	 */
	public function updateOrderPayStatus ($id)
	{
		if (!$id)
		{
			return false;
		}
		$sql = "update {$this->DB}.qp_order_info set pay_status=2 where order_sn='{$id}' limit 1";
		$this->log->info ($sql);
		$re = $this->execute ($sql);
		return $re ? 1 : 0;
	}

	/**
	 * 取消订单
	 * @param $id
	 * @return bool|int
	 */
	public function updateOrderStatus ($id)
	{
		if (!$id)
		{
			return false;
		}
		$sql = "update {$this->DB}.qp_order_info set order_status=2 where order_sn='{$id}' limit 1";
		$this->log->info ($sql);
		$re = $this->execute ($sql);
		return $re ? 1 : 0;
	}

	/**
	 * 获取插入sql
	 * @Author: ZLB
	 * @param $data //数据
	 * @param $table //表名
	 * @return string
	 */
	private function _getInsertSql ($data, $table)
	{

		$key       = array_keys ($data);
		$value     = array_values ($data);
		$key_str   = ' (`'.join ('`,`', $key).'`)';
		$value_str = '("'.join ('","', $value).'")';
		$sql       = 'insert into '.$table.$key_str.' value '.$value_str;
		return $sql;
	}


}