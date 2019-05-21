<?php

namespace Home\DAO;

use Home\Common\FIdConst;

/**
 * 门店消耗单 DAO
 *
 * @author 李静波
 */
class USBillDAO extends PSIBaseExDAO {

	/**
	 * 生成新的消耗单单号
	 *
	 * @param string $companyId        	
	 * @return string
	 */
	private function genNewBillRef($companyId) {
		$db = $this->db;
		
		// 取单号前缀
		$pre = "XH";
		
		$mid = date("Ymd");
		
		$sql = "select ref from t_us_bill where ref like '%s' order by ref desc limit 1";
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
	 * 模板列表
	 *
	 * @param array $params        	
	 */
	public function usTemplateList($params) {
		$db = $this->db;
		
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->emptyResult();
		}
		
		$start = $params["start"];
		$limit = $params["limit"];
		
		$billStatus = $params["billStatus"];
		$ref = $params["ref"];
		$queryParams = [];
		
		$result = [];
		$sql = "select p.id, p.ref, p.bill_status,p.bill_memo, p.date_created,
					u.name as input_user_name
				from t_us_template p, t_user u
				where (p.input_user_id = u.id) ";
		
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::SCM_STORE_US_TEMPLATE_MANAGEMENT, "p", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParams = array_merge($queryParams, $rs[1]);
		}
		if ($billStatus != - 1) {
			$sql .= " and (p.bill_status = %d) ";
			$queryParams[] = $billStatus;
		}
		if ($ref) {
			$sql .= " and (p.ref like '%s') ";
			$queryParams[] = "%$ref%";
		}
		
