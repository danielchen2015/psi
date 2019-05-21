<?php

namespace Home\DAO;

use Home\Common\FIdConst;

/**
 * 物资DAO
 *
 * @author 李静波
 */
class GoodsDAO extends PSIBaseExDAO {

	/**
	 * 物资列表
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function goodsList($params) {
		$db = $this->db;
		
		$categoryId = $params["categoryId"];
		$code = $params["code"];
		$name = $params["name"];
		$spec = $params["spec"];
		$barCode = $params["barCode"];
		
		$start = $params["start"];
		$limit = $params["limit"];
		
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->emptyResult();
		}
		
		$result = [];
		$sql = "select g.id, g.code, g.name, g.spec, u.name as unit_name,
					g.purchase_price_upper, g.cost_price_checkups, g.bar_code, g.memo, g.data_org, g.brand_id,
					g.py, g.ABC_category as abc, g.use_qc, g.qc_days
				from t_goods g, t_goods_unit u
				where (g.unit_id = u.id) and (g.category_id = '%s') ";
		$queryParam = [];
		$queryParam[] = $categoryId;
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::GOODS, "g", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParam = array_merge($queryParam, $rs[1]);
		}
		
		if ($code) {
			$sql .= " and (g.code like '%s') ";
			$queryParam[] = "%{$code}%";
		}
		if ($name) {
			$sql .= " and (g.name like '%s' or g.py like '%s') ";
			$queryParam[] = "%{$name}%";
			$queryParam[] = "%{$name}%";
		}
		if ($spec) {
			$sql .= " and (g.spec like '%s')";
			$queryParam[] = "%{$spec}%";
		}
		if ($barCode) {
			$sql .= " and (g.bar_code = '%s') ";
			$queryParam[] = $barCode;
		}
		
		$sql .= " order by g.code limit %d, %d";
		$queryParam[] = $start;
		$queryParam[] = $limit;
		$data = $db->query($sql, $queryParam);
		
		foreach ( $data as $v ) {
			$brandId = $v["brand_id"];
			$brandFullName = $brandId ? $this->getBrandFullNameById($db, $brandId) : null;
			
			$result[] = [
					"id" => $v["id"],
					"code" => $v["code"],
					"name" => $v["name"],
					"costPriceCheckups" => $v["cost_price_checkups"],
					"spec" => $v["spec"],
					"unitName" => $v["unit_name"],
					"purchasePriceUpper" => $v["purchase_price_upper"],
					"barCode" => $v["bar_code"],
					"memo" => $v["memo"],
					"dataOrg" => $v["data_org"],
					"brandFullName" => $brandFullName,
					"py" => $v["py"],
					"ABC" => $v["abc"],
					"useQC" => $v["use_qc"] == 1 ? "启用" : "不启用",
					"qcDays" => $v["qc_days"]
			];
		}
		
		$sql = "select count(*) as cnt from t_goods g where (g.category_id = '%s') ";
		$queryParam = [];
		$queryParam[] = $categoryId;
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::GOODS, "g", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParam = array_merge($queryParam, $rs[1]);
		}
		if ($code) {
			$sql .= " and (g.code like '%s') ";
			$queryParam[] = "%{$code}%";
		}
		if ($name) {
			$sql .= " and (g.name like '%s' or g.py like '%s') ";
			$queryParam[] = "%{$name}%";
			$queryParam[] = "%{$name}%";
		}
		if ($spec) {
			$sql .= " and (g.spec like '%s')";
			$queryParam[] = "%{$spec}%";
		}
		if ($barCode) {
			$sql .= " and (g.bar_code = '%s') ";
			$queryParam[] = $barCode;
		}
		
		$data = $db->query($sql, $queryParam);
		$totalCount = $data[0]["cnt"];
		
		return [
				"goodsList" => $result,
				"totalCount" => $totalCount
		];
	}

	private function getBrandFullNameById($db, $brandId) {
		$sql = "select full_name from t_goods_brand where id = '%s' ";
		$data = $db->query($sql, $brandId);
		if ($data) {
			return $data[0]["full_name"];
		} else {
			return null;
		}
	}

	/**
	 * 新增商品
	 *
	 * @param array $params        	
	 * @return NULL|array
	 */
	public function addGoods(& $params) {
		$db = $this->db;
		
		$code = $params["code"];
		$name = $params["name"];
		$py = $params["py"];
		$spec = $params["spec"];
		$categoryId = $params["categoryId"];
		$unitId = $params["unitId"];
		$abc = $params["abc"];
		$barCode = $params["barCode"];
		$memo = $params["memo"];
		
		$unitGroupObj = json_decode(html_entity_decode($params["unitGroup"]), true);
		if ($unitGroupObj == null) {
			return $this->bad("单位组传入的参数错误，不是正确的JSON格式");
		}
		$unitGroup = $unitGroupObj["items"];
		
		$costPriceCheckups = $params["costPriceCheckups"];
		$purchasePriceUpper = $params["purchasePriceUpper"];
		$brandId = $params["brandId"];
		$useQc = $params["useQc"];
		$qcDays = $params["qcDays"];
		
		$dataOrg = $params["dataOrg"];
		$companyId = $params["companyId"];
		if ($this->dataOrgNotExists($dataOrg)) {
			return $this->badParam("dataOrg");
		}
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		$goodsUnitDAO = new GoodsUnitDAO($db);
		$unit = $goodsUnitDAO->getGoodsUnitById($unitId);
		if (! $unit) {
			return $this->bad("基本单位不存在");
		}
		
		$goodsCategoryDAO = new GoodsCategoryDAO($db);
		$category = $goodsCategoryDAO->getGoodsCategoryById($categoryId);
		if (! $category) {
			return $this->bad("物资分类不存在");
		}
		
		// 检查品牌
		if ($brandId) {
			$brandDAO = new GoodsBrandDAO($db);
			$brand = $brandDAO->getBrandById($brandId);
			if (! $brand) {
				return $this->bad("品牌不存在");
			}
		}
		
		// 检查物资编码是否唯一
		$sql = "select count(*) as cnt from t_goods where code = '%s' ";
		$data = $db->query($sql, $code);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("编码为 [{$code}]的物资已经存在");
		}
		
