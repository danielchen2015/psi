<?php

namespace Home\Service;

use Home\DAO\LDBillDAO;

/**
 * 物流单Service
 *
 * @author 李静波
 */
class LDBillService extends PSIBaseExService {
	private $LOG_CATEGORY = "向门店发货";

	/**
	 * 物流单主表列表
	 *
	 * @param array $params        	
	 */
	public function ldbillList($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["loginUserId"] = $this->getLoginUserId();
		
		$dao = new LDBillDAO($this->db());
		return $dao->ldbillList($params);
	}

	/**
	 * 选择要发货的门店订货单 - 主表列表
	 *
	 * @param array $params        	
	 */
	public function selectSPOBillList($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["loginUserId"] = $this->getLoginUserId();
		
		$dao = new LDBillDAO($this->db());
		return $dao->selectSPOBillList($params);
	}

	/**
	 * 选择要发货的门店订货单 - 明细记录
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function spoBillDetailList($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["companyId"] = $this->getCompanyId();
		
		$dao = new LDBillDAO($this->db());
		return $dao->spoBillDetailList($params);
	}

	/**
	 * 查询门店订货单的信息，用于生成物流单
	 *
	 * @param array $params        	
	 */
	public function getSPOBillInfoForLDBill($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["loginUserName"] = $this->getLoginUserName();
		$params["loginUserId"] = $this->getLoginUserId();
		$params["companyId"] = $this->getCompanyId();
		
		$dao = new LDBillDAO($this->db());
		return $dao->getSPOBillInfoForLDBill($params);
	}

	/**
	 * 新建或编辑物流单
	 *
	 * @param string $json        	
	 */
	public function editLDBill($json) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$bill = json_decode(html_entity_decode($json), true);
		if ($bill == null) {
			return $this->bad("传入的参数错误，不是正确的JSON格式");
		}
		
		$id = $bill["id"];
		
		$db = $this->db();
		
		$db->startTrans();
		
		$dao = new LDBillDAO($db);
		
		$log = null;
		
		$bill["companyId"] = $this->getCompanyId();
		$bill["loginUserId"] = $this->getLoginUserId();
		$bill["dataOrg"] = $this->getLoginUserDataOrg();
		
		if ($id) {
			// 编辑物流单
			
			$rc = $dao->updateLDBill($bill);
			if ($rc) {
				$db->rollback();
				return $rc;
			}
			
			$ref = $bill["ref"];
			
			$log = "编辑物流单: 单号 = {$ref}";
		} else {
			// 新建物流单
			
			$rc = $dao->addLDBill($bill);
			if ($rc) {
				$db->rollback();
				return $rc;
			}
			
			$id = $bill["id"];
			$ref = $bill["ref"];
			
			$log = "新建物流单: 单号 = {$ref}";
		}
		
