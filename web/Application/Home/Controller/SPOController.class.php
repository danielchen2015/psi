<?php

namespace Home\Controller;

use Home\Common\FIdConst;
use Home\Service\UserService;
use Home\Service\SPOBillService;

/**
 * 门店订货Controller
 *
 * @author 李静波
 *        
 */
class SPOController extends PSIBaseController {

	/**
	 * 门店订货 - 主页面
	 */
	public function index() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::SCM_SPO)) {
			$this->initVar();
			
			$this->assign("title", "门店订货");
			
			$this->assign("pAdd", $us->hasPermission(FIdConst::SCM_SPO_ADD) ? "1" : "0");
			$this->assign("pEdit", $us->hasPermission(FIdConst::SCM_SPO_EDIT) ? "1" : "0");
			$this->assign("pDelete", $us->hasPermission(FIdConst::SCM_SPO_DELETE) ? "1" : "0");
			$this->assign("pConfirm", $us->hasPermission(FIdConst::SCM_SPO_CONFIRM) ? "1" : "0");
			$this->assign("pGenPOBill", 
					$us->hasPermission(FIdConst::SCM_SPO_GEN_POBILL) ? "1" : "0");
			$this->assign("pCloseBill", $us->hasPermission(FIdConst::SCM_SPO_CLOSE) ? "1" : "0");
			
			$this->display();
		} else {
			$this->gotoLoginPage("/Home/SPO/index");
		}
	}

	/**
	 * 新增或编辑门店订货单
	 */
	public function editSPOBill() {
		if (IS_POST) {
			$json = I("post.jsonStr");
			$ps = new SPOBillService();
			$this->ajaxReturn($ps->editSPOBill($json));
		}
	}

	/**
	 * 获得门店订货单主表信息列表
	 */
	public function spobillList() {
		if (IS_POST) {
			$params = array(
					"billStatus" => I("post.billStatus"),
					"ref" => I("post.ref"),
					"fromDT" => I("post.fromDT"),
					"toDT" => I("post.toDT"),
					"supplierId" => I("post.supplierId"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			);
			
			$service = new SPOBillService();
			$this->ajaxReturn($service->spobillList($params));
		}
	}

	/**
	 * 获得门店订货单的明细信息
	 */
	public function spoBillDetailList() {
		if (IS_POST) {
			$us = new UserService();
			$companyId = $us->getCompanyId();
			
			$params = [
					"id" => I("post.id"),
					"companyId" => $companyId
			];
			
			$ps = new SPOBillService();
			$this->ajaxReturn($ps->spoBillDetailList($params));
		}
	}

	/**
	 * 获得门店订货单的信息
	 */
	public function spoBillInfo() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			
			$ps = new SPOBillService();
			$this->ajaxReturn($ps->spoBillInfo($params));
		}
	}

	/**
	 * 删除门店订货单
	 */
	public function deleteSPOBill() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			
			$service = new SPOBillService();
			$this->ajaxReturn($service->deleteSPOBill($params));
		}
	}

	/**
	 * 审核门店订货单
	 */
	public function commitSPOBill() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			
			$service = new SPOBillService();
			$this->ajaxReturn($service->commitSPOBill($params));
		}
	}

	/**
	 * 取消审核门店订货单
	 */
	public function cancelConfirmSPOBill() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			
			$service = new SPOBillService();
			$this->ajaxReturn($service->cancelConfirmSPOBill($params));
		}
	}

	/**
	 * 门店订货单生成采购订单
	 */
	public function genPOBill() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new SPOBillService();
			$this->ajaxReturn($service->genPOBill($params));
		}
	}

	/**
	 * 关闭门店订货单
	 */
	public function closeSPOBill() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			
			$service = new SPOBillService();
			$this->ajaxReturn($service->closeSPOBill($params));
		}
	}

	/**
	 * 取消关闭门店订货单
	 */
	public function cancelClosedSPOBill() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			
			$service = new SPOBillService();
			$this->ajaxReturn($service->cancelClosedSPOBill($params));
		}
	}
}