<?php

namespace Home\DAO;

use Home\Common\FIdConst;

/**
 * 采购入库单 DAO
 *
 * @author 李静波
 */
class PWBillDAO extends PSIBaseExDAO {

	/**
	 * 生成新的采购入库单单号
	 *
	 * @param string $companyId        	
	 * @return string
	 */
	private function genNewBillRef($companyId) {
		$db = $this->db;
		
		$bs = new BizConfigDAO($db);
		
		// 取单号前缀
		$pre = $bs->getPWBillRefPre($companyId);
		
		$mid = date("Ymd");
		
		$sql = "select ref from t_pw_bill where ref like '%s' order by ref desc limit 1";
		$data = $db->query($sql, $pre . $mid . "%");
		$suf = "001";
		if ($data) {
			$ref = $data[0]["ref"];
			$nextNumber = intval(substr($ref, strlen($pre . $mid))) + 1;
			$suf = str_pad($nextNumber, 3, "0", STR_PAD_LEFT);
		}
		
		return $pre . $mid . $suf;
	}

	/**
	 * 单据状态标志转化为文字
	 *
	 * @param int $code        	
	 * @return string
	 */
	private function billStatusCodeToName($code) {
		switch ($code) {
			case 0 :
				return "待入库";
			case 1000 :
				return "已入库";
			case 2000 :
				return "已退货";
			default :
				return "";
		}
	}

