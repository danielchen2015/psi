<?php

namespace Home\DAO;

use Home\Common\FIdConst;

/**
 * 供应商 DAO
 *
 * @author 李静波
 */
class SupplierDAO extends PSIBaseExDAO {

	private function getSupplierCountWithAllSub($id, $rs, $params) {
		$db = $this->db;
		
		$code = $params["code"];
		$name = $params["name"];
		$address = $params["address"];
		$contact = $params["contact"];
		$mobile = $params["mobile"];
		$tel = $params["tel"];
		$qq = $params["qq"];
		
		$sql = "select count(*) as cnt 
				from t_supplier c
				where (category_id = '%s') ";
		$queryParam = [];
		$queryParam[] = $id;
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParam = array_merge($queryParam, $rs[1]);
		}
		if ($code) {
			$sql .= " and (c.code like '%s' ) ";
			$queryParam[] = "%{$code}%";
		}
		if ($name) {
			$sql .= " and (c.name like '%s' or c.py like '%s' ) ";
			$queryParam[] = "%{$name}%";
			$queryParam[] = "%{$name}%";
		}
		if ($address) {
			$sql .= " and (c.address like '%s' or c.address_shipping like '%s') ";
			$queryParam[] = "%$address%";
			$queryParam[] = "%$address%";
		}
		if ($contact) {
			$sql .= " and (c.contact01 like '%s' or c.contact02 like '%s' ) ";
			$queryParam[] = "%{$contact}%";
			$queryParam[] = "%{$contact}%";
		}
		if ($mobile) {
			$sql .= " and (c.mobile01 like '%s' or c.mobile02 like '%s' ) ";
			$queryParam[] = "%{$mobile}%";
			$queryParam[] = "%{$mobile}";
		}
		if ($tel) {
			$sql .= " and (c.tel01 like '%s' or c.tel02 like '%s' ) ";
			$queryParam[] = "%{$tel}%";
			$queryParam[] = "%{$tel}";
		}
		if ($qq) {
			$sql .= " and (c.qq01 like '%s' or c.qq02 like '%s' ) ";
			$queryParam[] = "%{$qq}%";
			$queryParam[] = "%{$qq}";
		}
		
		$data = $db->query($sql, $queryParam);
		$cnt = $data[0]["cnt"];
		
