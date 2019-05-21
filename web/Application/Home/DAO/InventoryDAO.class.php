<?php

namespace Home\DAO;

/**
 * 库存 DAO
 *
 * @author 李静波
 */
class InventoryDAO extends PSIBaseExDAO {

	/**
	 * 库存总账 - SKU细化到保质期
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function inventoryQcList($params) {
		$db = $this->db;
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$warehouseId = $params["warehouseId"];
		$code = $params["code"];
		$name = $params["name"];
		$spec = $params["spec"];
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$queryParams = [];
		$queryParams[] = $warehouseId;
		
		$sql = "select g.id, g.code, g.name, g.spec, u.name as unit_name,
					convert(v.in_count, $fmt) as in_count,
					v.in_price, v.in_money, convert(v.out_count, $fmt) as out_count, v.out_price, v.out_money,
					convert(v.balance_count, $fmt) as balance_count, v.balance_price, v.balance_money,
					v.qc_begin_dt, v.qc_end_dt, v.qc_days, v.qc_sn
				from t_inventory_fifo v, t_goods g, t_goods_unit u
				where (v.warehouse_id = '%s') and (v.goods_id = g.id) and (g.unit_id = u.id) ";
		if ($code) {
			$sql .= " and (g.code like '%s')";
			$queryParams[] = "%{$code}%";
		}
		if ($name) {
			$sql .= " and (g.name like '%s' or g.py like '%s')";
			$queryParams[] = "%{$name}%";
			$queryParams[] = "%{$name}%";
		}
		if ($spec) {
			$sql .= " and (g.spec like '%s')";
			$queryParams[] = "%{$spec}%";
		}
		$sql .= " order by g.code, v.qc_begin_dt
				limit %d, %d";
		$queryParams[] = $start;
		$queryParams[] = $limit;
		
		$data = $db->query($sql, $queryParams);
		
		$result = [];
		
		foreach ( $data as $i => $v ) {
			$result[$i]["goodsId"] = $v["id"];
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			$result[$i]["inCount"] = $v["in_count"];
			$result[$i]["inPrice"] = $v["in_price"];
			$result[$i]["inMoney"] = $v["in_money"];
			$result[$i]["outCount"] = $v["out_count"];
			$result[$i]["outPrice"] = $v["out_price"];
			$result[$i]["outMoney"] = $v["out_money"];
			$result[$i]["balanceCount"] = $v["balance_count"];
			$result[$i]["balancePrice"] = $v["balance_price"];
			$result[$i]["balanceMoney"] = $v["balance_money"];
			$result[$i]["qcBeginDT"] = $this->toQcYMD($v["qc_begin_dt"]);
			$result[$i]["qcEndDT"] = $this->toQcYMD($v["qc_end_dt"]);
			$result[$i]["qcDays"] = $this->toQcDays($v["qc_days"]);
			$result[$i]["qcSN"] = $v["qc_sn"];
		}
		
		$queryParams = [];
		$queryParams[] = $warehouseId;
		$sql = "select count(*) as cnt
				from t_inventory_fifo v, t_goods g, t_goods_unit u
				where (v.warehouse_id = '%s') and (v.goods_id = g.id) and (g.unit_id = u.id) ";
		if ($code) {
			$sql .= " and (g.code like '%s')";
			$queryParams[] = "%{$code}%";
		}
		if ($name) {
			$sql .= " and (g.name like '%s' or g.py like '%s')";
			$queryParams[] = "%{$name}%";
			$queryParams[] = "%{$name}%";
		}
		if ($spec) {
			$sql .= " and (g.spec like '%s')";
			$queryParams[] = "%{$spec}%";
		}
		
		$data = $db->query($sql, $queryParams);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	/**
	 * 库存明细账 - SKU细化到保质期
	 *
	 * @param array $params        	
	 */
	public function inventoryQcDetailList($params) {
		$db = $this->db;
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$warehouseId = $params["warehouseId"];
		$goodsId = $params["goodsId"];
		$qcBeginDT = $params["qcBeginDT"];
		if (! $qcBeginDT) {
			$qcBeginDT = "1970-01-01";
		}
		$qcDays = $params["qcDays"];
		$qcSN = $params["qcSN"];
		
		$dtFrom = $params["dtFrom"];
		$dtTo = $params["dtTo"];
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$sql = "select g.id, g.code, g.name, g.spec, u.name as unit_name,
					convert(v.in_count, $fmt) as in_count, v.in_price, v.in_money,
					convert(v.out_count, $fmt) as out_count, v.out_price, v.out_money,
					convert(v.balance_count, $fmt) as balance_count, v.balance_price, v.balance_money,
					v.biz_date,  user.name as biz_user_name, v.ref_number, v.ref_type,
					v.qc_begin_dt, v.qc_end_dt, v.qc_days, v.qc_sn
				from t_inventory_fifo_detail v, t_goods g, t_goods_unit u, t_user user
					where v.warehouse_id = '%s' and v.goods_id = '%s'
					and v.goods_id = g.id and g.unit_id = u.id
					and v.biz_user_id = user.id
					and (v.biz_date between '%s' and '%s' )
					and v.qc_begin_dt = '%s' and v.qc_days = %d and v.qc_sn = '%s'
				order by v.id
				limit %d, %d";
		$data = $db->query($sql, $warehouseId, $goodsId, $dtFrom, $dtTo, $qcBeginDT, $qcDays, $qcSN, 
				$start, $limit);
		
		$result = [];
		
		foreach ( $data as $i => $v ) {
			$result[$i]["goodsId"] = $v["id"];
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			$result[$i]["inCount"] = $v["in_count"];
			$result[$i]["inPrice"] = $v["in_price"];
			$result[$i]["inMoney"] = $v["in_money"];
			$result[$i]["outCount"] = $v["out_count"];
			$result[$i]["outPrice"] = $v["out_price"];
			$result[$i]["outMoney"] = $v["out_money"];
			$result[$i]["balanceCount"] = $v["balance_count"];
			$result[$i]["balancePrice"] = $v["balance_price"];
			$result[$i]["balanceMoney"] = $v["balance_money"];
			$result[$i]["bizDT"] = date("Y-m-d", strtotime($v["biz_date"]));
			$result[$i]["bizUserName"] = $v["biz_user_name"];
			$result[$i]["refNumber"] = $v["ref_number"];
			$result[$i]["refType"] = $v["ref_type"];
			$result[$i]["qcBeginDT"] = $this->toQcYMD($v["qc_begin_dt"]);
			$result[$i]["qcEndDT"] = $this->toQcYMD($v["qc_end_dt"]);
			$result[$i]["qcDays"] = $this->toQcDays($v["qc_days"]);
			$result[$i]["qcSN"] = $v["qc_sn"];
		}
		
		$sql = "select count(*) as cnt from t_inventory_fifo_detail v
				where v.warehouse_id = '%s' and v.goods_id = '%s' 
					and (v.biz_date between '%s' and '%s')
					and v.qc_begin_dt = '%s' and v.qc_days = %d and v.qc_sn = '%s' ";
		$data = $db->query($sql, $warehouseId, $goodsId, $dtFrom, $dtTo, $qcBeginDT, $qcDays, $qcSN);
		
		return array(
				"details" => $result,
				"totalCount" => $data[0]["cnt"]
		);
	}

