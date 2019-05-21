<?php

namespace Home\DAO;

/**
 * 库存建账 DAO
 *
 * @author 李静波
 */
class InitInventoryDAO extends PSIBaseExDAO {

	/**
	 * 计算并同步物资的库存，不考虑保质期和批号，计算出汇总数据
	 *
	 * @param string $warehouseId        	
	 * @param string $goodsId        	
	 */
	private function updateInventoryByFIFO($warehouseId, $goodsId, $dataOrg, $loginUserId) {
		$db = $this->db;
		
		$sql = "select sum(balance_count) as goods_count, sum(balance_money) as goods_money 
				from t_inventory_fifo
				where warehouse_id = '%s' and goods_id = '%s' ";
		$data = $db->query($sql, $warehouseId, $goodsId);
		$goodsCount = $data[0]["goods_count"];
		$goodsMoney = $data[0]["goods_money"];
		if ($goodsCount == 0) {
			// 没有建账数据
			$sql = "delete from t_inventory
					where warehouse_id = '%s' and goods_id = '%s'";
			$rc = $db->execute($sql, $warehouseId, $goodsId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
			
			$sql = "delete from t_inventory_detail
					where warehouse_id = '%s' and goods_id = '%s' ";
			$rc = $db->execute($sql, $warehouseId, $goodsId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		} else {
			$goodsPrice = $goodsMoney / $goodsCount;
			
			// 同步总账
			$sql = "select id from t_inventory where warehouse_id = '%s' and goods_id = '%s' ";
			$data = $db->query($sql, $warehouseId, $goodsId);
			if (! $data) {
				$sql = "insert into t_inventory(warehouse_id, goods_id, in_count, in_price,
						in_money, balance_count, balance_price, balance_money, data_org)
						values ('%s', '%s', %d, %f, %f, %d, %f, %f, '%s') ";
				$rc = $db->execute($sql, $warehouseId, $goodsId, $goodsCount, $goodsPrice, 
						$goodsMoney, $goodsCount, $goodsPrice, $goodsMoney, $dataOrg);
				if ($rc === false) {
					return $this->sqlError(__METHOD__, __LINE__);
				}
			} else {
				$id = $data[0]["id"];
				$sql = "update t_inventory
						set in_count = %d, in_price = %f, in_money = %f,
						balance_count = %d, balance_price = %f, balance_money = %f
						where id = %d ";
				$rc = $db->execute($sql, $goodsCount, $goodsPrice, $goodsMoney, $goodsCount, 
						$goodsPrice, $goodsMoney, $id);
				if ($rc === false) {
					return $this->sqlError(__METHOD__, __LINE__);
				}
			}
			
			// 同步明细账
			$sql = "select id from t_inventory_detail
					where warehouse_id = '%s' and goods_id = '%s' and ref_type = '库存建账'";
			$data = $db->query($sql, $warehouseId, $goodsId);
			if (! $data) {
				$sql = "insert into t_inventory_detail (warehouse_id, goods_id,  in_count, in_price,
							in_money, balance_count, balance_price, balance_money,
							biz_date, biz_user_id, date_created,  ref_number, ref_type, data_org)
						values ('%s', '%s', %d, %f, %f, %d, %f, %f, curdate(), '%s', now(), '', '库存建账', '%s')";
				$rc = $db->execute($sql, $warehouseId, $goodsId, $goodsCount, $goodsPrice, 
						$goodsMoney, $goodsCount, $goodsPrice, $goodsMoney, $loginUserId, $dataOrg);
				if ($rc === false) {
					return $this->sqlError(__METHOD__, __LINE__);
				}
			} else {
				$id = $data[0]["id"];
				$sql = "update t_inventory_detail
						set in_count = %d, in_price = %f, in_money = %f,
						balance_count = %d, balance_price = %f, balance_money = %f,
						biz_date = curdate()
						where id = %d ";
				$rc = $db->execute($sql, $goodsCount, $goodsPrice, $goodsMoney, $goodsCount, 
						$goodsPrice, $goodsMoney, $id);
				if ($rc === false) {
					return $this->sqlError(__METHOD__, __LINE__);
				}
			}
		}
		
		return null;
	}

	/**
	 * 新增或编辑建账信息
	 */
	public function editInitInv($params) {
		$db = $this->db;
		
		$dataOrg = $params["dataOrg"];
		if ($this->dataOrgNotExists($dataOrg)) {
			return $this->badParam("dataOrg");
		}
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->badParam("loginUserId");
		}
		
