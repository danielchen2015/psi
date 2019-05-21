<?php

namespace Home\Service;

use Home\DAO\USBillDAO;

/**
 * 门店消耗单Service
 *
 * @author 李静波
 */
class USBillService extends PSIBaseExService {
	private $LOG_CATEGORY_TEMPLATE = "门店盘点模板管理";
	private $LOG_CATEGORY = "门店盘点";

	/**
	 * 模板列表
	 *
	 * @param array $params        	
	 */
	public function usTemplateList($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["loginUserId"] = $this->getLoginUserId();
		
		$dao = new USBillDAO($this->db());
		return $dao->usTemplateList($params);
	}

	/**
	 * 模板 - 选择组织机构
	 *
	 * @param array $params        	
	 */
	public function selectOrgForUSTemplate($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["loginUserId"] = $this->getLoginUserId();
		
		$dao = new USBillDAO($this->db());
		return $dao->selectOrgForUSTemplate($params);
	}

	/**
	 * 某个模板的详情
	 *
	 * @param array $params        	
	 */
	public function usTemplateInfo($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["companyId"] = $this->getCompanyId();
		$dao = new USBillDAO($this->db());
		return $dao->usTemplateInfo($params);
	}

	/**
	 * 新建或编辑消耗单模板
	 *
	 * @param string $json        	
	 */
	public function editUSTemplate($json) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$bill = json_decode(html_entity_decode($json), true);
		if ($bill == null) {
			return $this->bad("传入的参数错误，不是正确的JSON格式");
		}
		
		$db = $this->db();
		
		$db->startTrans();
		
		$dao = new USBillDAO($db);
		
		$bill["companyId"] = $this->getCompanyId();
		$bill["loginUserId"] = $this->getLoginUserId();
		$bill["dataOrg"] = $this->getLoginUserDataOrg();
		
		$id = $bill["id"];
		
		// 模板编号大写
		$ref = strtoupper(trim($bill["ref"]));
		$bill["ref"] = $ref;
		
		$log = null;
		if ($id) {
			// 编辑
			
			$rc = $dao->updateUSTemplate($bill);
			if ($rc) {
				$db->rollback();
				return $rc;
			}
			
			$log = "编辑消耗模板，编号：{$ref}";
		} else {
			// 新建
			
			$rc = $dao->addUSTemplate($bill);
			if ($rc) {
				$db->rollback();
				return $rc;
			}
			
			$id = $bill["id"];
			
			$log = "新建消耗单模板，编号：{$ref}";
		}
		