	/**
	 * 入库
	 */
	public function inAction($companyId, $warehouseId, $goodsId, $qcBeginDT, $qcDays, $qcEndDT, $qcSN, $goodsCount, $goodsMoney, $goodsPrice, $bizDT, $bizUserId, $ref, $refType) {
		$db = $this->db;
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// 总账
		$balanceCount = 0;
		$balanceMoney = 0;
		$balancePrice = (float)0;
		$sql = "select convert(in_count, $fmt) as in_count, in_money, balance_count, balance_money
				from t_inventory
				where warehouse_id = '%s' and goods_id = '%s' ";
		$data = $db->query($sql, $warehouseId, $goodsId);
		if ($data) {
			// 之前已经发生过入库业务了
			$inCount = $data[0]["in_count"];
			$inMoney = floatval($data[0]["in_money"]);
			$balanceCount = $data[0]["balance_count"];
			$balanceMoney = floatval($data[0]["balance_money"]);
			
			$inCount += $goodsCount;
			$inMoney += $goodsMoney;
			$inPrice = $inMoney / $inCount;
			
			$balanceCount += $goodsCount;
			$balanceMoney += $goodsMoney;
			$balancePrice = $balanceMoney / $balanceCount;
			
			$sql = "update t_inventory
					set in_count = convert(%f, $fmt), in_price = %f, in_money = %f,
						balance_count = convert(%f, $fmt), balance_price = %f, balance_money = %f
					where warehouse_id = '%s' and goods_id = '%s' ";
			$rc = $db->execute($sql, $inCount, $inPrice, $inMoney, $balanceCount, $balancePrice, 
					$balanceMoney, $warehouseId, $goodsId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		} else {
			// 首次入库
			$inCount = $goodsCount;
			$inMoney = $goodsMoney;
			$inPrice = $inMoney / $inCount;
			$balanceCount = $goodsCount;
			$balanceMoney = $goodsMoney;
			$balancePrice = $balanceMoney / $balanceCount;
			
			$sql = "insert into t_inventory (in_count, in_price, in_money, balance_count,
						balance_price, balance_money, warehouse_id, goods_id)
					values (convert(%f, $fmt), %f, %f, convert(%f, $fmt), %f, %f, '%s', '%s')";
			$rc = $db->execute($sql, $inCount, $inPrice, $inMoney, $balanceCount, $balancePrice, 
					$balanceMoney, $warehouseId, $goodsId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 明细账
		$sql = "insert into t_inventory_detail (in_count, in_price, in_money, balance_count,
					balance_price, balance_money, warehouse_id, goods_id, biz_date,
					biz_user_id, date_created, ref_number, ref_type)
				values (convert(%f, $fmt), %f, %f, convert(%f, $fmt), %f, %f, '%s', '%s', '%s', '%s',
					now(), '%s', '%s')";
		$rc = $db->execute($sql, $goodsCount, $goodsPrice, $goodsMoney, $balanceCount, 
				$balancePrice, $balanceMoney, $warehouseId, $goodsId, $bizDT, $bizUserId, $ref, 
				$refType);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 总账 -SKU细化到保质期
		$balanceCount = 0;
		$balanceMoney = 0;
		$balancePrice = (float)0;
		
		if ($qcDays < 0) {
			$qcDays = 0;
		}
		if (! $this->dateIsValid($qcBeginDT)) {
			$qcBeginDT = "";
		}
		
		if (! $qcBeginDT) {
			// 没有保质期的SKU在数据库中存储为1970-01-01
			$qcBeginDT = "1970-01-01";
			$qcDays = 0;
			$qcEndDT = $qcBeginDT;
		} else {
			$qcEndDT = date("Y-m-d", strtotime($qcBeginDT . " +$qcDays day"));
		}
		
		// 库存总账
		$sql = "select convert(in_count, $fmt) as in_count, in_money, balance_count, balance_money
				from t_inventory_fifo
				where warehouse_id = '%s' and goods_id = '%s'
					and qc_begin_dt = '%s' and qc_days = %d and qc_sn = '%s' ";
		$data = $db->query($sql, $warehouseId, $goodsId, $qcBeginDT, $qcDays, $qcSN);
		if ($data) {
			// 已经发生过入库业务
			$inCount = $data[0]["in_count"];
			$inMoney = $data[0]["in_money"];
			$balanceCount = $data[0]["balance_count"];
			$balanceMoney = $data[0]["balance_money"];
			
			$inCount += $goodsCount;
			$inMoney += $goodsMoney;
			$inPrice = $inMoney / $inCount;
			
			$balanceCount += $goodsCount;
			$balanceMoney += $goodsMoney;
			$balancePrice = $balanceMoney / $balanceCount;
			
			$sql = "update t_inventory_fifo
					set in_count = convert(%f, $fmt), in_price = %f, in_money = %f,
						balance_count = convert(%f, $fmt), balance_price = %f, balance_money = %f
					where warehouse_id = '%s' and goods_id = '%s'
						and qc_begin_dt = '%s' and qc_days = %d and qc_sn = '%s' ";
			$rc = $db->execute($sql, $inCount, $inPrice, $inMoney, $balanceCount, $balancePrice, 
					$balanceMoney, $warehouseId, $goodsId, $qcBeginDT, $qcDays, $qcSN);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		} else {
			// 首次入库
			$inCount = $goodsCount;
			$inMoney = $goodsMoney;
			$inPrice = $inMoney / $inCount;
			$balanceCount = $goodsCount;
			$balanceMoney = $goodsMoney;
			$balancePrice = $balanceMoney / $balanceCount;
			
			$sql = "insert into t_inventory_fifo (in_count, in_price, in_money, balance_count,
						balance_price, balance_money, warehouse_id, goods_id,
						qc_begin_dt, qc_days, qc_end_dt, qc_sn)
					values (convert(%f, $fmt), %f, %f, convert(%f, $fmt), %f, %f, '%s', '%s',
						'%s', %d, '%s', '%s')";
			$rc = $db->execute($sql, $inCount, $inPrice, $inMoney, $balanceCount, $balancePrice, 
					$balanceMoney, $warehouseId, $goodsId, $qcBeginDT, $qcDays, $qcEndDT, $qcSN);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 总账 - SKU细化到保质期
		$sql = "insert into t_inventory_fifo_detail(in_count, in_price, in_money, balance_count,
					balance_price, balance_money, warehouse_id, goods_id, date_created,
					qc_begin_dt, qc_days, qc_end_dt, qc_sn,
					biz_date, biz_user_id, ref_type, ref_number)
				values (convert(%f, $fmt), %f, %f, convert(%f, $fmt),
					%f, %f, '%s', '%s', now(),
					'%s', %d, '%s', '%s',
					'%s', '%s', '%s', '%s')";
		$rc = $db->execute($sql, $goodsCount, $goodsPrice, $goodsMoney, $balanceCount, 
				$balancePrice, $balanceMoney, $warehouseId, $goodsId, $qcBeginDT, $qcDays, $qcEndDT, 
				$qcSN, $bizDT, $bizUserId, $refType, $ref);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 操作成功
		return null;
	}