	/**
	 * 获得采购入库单主表列表
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function pwbillList($params) {
		$db = $this->db;
		
		$start = $params["start"];
		$limit = $params["limit"];
		
		// 订单状态
		$billStatus = $params["billStatus"];
		
		// 单号
		$ref = $params["ref"];
		
		// 业务日期 -起
		$fromDT = $params["fromDT"];
		// 业务日期-止
		$toDT = $params["toDT"];
		
		// 仓库id
		$warehouseId = $params["warehouseId"];
		
		// 供应商id
		$supplierId = $params["supplierId"];
		
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->emptyResult();
		}
		
		$queryParams = [];
		$sql = "select p.id, p.bill_status, p.ref, p.biz_dt, u1.name as biz_user_name, u2.name as input_user_name,
					p.goods_money, w.name as warehouse_name, s.name as supplier_name,
					p.date_created, p.payment_type, p.bill_memo
				from t_pw_bill p, t_warehouse w, t_supplier s, t_user u1, t_user u2
				where (p.warehouse_id = w.id) and (p.supplier_id = s.id)
				and (p.biz_user_id = u1.id) and (p.input_user_id = u2.id) ";
		
		$ds = new DataOrgDAO($db);
		// 构建数据域SQL
		$rs = $ds->buildSQL(FIdConst::PURCHASE_WAREHOUSE, "p", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParams = $rs[1];
		}
		
		if ($billStatus != - 1) {
			$sql .= " and (p.bill_status = %d) ";
			$queryParams[] = $billStatus;
		}
		if ($ref) {
			$sql .= " and (p.ref like '%s') ";
			$queryParams[] = "%{$ref}%";
		}
		if ($fromDT) {
			$sql .= " and (p.biz_dt >= '%s') ";
			$queryParams[] = $fromDT;
		}
		if ($toDT) {
			$sql .= " and (p.biz_dt <= '%s') ";
			$queryParams[] = $toDT;
		}
		if ($supplierId) {
			$sql .= " and (p.supplier_id = '%s') ";
			$queryParams[] = $supplierId;
		}
		if ($warehouseId) {
			$sql .= " and (p.warehouse_id = '%s') ";
			$queryParams[] = $warehouseId;
		}
		
		$sql .= " order by p.biz_dt desc, p.ref desc
				limit %d, %d";
		$queryParams[] = $start;
		$queryParams[] = $limit;
		$data = $db->query($sql, $queryParams);
		$result = [];
		
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"ref" => $v["ref"],
					"bizDate" => $this->toYMD($v["biz_dt"]),
					"supplierName" => $v["supplier_name"],
					"warehouseName" => $v["warehouse_name"],
					"inputUserName" => $v["input_user_name"],
					"bizUserName" => $v["biz_user_name"],
					"billStatus" => $this->billStatusCodeToName($v["bill_status"]),
					"amount" => $v["goods_money"],
					"dateCreated" => $v["date_created"],
					"paymentType" => $v["payment_type"],
					"billMemo" => $v["bill_memo"]
			];
		}
		
		$sql = "select count(*) as cnt
				from t_pw_bill p, t_warehouse w, t_supplier s, t_user u1, t_user u2
				where (p.warehouse_id = w.id) and (p.supplier_id = s.id)
				and (p.biz_user_id = u1.id) and (p.input_user_id = u2.id)";
		$queryParams = [];
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::PURCHASE_WAREHOUSE, "p", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParams = $rs[1];
		}
		if ($billStatus != - 1) {
			$sql .= " and (p.bill_status = %d) ";
			$queryParams[] = $billStatus;
		}
		if ($ref) {
			$sql .= " and (p.ref like '%s') ";
			$queryParams[] = "%{$ref}%";
		}
		if ($fromDT) {
			$sql .= " and (p.biz_dt >= '%s') ";
			$queryParams[] = $fromDT;
		}
		if ($toDT) {
			$sql .= " and (p.biz_dt <= '%s') ";
			$queryParams[] = $toDT;
		}
		if ($supplierId) {
			$sql .= " and (p.supplier_id = '%s') ";
			$queryParams[] = $supplierId;
		}
		if ($warehouseId) {
			$sql .= " and (p.warehouse_id = '%s') ";
			$queryParams[] = $warehouseId;
		}
		
		$data = $db->query($sql, $queryParams);
		$cnt = $data[0]["cnt"];
		
		return [
				"dataList" => $result,
				"totalCount" => $cnt
		];
	}

	/**
	 * 获得采购入库单商品明细记录列表
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function pwBillDetailList($params) {
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		
		$db = $this->db;
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// 采购入库单id
		$pwbillId = $params["id"];
		
		$sql = "select p.id, g.code, g.name, g.spec, u.name as unit_name, 
					convert(p.goods_count, $fmt) as goods_count, p.goods_price,
					p.goods_money, p.memo, p.factor, p.factor_type, 
					convert(p.sku_goods_count, $fmt) as sku_goods_count,
					p.qc_begin_dt, p.qc_end_dt, p.qc_days, p.qc_sn, u2.name as sku_unit_name
				from t_pw_bill_detail p, t_goods g, t_goods_unit u, t_goods_unit u2
				where p.pwbill_id = '%s' and p.goods_id = g.id and p.unit_id = u.id and p.sku_goods_unit_id = u2.id
				order by p.show_order ";
		$data = $db->query($sql, $pwbillId);
		$result = [];
		
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"unitName" => $v["unit_name"],
					"goodsCount" => $v["goods_count"],
					"goodsMoney" => $v["goods_money"],
					"goodsPrice" => $v["goods_price"],
					"memo" => $v["memo"],
					"factor" => $v["factor"],
					"factorType" => $v["factor_type"],
					"skuUnitName" => $v["sku_unit_name"],
					"skuGoodsCount" => $v["sku_goods_count"],
					"qcBeginDT" => $this->toQcYMD($v["qc_begin_dt"]),
					"qcEndDT" => $this->toQcYMD($v["qc_end_dt"]),
					"qcDays" => $this->toQcDays($v["qc_days"]),
					"qcSN" => $v["qc_sn"]
			];
		}
		
		return $result;
	}

	/**
	 * 新建采购入库单
	 *
	 * @param array $bill        	
	 * @return NULL|array
	 */
	public function addPWBill(& $bill) {
		$db = $this->db;
		
		// 业务日期
		$bizDT = $bill["bizDT"];
		
		// 仓库id
		$warehouseId = $bill["warehouseId"];
		
		// 供应商id
		$supplierId = $bill["supplierId"];
		
		// 业务员id
		$bizUserId = $bill["bizUserId"];
		
		// 单据备注
		$billMemo = $bill["billMemo"];
		
		// 采购订单主表id
		$pobillId = $bill["pobillId"];
		
		$warehouseDAO = new WarehouseDAO($db);
		$warehouse = $warehouseDAO->getWarehouseById($warehouseId);
		if (! $warehouse) {
			return $this->bad("入库仓库不存在");
		}
		
		$supplierDAO = new SupplierDAO($db);
		$supplier = $supplierDAO->getSupplierById($supplierId);
		if (! $supplier) {
			return $this->bad("供应商不存在");
		}
		
		$userDAO = new UserDAO($db);
		$user = $userDAO->getUserById($bizUserId);
		if (! $user) {
			return $this->bad("业务人员不存在");
		}
		
		// 检查业务日期
		if (! $this->dateIsValid($bizDT)) {
			return $this->bad("业务日期不正确");
		}
		
		$loginUserId = $bill["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->badParam("loginUserId");
		}
		
		$dataOrg = $bill["dataOrg"];
		if ($this->dataOrgNotExists($dataOrg)) {
			return $this->badParam("dataOrg");
		}
		
		$companyId = $bill["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$ref = $this->genNewBillRef($companyId);
		
		$id = $this->newId();
		
		// 应付账款
		$paymentType = 0;
		
		// 主表
		$sql = "insert into t_pw_bill (id, ref, supplier_id, warehouse_id, biz_dt,
				biz_user_id, bill_status, date_created, goods_money, input_user_id, payment_type,
				data_org, company_id, bill_memo)
				values ('%s', '%s', '%s', '%s', '%s', '%s', 0, now(), 0, '%s', %d, '%s', '%s', '%s')";
		
		$rc = $db->execute($sql, $id, $ref, $supplierId, $warehouseId, $bizDT, $bizUserId, 
				$loginUserId, $paymentType, $dataOrg, $companyId, $billMemo);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$goodsDAO = new GoodsDAO($db);
		
		$goodsUnitDAO = new GoodsUnitDAO($db);
		
		// 明细记录
		$items = $bill["items"];
		foreach ( $items as $i => $item ) {
			// 商品id
			$goodsId = $item["goodsId"];
			if ($goodsId == null) {
				continue;
			}
			
			// 检查商品是否存在
			$goods = $goodsDAO->getGoodsById($goodsId);
			if (! $goods) {
				return $this->bad("选择的商品不存在");
			}
			
			// 关于入库数量为什么允许填写0：
			// 当由采购订单生成采购入库单的时候，采购订单中有多种商品，但是是部分到货
			// 那么就存在有些商品的数量是0的情形。
			$goodsCount = $item["goodsCount"];
			if ($goodsCount < 0) {
				return $this->bad("入库数量不能是负数");
			}
			
			// 入库单明细记录的备注
			$memo = $item["memo"];
			
			// 采购单价
			$goodsPrice = $item["goodsPrice"];
			
			// 采购金额
			$goodsMoney = $item["goodsMoney"];
			
			// 采购订单明细记录id
			$poBillDetailId = $item["poBillDetailId"];
			
			// 采购计量单位id
			$unitId = $item["unitId"];
			// 转换率
			$factor = $item["factor"];
			// 转换率类型
			$factorType = $item["factorType"];
			// 转换后的入库数量
			$skuGoodsCount = $item["skuGoodsCount"];
			$skuUnitId = $item["skuUnitId"];
			// 生产日期
			$qcBeginDT = $item["qcBeginDT"];
			// 到期日期
			$qcEndDT = $item["qcEndDT"];
			// 保质期
			$qcDays = $item["qcDays"];
			// 批号
			$qcSN = $item["qcSN"];
			$sql = "select use_qc from t_goods where id = '%s' ";
			$data = $db->query($sql, $goodsId);
			$useQC = $data[0]["use_qc"];
			if ($useQC) {
				// 启用保质期
				if (! $this->toQcYMD($qcBeginDT)) {
					$recordIndex = $i + 1;
					return $this->bad("第{$recordIndex}条记录中，没有录入保质期");
				}
			}
			
			if (! $poBillDetailId) {
				// 零星采购入库单
				$goodsUnitDAO->updateGoodsUnitDefault($goodsId, $unitId, "t_pw_bill");
			}
			
			$sql = "insert into t_pw_bill_detail
					(id, date_created, goods_id, goods_count, goods_price,
					goods_money,  pwbill_id, show_order, data_org, memo, company_id,
					pobilldetail_id, unit_id, factor, factor_type, sku_goods_count, sku_goods_unit_id,
					qc_begin_dt, qc_end_dt, qc_days, qc_sn, rej_goods_count, rev_goods_count)
					values ('%s', now(), '%s', convert(%f, $fmt), %f, %f, '%s', %d, '%s', '%s', '%s', '%s',
						'%s', %f, %d, %f, '%s', '%s', '%s', %d, '%s', 0, convert(%f, $fmt))";
			$rc = $db->execute($sql, $this->newId(), $goodsId, $goodsCount, $goodsPrice, 
					$goodsMoney, $id, $i, $dataOrg, $memo, $companyId, $poBillDetailId, $unitId, 
					$factor, $factorType, $skuGoodsCount, $skuUnitId, $qcBeginDT, $qcEndDT, $qcDays, 
					$qcSN, $goodsCount);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 同步入库单主表中的采购金额合计值
		$sql = "select sum(goods_money) as goods_money from t_pw_bill_detail
				where pwbill_id = '%s' ";
		$data = $db->query($sql, $id);
		$totalMoney = $data[0]["goods_money"];
		if (! $totalMoney) {
			$totalMoney = 0;
		}
		$sql = "update t_pw_bill
				set goods_money = %f
				where id = '%s' ";
		$rc = $db->execute($sql, $totalMoney, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		if ($pobillId) {
			// 从采购订单生成采购入库单
			$sql = "select company_id from t_po_bill where id = '%s' ";
			$data = $db->query($sql, $pobillId);
			if (! $data) {
				// 传入了不存在的采购订单单号
				return $this->sqlError(__METHOD__, __LINE__);
			}
			$companyId = $data[0]["company_id"];
			
			$sql = "update t_pw_bill
					set company_id = '%s'
					where id = '%s' ";
			$rc = $db->execute($sql, $companyId, $id);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
			
			// 关联采购订单和采购入库单
			$sql = "insert into t_po_pw(po_id, pw_id) values('%s', '%s')";
			$rc = $db->execute($sql, $pobillId, $id);
			if (! $rc) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		$bill["id"] = $id;
		$bill["ref"] = $ref;
		
		// 操作成功
		return null;
	}

	/**
	 * 编辑采购入库单
	 *
	 * @param array $bill        	
	 * @return NULL|array
	 */
	public function updatePWBill(& $bill) {
		$db = $this->db;
		
		// 采购入库单id
		$id = $bill["id"];
		
		// 业务日期
		$bizDT = $bill["bizDT"];
		
		// 仓库id
		$warehouseId = $bill["warehouseId"];
		
		// 供应商id
		$supplierId = $bill["supplierId"];
		
		// 业务员id
		$bizUserId = $bill["bizUserId"];
		
		// 采购入库单备注
		$billMemo = $bill["billMemo"];
		
		$warehouseDAO = new WarehouseDAO($db);
		$warehouse = $warehouseDAO->getWarehouseById($warehouseId);
		if (! $warehouse) {
			return $this->bad("入库仓库不存在");
		}
		
		$supplierDAO = new SupplierDAO($db);
		$supplier = $supplierDAO->getSupplierById($supplierId);
		if (! $supplier) {
			return $this->bad("供应商不存在");
		}
		
		$userDAO = new UserDAO($db);
		$user = $userDAO->getUserById($bizUserId);
		if (! $user) {
			return $this->bad("业务人员不存在");
		}
		
		// 检查业务日期
		if (! $this->dateIsValid($bizDT)) {
			return $this->bad("业务日期不正确");
		}
		
		$oldBill = $this->getPWBillById($id);
		if (! $oldBill) {
			return $this->bad("要编辑的采购入库单不存在");
		}
		$dataOrg = $oldBill["dataOrg"];
		$billStatus = $oldBill["billStatus"];
		$companyId = $oldBill["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$ref = $oldBill["ref"];
		if ($billStatus != 0) {
			return $this->bad("当前采购入库单已经提交入库，不能再编辑");
		}
		$bill["ref"] = $ref;
		
		// 编辑单据的时候，先删除原来的明细记录，再新增明细记录
		$sql = "delete from t_pw_bill_detail where pwbill_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$goodsDAO = new GoodsDAO($db);
		$goodsUnitDAO = new GoodsUnitDAO($db);
		
		// 明细记录
		$items = $bill["items"];
		foreach ( $items as $i => $item ) {
			// 商品id
			$goodsId = $item["goodsId"];
			
			if ($goodsId == null) {
				continue;
			}
			
			$goods = $goodsDAO->getGoodsById($goodsId);
			if (! $goods) {
				return $this->bad("选择的商品不存在");
			}
			
			// 关于入库数量为什么允许填写0：
			// 当由采购订单生成采购入库单的时候，采购订单中有多种商品，但是是部分到货
			// 那么就存在有些商品的数量是0的情形。
			$goodsCount = $item["goodsCount"];
			if ($goodsCount < 0) {
				return $this->bad("入库数量不能是负数");
			}
			
			// 入库明细记录的备注
			$memo = $item["memo"];
			
			// 采购单价
			$goodsPrice = $item["goodsPrice"];
			
			// 采购金额
			$goodsMoney = $item["goodsMoney"];
			
			// 采购订单明细记录id
			$poBillDetailId = $item["poBillDetailId"];
			
			// 采购计量单位id
			$unitId = $item["unitId"];
			// 转换率
			$factor = $item["factor"];
			// 转换率类型
			$factorType = $item["factorType"];
			// 转换后的入库数量
			$skuGoodsCount = $item["skuGoodsCount"];
			$skuUnitId = $item["skuUnitId"];
			// 生产日期
			$qcBeginDT = $item["qcBeginDT"];
			// 到期日期
			$qcEndDT = $item["qcEndDT"];
			// 保质期
			$qcDays = $item["qcDays"];
			// 批号
			$qcSN = $item["qcSN"];
			$sql = "select use_qc from t_goods where id = '%s' ";
			$data = $db->query($sql, $goodsId);
			$useQC = $data[0]["use_qc"];
			if ($useQC) {
				// 启用保质期
				if (! $this->toQcYMD($qcBeginDT)) {
					$recordIndex = $i + 1;
					return $this->bad("第{$recordIndex}条记录中，没有录入保质期");
				}
			}
			
			if (! $poBillDetailId) {
				// 零星采购入库单
				$goodsUnitDAO->updateGoodsUnitDefault($goodsId, $unitId, "t_pw_bill");
			}
			
			$sql = "insert into t_pw_bill_detail
						(id, date_created, goods_id, goods_count, goods_price,
						goods_money,  pwbill_id, show_order, data_org, memo, company_id,
						pobilldetail_id, unit_id, factor, factor_type, sku_goods_count, sku_goods_unit_id,
						qc_begin_dt, qc_end_dt, qc_days, qc_sn, rej_goods_count, rev_goods_count)
					values ('%s', now(), '%s', convert(%f, $fmt), %f, %f, '%s', %d, '%s', '%s', '%s', '%s',
						'%s', %f, %d, %f, '%s', '%s', '%s', %d, '%s', 0, convert(%f, $fmt))";
			$rc = $db->execute($sql, $this->newId(), $goodsId, $goodsCount, $goodsPrice, 
					$goodsMoney, $id, $i, $dataOrg, $memo, $companyId, $poBillDetailId, $unitId, 
					$factor, $factorType, $skuGoodsCount, $skuUnitId, $qcBeginDT, $qcEndDT, $qcDays, 
					$qcSN, $goodsCount);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 同步主表数据
		$sql = "select sum(goods_money) as goods_money from t_pw_bill_detail
				where pwbill_id = '%s' ";
		$data = $db->query($sql, $id);
		$totalMoney = $data[0]["goods_money"];
		if (! $totalMoney) {
			$totalMoney = 0;
		}
		$sql = "update t_pw_bill
				set goods_money = %f, warehouse_id = '%s',
					supplier_id = '%s', biz_dt = '%s',
					biz_user_id = '%s',
					bill_memo = '%s'
				where id = '%s' ";
		$rc = $db->execute($sql, $totalMoney, $warehouseId, $supplierId, $bizDT, $bizUserId, 
				$billMemo, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 操作成功
		return null;
	}

	/**
	 * 通过id查询采购入库单
	 *
	 * @param string $id
	 *        	采购入库单id
	 * @return NULL|array
	 */
	public function getPWBillById($id) {
		$db = $this->db;
		
		$sql = "select ref, bill_status, data_org, company_id, warehouse_id 
				from t_pw_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return null;
		} else {
			return [
					"ref" => $data[0]["ref"],
					"billStatus" => $data[0]["bill_status"],
					"dataOrg" => $data[0]["data_org"],
					"companyId" => $data[0]["company_id"],
					"warehouseId" => $data[0]["warehouse_id"]
			];
		}
	}

	/**
	 * 同步在途库存
	 *
	 * @param array $bill        	
	 * @return NULL|array
	 */
	public function updateAfloatInventoryByPWBill(& $bill) {
		$db = $this->db;
		
		// 采购入库单id
		$id = $bill["id"];
		
		// 仓库id
		$warehouseId = $bill["warehouseId"];
		
		// 公司id
		$companyId = $bill["companyId"];
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$sql = "select goods_id
				from t_pw_bill_detail
				where pwbill_id = '%s'
				order by show_order";
		$data = $db->query($sql, $id);
		foreach ( $data as $v ) {
			$goodsId = $v["goods_id"];
			
			$rc = $this->updateAfloatInventory($db, $warehouseId, $goodsId, $fmt);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		return null;
	}

	private function updateAfloatInventory($db, $warehouseId, $goodsId, $fmt) {
		$sql = "select sum(convert(pd.goods_count, $fmt)) as goods_count, 
						sum(convert(pd.goods_money, $fmt)) as goods_money
				from t_pw_bill p, t_pw_bill_detail pd
				where p.id = pd.pwbill_id
					and p.warehouse_id = '%s'
					and pd.goods_id = '%s'
					and p.bill_status = 0 ";
		
		$data = $db->query($sql, $warehouseId, $goodsId);
		$count = 0;
		$price = 0;
		$money = 0;
		if ($data) {
			$count = $data[0]["goods_count"];
			if (! $count) {
				$count = 0;
			}
			$money = $data[0]["goods_money"];
			if (! $money) {
				$money = 0;
			}
			
			if ($count !== 0) {
				$price = $money / $count;
			}
		}
		
		$sql = "select id from t_inventory where warehouse_id = '%s' and goods_id = '%s' ";
		$data = $db->query($sql, $warehouseId, $goodsId);
		if (! $data) {
			// 首次有库存记录
			$sql = "insert into t_inventory (warehouse_id, goods_id, afloat_count, afloat_price,
						afloat_money, balance_count, balance_price, balance_money)
					values ('%s', '%s', convert(%f, $fmt), %f, %f, 0, 0, 0)";
			return $db->execute($sql, $warehouseId, $goodsId, $count, $price, $money);
		} else {
			$sql = "update t_inventory
					set afloat_count = convert(%f, $fmt), afloat_price = %f, afloat_money = %f
					where warehouse_id = '%s' and goods_id = '%s' ";
			return $db->execute($sql, $count, $price, $money, $warehouseId, $goodsId);
		}
		
		return true;
	}

	/**
	 * 获得某个采购入库单的信息
	 *
	 * @param array $params        	
	 *
	 * @return array
	 */
	public function pwBillInfo($params) {
		$db = $this->db;
		
		// 公司id
		$companyId = $params["companyId"];
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// id: 采购入库单id
		$id = $params["id"];
		// pobillRef: 采购订单单号，可以为空，为空表示直接录入采购入库单；不为空表示是从采购订单生成入库单
		$pobillRef = $params["pobillRef"];
		
		$result = [
				"id" => $id
		];
		
		$sql = "select p.ref, p.bill_status, p.supplier_id, s.name as supplier_name,
				p.warehouse_id, w.name as  warehouse_name,
				p.biz_user_id, u.name as biz_user_name, p.biz_dt, p.payment_type,
				p.bill_memo
				from t_pw_bill p, t_supplier s, t_warehouse w, t_user u
				where p.id = '%s' and p.supplier_id = s.id and p.warehouse_id = w.id
				  and p.biz_user_id = u.id";
		$data = $db->query($sql, $id);
		if ($data) {
			$v = $data[0];
			$result["ref"] = $v["ref"];
			$result["billStatus"] = $v["bill_status"];
			$result["supplierId"] = $v["supplier_id"];
			$result["supplierName"] = $v["supplier_name"];
			$result["warehouseId"] = $v["warehouse_id"];
			$result["warehouseName"] = $v["warehouse_name"];
			$result["bizUserId"] = $v["biz_user_id"];
			$result["bizUserName"] = $v["biz_user_name"];
			$result["bizDT"] = $this->toYMD($v["biz_dt"]);
			$result["billMemo"] = $v["bill_memo"];
			
			// 采购的商品明细
			$items = [];
			$sql = "select p.id, p.goods_id, g.code, g.name, g.spec, u.name as unit_name, u.id as unit_id,
						convert(p.goods_count, $fmt) as goods_count, p.goods_price, p.goods_money, p.memo,
						p.pobilldetail_id, p.qc_begin_dt, p.qc_end_dt, p.qc_days, p.qc_sn,
						convert(p.sku_goods_count, $fmt) as sku_goods_count,p.factor, p.factor_type,
						u2.id as sku_unit_id, u2.name as sku_unit_name, g.use_qc
					from t_pw_bill_detail p, t_goods g, t_goods_unit u, t_goods_unit u2
					where p.goods_Id = g.id and p.unit_id = u.id and p.pwbill_id = '%s'
					order by p.show_order";
			$data = $db->query($sql, $id);
			foreach ( $data as $v ) {
				$items[] = [
						"id" => $v["id"],
						"goodsId" => $v["goods_id"],
						"goodsCode" => $v["code"],
						"goodsName" => $v["name"],
						"goodsSpec" => $v["spec"],
						"unitId" => $v["unit_id"],
						"unitName" => $v["unit_name"],
						"goodsCount" => $v["goods_count"],
						"goodsPrice" => $v["goods_price"],
						"goodsMoney" => $v["goods_money"],
						"memo" => $v["memo"],
						"poBillDetailId" => $v["pobilldetail_id"],
						"qcBeginDT" => $this->toQcYMD($v["qc_begin_dt"]),
						"qcEndDT" => $this->toQcYMD($v["qc_end_dt"]),
						"qcDays" => $this->toQcYMD($v["qc_days"]),
						"qcSN" => $v["qc_sn"],
						"useQC" => $v["use_qc"],
						"factor" => $v["factor"],
						"factorType" => $v["factor_type"],
						"skuUnitId" => $v["sku_unit_id"],
						"skuUnitName" => $v["sku_unit_name"],
						"skuGoodsCount" => $v["sku_goods_count"]
				];
			}
			
			$result["items"] = $items;
			
			// 查询该单据是否是由采购订单生成的
			$sql = "select po_id from t_po_pw where pw_id = '%s' ";
			$data = $db->query($sql, $id);
			if ($data) {
				$result["genBill"] = true;
			} else {
				$result["genBill"] = false;
			}
		} else {
			// 新建采购入库单
			$result["bizUserId"] = $params["loginUserId"];
			$result["bizUserName"] = $params["loginUserName"];
			
			$tc = new BizConfigDAO($db);
			$companyId = $params["companyId"];
			
			$warehouse = $tc->getPWBillDefaultWarehouse($companyId);
			if ($warehouse) {
				$result["warehouseId"] = $warehouse["id"];
				$result["warehouseName"] = $warehouse["name"];
			}
		}
		
		return $result;
	}

	/**
	 * 删除采购入库单
	 *
	 * @param array $params        	
	 * @return NULL|array
	 */
	public function deletePWBill(& $params) {
		$db = $this->db;
		
		// 采购入库单id
		$id = $params["id"];
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		$bill = $this->getPWBillById($id);
		if (! $bill) {
			return $this->bad("要删除的采购入库单不存在");
		}
		
		// 单号
		$ref = $bill["ref"];
		
		// 单据状态
		$billStatus = $bill["billStatus"];
		if ($billStatus != 0) {
			return $this->bad("当前采购入库单已经提交入库，不能删除");
		}
		
		// 仓库id
		$warehouseId = $bill["warehouseId"];
		
		$sql = "select goods_id
				from t_pw_bill_detail
				where pwbill_id = '%s'
				order by show_order";
		$data = $db->query($sql, $id);
		$goodsIdList = array();
		foreach ( $data as $v ) {
			$goodsIdList[] = $v["goods_id"];
		}
		
		// 先删除明细记录
		$sql = "delete from t_pw_bill_detail where pwbill_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 再删除主表
		$sql = "delete from t_pw_bill where id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 删除从采购订单生成的记录
		$sql = "delete from t_po_pw where pw_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// 同步库存账中的在途库存
		foreach ( $goodsIdList as $v ) {
			$goodsId = $v;
			
			$rc = $this->updateAfloatInventory($db, $warehouseId, $goodsId, $fmt);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		$params["ref"] = $ref;
		
		// 操作成功
		return null;
	}

	/**
	 * 提交采购入库单
	 *
	 * @param array $params        	
	 * @return NULL|array
	 */
	public function commitPWBill(& $params) {
		$db = $this->db;
		
		// id: 采购入库单id
		$id = $params["id"];
		
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->badParam("loginUserId");
		}
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$sql = "select ref, warehouse_id, bill_status, biz_dt, biz_user_id,  goods_money, supplier_id,
					payment_type, company_id
				from t_pw_bill
				where id = '%s' ";
		$data = $db->query($sql, $id);
		
		if (! $data) {
			return $this->bad("要提交的采购入库单不存在");
		}
		$billStatus = $data[0]["bill_status"];
		if ($billStatus != 0) {
			return $this->bad("采购入库单已经提交入库，不能再次提交");
		}
		
		$ref = $data[0]["ref"];
		$bizDT = $data[0]["biz_dt"];
		$bizUserId = $data[0]["biz_user_id"];
		$billPayables = floatval($data[0]["goods_money"]);
		$supplierId = $data[0]["supplier_id"];
		$warehouseId = $data[0]["warehouse_id"];
		$companyId = $data[0]["company_id"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		$warehouseDAO = new WarehouseDAO($db);
		$warehouse = $warehouseDAO->getWarehouseById($warehouseId);
		if (! $warehouse) {
			return $this->bad("要入库的仓库不存在");
		}
		$inited = $warehouse["inited"];
		if ($inited == 0) {
			return $this->bad("仓库 [{$warehouse['name']}] 还没有完成建账，不能做采购入库的操作");
		}
		
		// 检查供应商是否存在
		$supplierDAO = new SupplierDAO($db);
		$supplier = $supplierDAO->getSupplierById($supplierId);
		if (! $supplier) {
			return $this->bad("供应商不存在");
		}
		
		// 检查业务员是否存在
		$userDAO = new UserDAO($db);
		$user = $userDAO->getUserById($bizUserId);
		if (! $user) {
			return $this->bad("业务员不存在");
		}
		
		$sql = "select goods_id, convert(sku_goods_count, $fmt) as goods_count, goods_money, id,
					pobilldetail_id, qc_begin_dt, qc_days, qc_sn,
					convert(goods_count, $fmt) as order_goods_count
				from t_pw_bill_detail
				where pwbill_id = '%s' order by show_order";
		$items = $db->query($sql, $id);
		if (! $items) {
			return $this->bad("采购入库单没有采购明细记录，不能入库");
		}
		
		$bizConfigDAO = new BizConfigDAO($db);
		// $countLimit: true - 入库数量不能超过采购订单上未入库数量
		$countLimit = $bizConfigDAO->getPWCountLimit($companyId) == "1";
		$poId = null;
		$sql = "select po_id
				from t_po_pw
				where pw_id = '%s' ";
		$data = $db->query($sql, $id);
		if ($data) {
			$poId = $data[0]["po_id"];
		}
		
		// 检查入库数量、金额不能为负数
		foreach ( $items as $i => $v ) {
			// 订单上的数量
			$orderGoodsCount = $v["order_goods_count"];
			
			// goodsCount - 用于入库的SKU数量
			$goodsCount = $v["goods_count"];
			if ($goodsCount < 0) {
				$db->rollback();
				return $this->bad("采购数量不能小于0");
			}
			$goodsMoney = floatval($v["goods_money"]);
			if ($goodsMoney < 0) {
				$db->rollback();
				return $this->bad("采购金额不能为负数");
			}
			if ($goodsCount == 0) {
				continue;
			}
			
			if (! $countLimit) {
				continue;
			}
			
			if (! $poId) {
				// 没有采购订单
				continue;
			}
			
			// 检查采购入库数量是否超过采购订单上未入库数量
			$pobillDetailId = $v["pobilldetail_id"];
			$sql = "select convert(left_count, $fmt) as left_count
					from t_po_bill_detail
					where id = '%s' ";
			$data = $db->query($sql, $pobillDetailId);
			if (! $data) {
				continue;
			}
			$leftCount = $data[0]["left_count"];
			if ($orderGoodsCount > $leftCount) {
				$index = $i + 1;
				$info = "第{$index}条入库记录中采购入库数量超过采购订单上未入库数量<br/><br/>";
				$info .= "入库数量是: {$orderGoodsCount}<br/>采购订单中未入库数量是: {$leftCount}";
				return $this->bad($info);
			}
		}
		
		$poBillId = $poId;
		$spoBillId = null;
		if ($poBillId) {
			// 判断该采购订单是不是由门店订货单生成的
			$sql = "select spo_id from t_spo_po where po_id = '%s' ";
			$data = $db->query($sql, $poBillId);
			if ($data) {
				$spoBillId = $data[0]["spo_id"];
			}
			
			$directIn = false;
			if ($spoBillId) {
				// 采购订单是由门店订货单生成的
				// 这个时候需要判断入库的仓库是否是对应的门店的仓库
				$sql = "select s.org_id, o.full_name 
						from t_spo_bill s, t_org o 
						where s.id = '%s' and s.org_id = o.id ";
				$data = $db->query($sql, $spoBillId);
				if ($data) {
					$orgId = $data[0]["org_id"];
					$orgName = $data[0]["full_name"];
					
					$sql = "select w.org_id, o.org_type, o.full_name
							from t_warehouse w, t_org o
							where w.id = '%s' and w.org_id = o.id ";
					$data = $db->query($sql, $warehouseId);
					$orgType = $data[0]["org_type"];
					if ($orgType == 200) {
						// 这个时候是供应商订单直接送货到门店仓库
						$directIn = true;
						$warehouseOrgId = $data[0]["org_id"];
						$warehouseOrgName = $data[0]["full_name"];
						if ($warehouseOrgId != $orgId) {
							$info = "当前入库单对应的门店订货单的门店是：{$orgName}
									<br/>
									但是入库仓库属于:{$warehouseOrgName}<br/>";
							return $this->bad($info);
						}
					}
				}
			}
		}
		
		$inventoryDAO = new InventoryDAO($db);
		
		foreach ( $items as $v ) {
			$pwbilldetailId = $v["id"];
			
			$pobillDetailId = $v["pobilldetail_id"];
			
			// 订单上的数量
			$orderGoodsCount = $v["order_goods_count"];
			
			// goodsCount - 用于入库的SKU数量
			$goodsCount = $v["goods_count"];
			if ($goodsCount <= 0) {
				// 忽略非正入库数量
				continue;
			}
			$goodsMoney = floatval($v["goods_money"]);
			$goodsPrice = $goodsMoney / $goodsCount;
			
			$goodsId = $v["goods_id"];
			$qcBeginDT = $v["qc_begin_dt"];
			$qcDays = $v["qc_days"];
			$qcSN = $v["qc_sn"];
			if (! $qcBeginDT) {
				$qcBeginDT = "1970-01-01";
			}
			$qcEndDT = "1970-01-01";
			if ($qcBeginDT != "1970-01-01") {
				$qcEndDT = date("Y-m-d", strtotime($qcBeginDT . " +$qcDays day"));
			}
			
			$refType = "采购入库";
			$rc = $inventoryDAO->inAction($companyId, $warehouseId, $goodsId, $qcBeginDT, $qcDays, 
					$qcEndDT, $qcSN, $goodsCount, $goodsMoney, $goodsPrice, $bizDT, $bizUserId, $ref, 
					$refType);
			if ($rc) {
				return $rc;
			}
			
			// 同步采购订单中的到货情况
			$sql = "select convert(goods_count, $fmt) as goods_count, 
						convert(pw_count, $fmt) as pw_count
					from t_po_bill_detail
					where id = '%s' ";
			$poDetail = $db->query($sql, $pobillDetailId);
			if (! $poDetail) {
				// 当前采购入库单不是由采购订单创建的
				continue;
			}
			
			$totalGoodsCount = $poDetail[0]["goods_count"];
			$totalPWCount = $poDetail[0]["pw_count"];
			$totalPWCount += $orderGoodsCount;
			$totalLeftCount = $totalGoodsCount - $totalPWCount;
			
			$sql = "update t_po_bill_detail
					set pw_count = convert(%f, $fmt), left_count = convert(%f, $fmt)
					where id = '%s' ";
			$rc = $db->execute($sql, $totalPWCount, $totalLeftCount, $pobillDetailId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
			
			if ($spoBillId & $directIn) {
				// 如果采购订单是由门店订货单生成，需要同时同步门店订货单的到货情况
				// 门店订单每条明细记录和采购订单的明细记录是通过pctemplate_id来一一对应的
				$sql = "select pctemplate_detail_id 
						from t_po_bill_detail
						where id = '%s' ";
				$data = $db->query($sql, $pobillDetailId);
				if ($data) {
					$pctemplateDetailId = $data[0]["pctemplate_detail_id"];
					$sql = "select id, convert(goods_count, $fmt) as goods_count,
								convert(pw_count, $fmt) as pw_count
							from t_spo_bill_detail
							where spobill_id = '%s' and pctemplate_detail_id = '%s' ";
					$data = $db->query($sql, $spoBillId, $pctemplateDetailId);
					if ($data) {
						$spoBillDetailId = $data[0]["id"];
						
						$totalGoodsCount = $data[0]["goods_count"];
						$totalPWCount = $data[0]["pw_count"];
						$totalPWCount += $orderGoodsCount;
						$totalLeftCount = $totalGoodsCount - $totalPWCount;
						
						$sql = "update t_spo_bill_detail
									set pw_count = convert(%f, $fmt), left_count = convert(%f, $fmt)
								where id = '%s' ";
						$rc = $db->execute($sql, $totalPWCount, $totalLeftCount, $spoBillDetailId);
						if ($rc === false) {
							return $this->sqlError(__METHOD__, __LINE__);
						}
					}
				}
			}
		}
		
		// 修改本单据状态为已入库
		$sql = "update t_pw_bill set bill_status = 1000 where id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 同步采购订单的状态
		$sql = "select po_id
				from t_po_pw
				where pw_id = '%s' ";
		$data = $db->query($sql, $id);
		if ($data) {
			$poBillId = $data[0]["po_id"];
			
			$sql = "select count(*) as cnt from t_po_bill_detail
					where pobill_id = '%s' and convert(left_count, $fmt) > 0 ";
			$data = $db->query($sql, $poBillId);
			$cnt = $data[0]["cnt"];
			$billStatus = 1000;
			if ($cnt > 0) {
				// 部分入库
				$billStatus = 2000;
			} else {
				// 全部入库
				$billStatus = 3000;
			}
			$sql = "update t_po_bill
					set bill_status = %d
					where id = '%s' ";
			$rc = $db->execute($sql, $billStatus, $poBillId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
			
			// 同步门店订货单的状态
			$sql = "select spo_id from t_spo_po where po_id = '%s' ";
			$data = $db->query($sql, $poBillId);
			if ($data) {
				$spoBillId = $data[0]["spo_id"];
				
				$sql = "select count(*) as cnt from t_spo_bill_detail
						where spobill_id = '%s' and convert(left_count, $fmt) > 0 ";
				$data = $db->query($sql, $spoBillId);
				$cnt = $data[0]["cnt"];
				$billStatus = 1000;
				if ($cnt > 0) {
					// 部分入库
					$billStatus = 2000;
				} else {
					// 全部入库
					$billStatus = 3000;
				}
				$sql = "update t_spo_bill
							set bill_status = %d
						where id = '%s' ";
				$rc = $db->execute($sql, $billStatus, $spoBillId);
				if ($rc === false) {
					return $this->sqlError(__METHOD__, __LINE__);
				}
			}
		}
		
		// 记应付账款
		// 应付明细账
		$sql = "insert into t_payables_detail (id, pay_money, act_money, balance_money,
					ca_id, ca_type, date_created, ref_number, ref_type, biz_date, company_id)
					values ('%s', %f, 0, %f, '%s', 'supplier', now(), '%s', '采购入库', '%s', '%s')";
		$rc = $db->execute($sql, $this->newId(), $billPayables, $billPayables, $supplierId, $ref, 
				$bizDT, $companyId);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 应付总账
		$sql = "select id, pay_money, act_money
					from t_payables
					where ca_id = '%s' and ca_type = 'supplier' and company_id = '%s' ";
		$data = $db->query($sql, $supplierId, $companyId);
		if ($data) {
			$pId = $data[0]["id"];
			$payMoney = floatval($data[0]["pay_money"]);
			$payMoney += $billPayables;
			
			$actMoney = floatval($data[0]["act_money"]);
			$balanMoney = $payMoney - $actMoney;
			
			$sql = "update t_payables
						set pay_money = %f, balance_money = %f
						where id = '%s' ";
			$rc = $db->execute($sql, $payMoney, $balanMoney, $pId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		} else {
			$payMoney = $billPayables;
			
			$sql = "insert into t_payables (id, pay_money, act_money, balance_money,
						ca_id, ca_type, company_id)
						values ('%s', %f, 0, %f, '%s', 'supplier', '%s')";
			$rc = $db->execute($sql, $this->newId(), $payMoney, $payMoney, $supplierId, $companyId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		$params["ref"] = $ref;
		
		// 操作成功
		return null;
	}

	/**
	 * 查询采购入库单的数据，用于生成PDF文件
	 *
	 * @param array $params        	
	 *
	 * @return NULL|array
	 */
	public function getDataForPDF($params) {
		$db = $this->db;
		
		$ref = $params["ref"];
		
		$sql = "select p.id, p.bill_status, p.ref, p.biz_dt, u1.name as biz_user_name, u2.name as input_user_name,
					p.goods_money, w.name as warehouse_name, s.name as supplier_name,
					p.date_created, p.payment_type, p.company_id
				from t_pw_bill p, t_warehouse w, t_supplier s, t_user u1, t_user u2
				where (p.warehouse_id = w.id) and (p.supplier_id = s.id)
				and (p.biz_user_id = u1.id) and (p.input_user_id = u2.id) 
				and (p.ref = '%s')";
		
		$data = $db->query($sql, $ref);
		if (! $data) {
			return null;
		}
		
		$v = $data[0];
		$id = $v["id"];
		$companyId = $v["company_id"];
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$result = [];
		
		$result["billStatus"] = $v["bill_status"];
		$result["supplierName"] = $v["supplier_name"];
		$result["goodsMoney"] = $v["goods_money"];
		$result["bizDT"] = $this->toYMD($v["biz_dt"]);
		$result["warehouseName"] = $v["warehouse_name"];
		$result["bizUserName"] = $v["biz_user_name"];
		
		$sql = "select g.code, g.name, g.spec, u.name as unit_name, 
					convert(p.goods_count, $fmt) as goods_count, p.goods_price,
					p.goods_money
				from t_pw_bill_detail p, t_goods g, t_goods_unit u
				where p.pwbill_id = '%s' and p.goods_id = g.id and g.unit_id = u.id
				order by p.show_order ";
		$items = [];
		$data = $db->query($sql, $id);
		
		foreach ( $data as $v ) {
			$items[] = [
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"goodsCount" => $v["goods_count"],
					"unitName" => $v["unit_name"],
					"goodsPrice" => $v["goods_price"],
					"goodsMoney" => $v["goods_money"]
			];
		}
		
		$result["items"] = $items;
		
		return $result;
	}

	/**
	 * 通过单号查询采购入库的完整信息，包括明细入库记录
	 *
	 * @param string $ref
	 *        	采购入库单单号
	 * @return array|NULL
	 */
	public function getFullBillDataByRef($ref) {
		$db = $this->db;
		
		$sql = "select p.id, s.name as supplier_name,
					w.name as  warehouse_name,
					u.name as biz_user_name, p.biz_dt, p.company_id
				from t_pw_bill p, t_supplier s, t_warehouse w, t_user u
				where p.ref = '%s' and p.supplier_id = s.id and p.warehouse_id = w.id
				  and p.biz_user_id = u.id";
		$data = $db->query($sql, $ref);
		if (! $data) {
			return NULL;
		}
		
		$v = $data[0];
		$id = $v["id"];
		$companyId = $v["company_id"];
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$result = [
				"supplierName" => $v["supplier_name"],
				"warehouseName" => $v["warehouse_name"],
				"bizUserName" => $v["biz_user_name"],
				"bizDT" => $this->toYMD($v["biz_dt"])
		
		];
		
		// 明细记录
		$items = [];
		$sql = "select p.id, p.goods_id, g.code, g.name, g.spec, u.name as unit_name,
					convert(p.goods_count, $fmt) as goods_count, p.goods_price, p.goods_money, p.memo
				from t_pw_bill_detail p, t_goods g, t_goods_unit u
				where p.goods_Id = g.id and g.unit_id = u.id and p.pwbill_id = '%s'
				order by p.show_order";
		$data = $db->query($sql, $id);
		foreach ( $data as $v ) {
			$items[] = [
					"id" => $v["id"],
					"goodsId" => $v["goods_id"],
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"unitName" => $v["unit_name"],
					"goodsCount" => $v["goods_count"],
					"goodsPrice" => $v["goods_price"],
					"goodsMoney" => $v["goods_money"],
					"memo" => $v["memo"]
			];
		}
		
		$result["items"] = $items;
		
		return $result;
	}

	/**
	 * 获得采购入库单商品明细记录列表
	 * 采购退货模块 - 选择采购入库单
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function pwBillDetailListForPRBill($params) {
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		
		$db = $this->db;
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$pwbillId = $params["id"];
		
		$sql = "select p.id, g.code, g.name, g.spec, u.name as unit_name,
					convert(p.goods_count, $fmt) as goods_count, p.goods_price,
					p.goods_money, p.memo, p.qc_begin_dt, p.qc_end_dt, p.qc_days,
					p.qc_sn
				from t_pw_bill_detail p, t_goods g, t_goods_unit u
				where p.pwbill_id = '%s' and p.goods_id = g.id and p.unit_id = u.id
				order by p.show_order ";
		$data = $db->query($sql, $pwbillId);
		$result = [];
		
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"unitName" => $v["unit_name"],
					"goodsCount" => $v["goods_count"],
					"goodsMoney" => $v["goods_money"],
					"goodsPrice" => $v["goods_price"],
					"memo" => $v["memo"],
					"qcBeginDT" => $this->toQcYMD($v["qc_begin_dt"]),
					"qcEndDT" => $this->toQcYMD($v["qc_end_dt"]),
					"qcDays" => $this->toQcDays($v["qc_days"]),
					"qcSN" => $v["qc_sn"]
			];
		}
		
		return $result;
	}

	/**
	 * 生成打印采购入库单的页面
	 *
	 * @param array $params        	
	 */
	public function getPWBillDataForLodopPrint($params) {
		$db = $this->db;
		
		$id = $params["id"];
		
		$canViewPrice = $params["canViewPrice"];
		
		$sql = "select p.ref, p.bill_status, p.ref, p.biz_dt, u1.name as biz_user_name, u2.name as input_user_name,
					p.goods_money, w.name as warehouse_name, s.name as supplier_name,
					p.date_created, p.payment_type, p.company_id, p.bill_memo
				from t_pw_bill p, t_warehouse w, t_supplier s, t_user u1, t_user u2
				where (p.warehouse_id = w.id) and (p.supplier_id = s.id)
				and (p.biz_user_id = u1.id) and (p.input_user_id = u2.id)
				and (p.id = '%s')";
		
		$data = $db->query($sql, $id);
		if (! $data) {
			return null;
		}
		
		$v = $data[0];
		$companyId = $v["company_id"];
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$result = [];
		
		$result["ref"] = $v["ref"];
		$result["billStatus"] = $v["bill_status"];
		$result["supplierName"] = $v["supplier_name"];
		$result["goodsMoney"] = $canViewPrice ? $v["goods_money"] : "****";
		$result["bizDT"] = $this->toYMD($v["biz_dt"]);
		$result["warehouseName"] = $v["warehouse_name"];
		$result["bizUserName"] = $v["biz_user_name"];
		$result["billMemo"] = $v["bill_memo"];
		
		$result["printDT"] = date("Y-m-d H:i:s");
		
		$sql = "select g.code, g.name, g.spec, u.name as unit_name,
				convert(p.goods_count, $fmt) as goods_count, p.goods_price,
				p.goods_money, p.memo
				from t_pw_bill_detail p, t_goods g, t_goods_unit u
				where p.pwbill_id = '%s' and p.goods_id = g.id and g.unit_id = u.id
				order by p.show_order ";
		$items = [];
		$data = $db->query($sql, $id);
		
		foreach ( $data as $v ) {
			$items[] = [
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"goodsCount" => $v["goods_count"],
					"unitName" => $v["unit_name"],
					"goodsPrice" => $canViewPrice ? $v["goods_price"] : "****",
					"goodsMoney" => $canViewPrice ? $v["goods_money"] : "****",
					"memo" => $v["memo"]
			];
		}
		
		$result["items"] = $items;
		
		return $result;
	}

	/**
	 * 采购入库单中选择采购订单列表
	 *
	 * @param array $params        	
	 */
	public function selectPOBillListForPWBill($params) {
		$db = $this->db;
		
		$start = $params["start"];
		$limit = $params["limit"];
		
		$ref = $params["ref"];
		$supplierId = $params["supplierId"];
		$fromDT = $params["fromDT"];
		$toDT = $params["toDT"];
		
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->emptyResult();
		}
		
		$queryParams = [];
		
		$result = [];
		$sql = "select p.id, p.ref, p.bill_status, p.goods_money,
					s.name as supplier_name,
					p.date_created, p.deal_date,
					u1.name as biz_user_name, u2.name as input_user_name,
					p.confirm_user_id, p.confirm_date
				from t_po_bill p, t_supplier s, t_user u1, t_user u2
				where (p.bill_status >0 and p.bill_status < 4000) 
					and (p.supplier_id = s.id)
					and (p.biz_user_id = u1.id) and (p.input_user_id = u2.id) ";
		
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::PURCHASE_ORDER, "p", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParams = $rs[1];
		}
		
		if ($ref) {
			$sql .= " and (p.ref like '%s') ";
			$queryParams[] = "%$ref%";
		}
		if ($fromDT) {
			$sql .= " and (p.deal_date >= '%s')";
			$queryParams[] = $fromDT;
		}
		if ($toDT) {
			$sql .= " and (p.deal_date <= '%s')";
			$queryParams[] = $toDT;
		}
		if ($supplierId) {
			$sql .= " and (p.supplier_id = '%s')";
			$queryParams[] = $supplierId;
		}
		$sql .= " order by p.deal_date desc, p.ref desc
				  limit %d , %d";
		$queryParams[] = $start;
		$queryParams[] = $limit;
		$data = $db->query($sql, $queryParams);
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"ref" => $v["ref"],
					"dealDate" => $this->toYMD($v["deal_date"]),
					"supplierName" => $v["supplier_name"],
					"goodsMoney" => $v["goods_money"],
					"billMemo" => $v["bill_memo"],
					"bizUserName" => $v["biz_user_name"],
					"inputUserName" => $v["input_user_name"],
					"dateCreated" => $v["date_created"]
			];
		}
		
		$queryParams = [];
		$sql = "select count(*) as cnt
				from t_po_bill p, t_supplier s, t_user u1, t_user u2
				where (p.bill_status >0 and p.bill_status < 4000)
					and (p.supplier_id = s.id)
					and (p.biz_user_id = u1.id) and (p.input_user_id = u2.id) ";
		
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::PURCHASE_ORDER, "p", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParams = $rs[1];
		}
		
		if ($ref) {
			$sql .= " and (p.ref like '%s') ";
			$queryParams[] = "%$ref%";
		}
		if ($fromDT) {
			$sql .= " and (p.deal_date >= '%s')";
			$queryParams[] = $fromDT;
		}
		if ($toDT) {
			$sql .= " and (p.deal_date <= '%s')";
			$queryParams[] = $toDT;
		}
		if ($supplierId) {
			$sql .= " and (p.supplier_id = '%s')";
			$queryParams[] = $supplierId;
		}
		$data = $db->query($sql, $queryParams);
		$cnt = $data[0]["cnt"];
		
		return [
				"dataList" => $result,
				"totalCount" => $cnt
		];
	}

	/**
	 * 采购入库单中选择采购订单列表 - 物资明细
	 *
	 * @param array $params        	
	 */
	public function poBillDetailListForPWBillSelectPOBill($params) {
		$db = $this->db;
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// id: 采购订单id
		$id = $params["id"];
		
		$sql = "select p.id, g.code, g.name, g.spec, convert(p.goods_count, " . $fmt . ") as goods_count,
					p.goods_price, p.goods_money,
					convert(p.pw_count, " . $fmt . ") as pw_count,
					convert(p.left_count, " . $fmt . ") as left_count, p.memo,
					u.name as unit_name
				from t_po_bill_detail p, t_goods g, t_goods_unit u
				where p.pobill_id = '%s' and p.goods_id = g.id and p.unit_id = u.id
				order by p.show_order";
		$result = [];
		$data = $db->query($sql, $id);
		
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"goodsCount" => $v["goods_count"],
					"goodsPrice" => $v["goods_price"],
					"goodsMoney" => $v["goods_money"],
					"unitName" => $v["unit_name"],
					"pwCount" => $v["pw_count"],
					"leftCount" => $v["left_count"],
					"memo" => $v["memo"]
			];
		}
		
		return $result;
	}

	/**
	 * 采购入库单中获得采购订单的详情
	 *
	 * @param array $params        	
	 */
	public function getPOBillInfoForPWBill($params) {
		$db = $this->db;
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		
		// 采购订单主表id
		$id = $params["id"];
		
		$result = [];
		
		$bcDAO = new BizConfigDAO($db);
		
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$sql = "select s.id as supplier_id, s.name as supplier_name,
						p.biz_user_id, u.name as biz_user_name,
						p.bill_memo
					from t_po_bill p, t_supplier s, t_user u
					where p.id = '%s' and p.supplier_Id = s.id
						and p.biz_user_id = u.id ";
		$data = $db->query($sql, $id);
		if ($data) {
			$v = $data[0];
			$result["supplierId"] = $v["supplier_id"];
			$result["supplierName"] = $v["supplier_name"];
			$result["bizUserId"] = $v["biz_user_id"];
			$result["bizUserName"] = $v["biz_user_name"];
			$result["billMemo"] = $v["bill_memo"];
			
			// 明细表
			$sql = "select p.id, p.goods_id, g.code, g.name, g.spec,
							convert(p.left_count, " . $fmt . ") as goods_count,
							p.goods_price, p.goods_money,
							u.id as unit_id, u.name as unit_name
						from t_po_bill_detail p, t_goods g, t_goods_unit u
						where p.pobill_id = '%s' and p.goods_id = g.id and p.unit_id = u.id
						order by p.show_order";
			$items = [];
			$data = $db->query($sql, $id);
			
			foreach ( $data as $v ) {
				$factor = 1;
				$factorType = 0;
				$goodsId = $v["goods_id"];
				$unitId = $v["unit_id"];
				$sql = "select unit_id, u.name as unit_name, g.use_qc, g.qc_days 
						from t_goods g, t_goods_unit u 
						where g.id = '%s' and g.unit_id = u.id";
				$d = $db->query($sql, $goodsId);
				$skuUnitId = $d[0]["unit_id"];
				$skuUnitName = $d[0]["unit_name"];
				$useQC = $d[0]["use_qc"];
				$qcDays = $d[0]["qc_days"];
				
				if ($skuUnitId == $unitId) {
					// 单位是基本单位
					$factor = 1;
					$factorType = 0; // 固定转换率
				} else {
					$sql = "select factor, factor_type from t_goods_unit_group 
							where goods_id = '%s' and unit_id = '%s' ";
					$d = $db->query($sql, $goodsId, $unitId);
					if ($d) {
						$factor = $d[0]["factor"];
						$factorType = $d[0]["factor_type"];
					}
				}
				
				$items[] = [
						"id" => $v["id"],
						"poBillDetailId" => $v["id"],
						"goodsId" => $goodsId,
						"goodsCode" => $v["code"],
						"goodsName" => $v["name"],
						"goodsSpec" => $v["spec"],
						"goodsCount" => $v["goods_count"],
						"goodsPrice" => $v["goods_price"],
						"goodsMoney" => $v["goods_money"],
						"unitId" => $unitId,
						"unitName" => $v["unit_name"],
						"factor" => $factor,
						"factorType" => $factorType,
						"skuGoodsCount" => $v["goods_count"] * $factor,
						"skuUnitId" => $skuUnitId,
						"skuUnitName" => $skuUnitName,
						"useQC" => $useQC,
						"qcDays" => $useQC == 1 ? $qcDays : null
				];
			}
			
			$result["items"] = $items;
		}
		
		return $result;
	}
}