		// 记录业务日志
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		
		return $this->ok($id);
	}

	/**
	 * 物流单明细记录
	 *
	 * @param array $params        	
	 */
	public function ldBillDetailList($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["companyId"] = $this->getCompanyId();
		
		$dao = new LDBillDAO($this->db());
		return $dao->ldBillDetailList($params);
	}

	/**
	 * 删除物流单
	 *
	 * @param array $params        	
	 */
	public function deleteLDBill($params) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$params["companyId"] = $this->getCompanyId();
		
		$db = $this->db();
		$db->startTrans();
		
		$dao = new LDBillDAO($db);
		
		$rc = $dao->deleteLDBill($params);
		if ($rc) {
			$db->rollback();
			return $rc;
		}
		
		// 记录业务日志
		$ref = $params["ref"];
		$log = "删除物流单: 单号 = {$ref}";
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		
		return $this->ok();
	}

	/**
	 * 某个物流单的详情
	 *
	 * @param array $params        	
	 */
	public function ldBillInfo($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["companyId"] = $this->getCompanyId();
		
		$dao = new LDBillDAO($this->db());
		return $dao->ldBillInfo($params);
	}

	/**
	 * 提交出库
	 *
	 * @param array $params        	
	 */
	public function commitLDBill($params) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$params["companyId"] = $this->getCompanyId();
		
		$db = $this->db();
		$db->startTrans();
		
		$dao = new LDBillDAO($db);
		
		$rc = $dao->commitLDBill($params);
		if ($rc) {
			$db->rollback();
			return $rc;
		}
		
		// 记录业务日志
		$ref = $params["ref"];
		$log = "物流单提交出库: 单号 = {$ref}";
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		
		return $this->ok();
	}

	/**
	 * 物流单主表列表 - 门店收货
	 *
	 * @param array $params        	
	 */
	public function ldbillListForSRG($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["loginUserId"] = $this->getLoginUserId();
		
		$dao = new LDBillDAO($this->db());
		return $dao->ldbillListForSRG($params);
	}

	/**
	 * 物流单明细记录 - 门店收货
	 *
	 * @param array $params        	
	 */
	public function ldBillDetailListForSRG($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["companyId"] = $this->getCompanyId();
		
		$dao = new LDBillDAO($this->db());
		return $dao->ldBillDetailListForSRG($params);
	}

	/**
	 * 某个物流单的详情 - 门店收货
	 *
	 * @param array $params        	
	 */
	public function ldBillInfoForSRG($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["companyId"] = $this->getCompanyId();
		
		$dao = new LDBillDAO($this->db());
		return $dao->ldBillInfoForSRG($params);
	}

	/**
	 * 编辑物流单 - 门店收货
	 *
	 * @param string $json        	
	 */
	public function editLDBillForSRG($json) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$bill = json_decode(html_entity_decode($json), true);
		if ($bill == null) {
			return $this->bad("传入的参数错误，不是正确的JSON格式");
		}
		
		$id = $bill["id"];
		
		$db = $this->db();
		
		$db->startTrans();
		
		$dao = new LDBillDAO($db);
		
		$log = null;
		
		$bill["companyId"] = $this->getCompanyId();
		$bill["loginUserId"] = $this->getLoginUserId();
		$bill["dataOrg"] = $this->getLoginUserDataOrg();
		
		if ($id) {
			// 编辑物流单
			
			$rc = $dao->updateLDBillForSRG($bill);
			if ($rc) {
				$db->rollback();
				return $rc;
			}
			
			$ref = $bill["ref"];
			
			$log = "门店收货录入收货数据，物流单: 单号 = {$ref}";
		} else {
			return $this->bad("物流单不存在");
		}
		
		// 记录业务日志
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, "门店收货");
		
		$db->commit();
		
		return $this->ok($id);
	}

	/**
	 * 提交入库 - 门店收货
	 *
	 * @param array $params        	
	 */
	public function commitLDBillForSRG($params) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$params["companyId"] = $this->getCompanyId();
		
		$db = $this->db();
		$db->startTrans();
		
		$dao = new LDBillDAO($db);
		
		$rc = $dao->commitLDBillForSRG($params);
		if ($rc) {
			$db->rollback();
			return $rc;
		}
		
		// 记录业务日志
		$ref = $params["ref"];
		$log = "物流单门店收货提交入库: 单号 = {$ref}";
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		
		return $this->ok();
	}

	/**
	 * 门店退货提交入库
	 *
	 * @param array $params        	
	 */
	public function commitLDBillRej($params) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$params["companyId"] = $this->getCompanyId();
		
		$db = $this->db();
		$db->startTrans();
		
		$dao = new LDBillDAO($db);
		
		$rc = $dao->commitLDBillRej($params);
		if ($rc) {
			$db->rollback();
			return $rc;
		}
		
		// 记录业务日志
		$ref = $params["ref"];
		$log = "物流单门店退货提交入库: 单号 = {$ref}";
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		
		return $this->ok();
	}
}