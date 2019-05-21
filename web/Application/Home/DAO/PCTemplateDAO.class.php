<?php

namespace Home\DAO;

use Home\Common\FIdConst;

/**
 * 采购模板 DAO
 *
 * @author 李静波
 */
class PCTemplateDAO extends PSIBaseExDAO {

	/**
	 * 采购模板详情
	 *
	 * @param array $params        	
	 */
	public function pcTemplateInfo($params) {
		$db = $this->db;
		
		$result = [];
		
		// 采购模板id
		$id = $params["id"];
		
		// 采购合同id
		$pcBillId = $params["pcBillId"];
		
		if ($id) {
			// 编辑
			$sql = "select ref, bill_memo, bill_status
				from t_pc_template
				where id = '%s' ";
			$data = $db->query($sql, $id);
			if (! $data) {
				return $result;
			}
			
			$v = $data[0];
			$result["ref"] = $v["ref"];
			$result["billMemo"] = $v["bill_memo"];
			$result["billStatus"] = $v["bill_status"];
			
			// 物资明细
			$result["items"] = [];
			$sql = "select p.id, p.pcbill_detail_id,p.goods_id, g.code, g.name, g.spec, 
						p.unit_id, u.name as unit_name
					from t_pc_template_detail p, t_goods g, t_goods_unit u
					where p.pctemplate_id = '%s' and p.goods_id = g.id and p.unit_id = u.id
					order by p.show_order ";
			$data = $db->query($sql, $id);
			foreach ( $data as $v ) {
				$result["items"][] = [
						"id" => $v["id"],
						"pcDetailId" => $v["pcbill_detail_id"],
						"goodsId" => $v["goods_id"],
						"goodsCode" => $v["code"],
						"goodsName" => $v["name"],
						"goodsSpec" => $v["spec"],
						"unitId" => $v["unit_id"],
						"unitName" => $v["unit_name"]
				];
			}
			
			// 组织机构
			$sql = "select o.id, o.full_name, o.org_type
				from t_pc_template_org p, t_org o
				where p.pctemplate_id = '%s' and p.org_id = o.id
				order by o.org_code ";
			$data = $db->query($sql, $id);
			foreach ( $data as $v ) {
				$result["orgs"][] = [
						"id" => $v["id"],
						"orgName" => $v["full_name"],
						"orgType" => $this->orgTypeCodeToName($v["org_type"])
				];
			}
		} else {
			// 新建
			
			$sql = "select p.id as pc_detail_id, g.id, g.code, g.name, g.spec, u.name as fu_unit_name
					from t_pc_bill_detail p, t_goods g, t_goods_unit u
					where p.pcbill_id = '%s' and p.goods_id = g.id
						and g.fu_unit_id = u.id
					order by p.show_order ";
			$data = $db->query($sql, $pcBillId);
			
			$items = [];
			
			foreach ( $data as $v ) {
				$items[] = [
						"goodsId" => $v["id"],
						"goodsCode" => $v["code"],
						"goodsName" => $v["name"],
						"goodsSpec" => $v["spec"],
						"fuUnitName" => $v["fu_unit_name"],
						"pcDetailId" => $v["pc_detail_id"]
				];
			}
			
			$result["items"] = $items;
		}
		
		return $result;
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
	 * 选择组织机构
	 *
	 * @param array $params        	
	 */
	public function selectOrgForPCTemplate($params) {
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
	 * 选择物资
	 *
	 * @param array $params        	
	 */
	public function selectGoodsForPCTemplate($params) {
		$db = $this->db;
		
		$pcbillId = $params["pcbillId"];
		
		$result = [];
		
		$sql = "select p.id as pc_detail_id, g.id, g.code, g.name, g.spec, 
						p.unit_id, u.name as unit_name
					from t_pc_bill_detail p, t_goods g, t_goods_unit u
					where p.pcbill_id = '%s' and p.goods_id = g.id
						and p.unit_id = u.id
					order by p.show_order ";
		$data = $db->query($sql, $pcbillId);
		
		foreach ( $data as $v ) {
			$result[] = [
					"goodsId" => $v["id"],
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"unitId" => $v["unit_id"],
					"unitName" => $v["unit_name"],
					"pcDetailId" => $v["pc_detail_id"]
			];
		}
		
		return $result;
	}

	/**
	 * 新建采购模板
	 *
	 * @param array $bill        	
	 */
	public function addPCTemplate(&$bill) {
		$db = $this->db;
		
		$ref = $bill["ref"];
		$billMemo = $bill["billMemo"];
		$billStatus = $bill["billStatus"];
		$pcBillId = $bill["pcBillId"];
		
		$loginUserId = $bill["loginUserId"];
		$dataOrg = $bill["dataOrg"];
		$companyId = $bill["companyId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->badParam("loginUserId");
		}
		if ($this->dataOrgNotExists($dataOrg)) {
			return $this->badParam("dataOrg");
		}
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		// 检查模板编号是否存在
		$sql = "select count(*) as cnt from t_pc_template
				where ref = '%s' ";
		$data = $db->query($sql, $ref);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("编号为 {$ref} 的模板已经存在");
		}
		
		// 检查pcBillId是否存在
		$sql = "select count(*) as cnt from t_pc_bill where id = '%s' ";
		$data = $db->query($sql, $pcBillId);
		$cnt = $data[0]["cnt"];
		if ($cnt != 1) {
			return $this->bad("模板对应的供应合同不存在");
		}
		
		$id = $this->newId();
		
		// 主表
		$sql = "insert into t_pc_template (id, ref, pcbill_id, date_created, 
					bill_status, bill_memo, data_org, company_id, input_user_id)
				values ('%s', '%s', '%s', now(), %d, '%s', '%s', '%s', '%s')";
		$rc = $db->execute($sql, $id, $ref, $pcBillId, $billStatus, $billMemo, $dataOrg, $companyId, 
				$loginUserId);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 物资明细
		$unitDAO = new GoodsUnitDAO($db);
		$items = $bill["items"];
		foreach ( $items as $showOrder => $item ) {
			$pcDetailId = $item["pcDetailId"];
			$goodsId = $item["goodsId"];
			
			// 检查该物资在供应合同中是否存在
			$sql = "select count(*) as cnt from t_pc_bill_detail
					where id = '%s' and goods_id = '%s' ";
			$data = $db->query($sql, $pcDetailId, $goodsId);
			$cnt = $data[0]["cnt"];
			if ($cnt != 1) {
				continue;
			}
			
			$unitId = $item["unitId"];
			// 检查unitId
			if (! $unitDAO->unitIdIsValid($goodsId, $unitId)) {
				$rIndex = $showOrder + 1;
				return $this->bad("第{$rIndex}条记录中，传入的计量单位不正确");
			}
			
			$detailId = $this->newId();
			$sql = "insert into t_pc_template_detail(id, pctemplate_id, pcbill_detail_id, 
						show_order, goods_id, data_org, company_id, unit_id)
					values ('%s', '%s', '%s', %d, '%s', '%s', '%s', '%s')";
			$rc = $db->execute($sql, $detailId, $id, $pcDetailId, $showOrder, $goodsId, $dataOrg, 
					$companyId, $unitId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 组织机构
		$orgs = $bill["orgs"];
		foreach ( $orgs as $org ) {
			$detailId = $this->newId();
			$orgId = $org["id"];
			$sql = "insert into t_pc_template_org(id, pctemplate_id, org_id)
					values ('%s', '%s', '%s')";
			$rc = $db->execute($sql, $detailId, $id, $orgId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		$bill["id"] = $id;
		
		// 操作成功
		return null;
	}

	/**
	 * 采购模板列表
	 *
	 * @param array $params        	
	 */
	public function pcTemplateList($params) {
		$db = $this->db;
		
		$pcBillId = $params["pcBillId"];
		
		$sql = "select id, ref, bill_memo, bill_status
				from t_pc_template
				where pcbill_id = '%s' 
				order by ref ";
		$data = $db->query($sql, $pcBillId);
		$result = [];
		
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"ref" => $v["ref"],
					"billMemo" => $v["bill_memo"],
					"billStatus" => $v["bill_status"] == 1000 ? "启用" : "停用"
			];
		}
		
		return $result;
	}

	/**
	 * 采购模板详情 - 同时返回商品明细和组织机构明细
	 *
	 * @param array $params        	
	 */
	public function pcTemplateDetailInfo($params) {
		$db = $this->db;
		
		// 采购模板id
		$id = $params["id"];
		
		$result = [
				"items" => [],
				"orgs" => []
		];
		
		// 物资明细
		$sql = "select p.id, g.code, g.name, g.spec, u.name as unit_name
				from t_pc_template_detail p, t_goods g, t_goods_unit u
				where p.pctemplate_id = '%s' and p.goods_id = g.id and p.unit_id = u.id
				order by p.show_order ";
		$data = $db->query($sql, $id);
		foreach ( $data as $v ) {
			$result["items"][] = [
					"id" => $v["id"],
					"goodsCode" => $v["code"],
					"goodsName" => $v["name"],
					"goodsSpec" => $v["spec"],
					"unitName" => $v["unit_name"]
			];
		}
		
		// 组织机构
		$sql = "select p.id, o.full_name, o.org_type
				from t_pc_template_org p, t_org o
				where p.pctemplate_id = '%s' and p.org_id = o.id 
				order by o.org_code ";
		$data = $db->query($sql, $id);
		foreach ( $data as $v ) {
			$result["orgs"][] = [
					"id" => $v["id"],
					"orgName" => $v["full_name"],
					"orgType" => $this->orgTypeCodeToName($v["org_type"])
			];
		}
		
		return $result;
	}

	/**
	 * 删除采购模板
	 *
	 * @param array $params        	
	 */
	public function deletePCTemplate(&$params) {
		$db = $this->db;
		
		// 采购模板主表id
		$id = $params["id"];
		
		$sql = "select ref from t_pc_template where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要删除的采购模板不存在");
		}
		$ref = $data[0]["ref"];
		
