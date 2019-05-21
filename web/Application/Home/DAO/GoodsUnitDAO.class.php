<?php

namespace Home\DAO;

use Home\Service\PinyinService;

/**
 * 商品计量单位 DAO
 *
 * @author 李静波
 */
class GoodsUnitDAO extends PSIBaseExDAO {

	/**
	 * 返回所有商品计量单位
	 *
	 * @return array
	 */
	public function allUnits() {
		$db = $this->db;
		
		$sql = "select id, name, py
				from t_goods_unit
				order by py ";
		
		$data = $db->query($sql);
		
		$result = [];
		
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"name" => $v["name"],
					"py" => $v["py"]
			];
		}
		
		return $result;
	}

	/**
	 * 检查参数
	 *
	 * @param array $params        	
	 * @return array|NULL null: 没有错误
	 */
	private function checkParams($params) {
		$name = trim($params["name"]);
		
		if ($this->isEmptyStringAfterTrim($name)) {
			return $this->bad("计量单位不能为空");
		}
		
		if ($this->stringBeyondLimit($name, 10)) {
			return $this->bad("计量单位不能超过10位");
		}
		
		return null;
	}

	/**
	 * 新增商品计量单位
	 *
	 * @param array $params        	
	 * @return NULL|array
	 */
	public function addUnit(& $params) {
		$db = $this->db;
		
		$name = trim($params["name"]);
		$py = trim($params["py"]);
		
		if (! $py) {
			$pyService = new PinyinService();
			$py = $pyService->toPY($name);
		}
		
		$result = $this->checkParams($params);
		if ($result) {
			return $result;
		}
		
		// 检查计量单位是否存在
		$sql = "select count(*) as cnt from t_goods_unit where name = '%s' ";
		$data = $db->query($sql, $name);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("计量单位 [$name] 已经存在");
		}
		
		$dataOrg = $params["dataOrg"];
		$companyId = $params["companyId"];
		if ($this->dataOrgNotExists($dataOrg)) {
			return $this->badParam("dataOrg");
		}
		if ($this->companyIdNotExists($companyId)) {
			return $this->badParam("companyId");
		}
		
		$id = $this->newId();
		$params["id"] = $id;
		
		$sql = "insert into t_goods_unit(id, name, data_org, company_id, py)
					values ('%s', '%s', '%s', '%s', '%s') ";
		$rc = $db->execute($sql, $id, $name, $dataOrg, $companyId, $py);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 操作成功
		return null;
	}

	/**
	 * 编辑商品计量单位
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function updateUnit(& $params) {
		$db = $this->db;
		
		$id = $params["id"];
		$name = trim($params["name"]);
		$py = trim($params["py"]);
		
		if (! $py) {
			$pyService = new PinyinService();
			$py = $pyService->toPY($name);
		}
		
		$result = $this->checkParams($params);
		if ($result) {
			return $result;
		}
		
		// 检查计量单位是否存在
		$sql = "select count(*) as cnt from t_goods_unit where name = '%s' and id <> '%s' ";
		$data = $db->query($sql, $name, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("计量单位 [$name] 已经存在");
		}
		
		$sql = "update t_goods_unit set name = '%s', py = '%s' where id = '%s' ";
		$rc = $db->execute($sql, $name, $py, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		// 操作成功
		return null;
	}

	/**
	 * 通过id查询商品计量单位
	 *
	 * @param string $id        	
	 * @return array|NULL
	 */
	public function getGoodsUnitById($id) {
		$db = $this->db;
		
		$sql = "select name from t_goods_unit where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return null;
		} else {
			return array(
					"name" => $data[0]["name"]
			);
		}
	}

	/**
	 * 删除商品计量单位
	 *
	 * @param array $params        	
	 * @return NULL|array
	 */
	public function deleteUnit(& $params) {
		$db = $this->db;
		
		$id = $params["id"];
		
		$goodsUnit = $this->getGoodsUnitById($id);
		if (! $goodsUnit) {
			return $this->bad("要删除的商品计量单位不存在");
		}
		
		$name = $goodsUnit["name"];
		
		// 检查记录单位是否被使用
		$sql = "select count(*) as cnt from t_goods 
				where unit_id = '%s' ";
		$data = $db->query($sql, $id, $id, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("商品计量单位 [$name] 已经被使用，不能删除");
		}
		
		$sql = "select count(*) as cnt from t_goods_unit_group
				where unit_id = '%s' ";
		$data = $db->query($sql, $id, $id, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("商品计量单位 [$name] 已经被使用，不能删除");
		}
		
		$sql = "delete from t_goods_unit where id = '%s' ";
		$rc = $db->execute($sql, $id);
		if ($rc === false) {
			return $this->sqlError(__METHOD__, __LINE__);
		}
		
		$params["name"] = $name;
		
		// 操作成功
		return null;
	}

	/**
	 * 某个物资的单位组
	 *
	 * @param array $params        	
	 */
	public function goodsUnitGroupList($params) {
		$db = $this->db;
		
		// 物资id
		$id = $params["id"];
		
		$sql = "select u.name as unit_name
				from t_goods g, t_goods_unit u
				where g.id = '%s' and g.unit_id = u.id ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->emptyResult();
		}
		
		$unitName = $data[0]["unit_name"];
		
		$sql = "select u.id, u.name, p.factor, p.factor_type
				from t_goods_unit_group p, t_goods_unit u
				where p.goods_id = '%s' and p.unit_id = u.id";
		$data = $db->query($sql, $id);
		
		$result = [];
		foreach ( $data as $v ) {
			$factor = $v["factor"];
			if ($factor == floor($factor)) {
				// factor数据中有两位小数，但是实际中大部分是整数
				$factor = intval($factor);
			}
			
			$memo = "1{$v['name']} = {$factor}{$unitName}";
			
			$result[] = [
					"id" => $v["id"],
					"name" => $v["name"],
					"factor" => $factor,
					"factorType" => $v["factor_type"] == 0 ? "固定转换率" : "浮动转换率",
					"factorTypeValue" => $v["factor_type"],
					"memo" => $memo
			];
		}
		
		return $result;
	}

	/**
	 * 计量单位自定义字段查询数据
	 *
	 * @param array $params        	
	 */
	public function queryUnitData($params) {
		$db = $this->db;
		
		$goodsId = $params["goodsId"];
		$queryKey = $params["queryKey"];
		
		$sql = "select u.id, u.name
				from t_goods g, t_goods_unit u
				where (g.id = '%s') and (u.id = g.unit_id)
					and (u.py like '%s' or u.name like '%s')";
		$queryParams = [];
		$queryParams[] = $goodsId;
		$queryParams[] = "%{$queryKey}%";
		$queryParams[] = "%{$queryKey}%";
		
		$result = [];
		
		$data = $db->query($sql, $queryParams);
		
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"name" => $v["name"],
					"factor" => 1,
					"factorType" => 0
			];
		}
		
		// 单位组
		$sql = "select u.id, u.name, g.factor, g.factor_type
				from t_goods_unit_group g, t_goods_unit u
				where (g.goods_id = '%s') and (g.unit_id = u.id)
					and (u.py like '%s' or u.name like '%s')";
		$queryParams = [];
		$queryParams[] = $goodsId;
		$queryParams[] = "%{$queryKey}%";
		$queryParams[] = "%{$queryKey}%";
		$data = $db->query($sql, $queryParams);
		
		foreach ( $data as $v ) {
			$result[] = [
					"id" => $v["id"],
					"name" => $v["name"],
					"factor" => $v["factor"],
					"factorType" => $v["factor_type"]
			];
		}
		
		return $result;
	}

	/**
	 * 判断unitId是否正确
	 *
	 * @param string $goodsId        	
	 * @param string $unitId        	
	 *
	 * @return true:正确
	 */
	public function unitIdIsValid($goodsId, $unitId) {
		$db = $this->db;
		
		$sql = "select count(*) as cnt from t_goods where id = '%s' and unit_id = '%s' ";
		$data = $db->query($sql, $goodsId, $unitId);
		$cnt = $data[0]["cnt"];
		if ($cnt == 0) {
			// 检查unitId是否在单位组中
			$sql = "select count(*) as cnt from t_goods_unit_group where goods_id = '%s' and unit_id = '%s' ";
			$data = $db->query($sql, $goodsId, $unitId);
			$cnt = $data[0]["cnt"];
			if ($cnt == 0) {
				return false;
			}
		}
		
		return true;
	}

	/**
	 * 更新商品单位组中的默认单位
	 *
	 * @param string $goodsId        	
	 * @param string $unitId        	
	 */
	public function updateGoodsUnitDefault($goodsId, $unitId, $billType) {
		$db = $this->db;
		
		if (! $this->unitIdIsValid($goodsId, $unitId)) {
			return $this->badParam("unitId");
		}
		
		$sql = "select id from t_goods_unit_default
				where goods_id = '%s' and bill_type = '%s' ";
		$data = $db->query($sql, $goodsId, $billType);
		if (! $data) {
			// 首次记录
			$sql = "insert into t_goods_unit_default(id, goods_id, unit_id, bill_type)
					values ('%s', '%s', '%s', '%s')";
			$rc = $db->execute($sql, $this->newId(), $goodsId, $unitId, $billType);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		} else {
			$id = $data[0]["id"];
			$sql = "update t_goods_unit_default
					set unit_id = '%s'
					where id = '%s' ";
			$rc = $db->execute($sql, $unitId, $id);
			if ($rc === false) {
				return $this->sqlError(__METHOD__, __LINE__);
			}
		}
		
		return null;
	}
}