		// 子分类
		$sql = "select id from t_supplier_category c
				where (parent_id = '%s') ";
		$queryParam = [];
		$queryParam[] = $id;
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParam = array_merge($queryParam, $rs[1]);
		}
		
		$data = $db->query($sql, $queryParam);
		foreach ( $data as $v ) {
			// 递归调用自身
			$cnt += $this->getSupplierCountWithAllSub($v["id"], $rs, $params);
		}
		
		return $cnt;
	}

	private function allCategoriesInternal($id, $rs, $params) {
		$db = $this->db;
		
		$code = $params["code"];
		$name = $params["name"];
		$address = $params["address"];
		$contact = $params["contact"];
		$mobile = $params["mobile"];
		$tel = $params["tel"];
		$qq = $params["qq"];
		
		$loginUserId = $params["loginUserId"];
		
		$sql = "select c.id, c.code, c.name, c.full_name
				from t_supplier_category c
				where (c.parent_id = '%s') ";
		$queryParam = [];
		$queryParam[] = $id;
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParam = array_merge($queryParam, $rs[1]);
		}
		$sql .= " order by c.code";
		
		$data = $db->query($sql, $queryParam);
		
		$result = [];
		foreach ( $data as $v ) {
			$id = $v["id"];
			
			// 递归调用自己
			$children = $this->allCategoriesInternal($id, $rs, $params);
			
			$result[] = [
					"id" => $v["id"],
					"code" => $v["code"],
					"name" => $v["name"],
					"fullName" => $v["full_name"],
					"children" => $children,
					"leaf" => count($children) == 0,
					"expanded" => true,
					"cnt" => $this->getSupplierCountWithAllSub($id, $rs, $params),
					"iconCls" => "PSI-SupplierCategory"
			];
		}
		
		return $result;
	}

	/**
	 * 供应商分类
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function allCategories($params) {
		$db = $this->db;
		
		$code = $params["code"];
		$name = $params["name"];
		$address = $params["address"];
		$contact = $params["contact"];
		$mobile = $params["mobile"];
		$tel = $params["tel"];
		$qq = $params["qq"];
		
		$inQuery = false;
		if ($code || $name || $address || $contact || $mobile || $tel || $qq) {
			$inQuery = true;
		}
		
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->emptyResult();
		}
		
		$sql = "select c.id, c.code, c.name, c.full_name
				from t_supplier_category c 
				where (c.parent_id is null) ";
		$queryParam = [];
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::SUPPLIER_CATEGORY, "c", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParam = array_merge($queryParam, $rs[1]);
		}
		$sql .= " order by c.code";
		
		$data = $db->query($sql, $queryParam);
		
		$result = [];
		foreach ( $data as $v ) {
			$id = $v["id"];
			$children = $this->allCategoriesInternal($id, $rs, $params);
			
			$result[] = [
					"id" => $v["id"],
					"code" => $v["code"],
					"name" => $v["name"],
					"fullName" => $v["full_name"],
					"children" => $children,
					"leaf" => count($children) == 0,
					"expanded" => true,
					"cnt" => $this->getSupplierCountWithAllSub($id, $rs, $params),
					"iconCls" => "PSI-SupplierCategory"
			];
		}
		
		if ($inQuery) {
			$result = $this->filterCategory($result);
		}
		
		return $result;
	}

	/**
	 * 当查询的时候，把分类数是0的分类过滤掉
	 *
	 * @param array $data        	
	 */
	private function filterCategory($data) {
		$result = [];
		foreach ( $data as $v ) {
			if ($v["cnt"] == 0) {
				continue;
			}
			
			$result[] = $v;
		}
		
		return $result;
	}

	/**
	 * 某个分类下的供应商档案列表
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function supplierList($params) {
		$db = $this->db;
		
		$categoryId = $params["categoryId"];
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$code = $params["code"];
		$name = $params["name"];
		$address = $params["address"];
		$contact = $params["contact"];
		$mobile = $params["mobile"];
		$tel = $params["tel"];
		$qq = $params["qq"];
		
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->emptyResult();
		}
		
		$sql = "select id, category_id, code, name, contact01, qq01, tel01, mobile01,
				contact02, qq02, tel02, mobile02, init_payables, init_payables_dt,
				address, address_shipping,
				bank_name, bank_account, tax_number, fax, note, data_org, tax_rate
				from t_supplier
				where (category_id = '%s')";
		$queryParam = [];
		$queryParam[] = $categoryId;
		if ($code) {
			$sql .= " and (code like '%s' ) ";
			$queryParam[] = "%{$code}%";
		}
		if ($name) {
			$sql .= " and (name like '%s' or py like '%s' ) ";
			$queryParam[] = "%{$name}%";
			$queryParam[] = "%{$name}%";
		}
		if ($address) {
			$sql .= " and (address like '%s' or address_shipping like '%s') ";
			$queryParam[] = "%$address%";
			$queryParam[] = "%$address%";
		}
		if ($contact) {
			$sql .= " and (contact01 like '%s' or contact02 like '%s' ) ";
			$queryParam[] = "%{$contact}%";
			$queryParam[] = "%{$contact}%";
		}
		if ($mobile) {
			$sql .= " and (mobile01 like '%s' or mobile02 like '%s' ) ";
			$queryParam[] = "%{$mobile}%";
			$queryParam[] = "%{$mobile}";
		}
		if ($tel) {
			$sql .= " and (tel01 like '%s' or tel02 like '%s' ) ";
			$queryParam[] = "%{$tel}%";
			$queryParam[] = "%{$tel}";
		}
		if ($qq) {
			$sql .= " and (qq01 like '%s' or qq02 like '%s' ) ";
			$queryParam[] = "%{$qq}%";
			$queryParam[] = "%{$qq}";
		}
		
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::SUPPLIER, "t_supplier", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParam = array_merge($queryParam, $rs[1]);
		}
		
		$queryParam[] = $start;
		$queryParam[] = $limit;
		$sql .= " order by code
				limit %d, %d";
		$result = [];
		$data = $db->query($sql, $queryParam);
		foreach ( $data as $v ) {
			$initDT = $v["init_payables_dt"] ? $this->toYMD($v["init_payables_dt"]) : null;
			
			$taxRate = $v["tax_rate"];
			if ($taxRate) {
				if ($taxRate == - 1) {
					$taxRate = null;
				}
			}
			
			$result[] = [
					"id" => $v["id"],
					"categoryId" => $v["category_id"],
					"code" => $v["code"],
					"name" => $v["name"],
					"address" => $v["address"],
					"addressShipping" => $v["address_shipping"],
					"contact01" => $v["contact01"],
					"qq01" => $v["qq01"],
					"tel01" => $v["tel01"],
					"mobile01" => $v["mobile01"],
					"contact02" => $v["contact02"],
					"qq02" => $v["qq02"],
					"tel02" => $v["tel02"],
					"mobile02" => $v["mobile02"],
					"initPayables" => $v["init_payables"],
					"initPayablesDT" => $initDT,
					"bankName" => $v["bank_name"],
					"bankAccount" => $v["bank_account"],
					"tax" => $v["tax_number"],
					"fax" => $v["fax"],
					"note" => $v["note"],
					"dataOrg" => $v["data_org"],
					"taxRate" => $taxRate
			];
		}
		
		$sql = "select count(*) as cnt from t_supplier where (category_id  = '%s') ";
		$queryParam = [];
		$queryParam[] = $categoryId;
		if ($code) {
			$sql .= " and (code like '%s' ) ";
			$queryParam[] = "%{$code}%";
		}
		if ($name) {
			$sql .= " and (name like '%s' or py like '%s' ) ";
			$queryParam[] = "%{$name}%";
			$queryParam[] = "%{$name}%";
		}
		if ($address) {
			$sql .= " and (address like '%s') ";
			$queryParam[] = "%$address%";
		}
		if ($contact) {
			$sql .= " and (contact01 like '%s' or contact02 like '%s' ) ";
			$queryParam[] = "%{$contact}%";
			$queryParam[] = "%{$contact}%";
		}
		if ($mobile) {
			$sql .= " and (mobile01 like '%s' or mobile02 like '%s' ) ";
			$queryParam[] = "%{$mobile}%";
			$queryParam[] = "%{$mobile}";
		}
		if ($tel) {
			$sql .= " and (tel01 like '%s' or tel02 like '%s' ) ";
			$queryParam[] = "%{$tel}%";
			$queryParam[] = "%{$tel}";
		}
		if ($qq) {
			$sql .= " and (qq01 like '%s' or qq02 like '%s' ) ";
			$queryParam[] = "%{$qq}%";
			$queryParam[] = "%{$qq}";
		}
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::SUPPLIER, "t_supplier", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParam = array_merge($queryParam, $rs[1]);
		}
		$data = $db->query($sql, $queryParam);
		
		return [
				"supplierList" => $result,
				"totalCount" => $data[0]["cnt"]
		];
	}

	/**
	 * 新增供应商分类
	 *
	 * @param array $params        	
	 * @return NULL|array
	 */
	public function addSupplierCategory(& $params) {
		$db = $this->db;
		
		$code = trim($params["code"]);
		$name = trim($params["name"]);
		$parentId = $params["parentId"];
		
		$dataOrg = $params["dataOrg"];
		$companyId = $params["companyId"];
		if ($this->dataOrgNotExists($dataOrg)) {
			return $this->badParam("dataOrg");
		}
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		if ($this->isEmptyStringAfterTrim($code)) {
			return $this->bad("分类编码不能为空");
		}
		if ($this->isEmptyStringAfterTrim($name)) {
			return $this->bad("分类名称不能为空");
		}
		
		// 检查分类编码是否已经存在
		$sql = "select count(*) as cnt from t_supplier_category where code = '%s' ";
		$data = $db->query($sql, $code);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("编码为 [$code] 的分类已经存在");
		}
		
		// 检查上级分类
		$fullName = $name;
		if ($parentId) {
			$sql = "select full_name from t_supplier_category where id = '%s' ";
			$data = $db->query($sql, $parentId);
			if (! $data) {
				return $this->bad("上级分类不存在");
			}
			
			$fullName = $data[0]["full_name"] . "\\" . $name;
		}
		
		$id = $this->newId();
		$params["id"] = $id;
		
		$sql = "insert into t_supplier_category (id, code, name, data_org, company_id, full_name)
				values ('%s', '%s', '%s', '%s', '%s', '%s') ";
		$rc = $db->execute($sql, $id, $code, $name, $dataOrg, $companyId, $fullName);

		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		if ($parentId) {
			$sql = "update t_supplier_category
						set parent_id = '%s'
					where id = '%s' ";
			$rc = $db->execute($sql, $parentId, $id);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 操作成功
		return null;
	}

	/**
	 * 编辑供应商分类
	 *
	 * @param array $params        	
	 * @return NULL|array
	 */
	public function updateSupplierCategory(& $params) {
		$db = $this->db;
		
		$id = $params["id"];
		$code = trim($params["code"]);
		$name = trim($params["name"]);
		
		$parentId = $params["parentId"];
		
		if ($this->isEmptyStringAfterTrim($code)) {
			return $this->bad("分类编码不能为空");
		}
		if ($this->isEmptyStringAfterTrim($name)) {
			return $this->bad("分类名称不能为空");
		}
		
		// 检查分类编码是否已经存在
		$sql = "select count(*) as cnt from t_supplier_category where code = '%s' and id <> '%s' ";
		$data = $db->query($sql, $code, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("编码为 [$code] 的分类已经存在");
		}
		
		$fullName = $name;
		if ($parentId) {
			if ($parentId == $id) {
				return $this->bad("上级分类不能是自身");
			}
			
			// 检查上级分类是否存在
			$sql = "select parent_id, full_name from t_supplier_category where id = '%s' ";
			$data = $db->query($sql, $parentId);
			if (! $data) {
				return $this->bad("上级分类不存在");
			}
			$fullName = $data[0]["full_name"] . "\\" . $name;
			
			// 检查上级分类是否是循环引用了下级分类
			$pId = $data[0]["parent_id"];
			while ( $pId ) {
				if ($pId == $id) {
					return $this->bad("不能引用下级分类作为上级分类");
				}
				
				$sql = "select parent_id from t_supplier_category where id = '%s' ";
				$data = $db->query($sql, $pId);
				if ($data) {
					$pId = $data[0]["parent_id"];
				}
			}
		}
		
		if ($parentId) {
			$sql = "update t_supplier_category
				set code = '%s', name = '%s', full_name = '%s',
					parent_id = '%s'
				where id = '%s' ";
			$rc = $db->execute($sql, $code, $name, $fullName, $parentId, $id);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		} else {
			$sql = "update t_supplier_category
				set code = '%s', name = '%s', full_name = '%s',
					parent_id = null
				where id = '%s' ";
			$rc = $db->execute($sql, $code, $name, $fullName, $id);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 操作成功
		return null;
	}

	/**
	 * 根据供应商分类id查询供应商分类
	 *
	 * @param string $id        	
	 * @return array|NULL
	 */
	public function getSupplierCategoryById($id) {
		$db = $this->db;
		
		$sql = "select code, name from t_supplier_category where id = '%s' ";
		$data = $db->query($sql, $id);
		if ($data) {
			return array(
					"code" => $data[0]["code"],
					"name" => $data[0]["name"]
			);
		} else {
			return null;
		}
	}

	/**
	 * 删除供应商分类
	 *
	 * @param array $params        	
	 * @return NULL|array
	 */
	public function deleteSupplierCategory(& $params) {
		$db = $this->db;
		
		$id = $params["id"];
		
		$category = $this->getSupplierCategoryById($id);
		if (! $category) {
			return $this->bad("要删除的分类不存在");
		}
		
		$params["code"] = $category["code"];
		$params["name"] = $category["name"];
		$name = $params["name"];
		
		$sql = "select count(*) as cnt 
				from t_supplier 
				where category_id = '%s' ";
		$query = $db->query($sql, $id);
		$cnt = $query[0]["cnt"];
		if ($cnt > 0) {
			$db->rollback();
			return $this->bad("当前分类 [{$name}] 下还有供应商档案，不能删除");
		}
		
		$sql = "delete from t_supplier_category where id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 操作成功
		return null;
	}

	/**
	 * 新建供应商档案
	 *
	 * @param array $params        	
	 * @return NULL|array
	 */
	public function addSupplier(& $params) {
		$db = $this->db;
		
		$code = $params["code"];
		$name = $params["name"];
		$address = $params["address"];
		$addressShipping = $params["addressShipping"];
		$contact01 = $params["contact01"];
		$mobile01 = $params["mobile01"];
		$tel01 = $params["tel01"];
		$qq01 = $params["qq01"];
		$contact02 = $params["contact02"];
		$mobile02 = $params["mobile02"];
		$tel02 = $params["tel02"];
		$qq02 = $params["qq02"];
		$bankName = $params["bankName"];
		$bankAccount = $params["bankAccount"];
		$tax = $params["tax"];
		$fax = $params["fax"];
		$note = $params["note"];
		
		$taxRate = $params["taxRate"];
		if ($taxRate == "") {
			$taxRate = - 1;
		} else {
			$taxRate = intval($taxRate);
			if ($taxRate < 0 || $taxRate > 17) {
				$taxRate = - 1;
			}
		}
		
		$dataOrg = $params["dataOrg"];
		$companyId = $params["companyId"];
		if ($this->dataOrgNotExists($dataOrg)) {
			return $this->badParam("dataOrg");
		}
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		$categoryId = $params["categoryId"];
		$py = $params["py"];
		
		// 检查编码是否已经存在
		$sql = "select count(*) as cnt from t_supplier where code = '%s' ";
		$data = $db->query($sql, $code);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("编码为 [$code] 的供应商已经存在");
		}
		
		$id = $this->newId();
		$params["id"] = $id;
		
		$sql = "insert into t_supplier (id, category_id, code, name, py, contact01,
					qq01, tel01, mobile01, contact02, qq02,
					tel02, mobile02, address, address_shipping,
					bank_name, bank_account, tax_number, fax, note, data_org, company_id, tax_rate)
					values ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
							'%s', '%s', '%s', '%s',
							'%s', '%s', '%s', '%s', '%s', '%s', '%s', %d)  ";
		$rc = $db->execute($sql, $id, $categoryId, $code, $name, $py, $contact01, $qq01, $tel01, 
				$mobile01, $contact02, $qq02, $tel02, $mobile02, $address, $addressShipping, 
				$bankName, $bankAccount, $tax, $fax, $note, $dataOrg, $companyId, $taxRate);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 操作成功
		return null;
	}

	/**
	 * 初始化应付账款
	 *
	 * @param array $params        	
	 * @return NULL|array
	 */
	public function initPayables(& $params) {
		$db = $this->db;
		
		$id = $params["id"];
		$initPayables = $params["initPayables"];
		$initPayablesDT = $params["initPayablesDT"];
		
		$dataOrg = $params["dataOrg"];
		$companyId = $params["companyId"];
		if ($this->dataOrgNotExists($dataOrg)) {
			return $this->badParam("dataOrg");
		}
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		$sql = "select count(*) as cnt
				from t_payables_detail
				where ca_id = '%s' and ca_type = 'supplier' and ref_type <> '应付账款期初建账'
					and company_id = '%s' ";
		$data = $db->query($sql, $id, $companyId);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			// 已经有往来业务发生，就不能修改应付账了
			return null;
		}
		
		$initPayables = floatval($initPayables);
		if ($initPayables && $initPayablesDT) {
			$sql = "update t_supplier
					set init_payables = %f, init_payables_dt = '%s'
					where id = '%s' ";
			$rc = $db->execute($sql, $initPayables, $initPayablesDT, $id);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
			
			// 应付明细账
			$sql = "select id from t_payables_detail
					where ca_id = '%s' and ca_type = 'supplier' and ref_type = '应付账款期初建账'
						and company_id = '%s' ";
			$data = $db->query($sql, $id, $companyId);
			if ($data) {
				$payId = $data[0]["id"];
				$sql = "update t_payables_detail
						set pay_money = %f ,  balance_money = %f , biz_date = '%s', date_created = now(), act_money = 0
						where id = '%s' ";
				$rc = $db->execute($sql, $initPayables, $initPayables, $initPayablesDT, $payId);
				if ($rc === false) {
					return $this->sqlError(__METHOD__, __LINE__);
				}
			} else {
				$idGen = new IdGenDAO($db);
				$payId = $idGen->newId();
				$sql = "insert into t_payables_detail (id, pay_money, act_money, balance_money, ca_id,
						ca_type, ref_type, ref_number, biz_date, date_created, data_org, company_id)
						values ('%s', %f, 0, %f, '%s', 'supplier', '应付账款期初建账', '%s', '%s', now(), '%s', '%s') ";
				$rc = $db->execute($sql, $payId, $initPayables, $initPayables, $id, $id, 
						$initPayablesDT, $dataOrg, $companyId);
				if ($rc === false) {
					return $this->sqlError(__METHOD__, __LINE__);
				}
			}
			
			// 应付总账
			$sql = "select id from t_payables
					where ca_id = '%s' and ca_type = 'supplier'
						and company_id = '%s' ";
			$data = $db->query($sql, $id, $companyId);
			if ($data) {
				$pId = $data[0]["id"];
				$sql = "update t_payables
						set pay_money = %f ,  balance_money = %f , act_money = 0
						where id = '%s' ";
				$rc = $db->execute($sql, $initPayables, $initPayables, $pId);
				if ($rc === false) {
					return $this->sqlError(__METHOD__, __LINE__);
				}
			} else {
				$idGen = new IdGenDAO($db);
				$pId = $idGen->newId();
				$sql = "insert into t_payables (id, pay_money, act_money, balance_money, ca_id,
							ca_type, data_org, company_id)
						values ('%s', %f, 0, %f, '%s', 'supplier', '%s', '%s') ";
				$rc = $db->execute($sql, $pId, $initPayables, $initPayables, $id, $dataOrg, 
						$companyId);
				if ($rc === false) {
					return $this->sqlError(__METHOD__, __LINE__);
				}
			}
		} else {
			// 清除应付账款初始化数据
			$sql = "update t_supplier
					set init_payables = null, init_payables_dt = null
					where id = '%s' ";
			$rc = $db->execute($sql, $id);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
			
			// 明细账
			$sql = "delete from t_payables_detail
					where ca_id = '%s' and ca_type = 'supplier' and ref_type = '应付账款期初建账'
						and company_id = '%s' ";
			$rc = $db->execute($sql, $id, $companyId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
			
			// 总账
			$sql = "delete from t_payables
					where ca_id = '%s' and ca_type = 'supplier'
						and company_id = '%s' ";
			$rc = $db->execute($sql, $id, $companyId);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 操作成功
		return null;
	}

	/**
	 * 编辑供应商档案
	 *
	 * @param array $params        	
	 * @return NULL|array
	 */
	public function updateSupplier(& $params) {
		$db = $this->db;
		
		$id = $params["id"];
		$code = $params["code"];
		$name = $params["name"];
		$address = $params["address"];
		$addressShipping = $params["addressShipping"];
		$contact01 = $params["contact01"];
		$mobile01 = $params["mobile01"];
		$tel01 = $params["tel01"];
		$qq01 = $params["qq01"];
		$contact02 = $params["contact02"];
		$mobile02 = $params["mobile02"];
		$tel02 = $params["tel02"];
		$qq02 = $params["qq02"];
		$bankName = $params["bankName"];
		$bankAccount = $params["bankAccount"];
		$tax = $params["tax"];
		$fax = $params["fax"];
		$note = $params["note"];
		
		$taxRate = $params["taxRate"];
		if ($taxRate == "") {
			$taxRate = - 1;
		} else {
			$taxRate = intval($taxRate);
			if ($taxRate < 0 || $taxRate > 17) {
				$taxRate = - 1;
			}
		}
		
		$categoryId = $params["categoryId"];
		
		// 检查编码是否已经存在
		$sql = "select count(*) as cnt from t_supplier where code = '%s'  and id <> '%s' ";
		$data = $db->query($sql, $code, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("编码为 [$code] 的供应商已经存在");
		}
		
		$sql = "update t_supplier
				set code = '%s', name = '%s', category_id = '%s', py = '%s',
				contact01 = '%s', qq01 = '%s', tel01 = '%s', mobile01 = '%s',
				contact02 = '%s', qq02 = '%s', tel02 = '%s', mobile02 = '%s',
				address = '%s', address_shipping = '%s',
				bank_name = '%s', bank_account = '%s', tax_number = '%s',
				fax = '%s', note = '%s', tax_rate = %d
				where id = '%s'  ";
		
		$rc = $db->execute($sql, $code, $name, $categoryId, $py, $contact01, $qq01, $tel01, 
				$mobile01, $contact02, $qq02, $tel02, $mobile02, $address, $addressShipping, 
				$bankName, $bankAccount, $tax, $fax, $note, $taxRate, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 操作成功
		return null;
	}

	/**
	 * 删除供应商
	 *
	 * @param array $params        	
	 * @return NULL|array
	 */
	public function deleteSupplier(& $params) {
		$db = $this->db;
		
		$id = $params["id"];
		
		$supplier = $this->getSupplierById($id);
		
		if (! $supplier) {
			$db->rollback();
			return $this->bad("要删除的供应商档案不存在");
		}
		$code = $supplier["code"];
		$name = $supplier["name"];
		
		// 判断是否能删除供应商
		$sql = "select count(*) as cnt from t_pw_bill where supplier_id = '%s' ";
		$data = $db->query($sql, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("供应商档案 [{$code} {$name}] 在采购入库单中已经被使用，不能删除");
		}
		$sql = "select count(*) as cnt
				from t_payables_detail p, t_payment m
				where p.ref_type = m.ref_type and p.ref_number = m.ref_number
				and p.ca_id = '%s' and p.ca_type = 'supplier' ";
		$data = $db->query($sql, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("供应商档案 [{$code} {$name}] 已经产生付款记录，不能删除");
		}
		
		// 判断采购退货出库单中是否使用该供应商
		$sql = "select count(*) as cnt from t_pr_bill where supplier_id = '%s' ";
		$data = $db->query($sql, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("供应商档案 [{$code} {$name}] 在采购退货出库单中已经被使用，不能删除");
		}
		
		// 判断在采购订单中是否已经使用该供应商
		$sql = "select count(*) as cnt from t_po_bill where supplier_id = '%s' ";
		$data = $db->query($sql, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("供应商档案 [{$code} {$name}] 在采购订单中已经被使用，不能删除");
		}
		
		$sql = "delete from t_supplier where id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 删除应付总账
		$sql = "delete from t_payables where ca_id = '%s' and ca_type = 'supplier' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 删除应付明细账
		$sql = "delete from t_payables_detail where ca_id = '%s' and ca_type = 'supplier' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$params["code"] = $code;
		$params["name"] = $name;
		
		// 操作成功
		return null;
	}

	/**
	 * 通过供应商id查询供应商
	 *
	 * @param string $id
	 *        	供应商id
	 * @return array|NULL
	 */
	public function getSupplierById($id) {
		$db = $this->db;
		
		$sql = "select code, name from t_supplier where id = '%s' ";
		$data = $db->query($sql, $id);
		if ($data) {
			return array(
					"code" => $data[0]["code"],
					"name" => $data[0]["name"]
			);
		} else {
			return null;
		}
	}

	/**
	 * 供应商字段， 查询数据
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function queryData($params) {
		$db = $this->db;
		
		$queryKey = $params["queryKey"];
		$loginUserId = $params["loginUserId"];
		
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->emptyResult();
		}
		
		if ($queryKey == null) {
			$queryKey = "";
		}
		
		$sql = "select id, code, name, tel01, fax, address_shipping, contact01, tax_rate
				from t_supplier
				where (code like '%s' or name like '%s' or py like '%s') ";
		$queryParams = array();
		$key = "%{$queryKey}%";
		$queryParams[] = $key;
		$queryParams[] = $key;
		$queryParams[] = $key;
		
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::SUPPLIER_BILL, "t_supplier", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParams = array_merge($queryParams, $rs[1]);
		}
		
		$sql .= " order by code
				limit 20";
		$data = $db->query($sql, $queryParams);
		
		$result = [];
		
		foreach ( $data as $v ) {
			$taxRate = $v["tax_rate"];
			if ($taxRate == - 1) {
				$taxRate = null;
			}
			
			$result[] = [
					"id" => $v["id"],
					"code" => $v["code"],
					"name" => $v["name"],
					"tel01" => $v["tel01"],
					"fax" => $v["fax"],
					"address_shipping" => $v["address_shipping"],
					"contact01" => $v["contact01"],
					"taxRate" => $taxRate
			];
		}
		
		return $result;
	}

	/**
	 * 获得某个供应商档案的详情
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function supplierInfo($params) {
		$db = $this->db;
		
		$id = $params["id"];
		
		$result = array();
		
		$sql = "select category_id, code, name, contact01, qq01, mobile01, tel01,
					contact02, qq02, mobile02, tel02, address, address_shipping,
					init_payables, init_payables_dt,
					bank_name, bank_account, tax_number, fax, note, tax_rate
				from t_supplier
				where id = '%s' ";
		$data = $db->query($sql, $id);
		if ($data) {
			$result["categoryId"] = $data[0]["category_id"];
			$sql = "select full_name from t_supplier_category where id = '%s' ";
			$d = $db->query($sql, $data[0]["category_id"]);
			if ($d) {
				$result["categoryName"] = $d[0]["full_name"];
			}
			$result["code"] = $data[0]["code"];
			$result["name"] = $data[0]["name"];
			$result["contact01"] = $data[0]["contact01"];
			$result["qq01"] = $data[0]["qq01"];
			$result["mobile01"] = $data[0]["mobile01"];
			$result["tel01"] = $data[0]["tel01"];
			$result["contact02"] = $data[0]["contact02"];
			$result["qq02"] = $data[0]["qq02"];
			$result["mobile02"] = $data[0]["mobile02"];
			$result["tel02"] = $data[0]["tel02"];
			$result["address"] = $data[0]["address"];
			$result["addressShipping"] = $data[0]["address_shipping"];
			$result["initPayables"] = $data[0]["init_payables"];
			$d = $data[0]["init_payables_dt"];
			if ($d) {
				$result["initPayablesDT"] = $this->toYMD($d);
			}
			$result["bankName"] = $data[0]["bank_name"];
			$result["bankAccount"] = $data[0]["bank_account"];
			$result["tax"] = $data[0]["tax_number"];
			$result["fax"] = $data[0]["fax"];
			$result["note"] = $data[0]["note"];
			
			$taxRate = $data[0]["tax_rate"];
			if ($taxRate == - 1) {
				$taxRate = null;
			}
			$result["taxRate"] = $taxRate;
		}
		
		return $result;
	}

	private function allParentCategoriesInternal($id, $rs) {
		$db = $this->db;
		
		$queryParam = [];
		$sql = "select c.id, c.code, c.name, c.full_name
				from t_supplier_category c
				where (c.parent_id = '%s') ";
		$queryParam[] = $id;
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParam = array_merge($queryParam, $rs[1]);
		}
		$sql .= " order by code";
		$data = $db->query($sql, $queryParam);
		
		$result = [];
		foreach ( $data as $v ) {
			$id = $v["id"];
			
			// 递归调用自己
			$children = $this->allParentCategoriesInternal($id, $rs);
			
			$result[] = [
					"id" => $v["id"],
					"code" => $v["code"],
					"name" => $v["name"],
					"fullName" => $v["full_name"],
					"children" => $children,
					"leaf" => count($children) == 0,
					"expanded" => true,
					"iconCls" => "PSI-SupplierCategory"
			];
		}
		
		return $result;
	}

	/**
	 * 所有的上级供应商分类
	 *
	 * @param array $params        	
	 */
	public function allParentCategories($params) {
		$loginUserId = $params["loginUserId"];
		
		$db = $this->db;
		
		$queryParam = [];
		$sql = "select c.id, c.code, c.name, c.full_name 
				from t_supplier_category c
				where (c.parent_id is null) ";
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::SUPPLIER_CATEGORY, "c", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParam = array_merge($queryParam, $rs[1]);
		}
		$sql .= " order by code";
		
		$data = $db->query($sql, $queryParam);
		$result = [];
		foreach ( $data as $v ) {
			$id = $v["id"];
			$children = $this->allParentCategoriesInternal($id, $rs);
			
			$result[] = [
					"id" => $v["id"],
					"code" => $v["code"],
					"name" => $v["name"],
					"fullName" => $v["full_name"],
					"children" => $children,
					"leaf" => count($children) == 0,
					"expanded" => true,
					"iconCls" => "PSI-SupplierCategory"
			];
		}
		
		return $result;
	}

	/**
	 * 某个供应商分类的详情
	 *
	 * @param array $params        	
	 */
	public function categoryInfo($params) {
		$db = $this->db;
		
		$id = $params["id"];
		
		$result = [];
		$sql = "select code, name, parent_id
				from t_supplier_category
				where id = '%s' ";
		$data = $db->query($sql, $id);
		if ($data) {
			$v = $data[0];
			$result["code"] = $v["code"];
			$result["name"] = $v["name"];
			$result["parentId"] = $parentId;
			$result["parentName"] = "";
			
			$parentId = $v["parent_id"];
			if ($parentId) {
				$sql = "select full_name 
						from t_supplier_category
						where id = '%s' ";
				$data = $db->query($sql, $parentId);
				if ($data) {
					$result["parentName"] = $data[0]["full_name"];
				}
			}
		}
		
		return $result;
	}
}