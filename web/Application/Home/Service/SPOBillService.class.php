<?php

namespace Home\Service;

use Home\DAO\SPOBillDAO;

/**
 * 门店订货单Service
 *
 * @author 李静波
 */
class SPOBillService extends PSIBaseExService {
	private $LOG_CATEGORY = "门店订货";

	/**
	 * 新增或编辑门店订货单
	 */
	public function editSPOBill($json) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$bill = json_decode(html_entity_decode($json), true);
		if ($bill == null) {
			return $this->bad("传入的参数错误，不是正确的JSON格式");
		}
		
		$db = $this->db();
		
		$db->startTrans();
		
		$dao = new SPOBillDAO($db);
		
		$us = new UserService();
		$bill["companyId"] = $us->getCompanyId();
		$bill["loginUserId"] = $us->getLoginUserId();
		$bill["dataOrg"] = $us->getLoginUserDataOrg();
		
		$id = $bill["id"];
		
		$log = null;
		if ($id) {
			// 编辑
			
			$rc = $dao->updateSPOBill($bill);
			if ($rc) {
				$db->rollback();
				return $rc;
			}
			
			$ref = $bill["ref"];
			
			$log = "编辑门店订货单，单号：{$ref}";
		} else {
			// 新建
			
			$rc = $dao->addSPOBill($bill);
			if ($rc) {
				$db->rollback();
				return $rc;
			}
			
			$id = $bill["id"];
			$ref = $bill["ref"];
			
			$log = "新建门店订货单，单号：{$ref}";
		}
		
		// 记录业务日志
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		
		return $this->ok($id);
	}

	/**
	 * 获得门店订货单主表信息列表
	 *
	 * @param array $params        	
	 */
	public function spobillList($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$us = new UserService();
		$params["loginUserId"] = $us->getLoginUserId();
		
		$dao = new SPOBillDAO($this->db());
		return $dao->spobillList($params);
	}

	/**
	 * 获得门店订货单的明细信息
	 */
	public function spoBillDetailList($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$dao = new SPOBillDAO($this->db());
		return $dao->spoBillDetailList($params);
	}

	/**
	 * 获得门店订货单的信息
	 */
	public function spoBillInfo($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$params["companyId"] = $this->getCompanyId();
		$params["loginUserId"] = $this->getLoginUserId();
		$params["loginUserName"] = $this->getLoginUserName();
		
		$dao = new SPOBillDAO($this->db());
		return $dao->spoBillInfo($params);
	}

	/**
	 * 删除门店订货单
	 */
	public function deleteSPOBill($params) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$db = $this->db();
		
		$db->startTrans();
		
		$dao = new SPOBillDAO($db);
		
		$rc = $dao->deleteSPOBill($params);
		if ($rc) {
			$db->rollback();
			return $rc;
		}
		
		$ref = $params["ref"];
		$log = "删除门店订货单，单号：{$ref}";
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		
		return $this->ok();
	}

	/**
	 * 审核门店订货单
	 */
	public function commitSPOBill($params) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$db = $this->db();
		$db->startTrans();
		
		$dao = new SPOBillDAO($db);
		
		$params["loginUserId"] = $this->getLoginUserId();
		
		$rc = $dao->commitSPOBill($params);
		if ($rc) {
			$db->rollback();
			return $rc;
		}
		
		$ref = $params["ref"];
		$id = $params["id"];
		
		// 记录业务日志
		$log = "审核门店订货单，单号：{$ref}";
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		
		return $this->ok($id);
	}

	/**
	 * 取消审核门店订货单
	 */
	public function cancelConfirmSPOBill($params) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$id = $params["id"];
		
		$db = $this->db();
		$db->startTrans();
		
		$dao = new SPOBillDAO($db);
		$rc = $dao->cancelConfirmSPOBill($params);
		if ($rc) {
			$db->rollback();
			return $rc;
		}
		
		$ref = $params["ref"];
		
		// 记录业务日志
		$log = "取消审核门店订货单，单号：{$ref}";
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		
		return $this->ok($id);
	}

	/**
	 * 门店订货单生成采购订单
	 *
	 * @param array $params        	
	 */
	public function genPOBill($params) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$params["loginUserId"] = $this->getLoginUserId();
		
		$id = $params["id"];
		
		$db = $this->db();
		$db->startTrans();
		
		$dao = new SPOBillDAO($db);
		$rc = $dao->genPOBill($params);
		if ($rc) {
			$db->rollback();
			return $rc;
		}
		
		$ref = $params["ref"];
		$poBillRef = $params["poBillRef"];
		
		// 记录业务日志
		$log = "由门店订货单(单号：{$ref})生成采购订单(单号：{$poBillRef})";
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		
		return $this->ok($id);
	}

	/**
	 * 关闭门店订货单
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function closeSPOBill($params) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$id = $params["id"];
		
		$db = $this->db();
		$db->startTrans();
		$dao = new SPOBillDAO($this->db());
		$rc = $dao->closeSPOBill($params);
		if ($rc) {
			$db->rollback();
			return $rc;
		}
		
		$ref = $params["ref"];
		
		// 记录业务日志
		$log = "关闭门店订货单，单号：{$ref}";
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		
		return $this->ok($id);
	}

	/**
	 * 取消关闭门店订货单
	 *
	 * @param array $params        	
	 * @return array
	 */
	public function cancelClosedSPOBill($params) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$id = $params["id"];
		
		$db = $this->db();
		$db->startTrans();
		$dao = new SPOBillDAO($db);
		$rc = $dao->cancelClosedSPOBill($params);
		if ($rc) {
			$db->rollback();
			return $rc;
		}
		
		$ref = $params["ref"];
		
		// 记录业务日志
		$log = "取消关闭门店订货单，单号：{$ref}";
		$bs = new BizlogService($db);
		$bs->insertBizlog($log, $this->LOG_CATEGORY);
		
		$db->commit();
		
		return $this->ok($id);
	}
}