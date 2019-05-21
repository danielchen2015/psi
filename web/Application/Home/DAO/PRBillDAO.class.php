<?php

namespace Home\DAO;

use Home\Common\FIdConst;

/**
 * 采购退货出库单 DAO
 *
 * @author 李静波
 */
class PRBillDAO extends PSIBaseExDAO {

	/**
	 * 生成新的采购退货出库单单号
	 *
	 * @param string $companyId        	
	 *
	 * @return string
	 */
	private function genNewBillRef($companyId) {
		$db = $this->db;
		
		// 单号前缀
		$bs = new BizConfigDAO($db);
		$pre = $bs->getPRBillRefPre($companyId);
		
		$mid = date("Ymd");
		
		$sql = "select ref from t_pr_bill where ref like '%s' order by ref desc limit 1";
		$data = $db->query($sql, $pre . $mid . "%");
		$sufLength = 3;
		$suf = str_pad("1", $sufLength, "0", STR_PAD_LEFT);
		if ($data) {
			$ref = $data[0]["ref"];
			$nextNumber = intval(substr($ref, strlen($pre . $mid))) + 1;
			$suf = str_pad($nextNumber, $sufLength, "0", STR_PAD_LEFT);
		}
		
		return $pre . $mid . $suf;
	}

	/**
	 * 新建采购退货出库单
	 *
	 * @param array $bill        	
	 * @return NULL|array
	 */
	public function addPRBill(& $bill) {
		$db = $this->db;
		
		// 业务日期
		$bizDT = $bill["bizDT"];
		
		// 仓库id
		$warehouseId = $bill["warehouseId"];
		$warehouseDAO = new WarehouseDAO($db);
		$warehouse = $warehouseDAO->getWarehouseById($warehouseId);
		if (! $warehouse) {
			return $this->bad("选择的仓库不存在，无法保存");
		}
		
		$bizUserId = $bill["bizUserId"];
		$userDAO = new UserDAO($db);
		$user = $userDAO->getUserById($bizUserId);
		if (! $user) {
			return $this->bad("选择的业务人员不存在，无法保存");
		}
		
		// 采购入库单id
		$pwBillId = $bill["pwBillId"];
		$sql = "select supplier_id from t_pw_bill where id = '%s' ";
		$data = $db->query($sql, $pwBillId);
		if (! $data) {
			return $this->bad("选择采购入库单不存在，无法保存");
		}
		
		// 供应商id
		$supplierId = $data[0]["supplier_id"];
		
		// 收款方式
		// 目前只处理 2 == 冲销应收账款
		$receivingType = 2;
		
		// 出库方式
		$outType = $bill["outType"];
		
		// 退货明细记录
		$items = $bill["items"];
		
		// 检查业务日期
		if (! $this->dateIsValid($bizDT)) {
			return $this->bad("业务日期不正确");
		}
		
		$id = $this->newId();
		
		$dataOrg = $bill["dataOrg"];
		if ($this->dataOrgNotExists($dataOrg)) {
			return $this->badParam("dataOrg");
		}
		$companyId = $bill["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		$loginUserId = $bill["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->badParam("loginUserId");
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// 新增采购退货出库单
		// 生成单号
		$ref = $this->genNewBillRef($companyId);
		
		// 主表
		$sql = "insert into t_pr_bill(id, bill_status, bizdt, biz_user_id, supplier_id, date_created,
					input_user_id, ref, warehouse_id, pw_bill_id, receiving_type, data_org, company_id, out_type)
				values ('%s', 0, '%s', '%s', '%s', now(), '%s', '%s', '%s', '%s', %d, '%s', '%s', %d)";
		$rc = $db->execute($sql, $id, $bizDT, $bizUserId, $supplierId, $loginUserId, $ref, 
				$warehouseId, $pwBillId, $receivingType, $dataOrg, $companyId, $outType);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 明细表
		foreach ( $items as $i => $v ) {
			$pwbillDetailId = $v["id"];
			$goodsId = $v["goodsId"];
			$sql = "select unit_id from t_goods where id = '%s' ";
			$data = $db->query($sql, $goodsId);
			$skuUnitId = "";
			if ($data) {
				$skuUnitId = $data[0]["unit_id"];
			} else {
				continue;
			}
			
			$goodsCount = $v["goodsCount"];
			$unitId = $v["unitId"];
			$goodsPrice = $v["goodsPrice"];
			$goodsMoney = $goodsCount * $goodsPrice;
			$rejCount = $v["rejCount"];
			$rejPrice = $v["rejPrice"];
			$rejMoney = $v["rejMoney"];
			
			$qcBeginDT = $v["qcBeginDT"];
			$qcEndDT = $v["qcEndDT"];
			$qcDays = $v["qcDays"];
			$qcSN = $v["qcSN"];
			$factor = $v["factor"];
			$factorType = $v["factorType"];
			$skuGoodsCount = $v["skuGoodsCount"];
			
			$sql = "insert into t_pr_bill_detail(id, date_created, goods_id, goods_count, goods_price,
						goods_money, rejection_goods_count, rejection_goods_price, rejection_money, show_order,
						prbill_id, pwbilldetail_id, data_org, company_id, inventory_price, inventory_money,
						qc_begin_dt, qc_end_dt, qc_days, qc_sn, factor, factor_type, sku_goods_count,
						unit_id, sku_goods_unit_id)
					values ('%s', now(), '%s', convert(%f, $fmt), %f,
						%f, convert(%f, $fmt), %f, %f, %d,
						'%s', '%s', '%s', '%s', 0, 0,
						'%s', '%s', %d, '%s', %f, %d, convert(%f, $fmt),
						'%s', '%s')";
			$rc = $db->execute($sql, $this->newId(), $goodsId, $goodsCount, $goodsPrice, 
					$goodsMoney, $rejCount, $rejPrice, $rejMoney, $i, $id, $pwbillDetailId, $dataOrg, 
					$companyId, $qcBeginDT, $qcEndDT, $qcDays, $qcSN, $factor, $factorType, 
					$skuGoodsCount, $unitId, $skuUnitId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 同步主表退货金额
		$sql = "select sum(rejection_money) as rej_money
				from t_pr_bill_detail
				where prbill_id = '%s' ";
		$data = $db->query($sql, $id);
		$rejMoney = $data[0]["rej_money"];
		
		$sql = "update t_pr_bill
				set rejection_money = %f
				where id = '%s' ";
		$rc = $db->execute($sql, $rejMoney, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$bill["id"] = $id;
		$bill["ref"] = $ref;
		
		// 操作成功
		return null;
	}

	/**
	 * 根据采购退货出库单id查询采购退货出库单
	 *
	 * @param string $id        	
	 * @return array|NULL
	 */
	public function getPRBillById($id) {
		$db = $this->db;
		
		$sql = "select ref, bill_status, data_org, company_id
				from t_pr_bill
				where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return null;
		} else {
			return [
					"ref" => $data[0]["ref"],
					"billStatus" => $data[0]["bill_status"],
					"dataOrg" => $data[0]["data_org"],
					"companyId" => $data[0]["company_id"]
			];
		}
	}

	/**
	 * 编辑采购退货出库单
	 *
	 * @param array $bill        	
	 * @return NULL|array
	 */
	public function updatePRBill(& $bill) {
		$db = $this->db;
		
		$id = $bill["id"];
		
		$loginUserId = $bill["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->badParam("loginUserId");
		}
		
		// 业务日期
		$bizDT = $bill["bizDT"];
		
		// 仓库id
		$warehouseId = $bill["warehouseId"];
		$warehouseDAO = new WarehouseDAO($db);
		$warehouse = $warehouseDAO->getWarehouseById($warehouseId);
		if (! $warehouse) {
			return $this->bad("选择的仓库不存在，无法保存");
		}
		
		// 业务员id
		$bizUserId = $bill["bizUserId"];
		$userDAO = new UserDAO($db);
		$user = $userDAO->getUserById($bizUserId);
		if (! $user) {
			return $this->bad("选择的业务人员不存在，无法保存");
		}
		
		// 要退货的采购入库单id
		$pwBillId = $bill["pwBillId"];
		$sql = "select supplier_id from t_pw_bill where id = '%s' ";
		$data = $db->query($sql, $pwBillId);
		if (! $data) {
			return $this->bad("选择采购入库单不存在，无法保存");
		}
		
		// 供应商id
		$supplierId = $data[0]["supplier_id"];
		
		// 收款方式
		$receivingType = 2;
		
		// 出库方式
		$outType = $bill["outType"];
		
		// 退货商品明细记录
		$items = $bill["items"];
		
		// 检查业务日期
		if (! $this->dateIsValid($bizDT)) {
			return $this->bad("业务日期不正确");
		}
		
		$oldBill = $this->getPRBillById($id);
		if (! $oldBill) {
			return $this->bad("要编辑的采购退货出库单不存在");
		}
		
		// 单号
		$ref = $oldBill["ref"];
		
		$companyId = $oldBill["companyId"];
		$billStatus = $oldBill["billStatus"];
		if ($billStatus != 0) {
			return $this->bad("采购退货出库单(单号：$ref)已经提交，不能再被编辑");
		}
		$dataOrg = $oldBill["data_org"];
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// 明细表
		// 先删除旧数据，再插入新记录
		$sql = "delete from t_pr_bill_detail where prbill_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		foreach ( $items as $i => $v ) {
			$pwbillDetailId = $v["id"];
			$goodsId = $v["goodsId"];
			$sql = "select unit_id from t_goods where id = '%s' ";
			$data = $db->query($sql, $goodsId);
			$skuUnitId = "";
			if ($data) {
				$skuUnitId = $data[0]["unit_id"];
			} else {
				continue;
			}
			
			$goodsCount = $v["goodsCount"];
			$unitId = $v["unitId"];
			$goodsPrice = $v["goodsPrice"];
			$goodsMoney = $goodsCount * $goodsPrice;
			$rejCount = $v["rejCount"];
			$rejPrice = $v["rejPrice"];
			$rejMoney = $v["rejMoney"];
			
			$qcBeginDT = $v["qcBeginDT"];
			$qcEndDT = $v["qcEndDT"];
			$qcDays = $v["qcDays"];
			$qcSN = $v["qcSN"];
			$factor = $v["factor"];
			$factorType = $v["factorType"];
			$skuGoodsCount = $v["skuGoodsCount"];
			
			$sql = "insert into t_pr_bill_detail(id, date_created, goods_id, goods_count, goods_price,
						goods_money, rejection_goods_count, rejection_goods_price, rejection_money, show_order,
						prbill_id, pwbilldetail_id, data_org, company_id, inventory_price, inventory_money,
						qc_begin_dt, qc_end_dt, qc_days, qc_sn, factor, factor_type, sku_goods_count,
						unit_id, sku_goods_unit_id)
					values ('%s', now(), '%s', convert(%f, $fmt), %f,
						%f, convert(%f, $fmt), %f, %f, %d,
						'%s', '%s', '%s', '%s', 0, 0,
						'%s', '%s', %d, '%s', %f, %d, convert(%f, $fmt),
						'%s', '%s')";
			$rc = $db->execute($sql, $this->newId(), $goodsId, $goodsCount, $goodsPrice, 
					$goodsMoney, $rejCount, $rejPrice, $rejMoney, $i, $id, $pwbillDetailId, $dataOrg, 
					$companyId, $qcBeginDT, $qcEndDT, $qcDays, $qcSN, $factor, $factorType, 
					$skuGoodsCount, $unitId, $skuUnitId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 同步主表退货金额
		$sql = "select sum(rejection_money) as rej_money
				from t_pr_bill_detail
				where prbill_id = '%s' ";
		$data = $db->query($sql, $id);
		$rejMoney = $data[0]["rej_money"];
		if (! $rejMoney) {
			$rejMoney = 0;
		}
		
		$sql = "update t_pr_bill
				set rejection_money = %f,
					bizdt = '%s', biz_user_id = '%s',
					date_created = now(), input_user_id = '%s',
					warehouse_id = '%s', receiving_type = %d, out_type = %d
				where id = '%s' ";
		$rc = $db->execute($sql, $rejMoney, $bizDT, $bizUserId, $loginUserId, $warehouseId, 
				$receivingType, $outType, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$bill["ref"] = $ref;
		
		// 操作成功
		return null;
	}

	/**
	 * 选择可以退货的采购入库单
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function selectPWBillList($params) {
		$db = $this->db;
		
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$ref = $params["ref"];
		$supplierId = $params["supplierId"];
		$warehouseId = $params["warehouseId"];
		$fromDT = $params["fromDT"];
		$toDT = $params["toDT"];
		
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->emptyResult();
		}
		
		$result = [];
		
		$sql = "select p.id, p.ref, p.biz_dt, s.name as supplier_name, p.goods_money,
					w.name as warehouse_name, u1.name as biz_user_name, u2.name as input_user_name
				from t_pw_bill p, t_supplier s, t_warehouse w, t_user u1, t_user u2
				where (p.supplier_id = s.id)
					and (p.warehouse_id = w.id)
					and (p.biz_user_id = u1.id)
					and (p.input_user_id = u2.id)
					and (p.bill_status = 1000)";
		$queryParamas = [];
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::PURCHASE_REJECTION, "p", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParamas = $rs[1];
		}
		
		if ($ref) {
			$sql .= " and (p.ref like '%s') ";
			$queryParamas[] = "%$ref%";
		}
		if ($supplierId) {
			$sql .= " and (p.supplier_id = '%s') ";
			$queryParamas[] = $supplierId;
		}
		if ($warehouseId) {
			$sql .= " and (p.warehouse_id = '%s') ";
			$queryParamas[] = $warehouseId;
		}
		if ($fromDT) {
			$sql .= " and (p.biz_dt >= '%s') ";
			$queryParamas[] = $fromDT;
		}
		if ($toDT) {
			$sql .= " and (p.biz_dt <= '%s') ";
			$queryParamas[] = $toDT;
		}
		
		$sql .= " order by p.ref desc limit %d, %d";
		$queryParamas[] = $start;
		$queryParamas[] = $limit;
		
		$data = $db->query($sql, $queryParamas);
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"ref" => $v["ref"],
					"bizDate" => $this->toYMD($v["biz_dt"]),
					"supplierName" => $v["supplier_name"],
					"amount" => $v["goods_money"],
					"warehouseName" => $v["warehouse_name"],
					"bizUserName" => $v["biz_user_name"],
					"inputUserName" => $v["input_user_name"]
			];
		}
		
		$sql = "select count(*) as cnt
				from t_pw_bill p, t_supplier s, t_warehouse w, t_user u1, t_user u2
				where (p.supplier_id = s.id)
					and (p.warehouse_id = w.id)
					and (p.biz_user_id = u1.id)
					and (p.input_user_id = u2.id)
					and (p.bill_status = 1000)";
		$queryParamas = [];
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::PURCHASE_REJECTION, "p", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParamas = $rs[1];
		}
		
		if ($ref) {
			$sql .= " and (p.ref like '%s') ";
			$queryParamas[] = "%$ref%";
		}
		if ($supplierId) {
			$sql .= " and (p.supplier_id = '%s') ";
			$queryParamas[] = $supplierId;
		}
		if ($warehouseId) {
			$sql .= " and (p.warehouse_id = '%s') ";
			$queryParamas[] = $warehouseId;
		}
		if ($fromDT) {
			$sql .= " and (p.biz_dt >= '%s') ";
			$queryParamas[] = $fromDT;
		}
		if ($toDT) {
			$sql .= " and (p.biz_dt <= '%s') ";
			$queryParamas[] = $toDT;
		}
		
		$data = $db->query($sql, $queryParamas);
		$cnt = $data[0]["cnt"];
		
		return [
				"dataList" => $result,
				"totalCount" => $cnt
		];
	}

	/**
	 * 根据采购入库单的id查询采购入库单的详细信息
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function getPWBillInfoForPRBill($params) {
		$db = $this->db;
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// 采购入库单id
		$id = $params["id"];
		
		$result = [];
		
		$sql = "select p.ref,s.id as supplier_id, s.name as supplier_name,
					w.id as warehouse_id, w.name as warehouse_name
				from t_pw_bill p, t_supplier s, t_warehouse w
				where p.supplier_id = s.id
					and p.warehouse_id = w.id
					and p.id = '%s' ";
		
		$data = $db->query($sql, $id);
		if (! $data) {
			return $result;
		}
		
		$result["ref"] = $data[0]["ref"];
		$result["supplierId"] = $data[0]["supplier_id"];
		$result["supplierName"] = $data[0]["supplier_name"];
		$result["warehouseId"] = $data[0]["warehouse_id"];
		$result["warehouseName"] = $data[0]["warehouse_name"];
		
		$items = [];
		
		$sql = "select p.id, g.id as goods_id, g.code as goods_code, g.name as goods_name,
					g.spec as goods_spec, u.name as unit_name, u1.name as sku_unit_name,
					convert(p.goods_count, $fmt) as goods_count, p.goods_price, p.goods_money,
					p.qc_begin_dt, p.qc_end_dt, p.qc_days, p.qc_sn, p.factor, p.factor_type,
					p.unit_id
				from t_pw_bill_detail p, t_goods g, t_goods_unit u, t_goods_unit u1
				where p.goods_id = g.id
					and p.unit_id = u.id and g.unit_id = u1.id
					and p.pwbill_id = '%s'
				order by p.show_order ";
		$data = $db->query($sql, $id);
		foreach ( $data as $v ) {
			$items[] = [
					"id" => $v["id"],
					"goodsId" => $v["goods_id"],
					"goodsCode" => $v["goods_code"],
					"goodsName" => $v["goods_name"],
					"goodsSpec" => $v["goods_spec"],
					"unitId" => $v["unit_id"],
					"unitName" => $v["unit_name"],
					"goodsCount" => $v["goods_count"],
					"goodsPrice" => $v["goods_price"],
					"goodsMoney" => $v["goods_money"],
					"rejPrice" => $v["goods_price"],
					"qcBeginDT" => $this->toQcYMD($v["qc_begin_dt"]),
					"qcEndDT" => $this->toQcYMD($v["qc_end_dt"]),
					"qcDays" => $this->toQcDays($v["qc_days"]),
					"qcSN" => $v["qc_sn"],
					"factor" => $v["factor"],
					"factorType" => $v["factor_type"],
					"skuUnitName" => $v["sku_unit_name"]
			];
		}
		
		$result["items"] = $items;
		
		return $result;
	}

	/**
	 * 采购退货出库单列表
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function prbillList($params) {
		$db = $this->db;
		
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$billStatus = $params["billStatus"];
		$ref = $params["ref"];
		$fromDT = $params["fromDT"];
		$toDT = $params["toDT"];
		$warehouseId = $params["warehouseId"];
		$supplierId = $params["supplierId"];
		$receivingType = $params["receivingType"];
		
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->emptyResult();
		}
		
		$result = [];
		$queryParams = [];
		$sql = "select p.id, p.ref, p.bill_status, w.name as warehouse_name, p.bizdt,
					p.rejection_money, u1.name as biz_user_name, u2.name as input_user_name,
					s.name as supplier_name, p.date_created, p.out_type
				from t_pr_bill p, t_warehouse w, t_user u1, t_user u2, t_supplier s
				where (p.warehouse_id = w.id)
					and (p.biz_user_id = u1.id)
					and (p.input_user_id = u2.id)
					and (p.supplier_id = s.id) ";
		
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::PURCHASE_REJECTION, "p", $loginUserId);
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
			$sql .= " and (p.bizdt >= '%s') ";
			$queryParams[] = $fromDT;
		}
		if ($toDT) {
			$sql .= " and (p.bizdt <= '%s') ";
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
		
		$sql .= " order by p.bizdt desc, p.ref desc
				limit %d, %d";
		$queryParams[] = $start;
		$queryParams[] = $limit;
		$data = $db->query($sql, $queryParams);
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"ref" => $v["ref"],
					"billStatus" => $v["bill_status"] == 0 ? "待出库" : "已出库",
					"warehouseName" => $v["warehouse_name"],
					"supplierName" => $v["supplier_name"],
					"rejMoney" => $v["rejection_money"],
					"bizUserName" => $v["biz_user_name"],
					"inputUserName" => $v["input_user_name"],
					"bizDT" => $this->toYMD($v["bizdt"]),
					"dateCreated" => $v["date_created"],
					"outType" => $v["out_type"]
			];
		}
		
		$sql = "select count(*) as cnt
				from t_pr_bill p, t_warehouse w, t_user u1, t_user u2, t_supplier s
				where (p.warehouse_id = w.id)
					and (p.biz_user_id = u1.id)
					and (p.input_user_id = u2.id)
					and (p.supplier_id = s.id) ";
		$queryParams = [];
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::PURCHASE_REJECTION, "p", $loginUserId);
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
			$sql .= " and (p.bizdt >= '%s') ";
			$queryParams[] = $fromDT;
		}
		if ($toDT) {
			$sql .= " and (p.bizdt <= '%s') ";
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
	 * 采购退货出库单明细列表
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function prBillDetailList($params) {
		$db = $this->db;
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// id：采购退货出库单id
		$id = $params["id"];
		
		$sql = "select g.code, g.name, g.spec, u.name as unit_name,
					convert(p.rejection_goods_count, $fmt) as rej_count, p.rejection_goods_price as rej_price,
					p.rejection_money as rej_money, p.qc_begin_dt, p.qc_end_dt, p.qc_days, p.qc_sn,
					p.factor, p.factor_type, convert(p.sku_goods_count, $fmt) as sku_goods_count, 
					u2.name as sku_unit_name, convert(p.goods_count, $fmt) as goods_count
				from t_pr_bill_detail p, t_goods g, t_goods_unit u, t_goods_unit u2
				where p.goods_id = g.id and p.unit_id = u.id and p.prbill_id = '%s' 
					and p.sku_goods_unit_id = u2.id
					and p.rejection_goods_count > 0
				order by p.show_order";
		$result = [];
		$data = $db->query($sql, $id);
		foreach ( $data as $v ) {
			$result[] = [
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"unitName" => $v["unit_name"],
					"goodsCount" => $v["goods_count"],
					"rejCount" => $v["rej_count"],
					"rejPrice" => $v["rej_price"],
					"rejMoney" => $v["rej_money"],
					"qcBeginDT" => $this->toQcYMD($v["qc_begin_dt"]),
					"qcEndDT" => $this->toQcYMD($v["qc_end_dt"]),
					"qcDays" => $this->toQcDays($v["qc_days"]),
					"qcSN" => $v["qc_sn"],
					"factor" => $v["factor"],
					"factorType" => $v["factor_type"],
					"skuUnitName" => $v["sku_unit_name"],
					"skuGoodsCount" => $v["sku_goods_count"]
			];
		}
		
		return $result;
	}

	/**
	 * 查询采购退货出库单详情
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function prBillInfo($params) {
		$db = $this->db;
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// id:采购退货出库单id
		$id = $params["id"];
		
		$result = [];
		
		if ($id) {
			// 编辑
			$sql = "select p.ref, p.bill_status, p.warehouse_id, w.name as warehouse_name,
						p.biz_user_id, u.name as biz_user_name, pw.ref as pwbill_ref,
						s.name as supplier_name, s.id as supplier_id,
						p.pw_bill_id as pwbill_id, p.bizdt, p.out_type
					from t_pr_bill p, t_warehouse w, t_user u, t_pw_bill pw, t_supplier s
					where p.id = '%s'
						and p.warehouse_id = w.id
						and p.biz_user_id = u.id
						and p.pw_bill_id = pw.id
						and p.supplier_id = s.id ";
			$data = $db->query($sql, $id);
			if (! $data) {
				return $result;
			}
			
			$result["ref"] = $data[0]["ref"];
			$result["billStatus"] = $data[0]["bill_status"];
			$result["bizUserId"] = $data[0]["biz_user_id"];
			$result["bizUserName"] = $data[0]["biz_user_name"];
			$result["warehouseId"] = $data[0]["warehouse_id"];
			$result["warehouseName"] = $data[0]["warehouse_name"];
			$result["pwbillRef"] = $data[0]["pwbill_ref"];
			$result["supplierId"] = $data[0]["supplier_id"];
			$result["supplierName"] = $data[0]["supplier_name"];
			$result["pwbillId"] = $data[0]["pwbill_id"];
			$result["bizDT"] = $this->toYMD($data[0]["bizdt"]);
			$result["outType"] = $data[0]["out_type"];
			
			$items = [];
			$sql = "select p.pwbilldetail_id as id, p.goods_id, g.code as goods_code, g.name as goods_name,
						g.spec as goods_spec, u.name as unit_name, convert(p.goods_count, $fmt) as goods_count,
						p.goods_price, p.goods_money, convert(p.rejection_goods_count, $fmt) as rej_count,
						p.rejection_goods_price as rej_price, p.rejection_money as rej_money,
						p.qc_begin_dt, p.qc_end_dt, p.qc_days, p.qc_sn, p.unit_id, u2.name as sku_unit_name,
						p.factor, p.factor_type, convert(p.sku_goods_count, $fmt) as sku_goods_count
					from t_pr_bill_detail p, t_goods g, t_goods_unit u, t_goods_unit u2
					where p.prbill_id = '%s'
						and p.goods_id = g.id
						and p.unit_id = u.id and p.sku_goods_unit_id = u2.id
					order by p.show_order";
			$data = $db->query($sql, $id);
			foreach ( $data as $v ) {
				$items[] = [
						"id" => $v["id"],
						"goodsId" => $v["goods_id"],
						"goodsCode" => $v["goods_code"],
						"goodsName" => $v["goods_name"],
						"goodsSpec" => $v["goods_spec"],
						"unitId" => $v["unit_id"],
						"unitName" => $v["unit_name"],
						"goodsCount" => $v["goods_count"],
						"goodsPrice" => $v["goods_price"],
						"goodsMoney" => $v["goods_money"],
						"rejCount" => $v["rej_count"],
						"rejPrice" => $v["rej_price"],
						"rejMoney" => $v["rej_money"],
						"qcBeginDT" => $this->toQcYMD($v["qc_begin_dt"]),
						"qcEndDT" => $this->toQcYMD($v["qc_end_dt"]),
						"qcDays" => $this->toQcDays($v["qc_days"]),
						"qcSN" => $v["qc_sn"],
						"factor" => $v["factor"],
						"factorType" => $v["factor_type"],
						"skuUnitName" => $v["sku_unit_name"],
						"skuGoodsCount" => $v["sku_goods_count"]
				];
			}
			
			$result["items"] = $items;
		} else {
			// 新建
			$result["bizUserId"] = $params["loginUserId"];
			$result["bizUserName"] = $params["loginUserName"];
		}
		
		return $result;
	}

	/**
	 * 删除采购退货出库单
	 *
	 * @param array $params        	
	 * @return NULL|array
	 */
	public function deletePRBill(& $params) {
		$db = $this->db;
		
		$id = $params["id"];
		
		$bill = $this->getPRBillById($id);
		
		if (! $bill) {
			return $this->bad("要删除的采购退货出库单不存在");
		}
		$ref = $bill["ref"];
		$billStatus = $bill["billStatus"];
		if ($billStatus != 0) {
			return $this->bad("采购退货出库单(单号：$ref)已经提交，不能被删除");
		}
		
		$sql = "delete from t_pr_bill_detail where prbill_id = '%s'";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$sql = "delete from t_pr_bill where id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$params["ref"] = $ref;
		
		// 操作成功
		return null;
	}

	/**
	 * 提交采购退货出库单
	 *
	 * @param array $params        	
	 *
	 * @return null|array
	 */
	public function commitPRBill(& $params) {
		$db = $this->db;
		
		$id = $params["id"];
		
		$sql = "select ref, bill_status, warehouse_id, bizdt, biz_user_id, rejection_money,
					supplier_id, out_type, company_id, pw_bill_id
				from t_pr_bill
				where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要提交的采购退货出库单不存在");
		}
		$ref = $data[0]["ref"];
		$billStatus = $data[0]["bill_status"];
		$warehouseId = $data[0]["warehouse_id"];
		$bizDT = $this->toYMD($data[0]["bizdt"]);
		$bizUserId = $data[0]["biz_user_id"];
		$allRejMoney = $data[0]["rejection_money"];
		$supplierId = $data[0]["supplier_id"];
		$receivingType = 2; // 冲销应付账款
		$companyId = $data[0]["company_id"];
		$pwBillId = $data[0]["pw_bill_id"];
		$outType = $data[0]["out_type"];
		
		if ($billStatus != 0) {
			return $this->bad("采购退货出库单(单号：$ref)已经提交，不能再次提交");
		}
		
		$bs = new BizConfigDAO($db);
		$dataScale = $bs->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$warehouseDAO = new WarehouseDAO($db);
		$warehouse = $warehouseDAO->getWarehouseById($warehouseId);
		if (! $warehouse) {
			return $this->bad("要出库的仓库不存在");
		}
		$warehouseName = $warehouse["name"];
		$inited = $warehouse["inited"];
		if ($inited != 1) {
			return $this->bad("仓库[$warehouseName]还没有完成库存建账，不能进行出库操作");
		}
		
		$userDAO = new UserDAO($db);
		$user = $userDAO->getUserById($bizUserId);
		if (! $user) {
			return $this->bad("业务人员不存在，无法完成提交操作");
		}
		
		$supplierDAO = new SupplierDAO($db);
		$supplier = $supplierDAO->getSupplierById($supplierId);
		if (! $supplier) {
			return $this->bad("供应商不存在，无法完成提交操作");
		}
		
		$invDAO = new InventoryDAO($db);
		
		$sql = "select goods_id, convert(sku_goods_count, $fmt) as goods_count,
					pwbilldetail_id,
					qc_begin_dt, qc_end_dt, qc_days, qc_sn
				from t_pr_bill_detail
				where prbill_id = '%s'
				order by show_order";
		$items = $db->query($sql, $id);
		foreach ( $items as $i => $v ) {
			$goodsId = $v["goods_id"];
			$goodsCount = $v["goods_count"];
			$pwbillDetailId = $v["pwbilldetail_id"];
			
			$qcBeginDT = $this->toQcYMD($v["qc_begin_dt"]);
			$qcEndDT = $this->toQcYMD($v["qc_end_dt"]);
			$qcDays = $v["qc_days"];
			$qcSN = $v["qc_sn"];
			
			if ($goodsCount == 0) {
				continue;
			}
			
			if ($goodsCount < 0) {
				$index = $i + 1;
				return $this->bad("第{$index}条记录的退货数量不能为负数");
			}
			
			$recordIndex = $i + 1;
			$outMoney = 0;
			$outPrice = 0;
			$rc = $invDAO->outAction($warehouseId, $goodsId, $qcBeginDT, $qcDays, $qcEndDT, $qcSN, 
					$goodsCount, $bizDT, $bizUserId, $ref, "采购退货出库", $outType, $recordIndex, $fmt, 
					$outPrice, $outMoney);
			if ($rc) {
				return $rc;
			}
		}
		
		if ($receivingType == 2) {
			// 冲销应付账款
			// 应付明细账
			$billPayables = - $allRejMoney;
			$sql = "insert into t_payables_detail (id, pay_money, act_money, balance_money,
					ca_id, ca_type, date_created, ref_number, ref_type, biz_date, company_id)
					values ('%s', %f, 0, %f, '%s', 'supplier', now(), '%s', '采购退货出库', '%s', '%s')";
			$rc = $db->execute($sql, $this->newId(), $billPayables, $billPayables, $supplierId, 
					$ref, $bizDT, $companyId);
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
			}
		}
		
