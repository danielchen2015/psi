<?php

namespace Home\DAO;

use Home\Common\FIdConst;

/**
 * 物流单 DAO
 *
 * @author 李静波
 */
class LDBillDAO extends PSIBaseExDAO {

	/**
	 * 生成新的物流单单号
	 *
	 * @param string $companyId        	
	 * @return string
	 */
	private function genNewBillRef($companyId) {
		$db = $this->db;
		
		// 取单号前缀
		$pre = "WL";
		
		$mid = date("Ymd");
		
		$sql = "select ref from t_ld_bill where ref like '%s' order by ref desc limit 1";
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
	 * 物流单主表列表
	 *
	 * @param array $params        	
	 */
	public function ldbillList($params) {
		$db = $this->db;
		
		$start = $params["start"];
		$limit = $params["limit"];
		
		$loginUserId = $params["loginUserId"];
		
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->emptyResult();
		}
		$billStatus = $params["billStatus"];
		$ref = $params["ref"];
		$fromDT = $params["fromDT"];
		$toDT = $params["toDT"];
		$orgId = $params["orgId"];
		
		$queryParams = [];
		$sql = "select ld.id, ld.bill_status, ld.ref, ld.biz_dt, u1.name as biz_user_name, u2.name as input_user_name,
					w.name as to_warehouse_name,
					ld.date_created, ld.bill_memo, g1.full_name as to_org_name, g2.full_name as from_org_name,
					w2.name as from_warehouse_name, spo.ref as spobill_ref, ld.out_type
				from t_ld_bill ld, t_warehouse w, t_user u1, t_user u2, t_org g1, t_org g2,
					t_warehouse w2, t_spo_bill spo
				where (ld.to_warehouse_id = w.id)
					and (ld.biz_user_id = u1.id) and (ld.input_user_id = u2.id) 
					and (ld.to_org_id = g1.id) and (ld.from_org_id = g2.id) 
					and (ld.from_warehouse_id = w2.id)
					and (ld.spobill_id = spo.id) ";
		
		$ds = new DataOrgDAO($db);
		// 构建数据域SQL
		$rs = $ds->buildSQL(FIdConst::SCM_LOGISTICS_DISTRIBUTION, "ld", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParams = array_merge($queryParams, $rs[1]);
		}
		
		if ($billStatus != - 1) {
			$sql .= " and (ld.bill_status = %d) ";
			$queryParams[] = $billStatus;
		}
		if ($ref) {
			$sql .= " and (ld.ref like '%s') ";
			$queryParams[] = "%{$ref}%";
		}
		if ($fromDT) {
			$sql .= " and (ld.biz_dt >= '%s') ";
			$queryParams[] = $fromDT;
		}
		if ($toDT) {
			$sql .= " and (ld.biz_dt <= '%s') ";
			$queryParams[] = $toDT;
		}
		if ($orgId) {
			$sql .= " and (ld.to_org_id = '%s') ";
			$queryParams[] = $orgId;
		}
		
		$sql .= " order by ld.ref desc limit %d, %d";
		$queryParams[] = $start;
		$queryParams[] = $limit;
		
		$data = $db->query($sql, $queryParams);
		$result = [];
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"ref" => $v["ref"],
					"fromWarehouseName" => $v["from_warehouse_name"],
					"fromOrgName" => $v["from_org_name"],
					"toWarehouseName" => $v["to_warehouse_name"],
					"toOrgName" => $v["to_org_name"],
					"spoBillRef" => $v["spobill_ref"],
					"outType" => $v["out_type"],
					"bizUserName" => $v["biz_user_name"],
					"bizDT" => $this->toYMD($v["biz_dt"]),
					"inputUserName" => $v["input_user_name"],
					"dateCreated" => $v["date_created"],
					"billMemo" => $v["bill_memo"],
					"billStatus" => $v["bill_status"]
			];
		}
		
		$queryParams = [];
		$sql = "select count(*) as cnt
				from t_ld_bill ld, t_warehouse w, t_user u1, t_user u2, t_org g1, t_org g2,
					t_warehouse w2, t_spo_bill spo
				where (ld.to_warehouse_id = w.id)
					and (ld.biz_user_id = u1.id) and (ld.input_user_id = u2.id)
					and (ld.to_org_id = g1.id) and (ld.from_org_id = g2.id)
					and (ld.from_warehouse_id = w2.id)
					and (ld.spobill_id = spo.id) ";
		
		$ds = new DataOrgDAO($db);
		// 构建数据域SQL
		$rs = $ds->buildSQL(FIdConst::SCM_LOGISTICS_DISTRIBUTION, "ld", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParams = array_merge($queryParams, $rs[1]);
		}
		
		if ($billStatus != - 1) {
			$sql .= " and (ld.bill_status = %d) ";
			$queryParams[] = $billStatus;
		}
		if ($ref) {
			$sql .= " and (ld.ref like '%s') ";
			$queryParams[] = "%{$ref}%";
		}
		if ($fromDT) {
			$sql .= " and (ld.biz_dt >= '%s') ";
			$queryParams[] = $fromDT;
		}
		if ($toDT) {
			$sql .= " and (ld.biz_dt <= '%s') ";
			$queryParams[] = $toDT;
		}
		if ($orgId) {
			$sql .= " and (ld.to_org_id = '%s') ";
			$queryParams[] = $orgId;
		}
		$data = $db->query($sql, $queryParams);
		$cnt = $data[0]["cnt"];
		
		return [
				"dataList" => $result,
				"totalCount" => $cnt
		];
	}

	/**
	 * 选择要发货的门店订货单 - 主表列表
	 *
	 * @param array $params        	
	 */
	public function selectSPOBillList($params) {
		$db = $this->db;
		
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->emptyResult();
		}
		
		$ref = $params["ref"];
		$supplierId = $params["supplierId"];
		$fromDT = $params["fromDT"];
		$toDT = $params["toDT"];
		$orgId = $params["orgId"];
		
		$start = $params["start"];
		$limit = $params["limit"];
		
		$queryParams = [];
		
		$result = [];
		$sql = "select p.id, p.ref, p.bill_status, p.goods_money,
					s.name as supplier_name,
					p.deal_date,p.bill_memo, p.date_created,
					o.full_name as org_name, u1.name as biz_user_name, u2.name as input_user_name
				from t_spo_bill p, t_supplier s, t_org o, t_user u1, t_user u2
				where (p.supplier_id = s.id) and (p.org_id = o.id)
					and (p.biz_user_id = u1.id) and (p.input_user_id = u2.id) 
					and (p.bill_status > 0 and p.bill_status < 4000)";
		
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::SCM_LOGISTICS_DISTRIBUTION, "p", $loginUserId);
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
		if ($orgId) {
			$sql .= " and (p.org_id = '%s')";
			$queryParams[] = $orgId;
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
					"billMemo" => $v["bill_memo"],
					"bizUserName" => $v["biz_user_name"],
					"orgName" => $v["org_name"],
					"inputUserName" => $v["input_user_name"],
					"dateCreated" => $v["date_created"]
			];
		}
		
		$queryParams = [];
		$sql = "select count(*) as cnt
				from t_spo_bill p, t_supplier s, t_org o, t_user u1, t_user u2
				where (p.supplier_id = s.id) and (p.org_id = o.id)
					and (p.biz_user_id = u1.id) and (p.input_user_id = u2.id)
					and (p.bill_status > 0 and p.bill_status < 4000)";
		
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::SCM_LOGISTICS_DISTRIBUTION, "p", $loginUserId);
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
		if ($orgId) {
			$sql .= " and (p.org_id = '%s')";
			$queryParams[] = $orgId;
		}
		$data = $db->query($sql, $queryParams);
		$cnt = $data[0]["cnt"];
		
		return [
				"dataList" => $result,
				"totalCount" => $cnt
		];
	}

	/**
	 * 选择要发货的门店订货单 - 明细记录
	 *
	 * @param array $params        	
	 */
	public function spoBillDetailList($params) {
		$db = $this->db;
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// 门店订货单主表id
		$id = $params["id"];
		
		$sql = "select p.id, g.code, g.name, g.spec, convert(p.goods_count, " . $fmt . ") as goods_count,
					p.goods_price, p.goods_money,
					convert(p.pw_count, " . $fmt . ") as pw_count,
					convert(p.left_count, " . $fmt . ") as left_count, p.memo,
					u.name as unit_name
				from t_spo_bill_detail p, t_goods g, t_goods_unit u
				where p.spobill_id = '%s' and p.goods_id = g.id and p.unit_id = u.id
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
	 * 查询门店订货单的信息，用于生成物流单
	 *
	 * @param array $params        	
	 */
	public function getSPOBillInfoForLDBill($params) {
		$db = $this->db;
		
		// 门店订货单主表id
		$id = $params["id"];
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->emptyResult();
		}
		
		$result = [];
		
		$bcDAO = new BizConfigDAO($db);
		
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$sql = "select p.ref, s.name as supplier_name, p.org_id, g.full_name
					from t_spo_bill p, t_supplier s, t_org g
					where p.id = '%s' and p.supplier_id = s.id 
						and p.org_id = g.id ";
		$data = $db->query($sql, $id);
		if ($data) {
			$v = $data[0];
			$result["ref"] = $v["ref"];
			$result["supplierName"] = $v["supplier_name"];
			$result["toOrgId"] = $v["org_id"];
			$result["toOrgName"] = $v["full_name"];
			
			// 明细表
			$sql = "select p.id, p.goods_id, g.code, g.name, g.spec,
							convert(p.goods_count, " . $fmt . ") as goods_count,
							p.goods_price, p.goods_money,
							p.unit_id,
							u.name as unit_name
						from t_spo_bill_detail p, t_goods g, t_goods_unit u
						where p.spobill_id = '%s' and p.goods_id = g.id and p.unit_id = u.id
						order by p.show_order";
			$items = [];
			$data = $db->query($sql, $id);
			
			foreach ( $data as $v ) {
				$goodsId = $v["goods_id"];
				$unitId = $v["unit_id"];
				$sql = "select g.unit_id, u.name as unit_name  
						from t_goods g, t_goods_unit u
						where g.id = '%s' and g.unit_id = u.id ";
				$d = $db->query($sql, $goodsId);
				if (! $d) {
					continue;
				}
				$skuUnitId = $d[0]["unit_id"];
				$skuUnitName = $d[0]["unit_name"];
				$factor = 1;
				$factorType = 0;
				$sql = "select factor, factor_type 
						from t_goods_unit_group
						where goods_id = '%s' and unit_id = '%s' ";
				$d = $db->query($sql, $goodsId, $unitId);
				if ($d) {
					$factor = $d[0]["factor"];
					$factorType = $d[0]["factor_type"];
				}
				
				$items[] = [
						"spoBillDetailId" => $v["id"],
						"goodsId" => $v["goods_id"],
						"goodsCode" => $v["code"],
						"goodsName" => $v["name"],
						"goodsSpec" => $v["spec"],
						"goodsCount" => $v["goods_count"],
						"goodsPrice" => $v["goods_price"],
						"goodsMoney" => $v["goods_money"],
						"unitId" => $v["unit_id"],
						"unitName" => $v["unit_name"],
						"skuUnitId" => $skuUnitId,
						"skuUnitName" => $skuUnitName,
						"skuGoodsCount" => $v["goods_count"] * $factor,
						"factor" => $factor,
						"factorType" => $factorType
				];
			}
			
			$result["items"] = $items;
		}
		
		$result["bizUserName"] = $params["loginUserName"];
		$result["bizUserId"] = $loginUserId;
		
		// 当前用户的组织机构
		$sql = "select g.full_name, g.id
				from t_org g, t_user u
				where g.id = u.org_id and u.id = '%s' ";
		$data = $db->query($sql, $loginUserId);
		if ($data) {
			$v = $data[0];
			$result["fromOrgId"] = $v["id"];
			$result["fromOrgName"] = $v["full_name"];
		}
		
		return $result;
	}

	/**
	 * 新建物流单
	 *
	 * @param array $bill        	
	 */
	public function addLDBill(& $bill) {
		$db = $this->db;
		
		$companyId = $bill["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		$loginUserId = $bill["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->badParam("loginUserId");
		}
		$dataOrg = $bill["dataOrg"];
		if ($this->dataOrgNotExists($dataOrg)) {
			return $this->badParam("dataOrg");
		}
		
		// 发货仓库id
		$fromWarehouseId = $bill["fromWarehouseId"];
		// 发货组织id
		$fromOrgId = $bill["fromOrgId"];
		// 收货仓库id
		$toWarehouseId = $bill["toWarehouseId"];
		// 收货门店id
		$toOrgId = $bill["toOrgId"];
		$bizDT = $bill["bizDT"];
		$bizUserId = $bill["bizUserId"];
		$outType = $bill["outType"];
		// 门店订货单id
		$spoBillId = $bill["spoBillId"];
		$billMemo = $bill["billMemo"];
		
		$warehouseDAO = new WarehouseDAO($db);
		$w = $warehouseDAO->getWarehouseById($fromWarehouseId);
		if (! $w) {
			return $this->bad("出库仓库不存在");
		}
		$w = $warehouseDAO->getWarehouseById($toWarehouseId);
		if (! $w) {
			return $this->bad("收货仓库不存在");
		}
		$toWarehouseName = $w["name"];
		
		$orgDAO = new OrgDAO($db);
		$org = $orgDAO->getOrgById($fromOrgId);
		if (! $org) {
			return $this->bad("发货组织不存在");
		}
		$org = $orgDAO->getOrgById($toOrgId);
		if (! $org) {
			return $this->bad("收货门店不存在");
		}
		$toOrgType = $org["orgType"];
		$toOrgName = $org["name"];
		if ($toOrgType != 200) {
			return $this->bad("收货组织机构选择的不是门店");
		}
		
		// 检查收货仓库是不是属于收货门店的仓库
		$sql = "select count(*) as cnt 
				from t_warehouse
				where id = '%s' and org_id = '%s' ";
		$data = $db->query($sql, $toWarehouseId, $toOrgId);
		$cnt = $data[0]["cnt"];
		if ($cnt != 1) {
			return $this->bad("收货仓库[{$toWarehouseName}]不属于门店[{$toOrgName}]");
		}
		
		// 检查门店订货单是否存在
		$sql = "select ref from t_spo_bill where id = '%s' ";
		$data = $db->query($sql, $spoBillId);
		if (! $data) {
			return $this->bad("选择的门店订货单不存在");
		}
		
		// 检查业务日期
		if (! $this->dateIsValid($bizDT)) {
			return $this->bad("业务日期不正确");
		}
		
		// 检查业务员
		$userDAO = new UserDAO($db);
		$u = $userDAO->getUserById($bizUserId);
		if (! $u) {
			return $this->bad("业务员不存在");
		}
		
		// 发货明细
		$items = $bill["items"];
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$ref = $this->genNewBillRef($companyId);
		$id = $this->newId();
		
		// 主表
		$sql = "insert into t_ld_bill(id, ref, from_warehouse_id, to_warehouse_id, from_org_id, to_org_id,
					biz_user_id, biz_dt, input_user_id, date_created, bill_status, data_org, company_id,
					bill_memo, spobill_id, out_type)
				values ('%s', '%s', '%s', '%s', '%s', '%s',
					'%s', '%s', '%s', now(), 0, '%s', '%s',
					'%s', '%s', %d)";
		$rc = $db->execute($sql, $id, $ref, $fromWarehouseId, $toWarehouseId, $fromOrgId, $toOrgId, 
				$bizUserId, $bizDT, $loginUserId, $dataOrg, $companyId, $billMemo, $spoBillId, 
				$outType);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 明细表
		$goodsDAO = new GoodsDAO($db);
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
			
			// 门店订货单明细记录id
			$spoBillDetailId = $item["spoBillDetailId"];
			
			// 发货数量
			$goodsCount = $item["goodsCount"];
			// 发货计量单位id
			$unitId = $item["unitId"];
			// 转换率
			$factor = $item["factor"];
			// 转换率类型
			$factorType = $item["factorType"];
			// 转换后的出库数量
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
			
			$sql = "insert into t_ld_bill_detail (id, ldbill_id, show_order, goods_id, goods_count,
						unit_id, rej_goods_count, rev_goods_count, factor, factor_type, sku_goods_count,
						sku_goods_unit_id, qc_begin_dt, qc_end_dt, qc_days, qc_sn, date_created, data_org,
						company_id, spobilldetail_id)
					values ('%s', '%s', %d, '%s', convert(%f, $fmt),
						'%s', 0, 0, %f, %d, convert(%f, $fmt),
						'%s', '%s', '%s', %d, '%s', now(), '%s',
						'%s', '%s')";
			$rc = $db->execute($sql, $this->newId(), $id, $i, $goodsId, $goodsCount, $unitId, 
					$factor, $factorType, $skuGoodsCount, $skuUnitId, $qcBeginDT, $qcEndDT, $qcDays, 
					$qcSN, $dataOrg, $companyId, $spoBillDetailId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 操作成功
		$bill["ref"] = $ref;
		$bill["id"] = $id;
		return null;
	}

	/**
	 * 编辑物流单
	 *
	 * @param array $bill        	
	 */
	public function updateLDBill(&$bill) {
		$db = $this->db;
		
		$id = $bill["id"];
		$b = $this->getLDBillById($id);
		if (! $b) {
			return $this->bad("要编辑的物流单不存在");
		}
		$billStatus = $b["billStatus"];
		if ($billStatus > 0) {
			return $this->bad("当前物流单已经提交出库，不能再编辑");
		}
		$ref = $b["ref"];
		
		$companyId = $bill["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		$loginUserId = $bill["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->badParam("loginUserId");
		}
		$dataOrg = $bill["dataOrg"];
		if ($this->dataOrgNotExists($dataOrg)) {
			return $this->badParam("dataOrg");
		}
		
		// 发货仓库id
		$fromWarehouseId = $bill["fromWarehouseId"];
		// 发货组织id
		$fromOrgId = $bill["fromOrgId"];
		// 收货仓库id
		$toWarehouseId = $bill["toWarehouseId"];
		// 收货门店id
		$toOrgId = $bill["toOrgId"];
		$bizDT = $bill["bizDT"];
		$bizUserId = $bill["bizUserId"];
		$outType = $bill["outType"];
		// 门店订货单id
		$spoBillId = $bill["spoBillId"];
		$billMemo = $bill["billMemo"];
		
		$warehouseDAO = new WarehouseDAO($db);
		$w = $warehouseDAO->getWarehouseById($fromWarehouseId);
		if (! $w) {
			return $this->bad("出库仓库不存在");
		}
		$w = $warehouseDAO->getWarehouseById($toWarehouseId);
		if (! $w) {
			return $this->bad("收货仓库不存在");
		}
		$toWarehouseName = $w["name"];
		
		$orgDAO = new OrgDAO($db);
		$org = $orgDAO->getOrgById($fromOrgId);
		if (! $org) {
			return $this->bad("发货组织不存在");
		}
		$org = $orgDAO->getOrgById($toOrgId);
		if (! $org) {
			return $this->bad("收货门店不存在");
		}
		$toOrgType = $org["orgType"];
		$toOrgName = $org["name"];
		if ($toOrgType != 200) {
			return $this->bad("收货组织机构选择的不是门店");
		}
		
		// 检查收货仓库是不是属于收货门店的仓库
		$sql = "select count(*) as cnt
				from t_warehouse
				where id = '%s' and org_id = '%s' ";
		$data = $db->query($sql, $toWarehouseId, $toOrgId);
		$cnt = $data[0]["cnt"];
		if ($cnt != 1) {
			return $this->bad("收货仓库[{$toWarehouseName}]不属于门店[{$toOrgName}]");
		}
		
		// 检查门店订货单是否存在
		$sql = "select ref from t_spo_bill where id = '%s' ";
		$data = $db->query($sql, $spoBillId);
		if (! $data) {
			return $this->bad("选择的门店订货单不存在");
		}
		
		// 检查业务日期
		if (! $this->dateIsValid($bizDT)) {
			return $this->bad("业务日期不正确");
		}
		
		// 检查业务员
		$userDAO = new UserDAO($db);
		$u = $userDAO->getUserById($bizUserId);
		if (! $u) {
			return $this->bad("业务员不存在");
		}
		
		// 发货明细
		$items = $bill["items"];
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$ref = $this->genNewBillRef($companyId);
		
		// 主表
		$sql = "update t_ld_bill
				set from_warehouse_id = '%s', to_warehouse_id = '%s',
					from_org_id = '%s', to_org_id = '%s',
					biz_user_id = '%s', biz_dt = '%s',
					bill_memo = '%s', out_type = %d
				where id = '%s' ";
		$rc = $db->execute($sql, $fromWarehouseId, $toWarehouseId, $fromOrgId, $toOrgId, $bizUserId, 
				$bizDT, $billMemo, $outType, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 明细表
		// 删除旧数据
		$sql = "delete from t_ld_bill_detail where ldbill_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$goodsDAO = new GoodsDAO($db);
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
			
			// 门店订货单明细记录id
			$spoBillDetailId = $item["spoBillDetailId"];
			
			// 发货数量
			$goodsCount = $item["goodsCount"];
			// 发货计量单位id
			$unitId = $item["unitId"];
			// 转换率
			$factor = $item["factor"];
			// 转换率类型
			$factorType = $item["factorType"];
			// 转换后的出库数量
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
			
			$sql = "insert into t_ld_bill_detail (id, ldbill_id, show_order, goods_id, goods_count,
						unit_id, rej_goods_count, rev_goods_count, factor, factor_type, sku_goods_count,
						sku_goods_unit_id, qc_begin_dt, qc_end_dt, qc_days, qc_sn, date_created, data_org,
						company_id, spobilldetail_id)
					values ('%s', '%s', %d, '%s', convert(%f, $fmt),
						'%s', 0, 0, %f, %d, convert(%f, $fmt),
						'%s', '%s', '%s', %d, '%s', now(), '%s',
						'%s', '%s')";
			$rc = $db->execute($sql, $this->newId(), $id, $i, $goodsId, $goodsCount, $unitId, 
					$factor, $factorType, $skuGoodsCount, $skuUnitId, $qcBeginDT, $qcEndDT, $qcDays, 
					$qcSN, $dataOrg, $companyId, $spoBillDetailId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 操作成功
		$bill["ref"] = $ref;
		return null;
	}

	/**
	 * 物流单明细记录
	 *
	 * @param array $params        	
	 */
	public function ldBillDetailList($params) {
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		
		$db = $this->db;
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// 物流单id
		$id = $params["id"];
		
		$sql = "select bill_status from t_ld_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return [];
		}
		
		// 这里取单据状态，是因为：门店实际收货和退货数据只有在门店收货提交入库后才有有效，
		// 在这之前，实际收货和退货数据均视为0
		$billStatus = $data[0]["bill_status"];
		
		$sql = "select p.id, g.code, g.name, g.spec, u.name as unit_name,
					convert(p.goods_count, $fmt) as goods_count,
					convert(p.rej_goods_count, $fmt) as rej_goods_count,
					convert(p.rev_goods_count, $fmt) as rev_goods_count,
					p.factor, p.factor_type,
					convert(p.sku_goods_count, $fmt) as sku_goods_count,
					p.qc_begin_dt, p.qc_end_dt, p.qc_days, p.qc_sn, u2.name as sku_unit_name
				from t_ld_bill_detail p, t_goods g, t_goods_unit u, t_goods_unit u2
				where p.ldbill_id = '%s' and p.goods_id = g.id and p.unit_id = u.id and p.sku_goods_unit_id = u2.id
				order by p.show_order ";
		$data = $db->query($sql, $id);
		$result = [];
		
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"unitName" => $v["unit_name"],
					"goodsCount" => $v["goods_count"],
					"rejCount" => $billStatus > 1000 ? $v["rej_goods_count"] : null,
					"revCount" => $billStatus > 1000 ? $v["rev_goods_count"] : null,
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

	public function getLDBillById($id) {
		$db = $this->db;
		
		$sql = "select ref, bill_status from t_ld_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return null;
		} else {
			$v = $data[0];
			return [
					"ref" => $v["ref"],
					"billStatus" => $v["bill_status"]
			];
		}
	}

	/**
	 * 删除物流单
	 *
	 * @param array $params        	
	 */
	public function deleteLDBill(& $params) {
		$db = $this->db;
		
		// 物流单id
		$id = $params["id"];
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		$bill = $this->getLDBillById($id);
		if (! $bill) {
			return $this->bad("要删除的物流单不存在");
		}
		
		// 单号
		$ref = $bill["ref"];
		
		// 单据状态
		$billStatus = $bill["billStatus"];
		if ($billStatus != 0) {
			return $this->bad("当前物流单已经提交出库，不能删除");
		}
		
		// 先删除明细记录
		$sql = "delete from t_ld_bill_detail where ldbill_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 再删除主表
		$sql = "delete from t_ld_bill where id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$params["ref"] = $ref;
		
		// 操作成功
		return null;
	}

	/**
	 * 某个物流单的详情
	 *
	 * @param array $params        	
	 */
	public function ldBillInfo($params) {
		$db = $this->db;
		
		// 公司id
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// id: 物流单id
		$id = $params["id"];
		
		$result = [];
		
		$sql = "select p.ref, p.bill_status, s.name as supplier_name,
					p.from_warehouse_id, w.name as from_warehouse_name,
					p.biz_user_id, u.name as biz_user_name, p.biz_dt, p.out_type,
					p.bill_memo, p.to_warehouse_id, w2.name as to_warehouse_name,
					p.spobill_id, spo.ref as spobill_ref, p.from_org_id, g1.full_name as from_org_name,
					p.to_org_id, g2.full_name as to_org_name, p.out_type
				from t_ld_bill p, t_supplier s, t_warehouse w, t_user u, t_warehouse w2,
					t_spo_bill spo, t_org g1, t_org g2
				where p.id = '%s' and p.from_warehouse_id = w.id
				  and p.biz_user_id = u.id and p.to_warehouse_id = w2.id 
					and p.spobill_id = spo.id and spo.supplier_id = s.id
					and p.from_org_id = g1.id and p.to_org_id = g2.id ";
		$data = $db->query($sql, $id);
		if ($data) {
			$v = $data[0];
			$result["ref"] = $v["ref"];
			$result["billStatus"] = $v["bill_status"];
			$result["supplierName"] = $v["supplier_name"];
			$result["fromWarehouseId"] = $v["from_warehouse_id"];
			$result["fromWarehouseName"] = $v["from_warehouse_name"];
			$result["toWarehouseId"] = $v["to_warehouse_id"];
			$result["toWarehouseName"] = $v["to_warehouse_name"];
			$result["bizUserId"] = $v["biz_user_id"];
			$result["bizUserName"] = $v["biz_user_name"];
			$result["bizDT"] = $this->toYMD($v["biz_dt"]);
			$result["billMemo"] = $v["bill_memo"];
			$result["spoBillId"] = $v["spobill_id"];
			$result["spoBillRef"] = $v["spobill_ref"];
			$result["fromOrgId"] = $v["from_org_id"];
			$result["fromOrgName"] = $v["from_org_name"];
			$result["toOrgId"] = $v["to_org_id"];
			$result["toOrgName"] = $v["to_org_name"];
			$result["outType"] = $v["out_type"];
			
			// 物资明细
			$items = [];
			$sql = "select p.id, p.goods_id, g.code, g.name, g.spec, u.name as unit_name, u.id as unit_id,
						convert(p.goods_count, $fmt) as goods_count,
						p.spobilldetail_id, p.qc_begin_dt, p.qc_end_dt, p.qc_days, p.qc_sn,
						convert(p.sku_goods_count, $fmt) as sku_goods_count,p.factor, p.factor_type,
						u2.id as sku_unit_id, u2.name as sku_unit_name, g.use_qc
					from t_ld_bill_detail p, t_goods g, t_goods_unit u, t_goods_unit u2
						where p.goods_Id = g.id and p.unit_id = u.id and p.ldbill_id = '%s'
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
						"spoBillDetailId" => $v["spobilldetail_id"],
						"qcBeginDT" => $this->toQcYMD($v["qc_begin_dt"]),
						"qcEndDT" => $this->toQcYMD($v["qc_end_dt"]),
						"qcDays" => $this->toQcDays($v["qc_days"]),
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
		} else {
			// 新建物流单
			$result["bizUserId"] = $params["loginUserId"];
			$result["bizUserName"] = $params["loginUserName"];
		}
		
		return $result;
	}

	/**
	 * 提交出库
	 *
	 * @param array $params        	
	 */
	public function commitLDBill(&$params) {
		$db = $this->db;
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		// id: 物流单主表id
		$id = $params["id"];
		
		$sql = "select ref, bill_status, out_type,
					from_warehouse_id, biz_dt, biz_user_id
				from t_ld_bill
				where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要提交出库的物流单不存在");
		}
		$billStatus = $data[0]["bill_status"];
		if ($billStatus > 0) {
			return $this->bad("当前物流单已经提交出库，不能再次提交");
		}
		$ref = $data[0]["ref"];
		$outType = $data[0]["out_type"];
		$fromWarehouseId = $data[0]["from_warehouse_id"];
		$bizDT = $data[0]["biz_dt"];
		$bizUserId = $data[0]["biz_user_id"];
		
		// 检查出库仓库
		$sql = "select name, inited from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $fromWarehouseId);
		if (! $data) {
			return $this->bad("出库仓库不存在");
		}
		$inited = $data["0"]["inited"];
		if ($inited == 0) {
			$fromWarehouseName = $data[0]["name"];
			return $this->bad("仓库[{$fromWarehouseName}]还没有建账，不能进行业务操作");
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// 发货明细
		$sql = "select id, goods_id, qc_begin_dt, qc_days, qc_end_dt, qc_sn, 
					convert(sku_goods_count, $fmt) as goods_count
				from t_ld_bill_detail
				where ldbill_id = '%s'
				order by show_order";
		$items = $db->query($sql, $id);
		$goodsDAO = new GoodsDAO($db);
		$inventoryDAO = new InventoryDAO($db);
		foreach ( $items as $i => $item ) {
			$detailId = $item["id"];
			$goodsId = $item["goods_id"];
			if (! $goodsDAO->getGoodsById($goodsId)) {
				continue;
			}
			
			$qcBeginDT = $this->toQcYMD($item["qc_begin_dt"]);
			if (! $qcBeginDT) {
				$qcBeginDT = "1970-01-01";
			}
			$qcEndDT = $this->toQcYMD($item["qc_end_dt"]);
			if (! $qcEndDT) {
				$qcEndDT = "1970-01-01";
			}
			$qcDays = $item["qc_days"];
			if (! $qcDays) {
				$qcDays = 0;
			}
			$qcSN = $item["qc_sn"];
			
			$goodsCount = $item["goods_count"];
			
			$recordIndex = $i + 1;
			
			$outPrice = 0;
			$outMoney = 0;
			$rc = $inventoryDAO->outAction($fromWarehouseId, $goodsId, $qcBeginDT, $qcDays, 
					$qcEndDT, $qcSN, $goodsCount, $bizDT, $bizUserId, $ref, '物流单发货出库', $outType, 
					$recordIndex, $fmt, $outPrice, $outMoney);
			if ($rc) {
				return $rc;
			}
			
			// 同步发货物资的存货单价和金额
			$sql = "update t_ld_bill_detail
						set inv_goods_price = %f, inv_goods_money = %f
					where id = '%s' ";
			$rc = $db->execute($sql, $outPrice, $outMoney, $detailId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 更新物流单状态为已发货
		$sql = "update t_ld_bill
				set bill_status = 1000
				where id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 操作成功
		$params["ref"] = $ref;
		return null;
	}

	/**
	 * 物流单主表列表
	 *
	 * @param array $params        	
	 */
	public function ldbillListForSRG($params) {
		$db = $this->db;
		
		$start = $params["start"];
		$limit = $params["limit"];
		
		$loginUserId = $params["loginUserId"];
		
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->emptyResult();
		}
		$billStatus = $params["billStatus"];
		$ref = $params["ref"];
		$fromDT = $params["fromDT"];
		$toDT = $params["toDT"];
		
		$sql = "select org_id from t_user where id = '%s' ";
		$data = $db->query($sql, $loginUserId);
		$orgId = $data[0]["org_id"];
		
		$queryParams = [];
		$sql = "select ld.id, ld.bill_status, ld.ref, ld.biz_dt, u1.name as biz_user_name, u2.name as input_user_name,
					w.name as to_warehouse_name,
					ld.date_created, ld.bill_memo, g1.full_name as to_org_name, g2.full_name as from_org_name,
					w2.name as from_warehouse_name, spo.ref as spobill_ref, ld.out_type
				from t_ld_bill ld, t_warehouse w, t_user u1, t_user u2, t_org g1, t_org g2,
					t_warehouse w2, t_spo_bill spo
				where (ld.to_warehouse_id = w.id)
					and (ld.biz_user_id = u1.id) and (ld.input_user_id = u2.id)
					and (ld.to_org_id = g1.id) and (ld.from_org_id = g2.id)
					and (ld.from_warehouse_id = w2.id)
					and (ld.spobill_id = spo.id) and (ld.bill_status > 0) ";
		
		if ($billStatus != - 1) {
			$sql .= " and (ld.bill_status = %d) ";
			$queryParams[] = $billStatus;
		}
		if ($ref) {
			$sql .= " and (ld.ref like '%s') ";
			$queryParams[] = "%{$ref}%";
		}
		if ($fromDT) {
			$sql .= " and (ld.biz_dt >= '%s') ";
			$queryParams[] = $fromDT;
		}
		if ($toDT) {
			$sql .= " and (ld.biz_dt <= '%s') ";
			$queryParams[] = $toDT;
		}
		if ($orgId) {
			$sql .= " and (ld.to_org_id = '%s') ";
			$queryParams[] = $orgId;
		}
		
		$sql .= " order by ld.ref desc limit %d, %d";
		$queryParams[] = $start;
		$queryParams[] = $limit;
		
		$data = $db->query($sql, $queryParams);
		$result = [];
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"ref" => $v["ref"],
					"fromWarehouseName" => $v["from_warehouse_name"],
					"fromOrgName" => $v["from_org_name"],
					"toWarehouseName" => $v["to_warehouse_name"],
					"toOrgName" => $v["to_org_name"],
					"spoBillRef" => $v["spobill_ref"],
					"outType" => $v["out_type"],
					"bizUserName" => $v["biz_user_name"],
					"bizDT" => $this->toYMD($v["biz_dt"]),
					"inputUserName" => $v["input_user_name"],
					"dateCreated" => $v["date_created"],
					"billMemo" => $v["bill_memo"],
					"billStatus" => $v["bill_status"]
			];
		}
		
		$queryParams = [];
		$sql = "select count(*) as cnt
				from t_ld_bill ld, t_warehouse w, t_user u1, t_user u2, t_org g1, t_org g2,
					t_warehouse w2, t_spo_bill spo
				where (ld.to_warehouse_id = w.id)
					and (ld.biz_user_id = u1.id) and (ld.input_user_id = u2.id)
					and (ld.to_org_id = g1.id) and (ld.from_org_id = g2.id)
					and (ld.from_warehouse_id = w2.id)
					and (ld.spobill_id = spo.id)  and (ld.bill_status > 0) ";
		
		if ($billStatus != - 1) {
			$sql .= " and (ld.bill_status = %d) ";
			$queryParams[] = $billStatus;
		}
		if ($ref) {
			$sql .= " and (ld.ref like '%s') ";
			$queryParams[] = "%{$ref}%";
		}
		if ($fromDT) {
			$sql .= " and (ld.biz_dt >= '%s') ";
			$queryParams[] = $fromDT;
		}
		if ($toDT) {
			$sql .= " and (ld.biz_dt <= '%s') ";
			$queryParams[] = $toDT;
		}
		if ($orgId) {
			$sql .= " and (ld.to_org_id = '%s') ";
			$queryParams[] = $orgId;
		}
		$data = $db->query($sql, $queryParams);
		$cnt = $data[0]["cnt"];
		
		return [
				"dataList" => $result,
				"totalCount" => $cnt
		];
	}

	/**
	 * 物流单明细记录 - 门店收货
	 *
	 * @param array $params        	
	 */
	public function ldBillDetailListForSRG($params) {
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		
		$db = $this->db;
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// 物流单id
		$id = $params["id"];
		
		$sql = "select p.id, g.code, g.name, g.spec, u.name as unit_name,
					convert(p.goods_count, $fmt) as goods_count,
					convert(p.rej_goods_count, $fmt) as rej_goods_count,
					convert(p.rev_goods_count, $fmt) as rev_goods_count,
					p.factor, p.factor_type,
					convert(p.rev_sku_goods_count, $fmt) as sku_goods_count,
					p.qc_begin_dt, p.qc_end_dt, p.qc_days, p.qc_sn, u2.name as sku_unit_name
				from t_ld_bill_detail p, t_goods g, t_goods_unit u, t_goods_unit u2
				where p.ldbill_id = '%s' and p.goods_id = g.id and p.unit_id = u.id and p.sku_goods_unit_id = u2.id
					order by p.show_order ";
		$data = $db->query($sql, $id);
		$result = [];
		
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"unitName" => $v["unit_name"],
					"goodsCount" => $v["goods_count"],
					"rejCount" => $v["rej_goods_count"],
					"revCount" => $v["rev_goods_count"],
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
	 * 某个物流单的详情 - 门店收货
	 *
	 * @param array $params        	
	 */
	public function ldBillInfoForSRG($params) {
		$db = $this->db;
		
		// 公司id
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// id: 物流单id
		$id = $params["id"];
		
		$result = [];
		
		$sql = "select p.ref, p.bill_status, s.name as supplier_name,
					p.from_warehouse_id, w.name as from_warehouse_name,
					p.biz_user_id, u.name as biz_user_name, p.biz_dt, p.out_type,
					p.bill_memo, p.to_warehouse_id, w2.name as to_warehouse_name,
					p.spobill_id, spo.ref as spobill_ref, p.from_org_id, g1.full_name as from_org_name,
					p.to_org_id, g2.full_name as to_org_name
				from t_ld_bill p, t_supplier s, t_warehouse w, t_user u, t_warehouse w2,
					t_spo_bill spo, t_org g1, t_org g2
				where p.id = '%s' and p.from_warehouse_id = w.id
				  and p.biz_user_id = u.id and p.to_warehouse_id = w2.id
					and p.spobill_id = spo.id and spo.supplier_id = s.id
					and p.from_org_id = g1.id and p.to_org_id = g2.id ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->emptyResult();
		}
		
		$v = $data[0];
		$result["ref"] = $v["ref"];
		$result["billStatus"] = $v["bill_status"];
		$result["supplierName"] = $v["supplier_name"];
		$result["fromWarehouseId"] = $v["from_warehouse_id"];
		$result["fromWarehouseName"] = $v["from_warehouse_name"];
		$result["toWarehouseId"] = $v["to_warehouse_id"];
		$result["toWarehouseName"] = $v["to_warehouse_name"];
		$result["bizUserId"] = $v["biz_user_id"];
		$result["bizUserName"] = $v["biz_user_name"];
		$result["bizDT"] = $this->toYMD($v["biz_dt"]);
		$result["billMemo"] = $v["bill_memo"];
		$result["spoBillId"] = $v["spobill_id"];
		$result["spoBillRef"] = $v["spobill_ref"];
		$result["fromOrgId"] = $v["from_org_id"];
		$result["fromOrgName"] = $v["from_org_name"];
		$result["toOrgId"] = $v["to_org_id"];
		$result["toOrgName"] = $v["to_org_name"];
		
		// 物资明细
		$items = [];
		$sql = "select p.id, p.goods_id, g.code, g.name, g.spec, u.name as unit_name, u.id as unit_id,
					convert(p.goods_count, $fmt) as goods_count,
					p.spobilldetail_id, p.qc_begin_dt, p.qc_end_dt, p.qc_days, p.qc_sn,
					convert(p.rev_sku_goods_count, $fmt) as sku_goods_count,p.factor, p.factor_type,
					u2.id as sku_unit_id, u2.name as sku_unit_name, g.use_qc,
					convert(p.rev_goods_count, $fmt) as rev_goods_count,
					p.rev_factor, p.rev_edit_flag
				from t_ld_bill_detail p, t_goods g, t_goods_unit u, t_goods_unit u2
					where p.goods_Id = g.id and p.unit_id = u.id and p.ldbill_id = '%s'
					order by p.show_order";
		$data = $db->query($sql, $id);
		foreach ( $data as $v ) {
			$edited = $v["rev_edit_flag"] == 1;
			$revGoodsCount = $edited ? $v["rev_goods_count"] : $v["goods_count"];
			$revFactor = $edited ? $v["rev_factor"] : $v["factor"];
			$revSKUGoodsCount = $revGoodsCount * $revFactor;
			$rejGoodsCount = $v["goods_count"] - $revGoodsCount;
			
			$items[] = [
					"id" => $v["id"],
					"goodsId" => $v["goods_id"],
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"unitId" => $v["unit_id"],
					"unitName" => $v["unit_name"],
					"goodsCount" => $v["goods_count"],
					"revGoodsCount" => $revGoodsCount,
					"spoBillDetailId" => $v["spobilldetail_id"],
					"qcBeginDT" => $this->toQcYMD($v["qc_begin_dt"]),
					"qcEndDT" => $this->toQcYMD($v["qc_end_dt"]),
					"qcDays" => $this->toQcDays($v["qc_days"]),
					"qcSN" => $v["qc_sn"],
					"useQC" => $v["use_qc"],
					"factor" => $revFactor,
					"factorType" => $v["factor_type"],
					"skuUnitId" => $v["sku_unit_id"],
					"skuUnitName" => $v["sku_unit_name"],
					"skuGoodsCount" => $revSKUGoodsCount,
					"rejGoodsCount" => $rejGoodsCount
			];
		}
		
		$result["items"] = $items;
		
		return $result;
	}

	/**
	 * 编辑物流单，录入收货数据 - 门店收货
	 *
	 * @param array $bill        	
	 */
	public function updateLDBillForSRG(&$bill) {
		$db = $this->db;
		
		$id = $bill["id"];
		$b = $this->getLDBillById($id);
		if (! $b) {
			return $this->bad("要编辑的物流单不存在");
		}
		$billStatus = $b["billStatus"];
		if ($billStatus > 1000) {
			return $this->bad("当前物流单已经收货入库，不能再编辑");
		}
		$ref = $b["ref"];
		
		$companyId = $bill["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		$loginUserId = $bill["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->badParam("loginUserId");
		}
		$dataOrg = $bill["dataOrg"];
		if ($this->dataOrgNotExists($dataOrg)) {
			return $this->badParam("dataOrg");
		}
		
		// 发货明细
		$items = $bill["items"];
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$ref = $this->genNewBillRef($companyId);
		
		// 明细表
		
		foreach ( $items as $i => $item ) {
			$detailId = $item["id"];
			// 发货数量
			$revGoodsCount = $item["revGoodsCount"];
			// 转换率
			$factor = $item["factor"];
			$sql = "select convert(goods_count, $fmt) as goods_count
					from t_ld_bill_detail where id = '%s' ";
			$data = $db->query($sql, $detailId);
			if (! $data) {
				continue;
			}
			$goodsCount = $data[0]["goods_count"];
			if ($revGoodsCount > $goodsCount) {
				$recordIndex = $i + 1;
				return $this->bad("第{$recordIndex}条记录收货数量超过了发货数量");
			}
			
			$rejGoodsCount = $goodsCount - $revGoodsCount;
			$revSkuGoodsCount = $revGoodsCount * $factor;
			$rejSkuGoodsCount = $rejGoodsCount * $factor;
			
			$sql = "update t_ld_bill_detail
						set rev_edit_flag = 1, rev_factor_type = factor_type, rej_factor_type = factor_type,
							rev_factor = %f, rej_factor = %f, rev_goods_count = convert(%f, $fmt),
							rev_sku_goods_count = convert(%f, $fmt), 
							rej_goods_count = convert(%f, $fmt),
							rej_sku_goods_count = convert(%f, $fmt)
					where id = '%s' ";
			
			$rc = $db->execute($sql, $factor, $factor, $revGoodsCount, $revSkuGoodsCount, 
					$rejGoodsCount, $rejSkuGoodsCount, $detailId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 操作成功
		$bill["ref"] = $ref;
		return null;
	}

	/**
	 * 提交入库 - 门店收货
	 *
	 * @param array $params        	
	 */
	public function commitLDBillForSRG(& $params) {
		$db = $this->db;
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		// 物流单主表id
		$id = $params["id"];
		$sql = "select ref, bill_status, to_warehouse_id, biz_user_id, biz_dt, spobill_id 
				from t_ld_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要提交入库的物流单不存在");
		}
		$v = $data[0];
		$ref = $v["ref"];
		$billStatus = $v["bill_status"];
		$bizUserId = $v["biz_user_id"];
		$bizDT = $v["biz_dt"];
		if ($billStatus > 1000) {
			return $this->bad("物流单[单号：{$ref}]已经提交入库了，不能再次提交");
		}
		// 入库仓库id
		$warehouseId = $v["to_warehouse_id"];
		// 门店订货单id
		$spoBillId = $v["spobill_id"];
		
		// 判断是否录入过收货数据了
		$sql = "select count(*) as cnt from t_ld_bill_detail where rev_edit_flag = 0
					and ldbill_id = '%s' ";
		$data = $db->query($sql, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("当前物流单还没有录入过收货数据，不能提交入库");
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// 物资明细
		$sql = "select id, goods_id, qc_begin_dt, qc_end_dt, qc_days, qc_sn,
					convert(rev_sku_goods_count, $fmt) as rev_sku_goods_count,
					inv_goods_price, inv_goods_money,
					convert(goods_count, $fmt) as goods_count,
					convert(rev_goods_count, $fmt) as rev_goods_count,
					convert(rej_goods_count, $fmt) as rej_goods_count,
					factor, spobilldetail_id
				from t_ld_bill_detail
				where ldbill_id = '%s'
				order by show_order";
		$items = $db->query($sql, $id);
		
		$goodsDAO = new GoodsDAO($db);
		$inventoryDAO = new InventoryDAO($db);
		$refType = "物流单门店收货入库";
		foreach ( $items as $i => $v ) {
			$detailId = $v["id"];
			$spoBillDetailId = $v["spobilldetail_id"];
			$goodsId = $v["goods_id"];
			if (! $goodsDAO->getGoodsById($goodsId)) {
				continue;
			}
			$qcBeginDT = $v["qc_begin_dt"];
			$qcEndDT = $v["qc_end_dt"];
			$qcDays = $v["qc_days"];
			$qcSN = $v["qc_sn"];
			$revSkuGodsCount = $v["rev_sku_goods_count"];
			$invGoodsPrice = $v["inv_goods_price"];
			$invGoodsMoney = $v["inv_goods_money"];
			$goodsCount = $v["goods_count"];
			$revGoodsCount = $v["rev_goods_count"];
			$factor = $v["factor"];
			$rejGoodsCount = $v["rej_goods_count"];
			
			if ($goodsCount < $revGoodsCount) {
				$recordIndex = $i + 1;
				return $this->bad("第{$recordIndex}条记录中收货数量大于发货数量");
			}
			$goodsMoney = $invGoodsMoney;
			if ($revGoodsCount < $goodsCount) {
				// 有退货
				$goodsMoney = $invGoodsPrice * $revSkuGodsCount;
			}
			
			if ($goodsMoney > $invGoodsMoney) {
				$goodsMoney = $invGoodsMoney;
			}
			$goodsPrice = $goodsMoney / $revSkuGodsCount;
			$rc = $inventoryDAO->inAction($companyId, $warehouseId, $goodsId, $qcBeginDT, $qcDays, 
					$qcEndDT, $qcSN, $revSkuGodsCount, $goodsMoney, $goodsPrice, $bizDT, $bizUserId, 
					$ref, $refType);
			if ($rc) {
				return $rc;
			}
			
			// 更新退货的存货金额和单价
			$rejMoney = $invGoodsMoney - $goodsMoney;
			$rejPrice = $invGoodsPrice;
			if ($rejGoodsCount > 0) {
				$rejPrice = $rejMoney / ($rejGoodsCount * $factor);
			}
			
			$sql = "update t_ld_bill_detail
						set rev_goods_price = %f, rev_goods_money = %f,
							rej_goods_price = %f, rej_goods_money = %f
					where id = '%s' ";
			$rc = $db->execute($sql, $goodsPrice, $goodsMoney, $rejPrice, $rejMoney, $detailId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
			
			// 更新门店订货单明细中的收货信息
			$sql = "select convert(goods_count, $fmt) as goods_count, 
						convert(pw_count, $fmt) as pw_count, 
						convert(left_count, $fmt) as left_count
					from t_spo_bill_detail
					where id = '%s' ";
			$data = $db->query($sql, $spoBillDetailId);
			if ($data) {
				$v = $data[0];
				$orderTotalCount = $v["goods_count"];
				$orderPWCount = $v["pw_count"];
				$orderLeftCount = $v["left_count"];
				
				$orderPWCount += $revGoodsCount;
				$orderLeftCount = $orderTotalCount - $orderPWCount;
				$sql = "update t_spo_bill_detail
						set pw_count = convert(%f, $fmt),
							left_count = convert(%f, $fmt)
						where id = '%s' ";
				$rc = $db->execute($sql, $orderPWCount, $orderLeftCount, $spoBillDetailId);
				if ($rc === false) {
					return $this->sqlError(__METHOD__, __LINE__);
				}
			}
		}
		
		// 更新门店订货单单据状态
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
		
		// 更新物流单单据状态
		$sql = "select count(*) as cnt 
				from t_ld_bill_detail
				where ldbill_id = '%s' and rej_goods_count > 0";
		$data = $db->query($sql, $id);
		// 是否有退货
		$hasRej = $data[0]["cnt"] > 0;
		
		$billStatus = 2000; // 全部收货
		if ($hasRej) {
			$billStatus = 3000; // 部分收货并退货待入库
		}
		$sql = "update t_ld_bill set bill_status = %d where id = '%s' ";
		$rc = $db->execute($sql, $billStatus, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 操作成功
		$params["ref"] = $ref;
		return null;
	}

	/**
	 * 门店退货提交入库
	 *
	 * @param array $params        	
	 */
	public function commitLDBillRej(&$params) {
		$db = $this->db;
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		// 物流单主表id
		$id = $params["id"];
		$sql = "select ref, bill_status, from_warehouse_id, biz_user_id, biz_dt from t_ld_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要提交入库的物流单不存在");
		}
		$v = $data[0];
		$ref = $v["ref"];
		$billStatus = $v["bill_status"];
		$bizUserId = $v["biz_user_id"];
		$bizDT = $v["biz_dt"];
		if ($billStatus > 3000) {
			return $this->bad("物流单[单号：{$ref}]已经提交入库了，不能再次提交");
		}
		// 入库仓库id
		$warehouseId = $v["from_warehouse_id"];
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// 物资明细
		$sql = "select id, goods_id, qc_begin_dt, qc_end_dt, qc_days, qc_sn,
					convert(rej_sku_goods_count, $fmt) as rej_sku_goods_count,
					rej_goods_price, rej_goods_money,
					factor
				from t_ld_bill_detail
				where ldbill_id = '%s'
				order by show_order";
		$items = $db->query($sql, $id);
		
		$goodsDAO = new GoodsDAO($db);
		$inventoryDAO = new InventoryDAO($db);
		$refType = "物流单门店退货入库";
		foreach ( $items as $i => $v ) {
			$detailId = $v["id"];
			$goodsId = $v["goods_id"];
			if (! $goodsDAO->getGoodsById($goodsId)) {
				continue;
			}
			$qcBeginDT = $v["qc_begin_dt"];
			$qcEndDT = $v["qc_end_dt"];
			$qcDays = $v["qc_days"];
			$qcSN = $v["qc_sn"];
			$rejSkuGoodsCount = $v["rej_sku_goods_count"];
			$rejGoodsPrice = $v["rej_goods_price"];
			$rejGoodsMoney = $v["rej_goods_money"];
			$factor = $v["factor"];
			
			$goodsMoney = $rejGoodsMoney;
			$goodsPrice = $goodsMoney / $rejSkuGoodsCount;
			$rc = $inventoryDAO->inAction($companyId, $warehouseId, $goodsId, $qcBeginDT, $qcDays, 
					$qcEndDT, $qcSN, $rejSkuGoodsCount, $goodsMoney, $goodsPrice, $bizDT, $bizUserId, 
					$ref, $refType);
			if ($rc) {
				return $rc;
			}
		}
		
		// 更新单据状态: 4000 - 退货已入库
		$sql = "update t_ld_bill set bill_status = 4000 where id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 操作成功
		$params["ref"] = $ref;
		return null;
	}
}