		// 记录业务日志
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY_TEMPLATE);
		
		$db->commit();
		
		return $this->ok($id);
	}

	/**
	 * 某个消耗单模板的物资明细
	 *
	 * @param array $params        	
	 */
	public function usTemplateDetailList($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["companyId"] = $this->getCompanyId();
		
		$dao = new USBillDAO($this->db());
		return $dao->usTemplateDetailList($params);
	}

	/**
	 * 某个消耗单模板的使用组织机构
	 *
	 * @param array $params        	
	 */
	public function usTemplateOrgList($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$dao = new USBillDAO($this->db());
		return $dao->usTemplateOrgList($params);
	}

	/**
	 * 删除消耗单模板
	 *
	 * @param array $params        	
	 */
	public function deleteUSTemplate($params) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$db = $this->db();
		
		$db->startTrans();
		
		$dao = new USBillDAO($db);
		
		$rc = $dao->deleteUSTemplate($params);
		if ($rc) {
			$db->rollback();
			return $rc;
		}
		
		// 记录业务日志
		$ref = $params["ref"];
		$log = "删除消耗单模板，模板编号：{$ref}";
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY_TEMPLATE);
		
		$db->commit();
		
		return $this->ok();
	}

	/**
	 * 消耗单列表
	 *
	 * @param array $params        	
	 */
	public function usBillList($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["loginUserId"] = $this->getLoginUserId();
		
		$dao = new USBillDAO($this->db());
		return $dao->usBillList($params);
	}

	/**
	 * 损耗单 - 选择模板 - 主表列表
	 *
	 * @param array $params        	
	 */
	public function selectUSTemplateList($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["loginUserId"] = $this->getLoginUserId();
		
		$dao = new USBillDAO($this->db());
		return $dao->selectUSTemplateList($params);
	}

	/**
	 * 损耗单 - 选择模板 - 明细列表
	 *
	 * @param array $params        	
	 */
	public function usTemplateDetailListForUSBill($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["companyId"] = $this->getCompanyId();
		
		$dao = new USBillDAO($this->db());
		return $dao->usTemplateDetailListForUSBill($params);
	}

	/**
	 * 损耗单 - 选择模板后查询该模板的数据
	 *
	 * @param array $params        	
	 */
	public function getUSTemplateInfoForUSBill($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["companyId"] = $this->getCompanyId();
		$params["loginUserId"] = $this->getLoginUserId();
		$params["loginUserName"] = $this->getLoginUserName();
		
		$dao = new USBillDAO($this->db());
		return $dao->getUSTemplateInfoForUSBill($params);
	}

	/**
	 * 新建或编辑消耗单
	 *
	 * @param string $json        	
	 */
	public function editUSBill($json) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$bill = json_decode(html_entity_decode($json), true);
		if ($bill == null) {
			return $this->bad("传入的参数错误，不是正确的JSON格式");
		}
		
		$db = $this->db();
		
		$db->startTrans();
		
		$dao = new USBillDAO($db);
		
		$bill["companyId"] = $this->getCompanyId();
		$bill["loginUserId"] = $this->getLoginUserId();
		$bill["dataOrg"] = $this->getLoginUserDataOrg();
		
		$id = $bill["id"];
		
		$log = null;
		if ($id) {
			// 编辑
			
			$rc = $dao->updateUSBill($bill);
			if ($rc) {
				$db->rollback();
				return $rc;
			}
			
			$ref = $bill["ref"];
			
			$log = "编辑消耗单，单号：{$ref}";
		} else {
			// 新建
			
			$rc = $dao->addUSBill($bill);
			if ($rc) {
				$db->rollback();
				return $rc;
			}
			
			$id = $bill["id"];
			$ref = $bill["ref"];
			
			$log = "新建消耗单，单号：{$ref}";
		}
		
		// 记录业务日志
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		
		return $this->ok($id);
	}

	/**
	 * 某个消耗单明细列表
	 *
	 * @param array $params        	
	 */
	public function usBillDetailList($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["companyId"] = $this->getCompanyId();
		
		$dao = new USBillDAO($this->db());
		return $dao->usBillDetailList($params);
	}

	/**
	 * 某个消耗单的详情
	 *
	 * @param array $params        	
	 */
	public function usBillInfo($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["companyId"] = $this->getCompanyId();
		
		$dao = new USBillDAO($this->db());
		return $dao->usBillInfo($params);
	}

	/**
	 * 删除消耗单
	 *
	 * @param array $params        	
	 */
	public function deleteUSBill($params) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$db = $this->db();
		$db->startTrans();
		
		$dao = new USBillDAO($db);
		$rc = $dao->deleteUSBill($params);
		if ($rc) {
			$db->rollback();
			return $rc;
		}
		
		// 记录业务日志
		$ref = $params["ref"];
		$log = "删除消耗单，单号：{$ref}";
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		return $this->ok();
	}

	/**
	 * 提交消耗单
	 *
	 * @param array $params        	
	 */
	public function commitUSBill($params) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$params["companyId"] = $this->getCompanyId();
		
		$db = $this->db();
		$db->startTrans();
		
		$dao = new USBillDAO($db);
		$rc = $dao->commitUSBill($params);
		if ($rc) {
			$db->rollback();
			return $rc;
		}
		
		// 记录业务日志
		$ref = $params["ref"];
		$log = "提交消耗单，单号：{$ref}";
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		return $this->ok();
	}
}