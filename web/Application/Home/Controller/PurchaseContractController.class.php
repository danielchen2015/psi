<?php

namespace Home\Controller;

use Home\Common\FIdConst;
use Home\Service\UserService;
use Home\Service\PCBillService;

/**
 * 供应合同Controller
 *
 * @author 李静波
 *        
 */
class PurchaseContractController extends PSIBaseController {

	/**
	 * 供应合同 - 主页面
	 */
	public function pcbillIndex() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::SCM_PURCHASE_CONTRACT)) {
			$this->initVar();
			
			$this->assign("title", "供应合同");
			
			$this->display();
		} else {
			$this->gotoLoginPage("/Home/PurchaseContract/pcbillIndex");
		}
	}

	/**
	 * 某个供应合同的详细信息
	 */
	public function pcBillInfo() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new PCBillService();
			
			$this->ajaxReturn($service->pcBillInfo($params));
		}
	}

	/**
	 * 新增或编辑供应合同
	 */
	public function editPCBill() {
		if (IS_POST) {
			$json = I("post.jsonStr");
			$ps = new PCBillService();
			$this->ajaxReturn($ps->editPCBill($json));
		}
	}

	/**
	 * 获得供应合同主表信息列表
	 */
	public function pcbillList() {
		if (IS_POST) {
			$params = [
					"billStatus" => I("post.billStatus"),
					"ref" => I("post.ref"),
					"fromDT" => I("post.fromDT"),
					"toDT" => I("post.toDT"),
					"supplierId" => I("post.supplierId"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			];
			
			$service = new PCBillService();
			$this->ajaxReturn($service->pcbillList($params));
		}
	}

	/**
	 * 某个供应合同的明细列表
	 */
	public function pcBillDetailList() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new PCBillService();
			$this->ajaxReturn($service->pcBillDetailList($params));
		}
	}

	/**
	 * 删除供应合同
	 */
	public function deletePCBill() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new PCBillService();
			$this->ajaxReturn($service->deletePCBill($params));
		}
	}

	/**
	 * 审核供应合同
	 */
	public function commitPCBill() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new PCBillService();
			$this->ajaxReturn($service->commitPCBill($params));
		}
	}

	/**
	 * 取消审核
	 */
	public function cancelConfirmPCBill() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new PCBillService();
			$this->ajaxReturn($service->cancelConfirmPCBill($params));
		}
	}

	/**
	 * 关闭供应合同
	 */
	public function closePCBill() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new PCBillService();
			$this->ajaxReturn($service->closePCBill($params));
		}
	}

	/**
	 * 取消关闭供应合同
	 */
	public function cancelClosedPCBill() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new PCBillService();
			$this->ajaxReturn($service->cancelClosedPCBill($params));
		}
	}

	/**
	 * 采购模板详情
	 */
	public function pcTemplateInfo() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id"),
					"pcBillId" => I("post.pcBillId")
			];
			
			$service = new PCBillService();
			$this->ajaxReturn($service->pcTemplateInfo($params));
		}
	}

	/**
	 * 选择组织机构
	 */
	public function selectOrgForPCTemplate() {
		if (IS_POST) {
			$params = [];
			
			$service = new PCBillService();
			$this->ajaxReturn($service->selectOrgForPCTemplate($params));
		}
	}

	/**
	 * 选择物资
	 */
	public function selectGoodsForPCTemplate() {
		if (IS_POST) {
			$params = [
					"pcbillId" => I("post.pcbillId")
			];
			
			$service = new PCBillService();
			$this->ajaxReturn($service->selectGoodsForPCTemplate($params));
		}
	}

	/**
	 * 新建或编辑采购模板
	 */
	public function editPCTemplate() {
		if (IS_POST) {
			$json = I("post.jsonStr");
			$ps = new PCBillService();
			$this->ajaxReturn($ps->editPCTemplate($json));
		}
	}

	/**
	 * 采购模板列表
	 */
	public function pcTemplateList() {
		if (IS_POST) {
			$params = [
					"pcBillId" => I("post.pcBillId")
			];
			
			$service = new PCBillService();
			$this->ajaxReturn($service->pcTemplateList($params));
		}
	}

	/**
	 * 采购模板详情 - 同时返回商品明细和组织机构明细
	 */
	public function pcTemplateDetailInfo() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new PCBillService();
			$this->ajaxReturn($service->pcTemplateDetailInfo($params));
		}
	}

	/**
	 * 删除采购模板
	 */
	public function deletePCTemplate() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new PCBillService();
			$this->ajaxReturn($service->deletePCTemplate($params));
		}
	}
}