		$warehouseId = $params["warehouseId"];
		$goodsId = $params["goodsId"];
		$qcBeginDT = $params["qcBeginDT"];
		if (! $qcBeginDT) {
			$qcBeginDT = "1970-01-01";
		}
		$qcDays = $params["qcDays"];
		if ($qcBeginDT == "1970-01-01") {
			$qcEndDT = "1970-01-01";
		} else {
			$qcEndDT = date("Y-m-d", strtotime($qcBeginDT . " +$qcDays day"));
		}
		
		$qcSN = $params["qcSN"];
		
		$goodsCount = intval($params["goodsCount"]);
		$goodsMoney = floatval($params["goodsMoney"]);
		
		if ($goodsCount < 0) {
			return $this->bad("期初数量不能为负数");
		}
		
		if ($goodsMoney < 0) {
			return $this->bad("期初金额不能为负数");
		}
		
		$sql = "select name, inited from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $warehouseId);
		if (! $data) {
			return $this->bad("仓库不存在");
		}
		if ($data[0]["inited"] != 0) {
			return $this->bad("仓库 [{$data[0]["name"]}] 已经建账完成，不能再次建账");
		}
		
		$sql = "select name from t_goods where id = '%s' ";
		$data = $db->query($sql, $goodsId);
		if (! $data) {
			return $this->bad("物资不存在");
		}
		
