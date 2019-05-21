<?php

namespace Home\DAO;

use Home\Common\FIdConst;

/**
 * 采购订单 DAO
 *
 * @author 李静波
 */
class POBillDAO extends PSIBaseExDAO {

	/**
	 * 生成新的采购订单号
	 *
	 * @param string $companyId        	
	 * @return string
	 */
	public function genNewBillRef($companyId) {
		$db = $this->db;
		
		$bs = new BizConfigDAO($db);
		$pre = $bs->getPOBillRefPre($companyId);
		
		$mid = date("Ymd");
		
		$sql = "select ref from t_po_bill where ref like '%s' order by ref desc limit 1";
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
	 * 获得采购订单主表信息列表
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function pobillList($params) {
		$db = $this->db;
		
		$start = $params["start"];
		$limit = $params["limit"];
		
		$billStatus = $params["billStatus"];
		$ref = $params["ref"];
		$fromDT = $params["fromDT"];
		$toDT = $params["toDT"];
		$supplierId = $params["supplierId"];
		
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->emptyResult();
		}
		
		$queryParams = [];
		
		$result = [];
		$sql = "select p.id, p.ref, p.bill_status, p.goods_money,
					s.name as supplier_name, p.deal_address,
					p.deal_date,p.bill_memo, p.date_created,
					o.full_name as org_name, u1.name as biz_user_name, u2.name as input_user_name,
					p.confirm_user_id, p.confirm_date
				from t_po_bill p, t_supplier s, t_org o, t_user u1, t_user u2
				where (p.supplier_id = s.id) and (p.org_id = o.id)
					and (p.biz_user_id = u1.id) and (p.input_user_id = u2.id) ";
		
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::PURCHASE_ORDER, "p", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParams = $rs[1];
		}
		
		if ($billStatus != - 1) {
			if ($billStatus < 4000) {
				$sql .= " and (p.bill_status = %d) ";
			} else {
				// 订单关闭 - 有多种状态
				$sql .= " and (p.bill_status >= %d) ";
			}
			$queryParams[] = $billStatus;
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
			
			// 门店订货单单号
			$spoBillRef = "";
			$sql = "select p.ref as spobill_ref
					from t_spo_po s , t_spo_bill p
					where s.po_id = '%s' and s.spo_id = p.id";
			$d = $db->query($sql, $v["id"]);
			if ($d) {
				$spoBillRef = $d[0]["spobill_ref"];
			}
			
			$result[] = [
					"id" => $v["id"],
					"ref" => $v["ref"],
					"spoBillRef" => $spoBillRef,
					"billStatus" => $v["bill_status"],
					"dealDate" => $this->toYMD($v["deal_date"]),
					"dealAddress" => $v["deal_address"],
					"supplierName" => $v["supplier_name"],
					"goodsMoney" => $v["goods_money"],
					"billMemo" => $v["bill_memo"],
					"bizUserName" => $v["biz_user_name"],
					"orgName" => $v["org_name"],
					"inputUserName" => $v["input_user_name"],
					"dateCreated" => $v["date_created"],
					"confirmUserName" => $confirmUserName,
					"confirmDate" => $confirmDate
			];
		}
		
