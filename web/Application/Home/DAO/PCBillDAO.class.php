<?php

namespace Home\DAO;

use Home\Common\FIdConst;

/**
 * 供应合同 DAO
 *
 * @author 李静波
 */
class PCBillDAO extends PSIBaseExDAO {

	/**
	 * 某个供应合同的详细信息
	 */
	public function pcBillInfo($params) {
		$db = $this->db;
		
		$id = $params["id"];
		
		$sql = "select c.ref, c.biz_dt, c.from_dt, c.to_dt, 
					c.supplier_id, s.name as supplier_name,
					c.biz_user_id, u.name as biz_user_name, c.bill_memo
				from t_pc_bill c, t_supplier s, t_user u
				where c.id = '%s' and c.supplier_id = s.id and c.biz_user_id = u.id ";
		$data = $db->query($sql, $id);
		if (! $data) {
			// 该合同不存在，通常是新建合同的场景
			return [
					"bizUserId" => $params["loginUserId"],
					"bizUserName" => $params["loginUserName"]
			];
		}
		
		$v = $data[0];
		$result = [
				"ref" => $v["ref"],
				"bizDT" => $this->toYMD($v["biz_dt"]),
				"fromDT" => $this->toYMD($v["from_dt"]),
				"toDT" => $this->toYMD($v["to_dt"]),
				"supplierId" => $v["supplier_id"],
				"supplierName" => $v["supplier_name"],
				"bizUserId" => $v["biz_user_id"],
				"bizUserName" => $v["biz_user_name"],
				"billMemo" => $v["bill_memo"]
		];
		
		$items = [];
		
		$sql = "select d.id, g.id as goods_id, g.code as goods_code, 
					g.name as goods_name, g.spec as goods_spec,
					d.unit_id, u.name as unit_name, d.goods_price, d.memo
				from t_pc_bill_detail d, t_goods g, t_goods_unit u
				where d.pcbill_id = '%s' and d.goods_id = g.id and d.unit_id = u.id
				order by d.show_order ";
		
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
					"goodsPrice" => $v["goods_price"],
					"memo" => $v["memo"]
			];
		}
		
		$result["items"] = $items;
		
		return $result;
	}

	/**
	 * 新建供应合同
	 *
	 * @param array $bill        	
	 * @return NULL|array
	 */
	public function addPCBill(&$bill) {
		$db = $this->db;
		
		$ref = $bill["ref"];
		
		$bizDT = $bill["bizDT"];
		$fromDT = $bill["fromDT"];
		$toDT = $bill["toDT"];
		$supplierId = $bill["supplierId"];
		$bizUserId = $bill["bizUserId"];
		$billMemo = $bill["billMemo"];
		
		$loginUserId = $bill["loginUserId"];
		$dataOrg = $bill["dataOrg"];
		$companyId = $bill["companyId"];
		
		// 检查合同号是否已经存在
		$sql = "select count(*) as cnt from t_pc_bill where ref = '%s' ";
		$data = $db->query($sql, $ref);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("合同号为{$ref}的供应合同已经存在");
		}
		
		if (! $this->dateIsValid($bizDT)) {
			return $this->bad("合同签订日期不正确");
		}
		if (! $this->dateIsValid($fromDT)) {
			return $this->bad("合同开始日期不正确");
		}
		if (! $this->dateIsValid($toDT)) {
			return $this->bad("合同结束日期不正确");
		}
		
		// 检查供应商是否存在
		$supplierDAO = new SupplierDAO($db);
		$supplier = $supplierDAO->getSupplierById($supplierId);
		if (! $supplier) {
			return $this->bad("供应商不存在");
		}
		
		// 检查业务员
		$userDAO = new UserDAO($db);
		$bizUser = $userDAO->getUserById($bizUserId);
		if (! $bizUser) {
			return $this->bad("业务员不存在");
		}
		
		$id = $this->newId();
		$bill["id"] = $id;
		
		// 主表
		$sql = "insert into t_pc_bill (id, ref, biz_dt, from_dt, to_dt, supplier_id,
					biz_user_id, input_user_id, date_created, bill_status, bill_memo,
					data_org, company_id)
				values ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', now(), 0, '%s', '%s', '%s')";
		$rc = $db->execute($sql, $id, $ref, $bizDT, $fromDT, $toDT, $supplierId, $bizUserId, 
				$loginUserId, $billMemo, $dataOrg, $companyId);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 明细表
		$items = $bill["items"];
		
		$goodsDAO = new GoodsDAO($db);
		$unitDAO = new GoodsUnitDAO($db);
		foreach ( $items as $i => $v ) {
			$goodsId = $v["goodsId"];
			if (! $goodsId) {
				continue;
			}
			
			$goods = $goodsDAO->getGoodsById($goodsId);
			if (! $goods) {
				continue;
			}
			
			$goodsPrice = $v["goodsPrice"];
			if ($goodsPrice < 0) {
				$goodsPrice = 0;
			}
			$memo = $v["memo"];
			
			$unitId = $v["unitId"];
			// 检查unitId
			if (! $unitDAO->unitIdIsValid($goodsId, $unitId)) {
				$recordIndex = $i + 1;
				return $this->bad("第{$recordIndex}条记录中，传入的计量单位不正确");
			}
			
			$rc = $unitDAO->updateGoodsUnitDefault($goodsId, $unitId, "t_pc_bill");
			if ($rc) {
				return $rc;
			}
			
			$detailId = $this->newId();
			$sql = "insert into t_pc_bill_detail(id, pcbill_id, show_order, goods_id, goods_price, memo,
						date_created, data_org, company_id, unit_id)
					values ('%s', '%s', %d, '%s', %f, '%s', now(), '%s', '%s', '%s')";
			$rc = $db->execute($sql, $detailId, $id, $i, $goodsId, $goodsPrice, $memo, $dataOrg, 
					$companyId, $unitId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 操作成功
		return null;
	}

	/**
	 * 编辑供应合同
	 *
	 * @param array $bill        	
	 * @return NULL|array
	 */
	public function updatePCBill(&$bill) {
		$db = $this->db;
		
		// 供应合同主表id
		$id = $bill["id"];
		
		$ref = $bill["ref"];
		
		$bizDT = $bill["bizDT"];
		$fromDT = $bill["fromDT"];
		$toDT = $bill["toDT"];
		$supplierId = $bill["supplierId"];
		$bizUserId = $bill["bizUserId"];
		$billMemo = $bill["billMemo"];
		
		$loginUserId = $bill["loginUserId"];
		
		$sql = "select bill_status, data_org, company_id from t_pc_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要编辑的供应合同不存在");
		}
		$billStatus = $data[0]["bill_status"];
		if ($billStatus != 0) {
			return $this->bad("供应合同被审核后不能再修改");
		}
		$dataOrg = $data[0]["data_org"];
		$companyId = $data[0]["company_id"];
		if ($this->dataOrgNotExists($dataOrg)) {
			return $this->badParam("dataOrg");
		}
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		// 检查合同号是否已经存在
		$sql = "select count(*) as cnt from t_pc_bill 
				where ref = '%s' and id <> '%s' ";
		$data = $db->query($sql, $ref, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("合同号为{$ref}的供应合同已经存在");
		}
		
		if (! $this->dateIsValid($bizDT)) {
			return $this->bad("合同签订日期不正确");
		}
		if (! $this->dateIsValid($fromDT)) {
			return $this->bad("合同开始日期不正确");
		}
		if (! $this->dateIsValid($toDT)) {
			return $this->bad("合同结束日期不正确");
		}
		
		// 检查供应商是否存在
		$supplierDAO = new SupplierDAO($db);
		$supplier = $supplierDAO->getSupplierById($supplierId);
		if (! $supplier) {
			return $this->bad("供应商不存在");
		}
		
		// 检查业务员
		$userDAO = new UserDAO($db);
		$bizUser = $userDAO->getUserById($bizUserId);
		if (! $bizUser) {
			return $this->bad("业务员不存在");
		}
		
		// 主表
		$sql = "update t_pc_bill
				set ref = '%s', biz_dt = '%s', from_dt = '%s',
					to_dt = '%s', supplier_id = '%s', bill_memo = '%s'
				where id = '%s' ";
		$rc = $db->execute($sql, $ref, $bizDT, $fromDT, $toDT, $supplierId, $billMemo, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 明细表
		$sql = "delete from t_pc_bill_detail where pcbill_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$items = $bill["items"];
		
		$goodsDAO = new GoodsDAO($db);
		$unitDAO = new GoodsUnitDAO($db);
		
		foreach ( $items as $i => $v ) {
			$goodsId = $v["goodsId"];
			if (! $goodsId) {
				continue;
			}
			
			$goods = $goodsDAO->getGoodsById($goodsId);
			if (! $goods) {
				continue;
			}
			
			$goodsPrice = $v["goodsPrice"];
			if ($goodsPrice < 0) {
				$goodsPrice = 0;
			}
			$memo = $v["memo"];
			
			$unitId = $v["unitId"];
			// 检查unitId
			if (! $unitDAO->unitIdIsValid($goodsId, $unitId)) {
				$recordIndex = $i + 1;
				return $this->bad("第{$recordIndex}条记录中，传入的计量单位不正确");
			}
			
			$rc = $unitDAO->updateGoodsUnitDefault($goodsId, $unitId, "t_pc_bill");
			if ($rc) {
				return $rc;
			}
			
			$detailId = $v["id"] ? $v["id"] : $this->newId();
			$sql = "insert into t_pc_bill_detail(id, pcbill_id, show_order, goods_id, goods_price, memo,
						date_created, data_org, company_id, unit_id)
					values ('%s', '%s', %d, '%s', %f, '%s', now(), '%s', '%s', '%s')";
			$rc = $db->execute($sql, $detailId, $id, $i, $goodsId, $goodsPrice, $memo, $dataOrg, 
					$companyId, $unitId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 操作成功
		return null;
	}

	/**
	 * 供应合同主表列表
	 *
	 * @param array $params        	
	 */
	public function pcbillList($params) {
		$db = $this->db;
		
		$ref = $params["ref"];
		$fromDT = $params["fromDT"];
		$toDT = $params["toDT"];
		$billStatus = $params["billStatus"];
		$supplierId = $params["supplierId"];
		
		$start = $params["start"];
		$limit = $params["limit"];
		
		$queryParams = [];
		
		$sql = "select p.id, p.ref, p.biz_dt, p.from_dt, p.to_dt, s.name as supplier_name,
					p.bill_memo, p.date_created, u1.name as biz_user_name, u2.name as input_user_name,
					p.confirm_user_id, p.confirm_date, p.bill_status
				from t_pc_bill p, t_supplier s, t_user u1, t_user u2
				where (p.supplier_id = s.id) and (p.biz_user_id = u1.id) and (p.input_user_id = u2.id) ";
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::SCM_PURCHASE_CONTRACT, "p", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParams = array_merge($queryParams, $rs[1]);
		}
		
		if ($ref) {
			$sql .= " and (p.ref like '%s') ";
			$queryParams[] = "%{$ref}%";
		}
		
		if ($fromDT) {
			$sql .= " and (p.from_dt >= '%s') ";
			$queryParams[] = $fromDT;
		}
		if ($toDT) {
			$sql .= " and (p.to_dt <= '%s') ";
			$queryParams[] = $toDT;
		}
		
		if ($supplierId) {
			$sql .= " and (p.supplier_id = '%s') ";
			$queryParams[] = $supplierId;
		}
		
		if ($billStatus != - 1) {
			$sql .= " and (p.bill_status = %d)";
			$queryParams[] = $billStatus;
		}
		
		$sql .= " order by p.biz_dt desc, p.ref desc 
				  limit %d, %d";
		$queryParams[] = $start;
		$queryParams[] = $limit;
		
		$data = $db->query($sql, $queryParams);
		
		$result = [];
		foreach ( $data as $v ) {
			$confirmUserName = null;
			$confirmDate = null;
			$confirmUserId = $v["confirm_user_id"];
			if ($confirmUserId) {
				$sql = "select name from t_user where id = '%s' ";
				$d = $db->query($sql, $confirmUserId);
				if ($d) {
					$confirmUserName = $d[0]["name"];
					$confirmDate = $v["confirm_date"];
				}
			}
			
			$result[] = [
					"id" => $v["id"],
					"ref" => $v["ref"],
					"bizDT" => $this->toYMD($v["biz_dt"]),
					"fromDT" => $this->toYMD($v["from_dt"]),
					"toDT" => $this->toYMD($v["to_dt"]),
					"supplierName" => $v["supplier_name"],
					"billMemo" => $v["bill_memo"],
					"dateCreated" => $v["date_created"],
					"bizUserName" => $v["biz_user_name"],
					"inputUserName" => $v["input_user_name"],
					"confirmUserName" => $confirmUserName,
					"confirmDate" => $confirmDate,
					"billStatus" => $v["bill_status"]
			];
		}
		
		$queryParams = [];
		
		$sql = "select count(*) as cnt
				from t_pc_bill p, t_supplier s, t_user u1, t_user u2
				where (p.supplier_id = s.id) and (p.biz_user_id = u1.id) and (p.input_user_id = u2.id) ";
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::SCM_PURCHASE_CONTRACT, "p", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParams = array_merge($queryParams, $rs[1]);
		}
		
		if ($ref) {
			$sql .= " and (p.ref like '%s') ";
			$queryParams[] = "%{$ref}%";
		}
		
		if ($fromDT) {
			$sql .= " and (p.from_dt >= '%s') ";
			$queryParams[] = $fromDT;
		}
		if ($toDT) {
			$sql .= " and (p.to_dt <= '%s') ";
			$queryParams[] = $toDT;
		}
		
		if ($supplierId) {
			$sql .= " and (p.supplier_id = '%s') ";
			$queryParams[] = $supplierId;
		}
		
		if ($billStatus != - 1) {
			$sql .= " and (p.bill_status = %d)";
			$queryParams[] = $billStatus;
		}
		
		$data = $db->query($sql, $queryParams);
		$cnt = $data[0]["cnt"];
		
		return [
				"dataList" => $result,
				"totalCount" => $cnt
		];
	}

	public function pcBillDetailList($params) {
		$db = $this->db;
		
		$id = $params["id"];
		
		$result = [];
		
		$sql = "select g.code, g.name, g.spec, u.name as unit_name,
					d.goods_price, d.memo
				from t_pc_bill_detail d, t_goods g, 
					t_goods_unit u
				where d.pcbill_id = '%s' and d.goods_id = g.id 
					and d.unit_id = u.id
				order by d.show_order ";
		
		$data = $db->query($sql, $id);
		
		foreach ( $data as $v ) {
			$result[] = [
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"unitName" => $v["unit_name"],
					"goodsPrice" => $v["goods_price"],
					"memo" => $v["memo"]
			];
		}
		
		return $result;
	}

	/**
	 * 删除供应合同
	 *
	 * @param array $params        	
	 */
	public function deletePCBill(&$params) {
		$db = $this->db;
		
		// 供应合同主表id
		$id = $params["id"];
		
		$sql = "select ref, bill_status from t_pc_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要删除的供应合同不存在");
		}
		$ref = $data[0]["ref"];
		$billStatus = $data[0]["bill_status"];
		if ($billStatus > 0) {
			return $this->bad("供应合同被审核后不能删除");
		}
		
		// 删除明细表
		$sql = "delete from t_pc_bill_detail where pcbill_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 删除主表
		$sql = "delete from t_pc_bill where id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 删除采购模板
		$sql = "select id from t_pc_template where pcbill_id = '%s' ";
		$data = $db->query($sql, $id);
		foreach ( $data as $v ) {
			$pcTemplateId = $v["id"];
			
			// 采购模板使用组织机构
			$sql = "delete from t_pc_template_org where pctemplate_id = '%s' ";
			$rc = $db->execute($sql, $pcTemplateId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
			
			// 采购模板明细
			$sql = "delete from t_pc_template_detail where pctemplate_id = '%s' ";
			$rc = $db->execute($sql, $pcTemplateId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
			
			// 采购模板主表
			$sql = "delete from t_pc_template where id = '%s' ";
			$rc = $db->execute($sql, $pcTemplateId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		$params["ref"] = $ref;
		// 操作成功
		return null;
	}

	/**
	 * 审核供应合同
	 *
	 * @param array $params        	
	 */
	public function commitPCBill(&$params) {
		$db = $this->db;
		
		// 供应合同主表id
		$id = $params["id"];
		
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->badParam("loginUserId");
		}
		
		$sql = "select ref, bill_status from t_pc_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要审核的供应合同不存在");
		}
		$ref = $data[0]["ref"];
		$billStatus = $data[0]["bill_status"];
		if ($billStatus > 0) {
			return $this->bad("当前供应合同已经审核，不能再次审核");
		}
		
		$sql = "update t_pc_bill
				set bill_status = 1000, confirm_date = now(),
					confirm_user_id = '%s'
				where id = '%s' ";
		$rc = $db->execute($sql, $loginUserId, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$params["ref"] = $ref;
		
		// 操作成功
		return null;
	}

	/**
	 * 取消审核
	 *
	 * @param array $params        	
	 */
	public function cancelConfirmPCBill(&$params) {
		$db = $this->db;
		
		// 供应合同主表id
		$id = $params["id"];
		
		$sql = "select ref, bill_status from t_pc_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要取消审核的供应合同不存在");
		}
		$ref = $data[0]["ref"];
		$billStatus = $data[0]["bill_status"];
		if ($billStatus < 1000) {
			return $this->bad("当前供应合同还没有审核，不要取消审核");
		}
		if ($billStatus == 4000) {
			return $this->bad("当前供应合同已经关闭，不能取消审核");
		}
		
		$sql = "update t_pc_bill
				set bill_status = 0, confirm_date = null,
					confirm_user_id = null
				where id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$params["ref"] = $ref;
		
		// 操作成功
		return null;
	}

	/**
	 * 关闭供应合同
	 *
	 * @param array $params        	
	 */
	public function closePCBill(&$params) {
		$db = $this->db;
		
		// 供应合同主表id
		$id = $params["id"];
		
		$sql = "select ref, bill_status from t_pc_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要关闭的供应合同不存在");
		}
		$ref = $data[0]["ref"];
		$billStatus = $data[0]["bill_status"];
		if ($billStatus == 4000) {
			return $this->bad("当前供应合同已经关闭，不用再次关闭");
		}
		
		$sql = "update t_pc_bill
				set bill_status = 4000
				where id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$params["ref"] = $ref;
		
		// 操作成功
		return null;
	}

	/**
	 * 取消关闭供应合同
	 *
	 * @param array $params        	
	 */
	public function cancelClosedPCBill(&$params) {
		$db = $this->db;
		
		// 供应合同主表id
		$id = $params["id"];
		
		$sql = "select ref, bill_status, confirm_user_id 
				from t_pc_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要关闭的供应合同不存在");
		}
		$ref = $data[0]["ref"];
		$billStatus = $data[0]["bill_status"];
		if ($billStatus < 4000) {
			return $this->bad("当前供应合同还没有关闭，不用取消关闭状态");
		}
		
		$confirmUserId = $data[0]["confirm_user_id"];
		// 审核人不为空，则说明该合同被关闭前是审核过的，那么取消关闭状态后就需要回到审核状态
		$newBillStatus = $confirmUserId ? 1000 : 0;
		
		$sql = "update t_pc_bill
				set bill_status = %d
				where id = '%s' ";
		$rc = $db->execute($sql, $newBillStatus, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$params["ref"] = $ref;
		
		// 操作成功
		return null;
	}
}