		// 修改单据本身的状态
		$sql = "update t_pr_bill
				set bill_status = 1000
				where id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 修改对应的采购入库单的状态位：已退货
		$sql = "update t_pw_bill
				set bill_status = 2000
				where id = '%s' ";
		$rc = $db->execute($sql, $pwBillId);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$params["ref"] = $ref;
		
		return null;
	}

	/**
	 * 查询采购退货出库单的数据，用于生成PDF文件
	 *
	 * @param array $params        	
	 *
	 * @return NULL|array
	 */
	public function getDataForPDF($params) {
		$db = $this->db;
		
		$ref = $params["ref"];
		
		$sql = "select p.id, p.bill_status, w.name as warehouse_name, p.bizdt,
					p.rejection_money, u1.name as biz_user_name, u2.name as input_user_name,
					s.name as supplier_name, p.date_created, p.receiving_type, p.company_id
				from t_pr_bill p, t_warehouse w, t_user u1, t_user u2, t_supplier s
				where (p.warehouse_id = w.id)
					and (p.biz_user_id = u1.id)
					and (p.input_user_id = u2.id)
					and (p.supplier_id = s.id) 
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
		
		$result = [
				"billStatus" => $v["bill_status"],
				"supplierName" => $v["supplier_name"],
				"goodsMoney" => $v["rejection_money"],
				"bizDT" => $this->toYMD($v["bizdt"]),
				"warehouseName" => $v["warehouse_name"],
				"bizUserName" => $v["biz_user_name"]
		];
		
		$sql = "select g.code, g.name, g.spec, u.name as unit_name,
					convert(p.rejection_goods_count, $fmt) as rej_count, p.rejection_goods_price as rej_price,
					p.rejection_money as rej_money
				from t_pr_bill_detail p, t_goods g, t_goods_unit u
				where p.goods_id = g.id and g.unit_id = u.id and p.prbill_id = '%s'
					and p.rejection_goods_count > 0
				order by p.show_order";
		$items = [];
		$data = $db->query($sql, $id);
		
		foreach ( $data as $v ) {
			$items[] = [
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"goodsCount" => $v["rej_count"],
					"unitName" => $v["unit_name"],
					"goodsPrice" => $v["rej_price"],
					"goodsMoney" => $v["rej_money"]
			];
		}
		
		$result["items"] = $items;
		
		return $result;
	}

	/**
	 * 通过单号查询采购退货出库单完整信息，包括明细记录
	 *
	 * @param string $ref
	 *        	采购退货出库单单号
	 * @return array|NULL
	 */
	public function getFullBillDataByRef($ref) {
		$db = $this->db;
		$sql = "select p.id, w.name as warehouse_name,
					u.name as biz_user_name, pw.ref as pwbill_ref,
					s.name as supplier_name,
					p.bizdt, p.company_id
				from t_pr_bill p, t_warehouse w, t_user u, t_pw_bill pw, t_supplier s
				where p.ref = '%s'
					and p.warehouse_id = w.id
					and p.biz_user_id = u.id
					and p.pw_bill_id = pw.id
					and p.supplier_id = s.id ";
		$data = $db->query($sql, $ref);
		if (! $data) {
			return NULL;
		}
		
		$id = $data[0]["id"];
		$companyId = $data[0]["company_id"];
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$result = [
				"bizUserName" => $data[0]["biz_user_name"],
				"warehouseName" => $data[0]["warehouse_name"],
				"pwbillRef" => $data[0]["pwbill_ref"],
				"supplierName" => $data[0]["supplier_name"],
				"bizDT" => $this->toYMD($data[0]["bizdt"])
		];
		
		$items = [];
		$sql = "select p.pwbilldetail_id as id, p.goods_id, g.code as goods_code, g.name as goods_name,
					g.spec as goods_spec, u.name as unit_name, p.goods_count,
					p.goods_price, p.goods_money, convert(p.rejection_goods_count, $fmt) as rej_count,
					p.rejection_goods_price as rej_price, p.rejection_money as rej_money
				from t_pr_bill_detail p, t_goods g, t_goods_unit u
				where p.prbill_id = '%s'
					and p.goods_id = g.id
					and g.unit_id = u.id
				order by p.show_order";
		$data = $db->query($sql, $id);
		foreach ( $data as $v ) {
			$items[] = [
					"id" => $v["id"],
					"goodsId" => $v["goods_id"],
					"goodsCode" => $v["goods_code"],
					"goodsName" => $v["goods_name"],
					"goodsSpec" => $v["goods_spec"],
					"unitName" => $v["unit_name"],
					"goodsCount" => $v["goods_count"],
					"goodsPrice" => $v["goods_price"],
					"goodsMoney" => $v["goods_money"],
					"rejCount" => $v["rej_count"],
					"rejPrice" => $v["rej_price"],
					"rejMoney" => $v["rej_money"]
			];
		}
		
		$result["items"] = $items;
		
		return $result;
	}

	/**
	 * 生成打印采购退货出库单的页面
	 *
	 * @param array $params        	
	 */
	public function getPRBillDataForLodopPrint($params) {
		$db = $this->db;
		
		$id = $params["id"];
		
		$sql = "select p.ref, p.bill_status, w.name as warehouse_name, p.bizdt,
					p.rejection_money, u1.name as biz_user_name, u2.name as input_user_name,
					s.name as supplier_name, p.date_created, p.receiving_type, p.company_id
				from t_pr_bill p, t_warehouse w, t_user u1, t_user u2, t_supplier s
				where (p.warehouse_id = w.id)
					and (p.biz_user_id = u1.id)
					and (p.input_user_id = u2.id)
					and (p.supplier_id = s.id)
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
		
		$result = [
				"ref" => $v["ref"],
				"billStatus" => $v["bill_status"],
				"supplierName" => $v["supplier_name"],
				"goodsMoney" => $v["rejection_money"],
				"bizDT" => $this->toYMD($v["bizdt"]),
				"warehouseName" => $v["warehouse_name"],
				"bizUserName" => $v["biz_user_name"],
				"printDT" => date("Y-m-d H:i:s")
		];
		
		$sql = "select g.code, g.name, g.spec, u.name as unit_name,
				convert(p.rejection_goods_count, $fmt) as rej_count, p.rejection_goods_price as rej_price,
				p.rejection_money as rej_money
				from t_pr_bill_detail p, t_goods g, t_goods_unit u
				where p.goods_id = g.id and g.unit_id = u.id and p.prbill_id = '%s'
				and p.rejection_goods_count > 0
				order by p.show_order";
		$items = [];
		$data = $db->query($sql, $id);
		
		foreach ( $data as $v ) {
			$items[] = [
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"goodsCount" => $v["rej_count"],
					"unitName" => $v["unit_name"],
					"goodsPrice" => $v["rej_price"],
					"goodsMoney" => $v["rej_money"]
			];
		}
		
		$result["items"] = $items;
		
		return $result;
	}
}