	/**
	 * 出库
	 */
	public function outAction($warehouseId, $goodsId, $qcBeginDT, $qcDays, $qcEndDT, $qcSN, $goodsCount, $bizDT, $bizUserId, $ref, $refType, $outType, $recordIndex, $fmt, &$outPriceForResult, &$outMoneyForResult) {
		$db = $this->db;
		
		if ($qcDays < 0) {
			$qcDays = 0;
		}
		if (! $this->dateIsValid($qcBeginDT)) {
			$qcBeginDT = "";
		}
		
		if (! $qcBeginDT) {
			// 没有保质期的SKU在数据库中存储为1970-01-01
			$qcBeginDT = "1970-01-01";
			$qcDays = 0;
			$qcEndDT = $qcBeginDT;
		} else {
			$qcEndDT = date("Y-m-d", strtotime($qcBeginDT . " +$qcDays day"));
		}
		
		if ($outType == 0) {
			// 物资严格按保质期对应出库
			
			// 库存总账 - SKU细化到保质期
			$sql = "select convert(balance_count, $fmt) as balance_count, balance_price, balance_money,
						convert(out_count, $fmt) as out_count, out_money
					from t_inventory_fifo
					where warehouse_id = '%s' and goods_id = '%s' 
						and qc_begin_dt = '%s' and qc_days = %d and qc_sn = '%s' ";
			$data = $db->query($sql, $warehouseId, $goodsId, $qcBeginDT, $qcDays, $qcSN);
			if (! $data) {
				return $this->bad("第{$recordIndex}条物资库存不足，无法出库");
			}
			$balanceCount = $data[0]["balance_count"];
			$balancePrice = $data[0]["balance_price"];
			$balanceMoney = $data[0]["balance_money"];
			if ($goodsCount > $balanceCount) {
				return $this->bad("第{$recordIndex}条物资库存不足，无法出库");
			}
			$totalOutCount = $data[0]["out_count"];
			$totalOutMoney = $data[0]["out_money"];
			
			$outCount = $goodsCount;
			$outMoney = $balancePrice * $outCount;
			$outPrice = $balancePrice;
			
			$totalOutCount += $outCount;
			$totalOutMoney += $outMoney;
			$totalOutPrice = $totalOutMoney / $totalOutCount;
			$balanceCount -= $outCount;
			if ($balanceCount == 0) {
				// 数量余额为零，就把金额全部出库
				$outMoney = $balanceMoney;
				$balanceMoney = 0;
				$balancePrice = 0;
			} else {
				$balanceMoney -= $outMoney;
				$balancePrice = $balanceMoney / $balanceCount;
			}
			
			$sql = "update t_inventory_fifo
						set out_count = convert(%f, $fmt), out_price = %f, out_money = %f,
						balance_count = convert(%f, $fmt), balance_price = %f, balance_money = %f
					where warehouse_id = '%s' and goods_id = '%s' 
						 and qc_begin_dt = '%s' and qc_days = %d and qc_sn = '%s' ";
			$rc = $db->execute($sql, $totalOutCount, $totalOutPrice, $totalOutMoney, $balanceCount, 
					$balancePrice, $balanceMoney, $warehouseId, $goodsId, $qcBeginDT, $qcDays, $qcSN);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
			
			// 库存明细账 - SKU细化到保质期
			$sql = "insert into t_inventory_fifo_detail(out_count, out_price, out_money, balance_count,
						balance_price, balance_money, warehouse_id, goods_id, biz_date, biz_user_id,
						date_created, ref_number, ref_type, 
						qc_begin_dt, qc_end_dt, qc_days, qc_sn)
					values (convert(%f, $fmt), %f, %f, convert(%f, $fmt), 
						%f, %f, '%s', '%s', '%s', '%s', 
						now(), '%s', '%s',
						'%s', '%s', %d, '%s')";
			$rc = $db->execute($sql, $outCount, $outPrice, $outMoney, $balanceCount, $balancePrice, 
					$balanceMoney, $warehouseId, $goodsId, $bizDT, $bizUserId, $ref, $refType, 
					$qcBeginDT, $qcEndDT, $qcDays, $qcSN);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
			
			// 库存总账 - SKU物资编码，忽略保质期
			$sql = "select convert(balance_count, $fmt) as balance_count, balance_price, balance_money,
						convert(out_count, $fmt) as out_count, out_money
					from t_inventory
					where warehouse_id = '%s' and goods_id = '%s' ";
			$data = $db->query($sql, $warehouseId, $goodsId);
			if (! $data) {
				return $this->bad("第{$recordIndex}条物资库存不足，无法出库");
			}
			$balanceCount = $data[0]["balance_count"];
			$balancePrice = $data[0]["balance_price"];
			$balanceMoney = $data[0]["balance_money"];
			if ($goodsCount > $balanceCount) {
				return $this->bad("第{$recordIndex}条物资不足，无法出库");
			}
			$totalOutCount = $data[0]["out_count"];
			$totalOutMoney = $data[0]["out_money"];
			
			$outCount = $goodsCount;
			$outMoney = $balancePrice * $outCount;
			$outPrice = $balancePrice;
			
			$totalOutCount += $outCount;
			$totalOutMoney += $outMoney;
			$totalOutPrice = $totalOutMoney / $totalOutCount;
			$balanceCount -= $outCount;
			if ($balanceCount == 0) {
				$outMoney = $balanceMoney;
				$balanceMoney = 0;
				$balancePrice = 0;
			} else {
				$balanceMoney -= $outMoney;
				$balancePrice = $balanceMoney / $balanceCount;
			}
			
			$sql = "update t_inventory
					set out_count = convert(%f, $fmt), out_price = %f, out_money = %f,
						balance_count = convert(%f, $fmt), balance_price = %f, balance_money = %f
					where warehouse_id = '%s' and goods_id = '%s' ";
			$rc = $db->execute($sql, $totalOutCount, $totalOutPrice, $totalOutMoney, $balanceCount, 
					$balancePrice, $balanceMoney, $warehouseId, $goodsId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
			
			// 库存明细账 - SKU为物资编码，忽略保质期
			$sql = "insert into t_inventory_detail(out_count, out_price, out_money, balance_count,
						balance_price, balance_money, warehouse_id, goods_id, biz_date, biz_user_id,
						date_created, ref_number, ref_type)
					values (convert(%f, $fmt), %f, %f, convert(%f, $fmt),
						%f, %f, '%s', '%s', '%s', '%s',
						now(), '%s', '%s')";
			$rc = $db->execute($sql, $outCount, $outPrice, $outMoney, $balanceCount, $balancePrice, 
					$balanceMoney, $warehouseId, $goodsId, $bizDT, $bizUserId, $ref, $refType);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
			
			// 操作成功
			$outMoneyForResult = $outMoney;
			$outPriceForResult = $outPrice;
			return null;
		} else if ($outType == 1) {
			// 物资按编码对应先进先出法出库(忽略保质期)
			
			// 库存总账 - SKU物资编码，忽略保质期
			$sql = "select convert(balance_count, $fmt) as balance_count, balance_price, balance_money,
						convert(out_count, $fmt) as out_count, out_money
					from t_inventory
					where warehouse_id = '%s' and goods_id = '%s' ";
			$data = $db->query($sql, $warehouseId, $goodsId);
			if (! $data) {
				return $this->bad("第{$recordIndex}条物资库存不足，无法出库");
			}
			$balanceCount = $data[0]["balance_count"];
			$balancePrice = $data[0]["balance_price"];
			$balanceMoney = $data[0]["balance_money"];
			if ($goodsCount > $balanceCount) {
				return $this->bad("第{$recordIndex}条商品库存不足，无法出库");
			}
			$totalOutCount = $data[0]["out_count"];
			$totalOutMoney = $data[0]["out_money"];
			
			$outCount = $goodsCount;
			$outMoney = $balancePrice * $outCount;
			$outPrice = $balancePrice;
			
			$outMoneyForResult = $outMoney;
			$outPriceForResult = $outPrice;
			
			$totalOutCount += $outCount;
			$totalOutMoney += $outMoney;
			$totalOutPrice = $totalOutMoney / $totalOutCount;
			$balanceCount -= $outCount;
			if ($balanceCount == 0) {
				$outMoney = $balanceMoney;
				$balanceMoney = 0;
				$balancePrice = 0;
			} else {
				$balanceMoney -= $outMoney;
				$balancePrice = $balanceMoney / $balanceCount;
			}
			
			$sql = "update t_inventory
					set out_count = convert(%f, $fmt), out_price = %f, out_money = %f,
						balance_count = convert(%f, $fmt), balance_price = %f, balance_money = %f
					where warehouse_id = '%s' and goods_id = '%s' ";
			$rc = $db->execute($sql, $totalOutCount, $totalOutPrice, $totalOutMoney, $balanceCount, 
					$balancePrice, $balanceMoney, $warehouseId, $goodsId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
			
			// 库存明细账 - SKU为物资编码，忽略保质期
			$sql = "insert into t_inventory_detail(out_count, out_price, out_money, balance_count,
						balance_price, balance_money, warehouse_id, goods_id, biz_date, biz_user_id,
						date_created, ref_number, ref_type)
					values (convert(%f, $fmt), %f, %f, convert(%f, $fmt),
						%f, %f, '%s', '%s', '%s', '%s',
						now(), '%s', '%s')";
			$rc = $db->execute($sql, $outCount, $outPrice, $outMoney, $balanceCount, $balancePrice, 
					$balanceMoney, $warehouseId, $goodsId, $bizDT, $bizUserId, $ref, $refType);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
			
			// 把SKU为保质期的库存账，按先进先出法出库
			$leftOutCount = $goodsCount;
			while ( $leftOutCount > 0 ) {
				// 查找最先入库的，库存不为0的物资
				$sql = "select convert(balance_count, $fmt) as balance_count, balance_price, balance_money,
							convert(out_count, $fmt) as out_count, out_money, qc_begin_dt, qc_end_dt, qc_days, qc_sn
						from t_inventory_fifo_detail
						where warehouse_id = '%s' and goods_id = '%s' and balance_count > 0
						order by qc_begin_dt
						limit 1";
				$data = $db->query($sql, $warehouseId, $goodsId);
				if (! $data) {
					return $this->bad("第{$recordIndex}条物资库存不足，无法出库");
				}
				$balanceCount = $data[0]["balance_count"];
				$balancePrice = $data[0]["balance_price"];
				$balanceMoney = $data[0]["balance_money"];
				$totalOutCount = $data[0]["out_count"];
				$totalOutMoney = $data[0]["out_money"];
				$qcBeginDT = $this->toQcYMD($data[0]["qc_begin_dt"]);
				if (! $qcBeginDT) {
					$qcBeginDT = "1970-01-01";
				}
				$qcEndDT = $this->toQcYMD($data[0]["qc_end_dt"]);
				if (! $qcEndDT) {
					$qcEndDT = "1970-01-01";
				}
				$qcDays = $data[0]["qc_days"];
				$qcSN = $data[0]["qc_sn"];
				
				$outCount = 0;
				if ($leftOutCount <= $balanceCount) {
					// 当前保质期物资的库存数已经够出库了
					$outCount = $leftOutCount;
					$leftOutCount = 0;
					$balanceCount -= $outCount;
				} else {
					// 当前保质期物资即使全部出库，也仍然还不够，需要再依次出库下一个保质期的物资
					$outCount = $balanceCount;
					$leftOutCount = $leftOutCount - $balanceCount;
					$balanceCount = 0;
				}
				
				$outMoney = $balancePrice * $outCount;
				$outPrice = $balancePrice;
				
				if ($balanceCount == 0) {
					$outMoney = $balanceMoney;
					$balanceMoney = 0;
					$balancePrice = 0;
				} else {
					$balanceMoney -= $outMoney;
					$balancePrice = $balanceMoney / $balanceCount;
				}
				
				$totalOutCount += $outCount;
				$totalOutMoney += $outMoney;
				$totalOutPrice = $totalOutMoney / $totalOutCount;
				
				// 总账
				$sql = "update t_inventory_fifo
							set out_count = convert(%f, $fmt), out_price = %f, out_money = %f,
								balance_count = convert(%f, $fmt), balance_price = %f, balance_money = %f
						where warehouse_id = '%s' and goods_id = '%s'
							and qc_begin_dt = '%s' and qc_days = %d and qc_sn = '%s' ";
				$rc = $db->execute($sql, $totalOutCount, $totalOutPrice, $totalOutMoney, 
						$balanceCount, $balancePrice, $balanceMoney, $warehouseId, $goodsId, 
						$qcBeginDT, $qcDays, $qcSN);
				if ($rc === false) {
					return $this->sqlError(__METHOD__, __LINE__);
				}
				
				// 明细账
				$sql = "insert into t_inventory_fifo_detail(out_count, out_price, out_money, balance_count,
							balance_price, balance_money, warehouse_id, goods_id, biz_date, biz_user_id,
							date_created, ref_number, ref_type,
							qc_begin_dt, qc_end_dt, qc_days, qc_sn)
						values (convert(%f, $fmt), %f, %f, convert(%f, $fmt),
							%f, %f, '%s', '%s', '%s', '%s',
							now(), '%s', '%s',
							'%s', '%s', %d, '%s')";
				$rc = $db->execute($sql, $outCount, $outPrice, $outMoney, $balanceCount, 
						$balancePrice, $balanceMoney, $warehouseId, $goodsId, $bizDT, $bizUserId, 
						$ref, $refType, $qcBeginDT, $qcEndDT, $qcDays, $qcSN);
				if ($rc === false) {
					return $this->sqlError(__METHOD__, __LINE__);
				}
				
				// 如果$leftOutCount > 0 就会进入下一个循环
			} // end of while ( $leftOutCount > 0 )
			  
			// 操作成功
			return null;
		} else {
			return $this->badParam("outType");
		}
	}
}