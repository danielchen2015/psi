<?php

namespace Home\Controller;

use Home\Common\FIdConst;
use Home\Service\UserService;
use Home\Service\USBillService;

/**
 * 门店盘点Controller
 *
 * @author 李静波
 *        
 */
class USController extends PSIBaseController {

	/**
	 * 门店盘点模板管理 - 主页面
	 */
	public function templateIndex() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::SCM_STORE_US_TEMPLATE_MANAGEMENT)) {
			$this->initVar();
			
			$this->assign("title", "门店盘点模板管理");
			
			$this->display();
		} else {
			$this->gotoLoginPage("/Home/US/templateIndex");
		}
	}

	/**
	 * 模板列表
	 */
	public function usTemplateList() {
		if (IS_POST) {
			$params = [
					"billStatus" => I("post.billStatus"),
					"ref" => I("post.ref"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			];
			
			$service = new USBillService();
			$this->ajaxReturn($service->usTemplateList($params));
		}
	}

	/**
	 * 模板 - 选择组织机构
	 */
	public function selectOrgForUSTemplate() {
		if (IS_POST) {
			$params = [];
			
			$service = new USBillService();
			$this->ajaxReturn($service->selectOrgForUSTemplate($params));
		}
	}

	/**
	 * 某个模板的详情
	 */
	public function usTemplateInfo() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new USBillService();
			$this->ajaxReturn($service->usTemplateInfo($params));
		}
	}

	/**
	 * 新建或编辑消耗单模板
	 */
	public function editUSTemplate() {
		if (IS_POST) {
			$json = I("post.jsonStr");
			$ps = new USBillService();
			$this->ajaxReturn($ps->editUSTemplate($json));
		}
	}

	/**
	 * 某个消耗单模板的物资明细
	 */
	public function usTemplateDetailList() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new USBillService();
			$this->ajaxReturn($service->usTemplateDetailList($params));
		}
	}

	/**
	 * 某个消耗单模板的使用组织机构
	 */
	public function usTemplateOrgList() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new USBillService();
			$this->ajaxReturn($service->usTemplateOrgList($params));
		}
	}

	/**
	 * 删除消耗单模板
	 */
	public function deleteUSTemplate() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new USBillService();
			$this->ajaxReturn($service->deleteUSTemplate($params));
		}
	}

	/**
	 * 门店盘点 - 主页面
	 */
	public function usbillIndex() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::SCM_STORE_US)) {
			$this->initVar();
			
			$this->assign("title", "门店盘点");
			
			$this->assign("pAdd", $us->hasPermission(FIdConst::SCM_STORE_US_ADD) ? "1" : "0");
			$this->assign("pEdit", $us->hasPermission(FIdConst::SCM_STORE_US_EDIT) ? "1" : "0");
			$this->assign("pDelete", $us->hasPermission(FIdConst::SCM_STORE_US_DELETE) ? "1" : "0");
			$this->assign("pCommit", $us->hasPermission(FIdConst::SCM_STORE_US_COMMIT) ? "1" : "0");
			
			$this->display();
		} else {
			$this->gotoLoginPage("/Home/US/usbillIndex");
		}
	}

	/**
	 * 消耗单列表
	 */
	public function usBillList() {
		if (IS_POST) {
			$params = [
					"billStatus" => I("post.billStatus"),
					"ref" => I("post.ref"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			];
			
			$service = new USBillService();
			$this->ajaxReturn($service->usBillList($params));
		}
	}

	/**
	 * 损耗单 - 选择模板 - 主表列表
	 */
	public function selectUSTemplateList() {
		if (IS_POST) {
			$params = [
					"ref" => I("post.ref"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			];
			
			$service = new USBillService();
			$this->ajaxReturn($service->selectUSTemplateList($params));
		}
	}

	/**
	 * 损耗单 - 选择模板 - 明细列表
	 */
	public function usTemplateDetailListForUSBill() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new USBillService();
			$this->ajaxReturn($service->usTemplateDetailListForUSBill($params));
		}
	}

	/**
	 * 损耗单 - 选择模板后查询该模板的数据
	 */
	public function getUSTemplateInfoForUSBill() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new USBillService();
			$this->ajaxReturn($service->getUSTemplateInfoForUSBill($params));
		}
	}

	/**
	 * 新建或编辑消耗单
	 */
	public function editUSBill() {
		if (IS_POST) {
			$json = I("post.jsonStr");
			$ps = new USBillService();
			$this->ajaxReturn($ps->editUSBill($json));
		}
	}

	/**
	 * 某个消耗单明细列表
	 */
	public function usBillDetailList() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new USBillService();
			$this->ajaxReturn($service->usBillDetailList($params));
		}
	}

	/**
	 * 某个消耗单的详情
	 */
	public function usBillInfo() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new USBillService();
			$this->ajaxReturn($service->usBillInfo($params));
		}
	}

	/**
	 * 删除消耗单
	 */
	public function deleteUSBill() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new USBillService();
			$this->ajaxReturn($service->deleteUSBill($params));
		}
	}

	/**
	 * 提交消耗单
	 */
	public function commitUSBill() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new USBillService();
			$this->ajaxReturn($service->commitUSBill($params));
		}
	}
}