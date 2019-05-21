<?php

namespace Home\Service;

use Home\DAO\PCBillDAO;
use Home\DAO\PCTemplateDAO;

/**
 * 供应合同Service
 *
 * @author 李静波
 */
class PCBillService extends PSIBaseExService {
	private $LOG_CATEGORY = "供应合同";

	/**
	 * 某个供应合同的详细信息
	 */
	public function pcBillInfo($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["companyId"] = $this->getCompanyId();
		$params["loginUserId"] = $this->getLoginUserId();
		$params["loginUserName"] = $this->getLoginUserName();
		
		$dao = new PCBillDAO($this->db());
		return $dao->pcBillInfo($params);
	}

	/**
	 * 新建或编辑供应合同
	 */
	public function editPCBill($json) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$bill = json_decode(html_entity_decode($json), true);
		if ($bill == null) {
			return $this->bad("传入的参数错误，不是正确的JSON格式");
		}
		
		$db = $this->db();
		
		$db->startTrans();
		
		$dao = new PCBillDAO($db);
		
		$bill["companyId"] = $this->getCompanyId();
		$bill["loginUserId"] = $this->getLoginUserId();
		$bill["dataOrg"] = $this->getLoginUserDataOrg();
		
		$id = $bill["id"];
		
		// 合同号大写
		$ref = strtoupper(trim($bill["ref"]));
		$bill["ref"] = $ref;
		
		$log = null;
		if ($id) {
			// 编辑
			
			$rc = $dao->updatePCBill($bill);
			if ($rc) {
				$db->rollback();
				return $rc;
			}
			
			$log = "编辑供应合同，合同号：{$ref}";
		} else {
			// 新建
			
			$rc = $dao->addPCBill($bill);
			if ($rc) {
				$db->rollback();
				return $rc;
			}
			
			$id = $bill["id"];
			
			$log = "新建供应合同，合同号：{$ref}";
		}
		