		$sql = "select count(*) as cnt from t_inventory_fifo_detail
				where warehouse_id = '%s' and goods_id = '%s' and ref_type <> '库存建账' ";
		$data = $db->query($sql, $warehouseId, $goodsId);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("当前物资已经有业务发生，不能再建账");
		}
		
		if ($goodsCount == 0) {
			// 当输入物资数量为0的时候，就清除建账信息
			$sql = "delete from t_inventory_fifo
					where warehouse_id = '%s' and goods_id = '%s'
						and qc_begin_dt = '%s' and qc_days = %d and qc_sn = '%s' ";
			$rc = $db->execute($sql, $warehouseId, $goodsId, $qcBeginDT, $qcDays, $qcSN);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
			
			$sql = "delete from t_inventory_fifo_detail
					where warehouse_id = '%s' and goods_id = '%s'
						and qc_begin_dt = '%s' and qc_days = %d and qc_sn = '%s' ";
			$rc = $db->execute($sql, $warehouseId, $goodsId, $qcBeginDT, $qcDays, $qcSN);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
			
			$this->updateInventoryByFIFO($warehouseId, $goodsId, $dataOrg, $loginUserId);
			
			// 操作成功
			return null;
		}
		
		$goodsPrice = $goodsMoney / $goodsCount;
		
		// 总账
		$sql = "select id from t_inventory_fifo
				where warehouse_id = '%s' and goods_id = '%s'
					and qc_begin_dt = '%s' and qc_days = %d and qc_sn = '%s' ";
		$data = $db->query($sql, $warehouseId, $goodsId, $qcBeginDT, $qcDays, $qcSN);
		if (! $data) {
			$sql = "insert into t_inventory_fifo (warehouse_id, goods_id, in_count, in_price,
						in_money, balance_count, balance_price, balance_money, data_org,
						qc_begin_dt, qc_days, qc_end_dt, qc_sn, date_created)
						values ('%s', '%s', %d, %f, %f, %d, %f, %f, '%s', '%s', %d, '%s', '%s', now()) ";
			$rc = $db->execute($sql, $warehouseId, $goodsId, $goodsCount, $goodsPrice, $goodsMoney, 
					$goodsCount, $goodsPrice, $goodsMoney, $dataOrg, $qcBeginDT, $qcDays, $qcEndDT, 
					$qcSN);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		} else {
			$id = $data[0]["id"];
			$sql = "update t_inventory_fifo
						set in_count = %d, in_price = %f, in_money = %f,
						balance_count = %d, balance_price = %f, balance_money = %f
						where id = %d ";
			$rc = $db->execute($sql, $goodsCount, $goodsPrice, $goodsMoney, $goodsCount, 
					$goodsPrice, $goodsMoney, $id);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 明细账
		$sql = "select id from t_inventory_fifo_detail
					where warehouse_id = '%s' and goods_id = '%s' and ref_type = '库存建账'
						and qc_begin_dt = '%s' and qc_days = %d and qc_sn = '%s' ";
		$data = $db->query($sql, $warehouseId, $goodsId, $qcBeginDT, $qcDays, $qcSN);
		if (! $data) {
			$sql = "insert into t_inventory_fifo_detail (warehouse_id, goods_id,  in_count, in_price,
						in_money, balance_count, balance_price, balance_money,
						biz_date, biz_user_id, date_created,  ref_number, ref_type, data_org,
						qc_begin_dt, qc_days, qc_end_dt, qc_sn)
						values ('%s', '%s', %d, %f, %f, %d, %f, %f, curdate(), '%s', now(), '', '库存建账', '%s',
						'%s', %d, '%s', '%s')";
			$rc = $db->execute($sql, $warehouseId, $goodsId, $goodsCount, $goodsPrice, $goodsMoney, 
					$goodsCount, $goodsPrice, $goodsMoney, $loginUserId, $dataOrg, $qcBeginDT, 
					$qcDays, $qcEndDT, $qcSN);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		} else {
			$id = $data[0]["id"];
			$sql = "update t_inventory_fifo_detail
						set in_count = %d, in_price = %f, in_money = %f,
						balance_count = %d, balance_price = %f, balance_money = %f,
						biz_date = curdate()
						where id = %d ";
			$rc = $db->execute($sql, $goodsCount, $goodsPrice, $goodsMoney, $goodsCount, 
					$goodsPrice, $goodsMoney, $id);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		$this->updateInventoryByFIFO($warehouseId, $goodsId, $dataOrg, $loginUserId);
		
		// 操作成功
		return null;
	}

	/**
	 * 某个商品带保质期的建账详情列表
	 *
	 * @param array $params        	
	 */
	public function goodsDetailList($params) {
		$db = $this->db;
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		$warehouseId = $params["warehouseId"];
		$goodsId = $params["goodsId"];
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$sql = "select v.id,g.id as goods_id, g.code, g.name, g.spec, 
					convert(v.balance_count, $fmt) as balance_count, v.balance_price,
					v.balance_money, u.name as unit_name, v.biz_date,
					v.qc_begin_dt, v.qc_end_dt, v.qc_days, v.qc_sn
				from t_inventory_fifo_detail v, t_goods g, t_goods_unit u
					where v.goods_id = g.id and g.unit_id = u.id and v.warehouse_id = '%s'
					and v.ref_type = '库存建账' and g.id = '%s'
				order by v.qc_begin_dt ";
		$data = $db->query($sql, $warehouseId, $goodsId);
		$result = [];
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"goodsId" => $v["goods_id"],
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"goodsCount" => $v["balance_count"],
					"goodsMoney" => $v["balance_money"],
					"goodsPrice" => $v["balance_price"],
					"initDate" => $this->toYMD($v["biz_date"]),
					"qcBeginDT" => $this->toQcYMD($v["qc_begin_dt"]),
					"qcEndDT" => $this->toQcYMD($v["qc_end_dt"]),
					"qcDays" => $this->toQcDays($v["qc_days"]),
					"qcSN" => $v["qc_sn"]
			];
		}
		
		return $result;
	}

	/**
	 * 查询某个物资的建账信息
	 *
	 * @param array $params        	
	 */
	public function queryData($params) {
		$db = $this->db;
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		$warehouseId = $params["warehouseId"];
		$goodsId = $params["goodsId"];
		$qcBeginDT = $params["qcBeginDT"];
		if (! $qcBeginDT) {
			$qcBeginDT = "1970-01-01";
		}
		$qcDays = $params["qcDays"];
		if (! $qcDays) {
			$qcDays = 0;
		}
		$qcSN = $params["qcSN"];
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$sql = "select convert(v.balance_count, $fmt) as balance_count,
					v.balance_money
				from t_inventory_fifo_detail v
				where v.warehouse_id = '%s'	and v.ref_type = '库存建账' and v.goods_id = '%s'
					and v.qc_begin_dt = '%s' and v.qc_days = %d and v.qc_sn = '%s' ";
		$data = $db->query($sql, $warehouseId, $goodsId, $qcBeginDT, $qcDays, $qcSN);
		if (! $data) {
			return $this->emptyResult();
		}
		
		return [
				"success" => true,
				"count" => $data[0]["balance_count"],
				"money" => $data[0]["balance_money"]
		];
	}
}