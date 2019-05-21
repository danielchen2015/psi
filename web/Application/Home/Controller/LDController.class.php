<?php

namespace Home\Controller;

use Home\Common\FIdConst;
use Home\Service\UserService;
use Home\Service\LDBillService;

/**
 * 物流单Controller
 *
 * @author 李静波
 *        
 */
class LDController extends PSIBaseController {

	/**
	 * 向门店发货 - 主页面
	 */
	public function index() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::SCM_LOGISTICS_DISTRIBUTION)) {
			$this->initVar();
			
			$this->assign("title", "向门店发货");
			
			$this->assign("pAdd", 
					$us->hasPermission(FIdConst::SCM_LOGISTICS_DISTRIBUTION_ADD) ? "1" : "0");
			$this->assign("pEdit", 
					$us->hasPermission(FIdConst::SCM_LOGISTICS_DISTRIBUTION_EDIT) ? "1" : "0");
			$this->assign("pDelete", 
					$us->hasPermission(FIdConst::SCM_LOGISTICS_DISTRIBUTION_DELETE) ? "1" : "0");
			$this->assign("pCommit", 
					$us->hasPermission(FIdConst::SCM_LOGISTICS_DISTRIBUTION_COMMIT) ? "1" : "0");
			$this->assign("pPrint", 
					$us->hasPermission(FIdConst::SCM_LOGISTICS_DISTRIBUTION_PRINT) ? "1" : "0");
			$this->assign("pCommitRej", 
					$us->hasPermission(FIdConst::SCM_LOGISTICS_DISTRIBUTION_COMMIT_REJ) ? "1" : "0");
			
			$this->display();
		} else {
			$this->gotoLoginPage("/Home/LD/index");
		}
	}

	/**
	 * 门店收货 - 主页面
	 *
	 * srg是Store Receive Goods三个单词的首字母
	 */
	public function srgIndex() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::SCM_STORE_REVGOODS)) {
			$this->initVar();
			
			$this->assign("title", "门店收货");
			
			$this->display();
		} else {
			$this->gotoLoginPage("/Home/LD/srgIndex");
		}
	}

	/**
	 * 物流单主表列表
	 */
	public function ldbillList() {
		if (IS_POST) {
			$params = [
					"billStatus" => I("post.billStatus"),
					"ref" => I("post.ref"),
					"fromDT" => I("post.fromDT"),
					"toDT" => I("post.toDT"),
					"orgId" => I("post.orgId"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			
			];
			
			$service = new LDBillService();
			$this->ajaxReturn($service->ldbillList($params));
		}
	}

	/**
	 * 选择要发货的门店订货单 - 主表列表
	 */
	public function selectSPOBillList() {
		if (IS_POST) {
			$params = [
					"ref" => I("post.ref"),
					"supplierId" => I("post.supplierId"),
					"fromDT" => I("post.fromDT"),
					"toDT" => I("post.toDT"),
					"orgId" => I("post.orgId"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			];
			
			$service = new LDBillService();
			$this->ajaxReturn($service->selectSPOBillList($params));
		}
	}

	/**
	 * 选择要发货的门店订货单 - 明细记录
	 */
	public function spoBillDetailList() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new LDBillService();
			$this->ajaxReturn($service->spoBillDetailList($params));
		}
	}

	/**
	 * 查询门店订货单的信息，用于生成物流单
	 */
	public function getSPOBillInfoForLDBill() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new LDBillService();
			$this->ajaxReturn($service->getSPOBillInfoForLDBill($params));
		}
	}

	/**
	 * 新建或编辑物流单
	 */
	public function editLDBill() {
		if (IS_POST) {
			$json = I("post.jsonStr");
			$ps = new LDBillService();
			$this->ajaxReturn($ps->editLDBill($json));
		}
	}

	/**
	 * 物流单明细记录
	 */
	public function ldBillDetailList() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new LDBillService();
			$this->ajaxReturn($service->ldBillDetailList($params));
		}
	}

	/**
	 * 删除物流单
	 */
	public function deleteLDBill() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new LDBillService();
			$this->ajaxReturn($service->deleteLDBill($params));
		}
	}

	/**
	 * 某个物流单的详情
	 */
	public function ldBillInfo() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new LDBillService();
			$this->ajaxReturn($service->ldBillInfo($params));
		}
	}

	/**
	 * 发货提交出库
	 */
	public function commitLDBill() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new LDBillService();
			$this->ajaxReturn($service->commitLDBill($params));
		}
	}

	/**
	 * 物流单主表列表 - 门店收货
	 */
	public function ldbillListForSRG() {
		if (IS_POST) {
			$params = [
					"billStatus" => I("post.billStatus"),
					"ref" => I("post.ref"),
					"fromDT" => I("post.fromDT"),
					"toDT" => I("post.toDT"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			
			];
			
			$service = new LDBillService();
			$this->ajaxReturn($service->ldbillListForSRG($params));
		}
	}

	/**
	 * 物流单明细记录 - 门店收货
	 */
	public function ldBillDetailListForSRG() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new LDBillService();
			$this->ajaxReturn($service->ldBillDetailListForSRG($params));
		}
	}

	/**
	 * 某个物流单的详情 - 门店收货
	 */
	public function ldBillInfoForSRG() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new LDBillService();
			$this->ajaxReturn($service->ldBillInfoForSRG($params));
		}
	}

	/**
	 * 保存物流单数据 - 门店收货录入数据
	 */
	public function editLDBillForSRG() {
		if (IS_POST) {
			$json = I("post.jsonStr");
			$ps = new LDBillService();
			$this->ajaxReturn($ps->editLDBillForSRG($json));
		}
	}

	/**
	 * 提交入库 - 门店收货
	 */
	public function commitLDBillForSRG() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new LDBillService();
			$this->ajaxReturn($service->commitLDBillForSRG($params));
		}
	}

	/**
	 * 门店退货提交入库
	 */
	public function commitLDBillRej() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new LDBillService();
			$this->ajaxReturn($service->commitLDBillRej($params));
		}
	}
}