<?php

namespace Home\DAO;

use Home\Common\FIdConst;

/**
 * 门店订货 DAO
 *
 * @author 李静波
 */
class SPOBillDAO extends PSIBaseExDAO {

	/**
	 * 生成新的门店订货单号
	 */
	private function genNewBillRef(string $loginUserId, bool $isTodayOrder) {
		$db = $this->db;
		
		$sql = "select o.org_code
				from t_org o, t_user u
				where o.id = u.org_id and u.id = '%s' ";
		$data = $db->query($sql, $loginUserId);
		$orgCode = $data[0]["org_code"];
		
		$pre = $orgCode;
		
		$mid = date("Ymd");
		
		$pre = $orgCode . "-" . $mid;
		
		$sql = "select ref from t_spo_bill where ref like '%s' order by ref desc limit 1";
		$data = $db->query($sql, $pre . "%");
		$sufLength = 4;
		$suf = str_pad("1", $sufLength, "0", STR_PAD_LEFT);
		if ($data) {
			$ref = $data[0]["ref"];
			$nextNumber = intval(substr($ref, strlen($pre), $sufLength)) + 1;
			$suf = str_pad($nextNumber, $sufLength, "0", STR_PAD_LEFT);
		}
		
		$ito = "";
		if ($isTodayOrder) {
			$ito = "补";
		}
		
		return $pre . $suf . "-DHD" . $ito;
	}

	private function getDealDate($isTodayOrder) {
		if ($isTodayOrder) {
			// 补单的送货日期是今天
			return date("Y-m-d");
		} else {
			// 其他情况送货日期是明天
			return date("Y-m-d", strtotime("+1 day"));
		}
	}

	/**
	 * 新建门店订货单
	 *
	 * @param array $bill        	
	 */
	public function addSPOBill(& $bill) {
		$db = $this->db;
		
		$isTodayOrder = intval($bill["isTodayOrder"]);
		$pcTemplateId = $bill["pcTemplateId"];
		
		// 由采购模板id查询对应的供应商
		$sql = "select p.supplier_id
				from t_pc_template t , t_pc_bill p
				where t.id = '%s' and t.pcbill_id = p.id";
		$data = $db->query($sql, $pcTemplateId);
		if (! $data) {
			return $this->bad("采购模板不存在");
		}
		$supplierId = $data[0]["supplier_id"];
		
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
		
		$dealDate = $this->getDealDate($isTodayOrder == 1);
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
		$ref = $this->genNewBillRef($loginUserId, $isTodayOrder == 1);
		
		// 主表
		$sql = "insert into t_spo_bill(id, ref, bill_status, deal_date, biz_dt, org_id, biz_user_id,
					goods_money, input_user_id, supplier_id, bill_memo, date_created, data_org, company_id,
					is_today_order, pctemplate_id)
				values ('%s', '%s', 0, '%s', '%s', '%s', '%s',
					0, '%s', '%s', '%s', now(), '%s', '%s',
					%d, '%s')";
		$rc = $db->execute($sql, $id, $ref, $dealDate, $dealDate, $orgId, $bizUserId, $loginUserId, 
				$supplierId, $billMemo, $dataOrg, $companyId, $isTodayOrder, $pcTemplateId);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 明细记录
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
			
			$goodsCount = $v["goodsCount"];
			if ($goodsCount <= 0) {
				return $this->bad("采购数量需要大于0");
			}
			
			$goodsPrice = $v["goodsPrice"];
			if ($goodsPrice < 0) {
				return $this->bad("采购单价不能是负数");
			}
			
			$goodsMoney = $v["goodsMoney"];
			$memo = $v["memo"];
			
			$unitId = $v["unitId"];
			if (! $unitDAO->unitIdIsValid($goodsId, $unitId)) {
				$rIndex = $i + 1;
				return $this->bad("第{$rIndex}条记录中，传入的计量单位不正确");
			}
			
			$pcTemplateDetailId = $v["id"];
			