		// 如果录入了条形码，则需要检查条形码是否唯一
		if ($barCode) {
			$sql = "select count(*) as cnt from t_goods where bar_code = '%s' ";
			$data = $db->query($sql, $barCode);
			$cnt = $data[0]["cnt"];
			if ($cnt != 0) {
				return $this->bad("条形码[{$barCode}]已经被其他物资使用");
			}
		}
		
		$id = $this->newId();
		$sql = "insert into t_goods (id, code, name, spec, category_id, unit_id, cost_price_checkups,
					py, purchase_price_upper, bar_code, memo, data_org, company_id, brand_id,
					ABC_category, use_qc, qc_days)
				values ('%s', '%s', '%s', '%s', '%s', '%s', %f, 
					'%s', %f, '%s', '%s', '%s', '%s', if('%s' = '', null, '%s'),
					'%s', %d, %d)";
		$rc = $db->execute($sql, $id, $code, $name, $spec, $categoryId, $unitId, $costPriceCheckups, 
				$py, $purchasePriceUpper, $barCode, $memo, $dataOrg, $companyId, $brandId, $brandId, 
				$abc, $useQc, $qcDays);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 单位组
		foreach ( $unitGroup as $v ) {
			$unitId = $v["id"];
			$factor = $v["factor"];
			$factorType = $v["factorType"];
			
			$sql = "insert into t_goods_unit_group (id, goods_id, unit_id, factor, factor_type)
					values ('%s', '%s', '%s', %f, %d)";
			$rc = $db->execute($sql, $this->newId(), $id, $unitId, $factor, $factorType);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		$params["id"] = $id;
		
		// 操作成功
		return null;
	}

	/**
	 * 编辑商品
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function updateGoods(& $params) {
		$db = $this->db;
		
		$id = $params["id"];
		$code = $params["code"];
		$name = $params["name"];
		$py = $params["py"];
		$spec = $params["spec"];
		$categoryId = $params["categoryId"];
		$unitId = $params["unitId"];
		$abc = $params["abc"];
		$barCode = $params["barCode"];
		$memo = $params["memo"];
		
		$costPriceCheckups = $params["costPriceCheckups"];
		$purchasePriceUpper = $params["purchasePriceUpper"];
		$brandId = $params["brandId"];
		$useQc = $params["useQc"];
		$qcDays = $params["qcDays"];
		
		$unitGroupObj = json_decode(html_entity_decode($params["unitGroup"]), true);
		if ($unitGroupObj == null) {
			return $this->bad("单位组传入的参数错误，不是正确的JSON格式");
		}
		$unitGroup = $unitGroupObj["items"];
		
		$goods = $this->getGoodsById($id);
		if (! $goods) {
			return $this->bad("要编辑的商品不存在");
		}
		
		$goodsUnitDAO = new GoodsUnitDAO($db);
		$unit = $goodsUnitDAO->getGoodsUnitById($unitId);
		if (! $unit) {
			return $this->bad("基本单位不存在");
		}
		
		$goodsCategoryDAO = new GoodsCategoryDAO($db);
		$category = $goodsCategoryDAO->getGoodsCategoryById($categoryId);
		if (! $category) {
			return $this->bad("商品分类不存在");
		}
		
		// 检查商品品牌
		if ($brandId) {
			$brandDAO = new GoodsBrandDAO($db);
			$brand = $brandDAO->getBrandById($brandId);
			if (! $brand) {
				return $this->bad("商品品牌不存在");
			}
		}
		
		// 检查商品编码是否唯一
		$sql = "select count(*) as cnt from t_goods where code = '%s' and id <> '%s' ";
		$data = $db->query($sql, $code, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("编码为 [{$code}]的商品已经存在");
		}
		
		// 如果录入了条形码，则需要检查条形码是否唯一
		if ($barCode) {
			$sql = "select count(*) as cnt from t_goods where bar_code = '%s' and id <> '%s' ";
			$data = $db->query($sql, $barCode, $id);
			$cnt = $data[0]["cnt"];
			if ($cnt != 0) {
				return $this->bad("条形码[{$barCode}]已经被其他商品使用");
			}
		}
		
		$sql = "update t_goods
				set code = '%s', name = '%s', py = '%s', spec = '%s', category_id = '%s',
				    unit_id = '%s',
					ABC_category = '%s', bar_code = '%s', memo = '%s',
					cost_price_checkups = %f, purchase_price_upper = %f,
					brand_id = if('%s' = '', null, '%s'), use_qc = %d, qc_days = %d
				where id = '%s' ";
		
		$rc = $db->execute($sql, $code, $name, $py, $spec, $categoryId, $unitId, $abc, $barCode, 
				$memo, $costPriceCheckups, $purchasePriceUpper, $brandId, $brandId, $useQc, $qcDays, 
				$id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 单位组
		$sql = "delete from t_goods_unit_group where goods_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		foreach ( $unitGroup as $v ) {
			$unitId = $v["id"];
			$factor = $v["factor"];
			$factorType = $v["factorType"];
			
			$sql = "insert into t_goods_unit_group (id, goods_id, unit_id, factor, factor_type)
					values ('%s', '%s', '%s', %f, %d)";
			$rc = $db->execute($sql, $this->newId(), $id, $unitId, $factor, $factorType);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		// 操作成功
		return null;
	}

	/**
	 * 通过商品id查询商品
	 *
	 * @param string $id        	
	 * @return array|NULL
	 */
	public function getGoodsById($id) {
		$db = $this->db;
		
		$sql = "select code, name, spec from t_goods where id = '%s' ";
		$data = $db->query($sql, $id);
		if ($data) {
			return array(
					"code" => $data[0]["code"],
					"name" => $data[0]["name"],
					"spec" => $data[0]["spec"]
			);
		} else {
			return null;
		}
	}

	/**
	 * 删除商品
	 *
	 * @param array $params        	
	 * @return NULL|array
	 */
	public function deleteGoods(& $params) {
		$db = $this->db;
		
		$id = $params["id"];
		
		$goods = $this->getGoodsById($id);
		if (! $goods) {
			return $this->bad("要删除的商品不存在");
		}
		$code = $goods["code"];
		$name = $goods["name"];
		$spec = $goods["spec"];
		
		// 判断商品是否能删除
		$sql = "select count(*) as cnt from t_pc_bill_detail where goods_id = '%s' ";
		$data = $db->query($sql, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("商品[{$code} {$name}]已经在采购合同中使用了，不能删除");
		}
		
		$sql = "select count(*) as cnt from t_po_bill_detail where goods_id = '%s' ";
		$data = $db->query($sql, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("商品[{$code} {$name}]已经在采购订单中使用了，不能删除");
		}
		
		$sql = "select count(*) as cnt from t_pw_bill_detail where goods_id = '%s' ";
		$data = $db->query($sql, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("商品[{$code} {$name}]已经在采购入库单中使用了，不能删除");
		}
		
		$sql = "select count(*) as cnt from t_inventory_detail where goods_id = '%s' ";
		$data = $db->query($sql, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("商品[{$code} {$name}]在业务中已经使用了，不能删除");
		}
		
		$sql = "delete from t_goods where id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 删除单位组
		$sql = "delete from t_goods_unit_group where goods_id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$params["code"] = $code;
		$params["name"] = $name;
		$params["spec"] = $spec;
		
		// 操作成功
		return null;
	}

	/**
	 * 商品字段，查询数据
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
		
		$key = "%{$queryKey}%";
		
		$sql = "select g.id, g.code, g.name, g.spec, u.name as unit_name, g.use_qc, g.qc_days
				from t_goods g, t_goods_unit u
				where (g.unit_id = u.id)
				and (g.code like '%s' or g.name like '%s' or g.py like '%s'
					or g.spec like '%s') ";
		$queryParams = [];
		$queryParams[] = $key;
		$queryParams[] = $key;
		$queryParams[] = $key;
		$queryParams[] = $key;
		
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::GOODS_BILL, "g", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParams = array_merge($queryParams, $rs[1]);
		}
		
		$sql .= " order by g.code
				limit 20";
		$data = $db->query($sql, $queryParams);
		$result = [];
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"code" => $v["code"],
					"name" => $v["name"],
					"spec" => $v["spec"],
					"unitName" => $v["unit_name"],
					"useQC" => $v["use_qc"],
					"qcDays" => $v["qc_days"]
			];
		}
		
		return $result;
	}

	private function getPsIdForCustomer($customerId) {
		$result = null;
		$db = $this->db;
		$sql = "select c.ps_id
				from t_customer_category c, t_customer u
				where c.id = u.category_id and u.id = '%s' ";
		$data = $db->query($sql, $customerId);
		if ($data) {
			$result = $data[0]["ps_id"];
		}
		
		return $result;
	}

	/**
	 * 商品字段，查询数据
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function queryDataWithSalePrice($params) {
		$db = $this->db;
		
		$queryKey = $params["queryKey"];
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->emptyResult();
		}
		
		$customerId = $params["customerId"];
		$psId = $this->getPsIdForCustomer($customerId);
		
		if ($queryKey == null) {
			$queryKey = "";
		}
		
		$key = "%{$queryKey}%";
		
		$sql = "select g.id, g.code, g.name, g.spec, u.name as unit_name, g.sale_price, g.memo
				from t_goods g, t_goods_unit u
				where (g.unit_id = u.id)
				and (g.code like '%s' or g.name like '%s' or g.py like '%s'
					or g.spec like '%s' or g.spec_py like '%s') ";
		
		$queryParams = [];
		$queryParams[] = $key;
		$queryParams[] = $key;
		$queryParams[] = $key;
		$queryParams[] = $key;
		$queryParams[] = $key;
		
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::GOODS_BILL, "g", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParams = array_merge($queryParams, $rs[1]);
		}
		
		$sql .= " order by g.code
				limit 20";
		$data = $db->query($sql, $queryParams);
		$result = [];
		foreach ( $data as $v ) {
			$priceSystem = "";
			
			$price = $v["sale_price"];
			
			if ($psId) {
				// 取价格体系里面的价格
				$goodsId = $v["id"];
				$sql = "select g.price, p.name
						from t_goods_price g, t_price_system p
						where g.goods_id = '%s' and g.ps_id = '%s'
							and g.ps_id = p.id";
				$d = $db->query($sql, $goodsId, $psId);
				if ($d) {
					$priceSystem = $d[0]["name"];
					$price = $d[0]["price"];
				}
			}
			
			$result[] = [
					"id" => $v["id"],
					"code" => $v["code"],
					"name" => $v["name"],
					"spec" => $v["spec"],
					"unitName" => $v["unit_name"],
					"salePrice" => $price,
					"priceSystem" => $priceSystem,
					"memo" => $v["memo"]
			];
		}
		
		return $result;
	}

	/**
	 * 商品字段，查询数据
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function queryDataWithPurchasePrice($params) {
		$db = $this->db;
		
		$queryKey = $params["queryKey"];
		$billType = $params["billType"];
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->emptyResult();
		}
		
		if ($queryKey == null) {
			$queryKey = "";
		}
		
		$key = "%{$queryKey}%";
		
		$sql = "select g.id, g.code, g.name, g.spec, u.name as unit_name, 
					g.purchase_price_upper, g.memo, u.id as unit_id,
					g.use_qc, g.qc_days, u.id as sku_unit_id, u.name as sku_unit_name
				from t_goods g, t_goods_unit u
				where (g.unit_id = u.id)
				and (g.code like '%s' or g.name like '%s' or g.py like '%s'
					or g.spec like '%s') ";
		
		$queryParams = [];
		$queryParams[] = $key;
		$queryParams[] = $key;
		$queryParams[] = $key;
		$queryParams[] = $key;
		
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::GOODS_BILL, "g", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParams = array_merge($queryParams, $rs[1]);
		}
		
		$sql .= " order by g.code
				limit 20";
		$data = $db->query($sql, $queryParams);
		$result = [];
		foreach ( $data as $v ) {
			// 查询上次使用的计量单位
			$goodsId = $v["id"];
			$unitId = $v["unit_id"];
			$unitName = $v["unit_name"];
			$factor = 1;
			$factorType = 0;
			
			$sql = "select u.id, u.name
					from t_goods_unit_default d, t_goods_unit u
					where d.goods_id = '%s' and d.unit_id = u.id and d.bill_type = '%s' ";
			$d = $db->query($sql, $goodsId, $billType);
			if ($d) {
				$unitId = $d[0]["id"];
				$unitName = $d[0]["name"];
			}
			
			if ($unitId != $v["sku_unit_id"]) {
				// 查询转换率
				$sql = "select factor, factor_type 
						from t_goods_unit_group
						where goods_id = '%s' and unit_id = '%s' ";
				$d = $db->query($sql, $goodsId, $unitId);
				if ($d) {
					$factor = $d[0]["factor"];
					$factorType = $d[0]["factor_type"];
				}
			}
			
			$result[] = [
					"id" => $v["id"],
					"code" => $v["code"],
					"name" => $v["name"],
					"spec" => $v["spec"],
					"unitId" => $unitId,
					"unitName" => $unitName,
					"purchasePrice" => $v["purchase_price_upper"] == 0 ? null : $v["purchase_price_upper"],
					"memo" => $v["memo"],
					"useQC" => $v["use_qc"],
					"qcDays" => $this->toQcDays($v["qc_days"]),
					"skuUnitId" => $v["sku_unit_id"],
					"skuUnitName" => $v["sku_unit_name"],
					"factor" => $factor,
					"factorType" => $factorType
			];
		}
		
		return $result;
	}

	/**
	 * 获得某个商品的详情
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function getGoodsInfo($params) {
		$db = $this->db;
		
		$id = $params["id"];
		$categoryId = $params["categoryId"];
		
		$sql = "select category_id, code, name, py, spec, unit_id,
					ABC_category as abc,
					bar_code, memo, brand_id,
					cost_price_checkups, purchase_price_upper,
					use_qc, qc_days
				from t_goods
				where id = '%s' ";
		$data = $db->query($sql, $id);
		if ($data) {
			$result = [];
			$v = $data[0];
			$categoryId = $v["category_id"];
			$result["categoryId"] = $categoryId;
			
			$result["code"] = $v["code"];
			$result["name"] = $v["name"];
			$result["py"] = $v["py"];
			$result["spec"] = $v["spec"];
			
			$result["unitId"] = $v["unit_id"];
			$result["ABC"] = $v["abc"];
			$result["barCode"] = $v["bar_code"];
			$result["memo"] = $v["memo"];
			
			$result["salePrice"] = $v["sale_price"];
			$brandId = $v["brand_id"];
			$result["brandId"] = $brandId;
			
			$price = $v["purchase_price_upper"];
			if ($price == 0) {
				$result["purchasePriceUpper"] = null;
			} else {
				$result["purchasePriceUpper"] = $price;
			}
			
			$price = $v["cost_price_checkups"];
			if ($price == 0) {
				$result["costPriceCheckups"] = null;
			} else {
				$result["costPriceCheckups"] = $price;
			}
			
			$result["useQc"] = $v["use_qc"];
			$result["qcDays"] = $v["qc_days"];
			
			$sql = "select full_name from t_goods_category where id = '%s' ";
			$data = $db->query($sql, $categoryId);
			if ($data) {
				$result["categoryName"] = $data[0]["full_name"];
			}
			
			if ($brandId) {
				$sql = "select full_name from t_goods_brand where id = '%s' ";
				$data = $db->query($sql, $brandId);
				$result["brandFullName"] = $data[0]["full_name"];
			}
			
			return $result;
		} else {
			$result = [];
			
			$sql = "select full_name from t_goods_category where id = '%s' ";
			$data = $db->query($sql, $categoryId);
			if ($data) {
				$result["categoryId"] = $categoryId;
				$result["categoryName"] = $data[0]["full_name"];
			}
			return $result;
		}
	}

	/**
	 * 通过条形码查询商品信息, 销售出库单使用
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function queryGoodsInfoByBarcode($params) {
		$db = $this->db;
		
		$barcode = $params["barcode"];
		
		$result = array();
		
		$sql = "select g.id, g.code, g.name, g.spec, g.sale_price, u.name as unit_name
				from t_goods g, t_goods_unit u
				where g.bar_code = '%s' and g.unit_id = u.id ";
		$data = $db->query($sql, $barcode);
		
		if (! $data) {
			$result["success"] = false;
			$result["msg"] = "条码为[{$barcode}]的商品不存在";
		} else {
			$result["success"] = true;
			$result["id"] = $data[0]["id"];
			$result["code"] = $data[0]["code"];
			$result["name"] = $data[0]["name"];
			$result["spec"] = $data[0]["spec"];
			$result["salePrice"] = $data[0]["sale_price"];
			$result["unitName"] = $data[0]["unit_name"];
		}
		
		return $result;
	}

	/**
	 * 通过条形码查询商品信息, 采购入库单使用
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function queryGoodsInfoByBarcodeForPW($params) {
		$db = $this->db;
		
		$barcode = $params["barcode"];
		
		$result = array();
		
		$sql = "select g.id, g.code, g.name, g.spec, g.purchase_price, u.name as unit_name
				from t_goods g, t_goods_unit u
				where g.bar_code = '%s' and g.unit_id = u.id ";
		$data = $db->query($sql, $barcode);
		
		if (! $data) {
			$result["success"] = false;
			$result["msg"] = "条码为[{$barcode}]的商品不存在";
		} else {
			$result["success"] = true;
			$result["id"] = $data[0]["id"];
			$result["code"] = $data[0]["code"];
			$result["name"] = $data[0]["name"];
			$result["spec"] = $data[0]["spec"];
			$result["purchasePrice"] = $data[0]["purchase_price"];
			$result["unitName"] = $data[0]["unit_name"];
		}
		
		return $result;
	}

	/**
	 * 查询商品种类总数
	 *
	 * @param array $params        	
	 * @return int
	 */
	public function getTotalGoodsCount($params) {
		$db = $this->db;
		
		$code = $params["code"];
		$name = $params["name"];
		$spec = $params["spec"];
		$barCode = $params["barCode"];
		
		$loginUserId = $params["loginUserId"];
		
		$sql = "select count(*) as cnt
					from t_goods c
					where (1 = 1) ";
		$queryParam = array();
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::GOODS, "c", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParam = array_merge($queryParam, $rs[1]);
		}
		if ($code) {
			$sql .= " and (c.code like '%s') ";
			$queryParam[] = "%{$code}%";
		}
		if ($name) {
			$sql .= " and (c.name like '%s' or c.py like '%s') ";
			$queryParam[] = "%{$name}%";
			$queryParam[] = "%{$name}%";
		}
		if ($spec) {
			$sql .= " and (c.spec like '%s')";
			$queryParam[] = "%{$spec}%";
		}
		if ($barCode) {
			$sql .= " and (c.bar_code = '%s') ";
			$queryParam[] = $barCode;
		}
		$data = $db->query($sql, $queryParam);
		
		return array(
				"cnt" => $data[0]["cnt"]
		);
	}

	/**
	 * 子商品字段，查询数据
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function queryDataForSubGoods($params) {
		$db = $this->db;
		
		$parentGoodsId = $params["parentGoodsId"];
		if (! $parentGoodsId) {
			return $this->emptyResult();
		}
		
		$queryKey = $params["queryKey"];
		$loginUserId = $params["loginUserId"];
		if ($this->loginUserIdNotExists($loginUserId)) {
			return $this->emptyResult();
		}
		
		if ($queryKey == null) {
			$queryKey = "";
		}
		
		$key = "%{$queryKey}%";
		
		$sql = "select g.id, g.code, g.name, g.spec, u.name as unit_name
				from t_goods g, t_goods_unit u
				where (g.unit_id = u.id)
				and (g.code like '%s' or g.name like '%s' or g.py like '%s'
					or g.spec like '%s' or g.spec_py like '%s') 
				and (g.id <> '%s')";
		$queryParams = [];
		$queryParams[] = $key;
		$queryParams[] = $key;
		$queryParams[] = $key;
		$queryParams[] = $key;
		$queryParams[] = $key;
		$queryParams[] = $parentGoodsId;
		
		$ds = new DataOrgDAO($db);
		$rs = $ds->buildSQL(FIdConst::GOODS, "g", $loginUserId);
		if ($rs) {
			$sql .= " and " . $rs[0];
			$queryParams = array_merge($queryParams, $rs[1]);
		}
		
		$sql .= " order by g.code
				limit 20";
		$data = $db->query($sql, $queryParams);
		$result = [];
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"code" => $v["code"],
					"name" => $v["name"],
					"spec" => $v["spec"],
					"unitName" => $v["unit_name"]
			];
		}
		
		return $result;
	}
}