		// 记录业务日志
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		
		return $this->ok($id);
	}

	/**
	 * 供应合同主表列表
	 *
	 * @param array $params        	
	 */
	public function pcbillList($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["loginUserId"] = $this->getLoginUserId();
		
		$dao = new PCBillDAO($this->db());
		return $dao->pcbillList($params);
	}

	/**
	 * 供应合同明细列表
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function pcBillDetailList($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$dao = new PCBillDAO($this->db());
		return $dao->pcBillDetailList($params);
	}

	/**
	 * 删除供应合同
	 *
	 * @param array $params        	
	 */
	public function deletePCBill($params) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$db = $this->db();
		
		$db->startTrans();
		
		$dao = new PCBillDAO($db);
		
		$rc = $dao->deletePCBill($params);
		if ($rc) {
			$db->rollback();
			return $rc;
		}
		
		$ref = $params["ref"];
		$log = "删除供应合同，合同号：{$ref}";
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		
		return $this->ok();
	}

	/**
	 * 审核供应合同
	 *
	 * @param array $params        	
	 */
	public function commitPCBill($params) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$db = $this->db();
		$db->startTrans();
		
		$dao = new PCBillDAO($db);
		
		$params["loginUserId"] = $this->getLoginUserId();
		
		$rc = $dao->commitPCBill($params);
		if ($rc) {
			$db->rollback();
			return $rc;
		}
		
		$ref = $params["ref"];
		$id = $params["id"];
		
		// 记录业务日志
		$log = "审核供应合同，合同号：{$ref}";
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		
		return $this->ok($id);
	}

	/**
	 * 取消审核
	 *
	 * @param array $params        	
	 */
	public function cancelConfirmPCBill($params) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$id = $params["id"];
		
		$db = $this->db();
		$db->startTrans();
		
		$dao = new PCBillDAO($db);
		$rc = $dao->cancelConfirmPCBill($params);
		if ($rc) {
			$db->rollback();
			return $rc;
		}
		
		$ref = $params["ref"];
		
		// 记录业务日志
		$log = "取消审核供应合同，合同号：{$ref}";
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		
		return $this->ok($id);
	}

	/**
	 * 关闭供应合同
	 *
	 * @param array $params        	
	 */
	public function closePCBill($params) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$id = $params["id"];
		
		$db = $this->db();
		$db->startTrans();
		$dao = new PCBillDAO($this->db());
		$rc = $dao->closePCBill($params);
		if ($rc) {
			$db->rollback();
			return $rc;
		}
		
		$ref = $params["ref"];
		
		// 记录业务日志
		$log = "关闭供应合同，合同号：{$ref}";
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		
		return $this->ok($id);
	}

	/**
	 * 取消关闭供应合同
	 *
	 * @param array $params        	
	 */
	public function cancelClosedPCBill($params) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$id = $params["id"];
		
		$db = $this->db();
		$db->startTrans();
		$dao = new PCBillDAO($this->db());
		$rc = $dao->cancelClosedPCBill($params);
		if ($rc) {
			$db->rollback();
			return $rc;
		}
		
		$ref = $params["ref"];
		
		// 记录业务日志
		$log = "取消关闭供应合同，合同号：{$ref}";
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		
		return $this->ok($id);
	}

	/**
	 * 采购模板详情
	 *
	 * @param array $params        	
	 */
	public function pcTemplateInfo($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$dao = new PCTemplateDAO($this->db());
		return $dao->pcTemplateInfo($params);
	}

	/**
	 * 选择组织机构
	 *
	 * @param array $params        	
	 */
	public function selectOrgForPCTemplate($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["loginUserId"] = $this->getLoginUserId();
		
		$dao = new PCTemplateDAO($this->db());
		return $dao->selectOrgForPCTemplate($params);
	}

	/**
	 * 选择物资
	 *
	 * @param array $params        	
	 */
	public function selectGoodsForPCTemplate($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$dao = new PCTemplateDAO($this->db());
		return $dao->selectGoodsForPCTemplate($params);
	}

	/**
	 * 新建或编辑采购模板
	 *
	 * @param string $json        	
	 */
	public function editPCTemplate($json) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$bill = json_decode(html_entity_decode($json), true);
		if ($bill == null) {
			return $this->bad("传入的参数错误，不是正确的JSON格式");
		}
		
		$db = $this->db();
		
		$db->startTrans();
		
		$dao = new PCTemplateDAO($db);
		
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
			
			$rc = $dao->updatePCTemplate($bill);
			if ($rc) {
				$db->rollback();
				return $rc;
			}
			
			$log = "编辑采购模板，编号：{$ref}";
		} else {
			// 新建
			
			$rc = $dao->addPCTemplate($bill);
			if ($rc) {
				$db->rollback();
				return $rc;
			}
			
			$id = $bill["id"];
			
			$log = "新建采购模板，编号：{$ref}";
		}
		
		// 记录业务日志
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		
		return $this->ok($id);
	}

	/**
	 * 采购模板列表
	 *
	 * @param array $params        	
	 */
	public function pcTemplateList($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$dao = new PCTemplateDAO($this->db());
		return $dao->pcTemplateList($params);
	}

	/**
	 * 采购模板详情 - 同时返回商品明细和组织机构明细
	 *
	 * @param array $params        	
	 */
	public function pcTemplateDetailInfo($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$dao = new PCTemplateDAO($this->db());
		return $dao->pcTemplateDetailInfo($params);
	}

	/**
	 * 删除采购模板
	 *
	 * @param array $params        	
	 */
	public function deletePCTemplate($params) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$db = $this->db();
		
		$db->startTrans();
		
		$dao = new PCTemplateDAO($db);
		
		$rc = $dao->deletePCTemplate($params);
		if ($rc) {
			$db->rollback();
			return $rc;
		}
		
		$ref = $params["ref"];
		$log = "删除采购模板，编号：{$ref}";
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		
		return $this->ok();
	}
}