		$sql = "select count(*) as cnt
				from t_po_bill p, t_supplier s, t_org o, t_user u1, t_user u2
				where (p.supplier_id = s.id) and (p.org_id = o.id)
					and (p.biz_user_id = u1.id) and (p.input_user_id = u2.id)
				";
		$queryParams = [];
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::PURCHASE_ORDER, "p", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParams = $rs[1];
		}
		if ($billStatus != - 1) {
			if ($billStatus < 4000) {
				$sql .= " and (p.bill_status = %d) ";
			} else {
				// 订单关闭 - 有多种状态
				$sql .= " and (p.bill_status >= %d) ";
			}
			$queryParams[] = $billStatus;
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
	 * 采购订单的商品明细
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function poBillDetailList($params) {
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
	 * 新建采购订单
	 *
	 * @param array $bill        	
	 * @return NULL|array
	 */
	public function addPOBill(& $bill) {
		$db = $this->db;
		
		$pcTemplateId = $bill["pcTemplateId"];
		
		// 通过采购模板查询供应商id
		$sql = "select b.supplier_id
				from t_pc_template p, t_pc_bill b
				where p.id = '%s' and p.pcbill_id = b.id ";
		$data = $db->query($sql, $pcTemplateId);
		if (! $data) {
			return $this->bad("选择的采购模板不存在");
		}
		$supplierId = $data[0]["supplier_id"];
		
		$dealDate = $bill["dealDate"];
		$bizUserId = $bill["bizUserId"];
		$billMemo = $bill["billMemo"];
		
		$items = $bill["items"];
		
		$dataOrg = $bill["dataOrg"];
		$loginUserId = $bill["loginUserId"];
		$companyId = $bill["companyId"];
		if ($this->dataOrgNotExists($dataOrg)) {
			return $this->badParam("dataOrg");
		}
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->badParam("loginUserId");
		}
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		if (! $this->dateIsValid($dealDate)) {
			return $this->bad("交货日期不正确");
		}
		
		$supplierDAO = new SupplierDAO($db);
		$supplier = $supplierDAO->getSupplierById($supplierId);
		if (! $supplier) {
			return $this->bad("供应商不存在");
		}
		
		$sql = "select org_id from t_user where id = '%s' ";
		$data = $db->query($sql, $loginUserId);
		$orgId = $data[0]["org_id"];
		
		$orgDAO = new OrgDAO($db);
		$org = $orgDAO->getOrgById($orgId);
		if (! $org) {
			return $this->bad("组织机构不存在");
		}
		
		$userDAO = new UserDAO($db);
		$user = $userDAO->getUserById($bizUserId);
		if (! $user) {
			return $this->bad("业务员不存在");
		}
		
		$id = $this->newId();
		$ref = $this->genNewBillRef($companyId);
		
		// 主表
		$sql = "insert into t_po_bill(id, ref, bill_status, deal_date, biz_dt, org_id, biz_user_id,
					goods_money, input_user_id, supplier_id,
					bill_memo, date_created, data_org, company_id, pctemplate_id)
				values ('%s', '%s', 0, '%s', '%s', '%s', '%s',
					0, '%s', '%s',
					'%s', now(), '%s', '%s', '%s')";
		$rc = $db->execute($sql, $id, $ref, $dealDate, $dealDate, $orgId, $bizUserId, $loginUserId, 
				$supplierId, $billMemo, $dataOrg, $companyId, $pcTemplateId);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 明细记录
		$goodsDAO = new GoodsDAO($db);
		$unitDAO = new GoodsUnitDAO($db);
		
		foreach ( $items as $i => $v ) {
			$recordIndex = $i + 1;
			
			$detailId = $v["id"];
			if (! $detailId) {
				$detailId = $this->newId();
			}
			
			$goodsId = $v["goodsId"];
			if (! $goodsId) {
				continue;
			}
			$goods = $goodsDAO->getGoodsById($goodsId);
			if (! $goods) {
				continue;
			}
			
			$goodsCount = $v["goodsCount"];
			if ($goodsCount <= 0) {
				return $this->bad("第{$recordIndex}条记录中，采购数量需要大于0");
			}
			
			$goodsPrice = $v["goodsPrice"];
			if ($goodsPrice < 0) {
				return $this->bad("第{$recordIndex}条记录中，采购单价不能是负数");
			}
			
			$goodsMoney = $v["goodsMoney"];
			$memo = $v["memo"];
			
			$unitId = $v["unitId"];
			if (! $unitDAO->unitIdIsValid($goodsId, $unitId)) {
				return $this->bad("第{$recordIndex}条记录中，计量单位不正确");
			}
			
			$sql = "insert into t_po_bill_detail(id, pctemplate_detail_id, date_created, goods_id, goods_count, goods_money,
						goods_price, pobill_id, pw_count, left_count,
						show_order, data_org, company_id, memo, unit_id)
					values ('%s', '%s', now(), '%s', convert(%f, $fmt), %f,
						%f, '%s', 0, convert(%f, $fmt), 
						%d, '%s', '%s', '%s', '%s')";
			$rc = $db->execute($sql, $this->newId(), $detailId, $goodsId, $goodsCount, $goodsMoney, 
					$goodsPrice, $id, $goodsCount, $i, $dataOrg, $companyId, $memo, $unitId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 同步主表的金额合计字段
		$sql = "select sum(goods_money) as sum_goods_money
				from t_po_bill_detail
				where pobill_id = '%s' ";
		$data = $db->query($sql, $id);
		$sumGoodsMoney = $data[0]["sum_goods_money"];
		if (! $sumGoodsMoney) {
			$sumGoodsMoney = 0;
		}
		
		$sql = "update t_po_bill
				set goods_money = %f
				where id = '%s' ";
		$rc = $db->execute($sql, $sumGoodsMoney, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$bill["id"] = $id;
		$bill["ref"] = $ref;
		
		// 操作成功
		return null;
	}

	/**
	 * 编辑采购订单
	 *
	 * @param array $bill        	
	 * @return NULL|array
	 */
	public function updatePOBill(& $bill) {
		$db = $this->db;
		
		$id = $bill["id"];
		$poBill = $this->getPOBillById($id);
		if (! $poBill) {
			return $this->bad("要编辑的采购订单不存在");
		}
		
		$ref = $poBill["ref"];
		$dataOrg = $poBill["dataOrg"];
		$companyId = $poBill["companyId"];
		$billStatus = $poBill["billStatus"];
		if ($billStatus != 0) {
			return $this->bad("当前采购订单已经审核，不能再编辑");
		}
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$dealDate = $bill["dealDate"];
		$bizUserId = $bill["bizUserId"];
		$billMemo = $bill["billMemo"];
		
		$items = $bill["items"];
		
		$loginUserId = $bill["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->badParam("loginUserId");
		}
		
		if (! $this->dateIsValid($dealDate)) {
			return $this->bad("交货日期不正确");
		}
		
		$userDAO = new UserDAO($db);
		$user = $userDAO->getUserById($bizUserId);
		if (! $user) {
			return $this->bad("业务员不存在");
		}
		
		$sql = "delete from t_po_bill_detail where pobill_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$goodsDAO = new GoodsDAO($db);
		$unitDAO = new GoodsUnitDAO($db);
		
		foreach ( $items as $i => $v ) {
			$recordIndex = $i + 1;
			
			$detailId = $v["id"];
			if (! $detailId) {
				$detailId = $this->newId();
			}
			
			$goodsId = $v["goodsId"];
			if (! $goodsId) {
				continue;
			}
			if (! $goodsDAO->getGoodsById($goodsId)) {
				continue;
			}
			
			$goodsCount = $v["goodsCount"];
			if ($goodsCount <= 0) {
				return $this->bad("第{$recordIndex}条记录中，采购数量需要大于0");
			}
			$goodsPrice = $v["goodsPrice"];
			if ($goodsPrice < 0) {
				return $this->bad("第{$recordIndex}条记录中，采购单价不能是负数");
			}
			$goodsMoney = $v["goodsMoney"];
			$memo = $v["memo"];
			
			$unitId = $v["unitId"];
			if (! $unitDAO->unitIdIsValid($goodsId, $unitId)) {
				return $this->bad("第{$recordIndex}条记录中，计量单位不正确");
			}
			
			$sql = "insert into t_po_bill_detail(id, pctemplate_detail_id, date_created, goods_id, goods_count, goods_money,
						goods_price, pobill_id, pw_count, left_count,
						show_order, data_org, company_id, memo, unit_id)
					values ('%s', '%s', now(), '%s', convert(%f, $fmt), %f,
						%f, '%s', 0, convert(%f, $fmt), %d, '%s', '%s', '%s', '%s')";
			$rc = $db->execute($sql, $detailId, $detailId, $goodsId, $goodsCount, $goodsMoney, 
					$goodsPrice, $id, $goodsCount, $i, $dataOrg, $companyId, $memo, $unitId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 同步主表的金额合计字段
		$sql = "select sum(goods_money) as sum_goods_money
						from t_po_bill_detail
						where pobill_id = '%s' ";
		$data = $db->query($sql, $id);
		$sumGoodsMoney = $data[0]["sum_goods_money"];
		if (! $sumGoodsMoney) {
			$sumGoodsMoney = 0;
		}
		
		$sql = "update t_po_bill
				set goods_money = %f,
					deal_date = '%s',
					biz_user_id = '%s',
					bill_memo = '%s'
				where id = '%s' ";
		$rc = $db->execute($sql, $sumGoodsMoney, $dealDate, $bizUserId, $billMemo, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$bill["ref"] = $ref;
		
		// 操作成功
		return null;
	}

	/**
	 * 根据采购订单id查询采购订单
	 *
	 * @param string $id        	
	 * @return array|NULL
	 */
	public function getPOBillById($id) {
		$db = $this->db;
		
		$sql = "select ref, data_org, bill_status, company_id
				from t_po_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		if ($data) {
			return [
					"ref" => $data[0]["ref"],
					"dataOrg" => $data[0]["data_org"],
					"billStatus" => $data[0]["bill_status"],
					"companyId" => $data[0]["company_id"]
			];
		} else {
			return null;
		}
	}

	/**
	 * 删除采购订单
	 *
	 * @param array $params        	
	 * @return NULL|array
	 */
	public function deletePOBill(& $params) {
		$db = $this->db;
		
		$id = $params["id"];
		
		$bill = $this->getPOBillById($id);
		
		if (! $bill) {
			return $this->bad("要删除的采购订单不存在");
		}
		$ref = $bill["ref"];
		$billStatus = $bill["billStatus"];
		if ($billStatus > 0) {
			return $this->bad("采购订单(单号：{$ref})已经审核，不能被删除");
		}
		
		$params["ref"] = $ref;
		
		// 删除采购订单和门店订货单的关联
		$sql = "delete from t_spo_po where po_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 删除采购订单明细
		$sql = "delete from t_po_bill_detail where pobill_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 删除采购订单主表
		$sql = "delete from t_po_bill where id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		return null;
	}

	/**
	 * 获得采购订单的信息
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function poBillInfo($params) {
		$db = $this->db;
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		
		$id = $params["id"];
		
		$result = [];
		
		$bcDAO = new BizConfigDAO($db);
		
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		if ($id) {
			// 编辑采购订单
			$sql = "select p.ref, p.deal_date, pc.ref as pctemplate_ref, b.ref as pcbill_ref,
						s.name as supplier_name,
						p.biz_user_id, u.name as biz_user_name,
						p.bill_memo, p.bill_status
					from t_po_bill p, t_pc_template pc, t_pc_bill b, t_supplier s, t_user u
					where p.id = '%s' and p.supplier_Id = s.id
						and p.biz_user_id = u.id and p.pctemplate_id = pc.id and pc.pcbill_id = b.id ";
			$data = $db->query($sql, $id);
			if ($data) {
				$v = $data[0];
				$result["ref"] = $v["ref"];
				$result["dealDate"] = $this->toYMD($v["deal_date"]);
				$result["supplierName"] = $v["supplier_name"];
				$result["bizUserId"] = $v["biz_user_id"];
				$result["bizUserName"] = $v["biz_user_name"];
				$result["billMemo"] = $v["bill_memo"];
				$result["billStatus"] = $v["bill_status"];
				$result["pcTemplateRef"] = $v["pctemplate_ref"];
				$result["pcBillRef"] = $v["pcbill_ref"];
				
				// 明细表
				$sql = "select p.id, p.goods_id, g.code, g.name, g.spec, 
							convert(p.goods_count, " . $fmt . ") as goods_count, 
							p.goods_price, p.goods_money,
							u.id as unit_id, u.name as unit_name, p.memo
						from t_po_bill_detail p, t_goods g, t_goods_unit u
						where p.pobill_id = '%s' and p.goods_id = g.id and p.unit_id = u.id
						order by p.show_order";
				$items = [];
				$data = $db->query($sql, $id);
				
				foreach ( $data as $v ) {
					$items[] = [
							"id" => $v["id"],
							"goodsId" => $v["goods_id"],
							"goodsCode" => $v["code"],
							"goodsName" => $v["name"],
							"goodsSpec" => $v["spec"],
							"goodsCount" => $v["goods_count"],
							"goodsPrice" => $v["goods_price"],
							"goodsMoney" => $v["goods_money"],
							"unitId" => $v["unit_id"],
							"unitName" => $v["unit_name"],
							"memo" => $v["memo"]
					];
				}
				
				$result["items"] = $items;
			}
		} else {
			// 新建采购订单
			$loginUserId = $params["loginUserId"];
			$result["bizUserId"] = $loginUserId;
			$result["bizUserName"] = $params["loginUserName"];
		}
		
		return $result;
	}

	/**
	 * 审核采购订单
	 *
	 * @param array $params        	
	 * @return NULL|array
	 */
	public function commitPOBill(& $params) {
		$db = $this->db;
		
		$id = $params["id"];
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->badParam("loginUserId");
		}
		
		$bill = $this->getPOBillById($id);
		if (! $bill) {
			return $this->bad("要审核的采购订单不存在");
		}
		$ref = $bill["ref"];
		$billStatus = $bill["billStatus"];
		if ($billStatus > 0) {
			return $this->bad("采购订单(单号：$ref)已经被审核，不能再次审核");
		}
		
		$sql = "update t_po_bill
				set bill_status = 1000,
					confirm_user_id = '%s',
					confirm_date = now()
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
	 * 取消审核采购订单
	 *
	 * @param array $params        	
	 * @return NULL|array
	 */
	public function cancelConfirmPOBill(& $params) {
		$db = $this->db;
		$id = $params["id"];
		
		$bill = $this->getPOBillById($id);
		if (! $bill) {
			return $this->bad("要取消审核的采购订单不存在");
		}
		
		$ref = $bill["ref"];
		$params["ref"] = $ref;
		
		$billStatus = $bill["billStatus"];
		if ($billStatus > 1000) {
			return $this->bad("采购订单(单号:{$ref})不能取消审核");
		}
		
		if ($billStatus == 0) {
			return $this->bad("采购订单(单号:{$ref})还没有审核，无需进行取消审核操作");
		}
		
		$sql = "select count(*) as cnt from t_po_pw where po_id = '%s' ";
		$data = $db->query($sql, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("采购订单(单号:{$ref})已经生成了采购入库单，不能取消审核");
		}
		
		$sql = "update t_po_bill
				set bill_status = 0, confirm_user_id = null, confirm_date = null
				where id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 操作成功
		return null;
	}

	/**
	 * 查询采购订单的数据，用于生成PDF文件
	 *
	 * @param array $params        	
	 *
	 * @return NULL|array
	 */
	public function getDataForPDF($params) {
		$db = $this->db;
		
		$ref = $params["ref"];
		
		$sql = "select p.id, p.bill_status, p.goods_money, p.tax, p.money_with_tax,
					s.name as supplier_name, p.contact, p.tel, p.fax, p.deal_address,
					p.deal_date, p.payment_type, p.bill_memo, p.date_created,
					o.full_name as org_name, u1.name as biz_user_name, u2.name as input_user_name,
					p.confirm_user_id, p.confirm_date, p.company_id
				from t_po_bill p, t_supplier s, t_org o, t_user u1, t_user u2
				where (p.supplier_id = s.id) and (p.org_id = o.id)
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
		$result["tax"] = $v["tax"];
		$result["moneyWithTax"] = $v["money_with_tax"];
		$result["dealDate"] = $this->toYMD($v["deal_date"]);
		$result["dealAddress"] = $v["deal_address"];
		$result["bizUserName"] = $v["biz_user_name"];
		
		$sql = "select p.id, g.code, g.name, g.spec, convert(p.goods_count, $fmt) as goods_count, 
					p.goods_price, p.goods_money,
					p.tax_rate, p.tax, p.money_with_tax, u.name as unit_name
				from t_po_bill_detail p, t_goods g, t_goods_unit u
				where p.pobill_id = '%s' and p.goods_id = g.id and g.unit_id = u.id
				order by p.show_order";
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
	 * 采购订单执行的采购入库单信息
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function poBillPWBillList($params) {
		$db = $this->db;
		
		// id: 采购订单id
		$id = $params["id"];
		
		$sql = "select p.id, p.bill_status, p.ref, p.biz_dt, u1.name as biz_user_name, u2.name as input_user_name,
					p.goods_money, w.name as warehouse_name, s.name as supplier_name,
					p.date_created, p.payment_type
				from t_pw_bill p, t_warehouse w, t_supplier s, t_user u1, t_user u2,
					t_po_pw popw
				where (popw.po_id = '%s') and (popw.pw_id = p.id)
				and (p.warehouse_id = w.id) and (p.supplier_id = s.id)
				and (p.biz_user_id = u1.id) and (p.input_user_id = u2.id)
				order by p.ref ";
		$data = $db->query($sql, $id);
		$result = [];
		
		foreach ( $data as $v ) {
			$billStatus = $v["bill_status"];
			$bs = "";
			if ($billStatus == 0) {
				$bs = "待入库";
			} else if ($billStatus == 1000) {
				$bs = "已入库";
			} else if ($billStatus == 2000) {
				$bs = "已退货";
			} else if ($billStatus == 9000) {
				// TODO 9000这个状态似乎并没有使用？？？
				$bs = "作废";
			}
			
			$result[] = [
					"id" => $v["id"],
					"ref" => $v["ref"],
					"bizDate" => $this->toYMD($v["biz_dt"]),
					"supplierName" => $v["supplier_name"],
					"warehouseName" => $v["warehouse_name"],
					"inputUserName" => $v["input_user_name"],
					"bizUserName" => $v["biz_user_name"],
					"billStatus" => $bs,
					"amount" => $v["goods_money"],
					"dateCreated" => $v["date_created"],
					"paymentType" => $v["payment_type"]
			];
		}
		
		return $result;
	}

	/**
	 * 关闭采购订单
	 *
	 * @param array $params        	
	 * @return null|array
	 */
	public function closePOBill(&$params) {
		$db = $this->db;
		
		$id = $params["id"];
		
		$sql = "select ref, bill_status
				from t_po_bill
				where id = '%s' ";
		$data = $db->query($sql, $id);
		
		if (! $data) {
			return $this->bad("要关闭的采购订单不存在");
		}
		
		$ref = $data[0]["ref"];
		$billStatus = $data[0]["bill_status"];
		
		if ($billStatus >= 4000) {
			return $this->bad("采购订单已经被关闭");
		}
		
		// 检查该采购订单是否有生成的采购入库单，并且这些采购入库单是没有提交入库的
		// 如果存在这类采购入库单，那么该采购订单不能关闭。
		$sql = "select count(*) as cnt
				from t_pw_bill w, t_po_pw p
				where w.id = p.pw_id and p.po_id = '%s'
					and w.bill_status = 0 ";
		$data = $db->query($sql, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			$info = "当前采购订单生成的入库单中还有没提交的<br/><br/>把这些入库单删除后，才能关闭采购订单";
			return $this->bad($info);
		}
		
		if ($billStatus < 1000) {
			return $this->bad("当前采购订单还没有审核，没有审核的采购订单不能关闭");
		}
		
		$newBillStatus = - 1;
		if ($billStatus == 1000) {
			// 当前订单只是审核了
			$newBillStatus = 4000;
		} else if ($billStatus == 2000) {
			// 部分入库
			$newBillStatus = 4001;
		} else if ($billStatus == 3000) {
			// 全部入库
			$newBillStatus = 4002;
		}
		
		if ($newBillStatus == - 1) {
			return $this->bad("当前采购订单的订单状态是不能识别的状态码：{$billStatus}");
		}
		
		$sql = "update t_po_bill
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

	/**
	 * 取消关闭采购订单
	 *
	 * @param array $params        	
	 * @return null|array
	 */
	public function cancelClosedPOBill(&$params) {
		$db = $this->db;
		
		$id = $params["id"];
		
		$sql = "select ref, bill_status
				from t_po_bill
				where id = '%s' ";
		$data = $db->query($sql, $id);
		
		if (! $data) {
			return $this->bad("要关闭的采购订单不存在");
		}
		
		$ref = $data[0]["ref"];
		$billStatus = $data[0]["bill_status"];
		
		if ($billStatus < 4000) {
			return $this->bad("采购订单没有被关闭，无需取消");
		}
		
		$newBillStatus = - 1;
		if ($billStatus == 4000) {
			$newBillStatus = 1000;
		} else if ($billStatus == 4001) {
			$newBillStatus = 2000;
		} else if ($billStatus == 4002) {
			$newBillStatus = 3000;
		}
		
		if ($newBillStatus == - 1) {
			return $this->bad("当前采购订单的订单状态是不能识别的状态码：{$billStatus}");
		}
		
		$sql = "update t_po_bill
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

	/**
	 * 为使用Lodop打印准备数据
	 *
	 * @param array $params        	
	 */
	public function getPOBillDataForLodopPrint($params) {
		$db = $this->db;
		$result = [];
		
		$id = $params["id"];
		
		$sql = "select p.ref, p.bill_status, p.goods_money, p.tax, p.money_with_tax,
					s.name as supplier_name, p.contact, p.tel, p.fax, p.deal_address,
					p.deal_date, p.payment_type, p.bill_memo, p.date_created,
					o.full_name as org_name, u1.name as biz_user_name, u2.name as input_user_name,
					p.confirm_user_id, p.confirm_date, p.company_id
				from t_po_bill p, t_supplier s, t_org o, t_user u1, t_user u2
				where (p.supplier_id = s.id) and (p.org_id = o.id)
					and (p.biz_user_id = u1.id) and (p.input_user_id = u2.id)
					and (p.id = '%s')";
		
		$data = $db->query($sql, $id);
		if (! $data) {
			return $result;
		}
		
		$v = $data[0];
		$result["ref"] = $v["ref"];
		$result["goodsMoney"] = $v["goods_money"];
		$result["tax"] = $v["tax"];
		$result["moneyWithTax"] = $v["money_with_tax"];
		$result["supplierName"] = $v["supplier_name"];
		$result["contact"] = $v["contact"];
		$result["tel"] = $v["tel"];
		$result["dealDate"] = $this->toYMD($v["deal_date"]);
		$result["dealAddress"] = $v["deal_address"];
		$result["billMemo"] = $v["bill_memo"];
		
		$result["printDT"] = date("Y-m-d H:i:s");
		
		$companyId = $v["company_id"];
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$sql = "select p.id, g.code, g.name, g.spec, convert(p.goods_count, $fmt) as goods_count,
				p.goods_price, p.goods_money,
				p.tax_rate, p.tax, p.money_with_tax, u.name as unit_name
				from t_po_bill_detail p, t_goods g, t_goods_unit u
				where p.pobill_id = '%s' and p.goods_id = g.id and g.unit_id = u.id
				order by p.show_order";
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
					"goodsMoney" => $v["goods_money"],
					"taxRate" => intval($v["tax_rate"]),
					"goodsMoneyWithTax" => $v["money_with_tax"]
			];
		}
		
		$result["items"] = $items;
		
		return $result;
	}

	/**
	 * 选择采购模板主表列表
	 *
	 * @param array $params        	
	 */
	public function selectPCTemplateList($params) {
		$db = $this->db;
		
		$start = $params["start"];
		$limit = $params["limit"];
		
		$loginUserId = $params["loginUserId"];
		
		$sql = "select org_id from t_user where id = '%s' ";
		$data = $db->query($sql, $loginUserId);
		if (! $data) {
			return $this->emptyResult();
		}
		
		$orgId = $data[0]["org_id"];
		
		$ref = $params["ref"];
		$supplierId = $params["supplierId"];
		$goodsCode = $params["goodsCode"];
		$goodsId = null;
		if ($goodsCode) {
			$sql = "select id from t_goods where code = '%s' ";
			$data = $db->query($sql, $goodsCode);
			if ($data) {
				$goodsId = $data[0]["id"];
			}
		}
		
		$sql = "select distinct p.id, p.ref, p.bill_memo
				from t_pc_template p, t_pc_bill b, t_pc_template_org o,
					t_pc_template_detail d
				where (p.bill_status = 1000) and (p.pcbill_id = b.id)
					and (p.id = o.pctemplate_id) and (o.org_id = '%s')
					and (p.id = d.pctemplate_id) ";
		$queryParams = [];
		$queryParams[] = $orgId;
		
		if ($ref) {
			$sql .= " and (p.ref like '%s') ";
			$queryParams[] = "%{$ref}%";
		}
		if ($supplierId) {
			$sql .= " and (b.supplier_id = '%s') ";
			$queryParams[] = $supplierId;
		}
		if ($goodsId) {
			$sql .= " and (d.goods_id = '%s')";
			$queryParams[] = $goodsId;
		}
		
		$sql .= " order by p.ref limit %d, %d";
		$queryParams[] = $start;
		$queryParams[] = $limit;
		
		$data = $db->query($sql, $queryParams);
		$result = [];
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"ref" => $v["ref"],
					"billMemo" => $v["bill_memo"]
			];
		}
		
		$sql = "select count(distinct p.id) as cnt
				from t_pc_template p, t_pc_bill b, t_pc_template_org o,
					t_pc_template_detail d
				where (p.bill_status = 1000) and (p.pcbill_id = b.id)
					and (p.id = o.pctemplate_id) and (o.org_id = '%s')
					and (p.id = d.pctemplate_id) ";
		$queryParams = [];
		$queryParams[] = $orgId;
		if ($ref) {
			$sql .= " and (p.ref like '%s') ";
			$queryParams[] = "%{$ref}%";
		}
		if ($supplierId) {
			$sql .= " and (b.supplier_id = '%s') ";
			$queryParams[] = $supplierId;
		}
		if ($goodsId) {
			$sql .= " and (d.goods_id = '%s')";
			$queryParams[] = $goodsId;
		}
		$data = $db->query($sql, $queryParams);
		$cnt = $data[0]["cnt"];
		
		return [
				"dataList" => $result,
				"totalCount" => $cnt
		];
	}

	/**
	 * 选择采购模板物资明细列表
	 *
	 * @param array $params        	
	 */
	public function pcTemplateDetailListForPOBill($params) {
		$db = $this->db;
		
		// 采购模板主表id
		$id = $params["id"];
		
		$result = [];
		
		$sql = "select p.id, g.code, g.name, g.spec, u.name as unit_name, p.unit_id
				from t_pc_template_detail p, t_goods g, t_goods_unit u
				where p.pctemplate_id = '%s' and p.goods_id = g.id and p.unit_id = u.id
				order by p.show_order ";
		$data = $db->query($sql, $id);
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"unitId" => $v["unit_id"],
					"unitName" => $v["unit_name"]
			];
		}
		
		return $result;
	}

	/**
	 * 选择采购模板后，获取模板详情填充到采购订单
	 *
	 * @param array $params        	
	 */
	public function getPCTemplateInfoForPOBill($params) {
		$db = $this->db;
		
		// 采购模板主表id
		$id = $params["id"];
		$result = [];
		
		// 主表
		$sql = "select p.ref as template_ref, b.ref as contract_ref, s.name as supplier_name
				from t_pc_template p, t_pc_bill b, t_supplier s
				where p.id = '%s' and p.pcbill_id = b.id
					and b.supplier_id = s.id";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $result;
		}
		
		$v = $data[0];
		$result["templateRef"] = $v["template_ref"];
		$result["contractRef"] = $v["contract_ref"];
		$result["supplierName"] = $v["supplier_name"];
		
		// 物资明细
		$items = [];
		
		$sql = "select pd.goods_price, pd.goods_id, g.name as goods_name,
					g.code as goods_code, g.spec as goods_spec,
					u.name as unit_name, d.unit_id, d.id
				from t_pc_template_detail d, t_pc_bill_detail pd, t_goods g,
					t_goods_unit u
				where d.pctemplate_id = '%s' and d.pcbill_detail_id = pd.id
					and g.id = pd.goods_id and d.unit_id = u.id 
				order by pd.show_order ";
		$data = $db->query($sql, $id);
		foreach ( $data as $v ) {
			$items[] = [
					"id" => $v["id"],
					"goodsId" => $v["goods_id"],
					"goodsCode" => $v["goods_code"],
					"goodsName" => $v["goods_name"],
					"goodsSpec" => $v["goods_spec"],
					"goodsPrice" => $v["goods_price"],
					"unitId" => $v["unit_id"],
					"unitName" => $v["unit_name"]
			];
		}
		
		$result["items"] = $items;
		
		return $result;
	}
}