		$data = $db->query($sql, $queryParams);
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"ref" => $v["ref"],
					"billStatus" => $v["bill_status"],
					"billMemo" => $v["bill_memo"],
					"dateCreated" => $v["date_created"],
					"inputUserName" => $v["input_user_name"]
			];
		}
		
		$sql = "select count(*) as cnt
				from t_us_template p, t_user u
				where (p.input_user_id = u.id) ";
		
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::SCM_STORE_US_TEMPLATE_MANAGEMENT, "p", $loginUserId);
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
			$queryParams[] = "%$ref%";
		}
		$data = $db->query($sql, $queryParams);
		$cnt = $data[0]["cnt"];
		
		return [
				"dataList" => $result,
				"totalCount" => $cnt
		];
	}

	private function orgTypeCodeToName($code) {
		switch ($code) {
			case 1 :
				return "总部";
			case 100 :
				return "物流中心";
			case 200 :
				return "门店";
			default :
				return "";
		}
	}

	/**
	 * 模板 - 选择组织机构
	 *
	 * @param array $params        	
	 */
	public function selectOrgForUSTemplate($params) {
		$db = $this->db;
		
		$loginUserId = $params["loginUserId"];
		
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->emptyResult();
		}
		
		$sql = "select o.id, o.full_name, o.org_type
				from t_org o
				where (1 = 1) ";
		
		$queryParams = [];
		
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::USR_MANAGEMENT, "o", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParams = array_merge($queryParams, $rs[1]);
		}
		
		$sql .= " order by o.org_code";
		
		$data = $db->query($sql, $queryParams);
		$result = [];
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"orgName" => $v["full_name"],
					"orgType" => $this->orgTypeCodeToName($v["org_type"])
			];
		}
		
		return $result;
	}

	/**
	 * 某个模板的详情
	 *
	 * @param array $params        	
	 */
	public function usTemplateInfo($params) {
		$db = $this->db;
		
		// 模板id
		$id = $params["id"];
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		
		$result = [];
		
		// 主表
		$sql = "select ref, bill_memo, bill_status from t_us_template where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $result;
		}
		$v = $data[0];
		$result["ref"] = $v["ref"];
		$result["billMemo"] = $v["bill_memo"];
		$result["billStatus"] = $v["bill_status"];
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// 物资明细
		$sql = "select t.id, g.id as goods_id, g.code, g.name, g.spec, u.name as unit_name,
					convert(t.sale_count, $fmt) as sale_count, 
					convert(t.lost_count, $fmt) as lost_count
				from t_us_template_detail t, t_goods g, t_goods_unit u
				where t.ustemplate_id = '%s' and t.goods_id = g.id and g.unit_id = u.id
				order by t.show_order";
		$data = $db->query($sql, $id);
		$items = [];
		foreach ( $data as $v ) {
			$saleCount = $v["sale_count"];
			if ($saleCount == 0) {
				$saleCount = null;
			}
			$lostCount = $v["lost_count"];
			if ($lostCount == 0) {
				$lostCount = null;
			}
			
			$items[] = [
					"id" => $v["id"],
					"goodsId" => $v["goods_id"],
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"unitName" => $v["unit_name"],
					"saleCount" => $saleCount,
					"lostCount" => $lostCount
			];
		}
		$result["items"] = $items;
		
		// 组织机构
		$orgs = [];
		$sql = "select o.id, o.full_name
				from t_us_template_org p, t_org o
				where p.ustemplate_id = '%s' and p.org_id = o.id
				order by o.org_code ";
		$data = $db->query($sql, $id);
		foreach ( $data as $v ) {
			$orgs[] = [
					"id" => $v["id"],
					"orgName" => $v["full_name"]
			];
		}
		$result["orgs"] = $orgs;
		
		return $result;
	}

	/**
	 * 新建消耗单模板
	 *
	 * @param array $bill        	
	 */
	public function addUSTemplate(&$bill) {
		$db = $this->db;
		$dataOrg = $bill["dataOrg"];
		if ($this->dataOrgNotExists($dataOrg)) {
			return $this->badParam("dataOrg");
		}
		$loginUserId = $bill["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->badParam("loginUserId");
		}
		$companyId = $bill["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// 模板编号
		$ref = $bill["ref"];
		
		// 备注
		$billMemo = $bill["billMemo"];
		
		// 启用与否
		$billStatus = $bill["billStatus"];
		
		// 物资明细
		$items = $bill["items"];
		// 能使用模板的组织机构
		$orgs = $bill["orgs"];
		
		// 主表
		$id = $this->newId();
		$sql = "insert into t_us_template(id, ref, input_user_id, date_created, bill_status, bill_memo, data_org, company_id)
				values ('%s', '%s', '%s', now(), %d, '%s', '%s', '%s')";
		$rc = $db->execute($sql, $id, $ref, $loginUserId, $billStatus, $billMemo, $dataOrg, 
				$companyId);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 物资明细
		$goodsDAO = new GoodsDAO($db);
		foreach ( $items as $i => $v ) {
			$goodsId = $v["goodsId"];
			if (! $goodsDAO->getGoodsById($goodsId)) {
				continue;
			}
			
			$saleCount = $v["saleCount"];
			$lostCount = $v["lostCount"];
			$calcCount = $saleCount + $lostCount;
			$memo = $v["memo"];
			
			$sql = "insert into t_us_template_detail(id, ustemplate_id, show_order, goods_id, 
						sale_count, lost_count, calc_count, data_org, company_id, memo)
					values ('%s', '%s', %d, '%s',
						convert(%f, $fmt), convert(%f, $fmt),convert(%f, $fmt),'%s', '%s', '%s')";
			$rc = $db->execute($sql, $this->newId(), $id, $i, $goodsId, $saleCount, $lostCount, 
					$calcCount, $dataOrg, $companyId, $memo);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 组织机构
		$orgDAO = new OrgDAO($db);
		foreach ( $orgs as $v ) {
			$orgId = $v["id"];
			if (! $orgDAO->getOrgById($orgId)) {
				continue;
			}
			
			$sql = "insert into t_us_template_org(id, ustemplate_id, org_id) values ('%s', '%s', '%s')";
			$rc = $db->execute($sql, $this->newId(), $id, $orgId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 操作成功
		$bill["id"] = $id;
		return null;
	}

	/**
	 * 编辑消耗单模板
	 *
	 * @param array $bill        	
	 */
	public function updateUSTemplate(&$bill) {
		$db = $this->db;
		$dataOrg = $bill["dataOrg"];
		if ($this->dataOrgNotExists($dataOrg)) {
			return $this->badParam("dataOrg");
		}
		$loginUserId = $bill["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->badParam("loginUserId");
		}
		$companyId = $bill["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// 模板id
		$id = $bill["id"];
		
		// 模板编号
		$ref = $bill["ref"];
		
		// 备注
		$billMemo = $bill["billMemo"];
		
		// 启用与否
		$billStatus = $bill["billStatus"];
		
		// 物资明细
		$items = $bill["items"];
		// 能使用模板的组织机构
		$orgs = $bill["orgs"];
		
		// 主表
		// 检查模板是否存在
		$sql = "select ref from t_us_template where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要编辑的消耗单模板不存在");
		}
		
		// 检查编号是否重复
		$sql = "select count(*) as cnt from t_us_template where ref = '%s' and id <> '%s' ";
		$data = $db->query($sql, $ref, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("模板编号[$ref]已经存在");
		}
		
		$sql = "update t_us_template
					set ref = '%s', bill_memo = '%s', bill_status = %d
				where id = '%s' ";
		$rc = $db->execute($sql, $ref, $billMemo, $billStatus, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 物资明细
		$sql = "delete from t_us_template_detail where ustemplate_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$goodsDAO = new GoodsDAO($db);
		foreach ( $items as $i => $v ) {
			$goodsId = $v["goodsId"];
			if (! $goodsDAO->getGoodsById($goodsId)) {
				continue;
			}
			
			$saleCount = $v["saleCount"];
			$lostCount = $v["lostCount"];
			$calcCount = $saleCount + $lostCount;
			$memo = $v["memo"];
			
			$sql = "insert into t_us_template_detail(id, ustemplate_id, show_order, goods_id,
			sale_count, lost_count, calc_count, data_org, company_id, memo)
			values ('%s', '%s', %d, '%s',
			convert(%f, $fmt), convert(%f, $fmt),convert(%f, $fmt),'%s', '%s', '%s')";
			$rc = $db->execute($sql, $this->newId(), $id, $i, $goodsId, $saleCount, $lostCount, 
					$calcCount, $dataOrg, $companyId, $memo);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 组织机构
		$sql = "delete from t_us_template_org where ustemplate_id = '%s'";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$orgDAO = new OrgDAO($db);
		foreach ( $orgs as $v ) {
			$orgId = $v["id"];
			if (! $orgDAO->getOrgById($orgId)) {
				continue;
			}
			
			$sql = "insert into t_us_template_org(id, ustemplate_id, org_id) values ('%s', '%s', '%s')";
			$rc = $db->execute($sql, $this->newId(), $id, $orgId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 操作成功
		$bill["id"] = $id;
		return null;
	}

	/**
	 * 某个消耗单模板的物资明细
	 *
	 * @param array $params        	
	 */
	public function usTemplateDetailList($params) {
		$db = $this->db;
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// 模板主表id
		$id = $params["id"];
		
		$sql = "select t.id, g.code, g.name, g.spec, u.name as unit_name, 
					convert(t.sale_count, $fmt) as sale_count,
					convert(t.lost_count, $fmt) as lost_count, t.memo 
				from t_us_template_detail t, t_goods g, t_goods_unit u
				where t.goods_id = g.id and g.unit_id = u.id
					and t.ustemplate_id  = '%s'
				order by t.show_order ";
		$data = $db->query($sql, $id);
		$result = [];
		foreach ( $data as $v ) {
			$saleCount = $v["sale_count"];
			if ($saleCount == 0) {
				$saleCount = null;
			}
			$lostCount = $v["lost_count"];
			if ($lostCount == 0) {
				$lostCount = null;
			}
			$result[] = [
					"id" => $v["id"],
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"unitName" => $v["unit_name"],
					"saleCount" => $saleCount,
					"lostCount" => $lostCount,
					"memo" => $v["memo"]
			];
		}
		
		return $result;
	}

	/**
	 * 某个消耗单模板的使用组织机构
	 *
	 * @param array $params        	
	 */
	public function usTemplateOrgList($params) {
		$db = $this->db;
		
		// 模板主表id
		$id = $params["id"];
		
		$result = [];
		$sql = "select p.id, o.full_name, o.org_type
				from t_us_template_org p, t_org o
				where p.ustemplate_id = '%s' and p.org_id = o.id
				order by o.org_code ";
		$data = $db->query($sql, $id);
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"orgName" => $v["full_name"],
					"orgType" => $this->orgTypeCodeToName($v["org_type"])
			];
		}
		
		return $result;
	}

	/**
	 * 删除消耗单模板
	 *
	 * @param array $params        	
	 */
	public function deleteUSTemplate(&$params) {
		$db = $this->db;
		
		$id = $params["id"];
		
		$sql = "select ref from t_us_template where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要删除的消耗单模板不存在");
		}
		$ref = $data[0]["ref"];
		
		// 删除使用的组织机构
		$sql = "delete from t_us_template_org where ustemplate_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 删除物资明细
		$sql = "delete from t_us_template_detail where ustemplate_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 删除主表
		$sql = "delete from t_us_template where id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 操作成功
		$params["ref"] = $ref;
		return null;
	}

	/**
	 * 消耗单列表
	 *
	 * @param array $params        	
	 */
	public function usBillList($params) {
		$db = $this->db;
		
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->emptyResult();
		}
		
		$start = $params["start"];
		$limit = $params["limit"];
		
		$billStatus = $params["billStatus"];
		$ref = $params["ref"];
		$queryParams = [];
		
		$sql = "select b.id, b.ref, g.full_name as org_name, u1.name as biz_user_name,
					u2.name as input_user_name, w.name as warehouse_name,
					b.date_created, b.bill_memo, b.bill_status
				from t_us_bill b, t_org g, t_user u1, t_user u2, t_warehouse w
				where (b.org_id = g.id) and (b.biz_user_id = u1.id) 
					and (b.input_user_id = u2.id) and (b.warehouse_id = w.id)";
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::SCM_STORE_US, "b", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParams = array_merge($queryParams, $rs[1]);
		}
		if ($billStatus != - 1) {
			$sql .= " and (b.bill_status = %d) ";
			$queryParams[] = $billStatus;
		}
		if ($ref) {
			$sql .= " and (b.ref like '%s') ";
			$queryParams[] = "%$ref%";
		}
		
		$sql .= " order by b.ref desc";
		
		$result = [];
		$data = $db->query($sql, $queryParams);
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"ref" => $v["ref"],
					"orgName" => $v["org_name"],
					"bizUserName" => $v["biz_user_name"],
					"inputUserName" => $v["input_user_name"],
					"warehouseName" => $v["warehouse_name"],
					"dateCreated" => $v["date_created"],
					"billMemo" => $v["bill_memo"],
					"billStatus" => $v["bill_status"]
			];
		}
		
		$queryParams = [];
		
		$sql = "select count(*) as cnt
				from t_us_bill b, t_org g, t_user u1, t_user u2, t_warehouse w
				where (b.org_id = g.id) and (b.biz_user_id = u1.id)
					and (b.input_user_id = u2.id) and (b.warehouse_id = w.id)";
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::SCM_STORE_US, "b", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParams = array_merge($queryParams, $rs[1]);
		}
		if ($billStatus != - 1) {
			$sql .= " and (b.bill_status = %d) ";
			$queryParams[] = $billStatus;
		}
		if ($ref) {
			$sql .= " and (b.ref like '%s') ";
			$queryParams[] = "%$ref%";
		}
		
		$data = $db->query($sql, $queryParams);
		$cnt = $data[0]["cnt"];
		
		return [
				"dataList" => $result,
				"totalCount" => $cnt
		];
	}

	/**
	 * 损耗单 - 选择模板 - 主表列表
	 *
	 * @param array $params        	
	 */
	public function selectUSTemplateList($params) {
		$db = $this->db;
		
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->emptyResult();
		}
		
		$sql = "select org_id from t_user where id = '%s' ";
		$data = $db->query($sql, $loginUserId);
		if (! $data) {
			return $this->emptyResult();
		}
		$orgId = $data[0]["org_id"];
		
		$ref = $params["ref"];
		
		$sql = "select t.id, t.ref, t.bill_memo
				from t_us_template t, t_us_template_org g
				where (t.bill_status = 1000) and (t.id = g.ustemplate_id) 
						and (org_id = '%s') ";
		$queryParams = [];
		$queryParams[] = $orgId;
		if ($ref) {
			$sql .= " and (t.ref like '%s') ";
			$queryParams[] = "%{$ref}%";
		}
		$sql .= " order by t.ref ";
		$data = $db->query($sql, $queryParams);
		$result = [];
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"ref" => $v["ref"],
					"billMemo" => $v["bill_memo"]
			];
		}
		
		$sql = "select count(*) as cnt 
				from t_us_template t, t_us_template_org g
				where (t.bill_status = 1000) and (t.id = g.ustemplate_id) 
						and (org_id = '%s') ";
		$queryParams = [];
		$queryParams[] = $orgId;
		if ($ref) {
			$sql .= " and (t.ref like '%s') ";
			$queryParams[] = "%{$ref}%";
		}
		$data = $db->query($sql, $queryParams);
		$cnt = $data[0]["cnt"];
		
		return [
				"dataList" => $result,
				"totalCount" => $cnt
		];
	}

	/**
	 * 损耗单 - 选择模板 - 明细列表
	 *
	 * @param array $params        	
	 */
	public function usTemplateDetailListForUSBill($params) {
		$db = $this->db;
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// 模板主表id
		$id = $params["id"];
		
		$sql = "select t.id, g.code, g.name, g.spec, u.name as unit_name,
					convert(t.sale_count, $fmt) as sale_count,
					convert(t.lost_count, $fmt) as lost_count, t.memo
				from t_us_template_detail t, t_goods g, t_goods_unit u
				where t.goods_id = g.id and g.unit_id = u.id
					and t.ustemplate_id  = '%s'
				order by t.show_order ";
		$data = $db->query($sql, $id);
		$result = [];
		foreach ( $data as $v ) {
			$saleCount = $v["sale_count"];
			if ($saleCount == 0) {
				$saleCount = null;
			}
			$lostCount = $v["lost_count"];
			if ($lostCount == 0) {
				$lostCount = null;
			}
			$result[] = [
					"id" => $v["id"],
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"unitName" => $v["unit_name"],
					"saleCount" => $saleCount,
					"lostCount" => $lostCount,
					"memo" => $v["memo"]
			];
		}
		
		return $result;
	}

	/**
	 * 损耗单 - 选择模板后查询该模板的数据
	 *
	 * @param array $params        	
	 */
	public function getUSTemplateInfoForUSBill($params) {
		$db = $this->db;
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// 模板主表id
		$id = $params["id"];
		
		$sql = "select t.id, g.id as goods_id, g.code, g.name, g.spec, u.name as unit_name,
					convert(t.sale_count, $fmt) as sale_count,
					convert(t.lost_count, $fmt) as lost_count, t.memo
				from t_us_template_detail t, t_goods g, t_goods_unit u
				where t.goods_id = g.id and g.unit_id = u.id
					and t.ustemplate_id  = '%s'
				order by t.show_order ";
		$data = $db->query($sql, $id);
		$result = [];
		$items = [];
		foreach ( $data as $v ) {
			$saleCount = $v["sale_count"];
			if ($saleCount == 0) {
				$saleCount = null;
			}
			$lostCount = $v["lost_count"];
			if ($lostCount == 0) {
				$lostCount = null;
			}
			$items[] = [
					"id" => $v["id"],
					"goodsId" => $v["goods_id"],
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"unitName" => $v["unit_name"],
					"saleCount" => $saleCount,
					"lostCount" => $lostCount,
					"memo" => $v["memo"]
			];
		}
		
		$result["items"] = $items;
		$result["bizUserId"] = $params["loginUserId"];
		$result["bizUserName"] = $params["loginUserName"];
		
		// 当前业务员
		
		return $result;
	}

	public function getUSTemplateById($id) {
		$db = $this->db;
		$sql = "select ref, bill_status from t_us_template where id = '%s' ";
		$data = $db->query($sql, $id);
		if ($data) {
			$v = $data[0];
			return [
					"id" => $id,
					"ref" => $v["ref"],
					"billStatus" => $v["bill_status"]
			];
		} else {
			return null;
		}
	}

	/**
	 * 检查仓库的正确性
	 *
	 * @param string $warehouseId        	
	 * @param string $ustemplateId        	
	 */
	private function checkWarehouseForUSTemplate($warehouseId, $ustemplateId) {
		$db = $this->db;
		
		// 获得仓库的组织机构
		$sql = "select org_id from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $warehouseId);
		if (! $data) {
			return $this->badParam("warehouseId");
		}
		$orgId = $data[0]["org_id"];
		
		// 检查仓库所属的组织机构是否在在模板允许的组织机构之中
		$sql = "select count(*) as cnt from t_us_template_org
				where org_id = '%s' and ustemplate_id = '%s' ";
		$data = $db->query($sql, $orgId, $ustemplateId);
		$cnt = $data[0]["cnt"];
		if ($cnt == 0) {
			return $this->bad("仓库不属于当前模板所允许使用的组织机构");
		}
		
		// 正确
		return null;
	}

	/**
	 * 新建消耗单
	 *
	 * @param array $bill        	
	 */
	public function addUSBill(& $bill) {
		$db = $this->db;
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
		
		$bizUserId = $bill["bizUserId"];
		$userDAO = new UserDAO($db);
		if (! $userDAO->getUserById($bizUserId)) {
			return $this->bad("业务员不存在");
		}
		$bizDT = $bill["bizDT"];
		if (! $this->dateIsValid($bizDT)) {
			return $this->bad("业务日期不正确");
		}
		
		$ustemplateId = $bill["ustemplateId"];
		if (! $this->getUSTemplateById($ustemplateId)) {
			return $this->bad("消耗单模板不存在");
		}
		
		$warehouseId = $bill["warehouseId"];
		$sql = "select org_id from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $warehouseId);
		if (! $data) {
			return $this->bad("仓库不存在");
		}
		$orgId = $data[0]["org_id"];
		
		$rc = $this->checkWarehouseForUSTemplate($warehouseId, $ustemplateId);
		if ($rc) {
			return $rc;
		}
		
		$billMemo = $bill["billMemo"];
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// 主表
		$id = $this->newId();
		$ref = $this->genNewBillRef($companyId);
		
		$sql = "insert into t_us_bill(id, ref, biz_user_id, biz_dt, input_user_id,
					date_created, bill_status, bill_memo, data_org, company_id,
					org_id, ustemplate_id, warehouse_id)
				values('%s', '%s', '%s', '%s', '%s',
					now(), 0, '%s', '%s', '%s',
					'%s', '%s', '%s')";
		$rc = $db->execute($sql, $id, $ref, $bizUserId, $bizDT, $loginUserId, $billMemo, $dataOrg, 
				$companyId, $orgId, $ustemplateId, $warehouseId);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 明细表
		$items = $bill["items"];
		foreach ( $items as $showOrder => $v ) {
			$goodsId = $v["goodsId"];
			$checkCount = $v["checkCount"];
			$saleCount = $v["saleCount"];
			$lostCount = $v["lostCount"];
			$memo = $v["memo"];
			
			$detailId = $this->newId();
			
			$sql = "insert into t_us_bill_detail(id, usbill_id, show_order, goods_id, 
						check_count, sale_count, lost_count, 
						data_org, company_id, memo)
					values('%s', '%s', %d, '%s',
						convert(%f, $fmt), convert(%f, $fmt),convert(%f, $fmt),
						'%s', '%s', '%s')";
			$rc = $db->execute($sql, $detailId, $id, $showOrder, $goodsId, $checkCount, $saleCount, 
					$lostCount, $dataOrg, $companyId, $memo);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 操作成功
		$bill["id"] = $id;
		$bill["ref"] = $ref;
		return null;
	}

	/**
	 * 编辑消耗单
	 *
	 * @param array $bill        	
	 */
	public function updateUSBill(& $bill) {
		$db = $this->db;
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
		
		$id = $bill["id"];
		$b = $this->getUSBillById($id);
		if (! $b) {
			return $this->bad("要编辑的消耗单不存在");
		}
		$billStatus = $b["billStatus"];
		if ($billStatus > 0) {
			return $this->bad("消耗单已经提交，不能再次编辑");
		}
		$ref = $b["ref"];
		
		$bizUserId = $bill["bizUserId"];
		$userDAO = new UserDAO($db);
		if (! $userDAO->getUserById($bizUserId)) {
			return $this->bad("业务员不存在");
		}
		$bizDT = $bill["bizDT"];
		if (! $this->dateIsValid($bizDT)) {
			return $this->bad("业务日期不正确");
		}
		
		$ustemplateId = $bill["ustemplateId"];
		if (! $this->getUSTemplateById($ustemplateId)) {
			return $this->bad("消耗单模板不存在");
		}
		
		$warehouseId = $bill["warehouseId"];
		$sql = "select org_id from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $warehouseId);
		if (! $data) {
			return $this->bad("仓库不存在");
		}
		$orgId = $data[0]["org_id"];
		
		$rc = $this->checkWarehouseForUSTemplate($warehouseId, $ustemplateId);
		if ($rc) {
			return $rc;
		}
		
		$billMemo = $bill["billMemo"];
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// 主表
		$sql = "update t_us_bill
					set biz_user_id = '%s', biz_dt = '%s', warehouse_id = '%s',
						org_id = '%s', bill_memo = '%s'
				where id = '%s' ";
		$rc = $db->execute($sql, $bizUserId, $bizDT, $warehouseId, $orgId, $billMemo, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 明细表
		$sql = "delete from t_us_bill_detail where usbill_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$items = $bill["items"];
		foreach ( $items as $showOrder => $v ) {
			$goodsId = $v["goodsId"];
			$checkCount = $v["checkCount"];
			$saleCount = $v["saleCount"];
			$lostCount = $v["lostCount"];
			$memo = $v["memo"];
			
			$detailId = $this->newId();
			
			$sql = "insert into t_us_bill_detail(id, usbill_id, show_order, goods_id,
						check_count, sale_count, lost_count,
						data_org, company_id, memo)
					values('%s', '%s', %d, '%s',
						convert(%f, $fmt), convert(%f, $fmt),convert(%f, $fmt),
						'%s', '%s', '%s')";
			$rc = $db->execute($sql, $detailId, $id, $showOrder, $goodsId, $checkCount, $saleCount, 
					$lostCount, $dataOrg, $companyId, $memo);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 操作成功
		$bill["ref"] = $ref;
		return null;
	}

	/**
	 * 某个消耗单明细列表
	 *
	 * @param array $params        	
	 */
	public function usBillDetailList($params) {
		$db = $this->db;
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		// 消耗单主表id
		$id = $params["id"];
		
		$sql = "select t.id, g.code, g.name, g.spec, u.name as unit_name,
				convert(t.check_count, $fmt) as check_count,
				convert(t.sale_count, $fmt) as sale_count,
				convert(t.lost_count, $fmt) as lost_count, t.memo, t.inv_goods_price
			from t_us_bill_detail t, t_goods g, t_goods_unit u
			where t.goods_id = g.id and g.unit_id = u.id
				and t.usbill_id  = '%s'
			order by t.show_order ";
		$data = $db->query($sql, $id);
		$result = [];
		foreach ( $data as $v ) {
			$checkCount = $v["check_count"];
			if ($checkCount == 0) {
				$checkCount = null;
			}
			$saleCount = $v["sale_count"];
			if ($saleCount == 0) {
				$saleCount = null;
			}
			$lostCount = $v["lost_count"];
			if ($lostCount == 0) {
				$lostCount = null;
			}
			$result[] = [
					"id" => $v["id"],
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"unitName" => $v["unit_name"],
					"checkCount" => $checkCount,
					"saleCount" => $saleCount,
					"lostCount" => $lostCount,
					"invGoodsPrice" => $v["inv_goods_price"],
					"memo" => $v["memo"]
			];
		}
		
		return $result;
	}

	/**
	 * 某个消耗单的详情
	 *
	 * @param array $params        	
	 */
	public function usBillInfo($params) {
		$db = $this->db;
		
		// 消耗单主表id
		$id = $params["id"];
		
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->emptyResult();
		}
		
		// 主表
		$sql = "select b.ref, u.name as biz_user_name, u.id as biz_user_id, b.biz_dt,
					w.name as warehouse_name, w.id as warehouse_id,
					b.bill_memo, b.ustemplate_id
				from t_us_bill b, t_user u, t_warehouse w
				where (b.biz_user_id = u.id) and (b.warehouse_id = w.id)
					and (b.id = '%s') ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->emptyResult();
		}
		$v = $data[0];
		
		$result = [
				"ref" => $v["ref"],
				"bizDT" => $this->toYMD($v["biz_dt"]),
				"warehouseId" => $v["warehouse_id"],
				"warehouseName" => $v["warehouse_name"],
				"bizUserId" => $v["biz_user_id"],
				"bizUserName" => $v["biz_user_name"],
				"billMemo" => $v["bill_memo"],
				"ustemplateId" => $v["ustemplate_id"]
		];
		
		// 明细表
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$sql = "select t.id, t.goods_id, g.code, g.name, g.spec, u.name as unit_name,
					convert(t.check_count, $fmt) as check_count,
					convert(t.sale_count, $fmt) as sale_count,
					convert(t.lost_count, $fmt) as lost_count, t.memo
				from t_us_bill_detail t, t_goods g, t_goods_unit u
				where t.goods_id = g.id and g.unit_id = u.id
				and t.usbill_id  = '%s'
				order by t.show_order ";
		$data = $db->query($sql, $id);
		$items = [];
		foreach ( $data as $v ) {
			$checkCount = $v["check_count"];
			if ($checkCount == 0) {
				$checkCount = null;
			}
			$saleCount = $v["sale_count"];
			if ($saleCount == 0) {
				$saleCount = null;
			}
			$lostCount = $v["lost_count"];
			if ($lostCount == 0) {
				$lostCount = null;
			}
			$items[] = [
					"id" => $v["id"],
					"goodsId" => $v["goods_id"],
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"unitName" => $v["unit_name"],
					"checkCount" => $checkCount,
					"saleCount" => $saleCount,
					"lostCount" => $lostCount,
					"memo" => $v["memo"]
			];
		}
		
		$result["items"] = $items;
		
		return $result;
	}

	public function getUSBillById($id) {
		$db = $this->db;
		
		$sql = "select ref, bill_status from t_us_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		if ($data) {
			$v = $data[0];
			return [
					"id" => $id,
					"ref" => $v["ref"],
					"billStatus" => $v["bill_status"]
			];
		} else {
			return null;
		}
	}

	/**
	 * 删除消耗单
	 *
	 * @param array $params        	
	 */
	public function deleteUSBill(&$params) {
		$db = $this->db;
		
		// 消耗单主表id
		$id = $params["id"];
		
		$b = $this->getUSBillById($id);
		if (! $b) {
			return $this->bad("要删除的消耗单不存在");
		}
		$ref = $b["ref"];
		$billStatus = $b["billStatus"];
		if ($billStatus > 0) {
			return $this->bad("消耗单[单号：{$ref}]已经提交了，不能被删除");
		}
		
		// 删除明细记录
		$sql = "delete from t_us_bill_detail where usbill_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 删除主表
		$sql = "delete from t_us_bill where id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 操作成功
		$params["ref"] = $ref;
		return null;
	}

	/**
	 * 提交消耗单
	 *
	 * @param array $params        	
	 */
	public function commitUSBill(&$params) {
		$db = $this->db;
		$companyId = $params["companyId"];
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		// 消耗单主表
		$id = $params["id"];
		$sql = "select ref, bill_status, warehouse_id, biz_user_id, biz_dt 
				from t_us_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		
		$bill = $data[0];
		if (! $bill) {
			return $this->bad("要提交的消耗单不存在");
		}
		$ref = $bill["ref"];
		$billStatus = $bill["bill_status"];
		if ($billStatus > 0) {
			return $this->bad("消耗单[单号：{$ref}]已经提交过了，不能再次提交");
		}
		
		$warehouseId = $bill["warehouse_id"];
		$warehouseDAO = new WarehouseDAO($db);
		$w = $warehouseDAO->getWarehouseById($warehouseId);
		if (! $w) {
			return $this->bad("盘点的仓库不存在");
		}
		$inited = $w["inited"];
		if ($inited == 0) {
			return $this->bad("盘点的仓库还没有建账，不能进行业务操作");
		}
		$bizUserId = $bill["biz_user_id"];
		$userDAO = new UserDAO($db);
		if (! $userDAO->getUserById($bizUserId)) {
			return $this->bad("业务员不存在");
		}
		$bizDT = $this->toYMD($bill["biz_dt"]);
		
		$bcDAO = new BizConfigDAO($db);
		$dataScale = $bcDAO->getGoodsCountDecNumber($companyId);
		$fmt = "decimal(19, " . $dataScale . ")";
		
		$sql = "select id, goods_id, convert(check_count, $fmt) as check_count 
				from t_us_bill_detail
				where usbill_id = '%s'
				order by show_order ";
		$items = $db->query($sql, $id);
		$goodsDAO = new GoodsDAO($db);
		$invDAO = new InventoryDAO($db);
		foreach ( $items as $i => $v ) {
			$detailId = $v["id"];
			$goodsId = $v["goods_id"];
			if (! $goodsDAO->getGoodsById($goodsId)) {
				continue;
			}
			
			$checkCount = $v["check_count"];
			
			$qcBeginDT = "1970-01-01";
			$qcDays = 0;
			$qcEndDT = "1970-01-01";
			$qcSN = "";
			$goodsCount = $checkCount;
			$refType = "门店盘点-消耗出库";
			$outType = 1; // 忽略保质期，先进先出
			$recordIndex = $i + 1;
			
			$outPriceForResult = 0;
			
			$rc = $invDAO->outAction($warehouseId, $goodsId, $qcBeginDT, $qcDays, $qcEndDT, $qcSN, 
					$goodsCount, $bizDT, $bizUserId, $ref, $refType, $outType, $recordIndex, $fmt, 
					$outPriceForResult, $outMoneyForResult);
			if ($rc) {
				return $rc;
			}
			
			// 同步消耗单中的存货单价字段
			$sql = "update t_us_bill_detail
						set inv_goods_price = %f
					where id = '%s' ";
			$rc = $db->execute($sql, $outPriceForResult, $detailId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 更新主表状态
		$sql = "update t_us_bill set bill_status = 1000 where id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 操作成功
		$params["ref"] = $ref;
		return null;
	}
}