		// 删除物资明细
		$sql = "delete from t_pc_template_detail where pctemplate_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 删除组织机构
		$sql = "delete from t_pc_template_org where pctemplate_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 删除主表
		$sql = "delete from t_pc_template where id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$params["ref"] = $ref;
		// 操作成功
		return null;
	}

	/**
	 * 编辑采购模板
	 *
	 * @param array $bill        	
	 */
	public function updatePCTemplate(&$bill) {
		$db = $this->db;
		
		// 采购模板主表id
		$id = $bill["id"];
		
		$ref = $bill["ref"];
		$billMemo = $bill["billMemo"];
		$billStatus = $bill["billStatus"];
		$pcBillId = $bill["pcBillId"];
		
		$loginUserId = $bill["loginUserId"];
		$dataOrg = $bill["dataOrg"];
		$companyId = $bill["companyId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->badParam("loginUserId");
		}
		if ($this->dataOrgNotExists($dataOrg)) {
			return $this->badParam("dataOrg");
		}
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		// 检查要编辑的采购模板是否存在
		$sql = "select count(*) as cnt from t_pc_template where id = '%s' ";
		$data = $db->query($sql, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt != 1) {
			return $this->bad("要编辑的采购模板不存在");
		}
		
		// 检查模板编号是否存在
		$sql = "select count(*) as cnt from t_pc_template
				where ref = '%s' and id <> '%s' ";
		$data = $db->query($sql, $ref, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("编号为 {$ref} 的模板已经存在");
		}
		
		// 检查pcBillId是否存在
		$sql = "select count(*) as cnt from t_pc_bill where id = '%s' ";
		$data = $db->query($sql, $pcBillId);
		$cnt = $data[0]["cnt"];
		if ($cnt != 1) {
			return $this->bad("模板对应的供应合同不存在");
		}
		
		// 主表
		$sql = "update t_pc_template
				set ref = '%s', bill_memo = '%s', bill_status = %d
				where id = '%s' ";
		$rc = $db->execute($sql, $ref, $billMemo, $billStatus, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 物资明细
		$sql = "delete from t_pc_template_detail where pctemplate_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$unitDAO = new GoodsUnitDAO($db);
		
		$items = $bill["items"];
		foreach ( $items as $showOrder => $item ) {
			$pcDetailId = $item["pcDetailId"];
			$goodsId = $item["goodsId"];
			
			// 检查该物资在供应合同中是否存在
			$sql = "select count(*) as cnt from t_pc_bill_detail
					where id = '%s' and goods_id = '%s' ";
			$data = $db->query($sql, $pcDetailId, $goodsId);
			$cnt = $data[0]["cnt"];
			if ($cnt != 1) {
				continue;
			}
			
			$unitId = $item["unitId"];
			// 检查unitId
			if (! $unitDAO->unitIdIsValid($goodsId, $unitId)) {
				$rIndex = $showOrder + 1;
				return $this->bad("第{$rIndex}条记录中，传入的计量单位不正确");
			}
			
			$detailId = $this->newId();
			$sql = "insert into t_pc_template_detail(id, pctemplate_id, pcbill_detail_id,
						show_order, goods_id, data_org, company_id, unit_id)
					values ('%s', '%s', '%s', %d, '%s', '%s', '%s', '%s')";
			$rc = $db->execute($sql, $detailId, $id, $pcDetailId, $showOrder, $goodsId, $dataOrg, 
					$companyId, $unitId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 组织机构
		$sql = "delete from t_pc_template_org where pctemplate_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$orgs = $bill["orgs"];
		foreach ( $orgs as $org ) {
			$detailId = $this->newId();
			$orgId = $org["id"];
			$sql = "insert into t_pc_template_org(id, pctemplate_id, org_id)
					values ('%s', '%s', '%s')";
			$rc = $db->execute($sql, $detailId, $id, $orgId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 操作成功
		return null;
	}
}