			$sql = "insert into t_spo_bill_detail(id, date_created, goods_id, goods_count, goods_money,
						goods_price, spobill_id, pw_count, left_count,
						show_order, data_org, company_id, memo, pctemplate_detail_id, unit_id)
					values ('%s', now(), '%s', convert(%f, $fmt), %f,
							%f, '%s', 0, %d, 
							%d, '%s', '%s', '%s', '%s', '%s')";
			$rc = $db->execute($sql, $this->newId(), $goodsId, $goodsCount, $goodsMoney, 
					$goodsPrice, $id, $goodsCount, $i, $dataOrg, $companyId, $memo, 
					$pcTemplateDetailId, $unitId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 同步主表的金额合计字段
		$sql = "select sum(goods_money) as sum_goods_money
				from t_spo_bill_detail
				where spobill_id = '%s' ";
		$data = $db->query($sql, $id);
		$sumGoodsMoney = $data[0]["sum_goods_money"];
		if (! $sumGoodsMoney) {
			$sumGoodsMoney = 0;
		}
		
		$sql = "update t_spo_bill
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
	 * 编辑门店订货单
	 *
	 * @param array $bill        	
	 * @return NULL|array
	 */
	public function updateSPOBill(& $bill) {
		$db = $this->db;
		
		$id = $bill["id"];
		$spoBill = $this->getSPOBillById($id);
		if (! $spoBill) {
			return $this->bad("要编辑的门店订货单不存在");
		}
		
		$ref = $spoBill["ref"];
		$dataOrg = $spoBill["dataOrg"];
		$companyId = $spoBill["companyId"];
		$billStatus = $spoBill["billStatus"];
		if ($billStatus != 0) {
			return $this->bad("当前门店订货单已经审核，不能再编辑");
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
		
		$userDAO = new UserDAO($db);
		$user = $userDAO->getUserById($bizUserId);
		if (! $user) {
			return $this->bad("业务员不存在");
		}
		
		$sql = "delete from t_spo_bill_detail where spobill_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$goodsDAO = new GoodsDAO($db);
		$unitDAO = new GoodsUnitDAO($db);
		
		foreach ( $items as $i => $v ) {
			$detailId = $v["id"];
			
			$goodsId = $v["goodsId"];
			if (! $goodsId) {
				continue;
			}
			if (! $goodsDAO->getGoodsById($goodsId)) {
				continue;
			}
			
			$goodsCount = $v["goodsCount"];
			if ($goodsCount <= 0) {
				return $this->bad("采购数量需要大于0");
			}
			$goodsPrice = $v["goodsPrice"];
			if ($goodsPrice < 0) {
				return $this->bad("采购单价不能是负数");
			}
			$goodsMoney = $v["goodsMoney"];
			$memo = $v["memo"];
			
			$unitId = $v["unitId"];
			if (! $unitDAO->unitIdIsValid($goodsId, $unitId)) {
				$rIndex = $i + 1;
				return $this->bad("第{$rIndex}条记录中，传入的计量单位不正确");
			}
			
			$sql = "insert into t_spo_bill_detail(id, date_created, goods_id, goods_count, goods_money,
					goods_price, spobill_id, pw_count, left_count,
					show_order, data_org, company_id, memo, unit_id)
					values ('%s', now(), '%s', convert(%f, $fmt), %f,
					%f, '%s', 0, convert(%f, $fmt), 
					%d, '%s', '%s', '%s', '%s')";
			$rc = $db->execute($sql, $detailId, $goodsId, $goodsCount, $goodsMoney, $goodsPrice, 
					$id, $goodsCount, $i, $dataOrg, $companyId, $memo, $unitId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 同步主表的金额合计字段
		$sql = "select sum(goods_money) as sum_goods_money
						from t_spo_bill_detail
						where spobill_id = '%s' ";
		$data = $db->query($sql, $id);
		$sumGoodsMoney = $data[0]["sum_goods_money"];
		if (! $sumGoodsMoney) {
			$sumGoodsMoney = 0;
		}
		
		$sql = "update t_spo_bill
				set goods_money = %f, 
					biz_user_id = '%s', 
					bill_memo = '%s'
				where id = '%s' ";
		$rc = $db->execute($sql, $sumGoodsMoney, $bizUserId, $billMemo, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$bill["ref"] = $ref;
		
		// 操作成功
		return null;
	}

	/**
	 * 根据门店订货单主表id查询门店订货单
	 *
	 * @param string $id        	
	 * @return array|NULL
	 */
	public function getSPOBillById($id) {
		$db = $this->db;
		
		$sql = "select ref, data_org, bill_status, company_id
				from t_spo_bill where id = '%s' ";
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
	 * 获得门店订货单主表信息列表
	 *
	 * @param array $params        	
	 */
	public function spobillList($params) {
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
				from t_spo_bill p, t_supplier s, t_org o, t_user u1, t_user u2
				where (p.supplier_id = s.id) and (p.org_id = o.id)
					and (p.biz_user_id = u1.id) and (p.input_user_id = u2.id) ";
		
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::SCM_SPO, "p", $loginUserId);
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
			
			// 查询生成的采购订单单号
			$sql = "select ref as pobill_ref
					from t_spo_po s, t_po_bill p
					where s.spo_id = '%s' and s.po_id = p.id";
			$d = $db->query($sql, $v["id"]);
			$poBillRef = "";
			if ($d) {
				$poBillRef = $d[0]["pobill_ref"];
			}
			
			$result[] = [
					"id" => $v["id"],
					"ref" => $v["ref"],
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
					"confirmDate" => $confirmDate,
					"poBillRef" => $poBillRef
			];
		}
		
		$sql = "select count(*) as cnt
				from t_spo_bill p, t_supplier s, t_org o, t_user u1, t_user u2
				where (p.supplier_id = s.id) and (p.org_id = o.id)
					and (p.biz_user_id = u1.id) and (p.input_user_id = u2.id)
				";
		$queryParams = [];
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::SCM_SPO, "p", $loginUserId);
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
	 * 获得门店订货单的明细信息
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
		
		// id: 门店订货单id
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
	 * 获得门店订货单的信息
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function spoBillInfo($params) {
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
			// 编辑门店订货单
			$sql = "select p.ref, p.deal_date, b.supplier_id,
						s.name as supplier_name,
						p.biz_user_id, u.name as biz_user_name,
						p.bill_memo, p.bill_status, p.is_today_order,
						pc.ref as pctemplate_ref, b.ref as pcbill_ref
					from t_spo_bill p, t_pc_template pc, t_pc_bill b, t_supplier s, t_user u
					where p.id = '%s' and p.pctemplate_id = pc.id and pc.pcbill_id = b.id 
							and b.supplier_id = s.id
							and p.biz_user_id = u.id ";
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
				$result["isTodayOrder"] = $v["is_today_order"];
				
				// 明细表
				$sql = "select p.id, p.goods_id, g.code, g.name, g.spec,
							convert(p.goods_count, " . $fmt . ") as goods_count,
							p.goods_price, p.goods_money,
							p.unit_id,
							u.name as unit_name, p.memo
						from t_spo_bill_detail p, t_goods g, t_goods_unit u
						where p.spobill_id = '%s' and p.goods_id = g.id and p.unit_id = u.id
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
			// 新建门店订货单
			$loginUserId = $params["loginUserId"];
			$result["bizUserId"] = $loginUserId;
			$result["bizUserName"] = $params["loginUserName"];
		}
		
		return $result;
	}

	/**
	 * 删除门店订货单
	 *
	 * @param array $params        	
	 * @return NULL|array
	 */
	public function deleteSPOBill(& $params) {
		$db = $this->db;
		
		$id = $params["id"];
		
		$bill = $this->getSPOBillById($id);
		
		if (! $bill) {
			return $this->bad("要删除的门店订货单不存在");
		}
		$ref = $bill["ref"];
		$billStatus = $bill["billStatus"];
		if ($billStatus > 0) {
			return $this->bad("门店订货单(单号：{$ref})已经审核，不能被删除");
		}
		
		$params["ref"] = $ref;
		
		$sql = "delete from t_spo_bill_detail where spobill_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$sql = "delete from t_spo_bill where id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		return null;
	}

	/**
	 * 审核门店订货单
	 *
	 * @param array $params        	
	 * @return NULL|array
	 */
	public function commitSPOBill(& $params) {
		$db = $this->db;
		
		$id = $params["id"];
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->badParam("loginUserId");
		}
		
		$bill = $this->getSPOBillById($id);
		if (! $bill) {
			return $this->bad("要审核的门店订货单不存在");
		}
		$ref = $bill["ref"];
		$billStatus = $bill["billStatus"];
		if ($billStatus > 0) {
			return $this->bad("门店订货单(单号：$ref)已经被审核，不能再次审核");
		}
		
		$sql = "update t_spo_bill
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
	 * 取消审核门店订货单
	 *
	 * @param array $params        	
	 * @return NULL|array
	 */
	public function cancelConfirmSPOBill(& $params) {
		$db = $this->db;
		$id = $params["id"];
		
		$bill = $this->getSPOBillById($id);
		if (! $bill) {
			return $this->bad("要取消审核的门店订货单不存在");
		}
		
		$ref = $bill["ref"];
		$params["ref"] = $ref;
		
		$billStatus = $bill["billStatus"];
		if ($billStatus > 1000) {
			return $this->bad("门店订货单(单号:{$ref})不能取消审核");
		}
		
		if ($billStatus == 0) {
			return $this->bad("门店订货单(单号:{$ref})还没有审核，无需进行取消审核操作");
		}
		
		$sql = "select count(*) as cnt from t_spo_po where spo_id = '%s' ";
		$data = $db->query($sql, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("门店订货单(单号:{$ref})已经生成了采购订单，不能取消审核");
		}
		
		$sql = "update t_spo_bill
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
	 * 门店订货单生成采购订单
	 *
	 * @param array $params        	
	 */
	public function genPOBill(&$params) {
		$db = $this->db;
		
		// 门店订货单主表id
		$id = $params["id"];
		$bill = $this->getSPOBillById($id);
		if (! $bill) {
			return $this->bad("门店订货单不存在");
		}
		$ref = $bill["ref"];
		$billStatus = $bill["billStatus"];
		$dataOrg = $bill["dataOrg"];
		$companyId = $bill["companyId"];
		if ($this->dataOrgNotExists($dataOrg)) {
			return $this->badParam("dataOrg");
		}
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->badParam("loginUserId");
		}
		
		if ($billStatus < 1000) {
			return $this->bad("门店订货单还没有审核，不能生成采购订单");
		}
		if ($billStatus >= 4000) {
			return $this->bad("门店订货单已经关闭，不能生成采购订单");
		}
		
		$sql = "select count(*) as cnt from t_spo_po where spo_id = '%s' ";
		$data = $db->query($sql, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("当前门店订货单已经生成过采购订单了，不能再次生成");
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$poBillDAO = new POBillDAO($db);
		$poBillRef = $poBillDAO->genNewBillRef($companyId);
		
		// 主表
		$sql = "select supplier_id, pctemplate_id, org_id, biz_user_id, input_user_id, deal_date
				from t_spo_bill
				where id = '%s' ";
		$data = $db->query($sql, $id);
		$v = $data[0];
		$dealDate = $this->toYMD($v["deal_date"]);
		$orgId = $v["org_id"];
		$supplierId = $v["supplier_id"];
		$pcTemplateId = $v["pctemplate_id"];
		$billMemo = "";
		$bizUserId = $v["biz_user_id"];
		
		$poBillId = $this->newId();
		
		$sql = "insert into t_po_bill(id, ref, bill_status, deal_date, biz_dt, org_id, biz_user_id,
					goods_money, input_user_id, supplier_id,
					bill_memo, date_created, data_org, company_id, pctemplate_id)
				values ('%s', '%s', 0, '%s', '%s', '%s', '%s',
					0, '%s', '%s',
					'%s', now(), '%s', '%s', '%s')";
		$rc = $db->execute($sql, $poBillId, $poBillRef, $dealDate, $dealDate, $orgId, $bizUserId, 
				$loginUserId, $supplierId, $billMemo, $dataOrg, $companyId, $pcTemplateId);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 明细表
		$items = [];
		$sql = "select id, pctemplate_detail_id, goods_id, goods_count, goods_money, goods_price, unit_id
				from t_spo_bill_detail
				where spobill_id = '%s'
				order by show_order";
		$data = $db->query($sql, $id);
		foreach ( $data as $v ) {
			$items[] = [
					"id" => $v["id"],
					"pcTemplateDetailId" => $v["pctemplate_detail_id"],
					"goodsId" => $v["goods_id"],
					"goodsCount" => $v["goods_count"],
					"goodsPrice" => $v["goods_price"],
					"goodsMoney" => $v["goods_money"],
					"unitId" => $v["unit_id"]
			];
		}
		
		foreach ( $items as $i => $v ) {
			$detailId = $v["id"];
			$pcTemplateDetailId = $v["pcTemplateDetailId"];
			
			$goodsId = $v["goodsId"];
			$unitId = $v["unitId"];
			$goodsCount = $v["goodsCount"];
			$goodsPrice = $v["goodsPrice"];
			$goodsMoney = $v["goodsMoney"];
			$memo = "";
			
			$sql = "insert into t_po_bill_detail(id, pctemplate_detail_id, date_created, goods_id, goods_count, goods_money,
						goods_price, pobill_id, pw_count, left_count,
						show_order, data_org, company_id, memo, unit_id)
					values ('%s', '%s', now(), '%s', convert(%f, $fmt), %f,
						%f, '%s', 0, convert(%f, $fmt),
						%d, '%s', '%s', '%s', '%s')";
			$rc = $db->execute($sql, $detailId, $pcTemplateDetailId, $goodsId, $goodsCount, 
					$goodsMoney, $goodsPrice, $poBillId, $goodsCount, $i, $dataOrg, $companyId, 
					$memo, $unitId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 同步主表的金额合计字段
		$sql = "select sum(goods_money) as sum_goods_money
				from t_po_bill_detail
				where pobill_id = '%s' ";
		$data = $db->query($sql, $poBillId);
		$sumGoodsMoney = $data[0]["sum_goods_money"];
		if (! $sumGoodsMoney) {
			$sumGoodsMoney = 0;
		}
		
		$sql = "update t_po_bill
				set goods_money = %f
				where id = '%s' ";
		$rc = $db->execute($sql, $sumGoodsMoney, $poBillId);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 关联门店订货单和采购订单
		$sql = "insert into t_spo_po (spo_id, po_id) values ('%s', '%s')";
		$rc = $db->execute($sql, $id, $poBillId);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$params["ref"] = $ref;
		$params["poBillRef"] = $poBillRef;
		// 操作成功
		return null;
	}

	/**
	 * 关闭门店订货单
	 *
	 * @param array $params        	
	 * @return null|array
	 */
	public function closeSPOBill(&$params) {
		$db = $this->db;
		
		$id = $params["id"];
		
		$sql = "select ref, bill_status
				from t_spo_bill
				where id = '%s' ";
		$data = $db->query($sql, $id);
		
		if (! $data) {
			return $this->bad("要关闭的门店订货单不存在");
		}
		
		$ref = $data[0]["ref"];
		$billStatus = $data[0]["bill_status"];
		
		if ($billStatus >= 4000) {
			return $this->bad("门店订货单已经被关闭");
		}
		
		if ($billStatus < 1000) {
			return $this->bad("当前门店订货单还没有审核，没有审核的门店订货单不能关闭");
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
			return $this->bad("当前门店订货单的订单状态是不能识别的状态码：{$billStatus}");
		}
		
		$sql = "update t_spo_bill
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
	 * 取消关闭门店订货单
	 *
	 * @param array $params        	
	 * @return null|array
	 */
	public function cancelClosedSPOBill(&$params) {
		$db = $this->db;
		
		$id = $params["id"];
		
		$sql = "select ref, bill_status
				from t_spo_bill
				where id = '%s' ";
		$data = $db->query($sql, $id);
		
		if (! $data) {
			return $this->bad("要关闭的门店订货单不存在");
		}
		
		$ref = $data[0]["ref"];
		$billStatus = $data[0]["bill_status"];
		
		if ($billStatus < 4000) {
			return $this->bad("门店订货单没有被关闭，无需取消");
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
			return $this->bad("当前门店订货单的状态是不能识别的状态码：{$billStatus}");
		}
		
		$sql = "update t_spo_bill
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