<?php

namespace Home\Controller;

use Home\Common\FIdConst;
use Home\Service\SupplierService;
use Home\Service\UserService;

/**
 * 供应商档案Controller
 *
 * @author 李静波
 *        
 */
class SupplierController extends PSIBaseController {

	/**
	 * 供应商档案 - 主页面
	 */
	public function index() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::SUPPLIER)) {
			$this->initVar();
			
			$this->assign("title", "供应商档案");
			
			$this->assign("pAddCategory", 
					$us->hasPermission(FIdConst::SUPPLIER_CATEGORY_ADD) ? 1 : 0);
			$this->assign("pEditCategory", 
					$us->hasPermission(FIdConst::SUPPLIER_CATEGORY_EDIT) ? 1 : 0);
			$this->assign("pDeleteCategory", 
					$us->hasPermission(FIdConst::SUPPLIER_CATEGORY_DELETE) ? 1 : 0);
			$this->assign("pAddSupplier", $us->hasPermission(FIdConst::SUPPLIER_ADD) ? 1 : 0);
			$this->assign("pEditSupplier", $us->hasPermission(FIdConst::SUPPLIER_EDIT) ? 1 : 0);
			$this->assign("pDeleteSupplier", $us->hasPermission(FIdConst::SUPPLIER_DELETE) ? 1 : 0);
			
			$this->display();
		} else {
			$this->gotoLoginPage("/Home/Supplier/index");
		}
	}

	/**
	 * 供应商分类
	 */
	public function allCategories() {
		if (IS_POST) {
			$params = array(
					"code" => I("post.code"),
					"name" => I("post.name"),
					"address" => I("post.address"),
					"contact" => I("post.contact"),
					"mobile" => I("post.mobile"),
					"tel" => I("post.tel"),
					"qq" => I("post.qq")
			);
			$ss = new SupplierService();
			$this->ajaxReturn($ss->allCategories($params));
		}
	}

	/**
	 * 供应商档案列表
	 */
	public function supplierList() {
		if (IS_POST) {
			$params = array(
					"categoryId" => I("post.categoryId"),
					"code" => I("post.code"),
					"name" => I("post.name"),
					"address" => I("post.address"),
					"contact" => I("post.contact"),
					"mobile" => I("post.mobile"),
					"tel" => I("post.tel"),
					"qq" => I("post.qq"),
					"page" => I("post.page"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			);
			$ss = new SupplierService();
			$this->ajaxReturn($ss->supplierList($params));
		}
	}

	/**
	 * 新建或编辑供应商分类
	 */
	public function editCategory() {
		if (IS_POST) {
			$us = new UserService();
			if (I("post.id")) {
				// 编辑供应商分类
				if (! $us->hasPermission(FIdConst::SUPPLIER_CATEGORY_EDIT)) {
					$this->ajaxReturn($this->noPermission("编辑供应商分类"));
					return;
				}
			} else {
				// 新增供应商分类
				if (! $us->hasPermission(FIdConst::SUPPLIER_CATEGORY_ADD)) {
					$this->ajaxReturn($this->noPermission("新增供应商分类"));
					return;
				}
			}
			
			$params = array(
					"id" => I("post.id"),
					"code" => strtoupper(I("post.code")),
					"name" => I("post.name"),
					"parentId" => I("post.parentId")
			);
			$ss = new SupplierService();
			$this->ajaxReturn($ss->editCategory($params));
		}
	}

	/**
	 * 删除供应商分类
	 */
	public function deleteCategory() {
		if (IS_POST) {
			$us = new UserService();
			if (! $us->hasPermission(FIdConst::SUPPLIER_CATEGORY_DELETE)) {
				$this->ajaxReturn($this->noPermission("删除供应商分类"));
				return;
			}
			
			$params = array(
					"id" => I("post.id")
			);
			$ss = new SupplierService();
			$this->ajaxReturn($ss->deleteCategory($params));
		}
	}

	/**
	 * 新建或编辑供应商档案
	 */
	public function editSupplier() {
		if (IS_POST) {
			$us = new UserService();
			if (I("post.id")) {
				// 编辑供应商档案
				if (! $us->hasPermission(FIdConst::SUPPLIER_EDIT)) {
					$this->ajaxReturn($this->noPermission("编辑供应商档案"));
					return;
				}
			} else {
				// 新增供应商档案
				if (! $us->hasPermission(FIdConst::SUPPLIER_ADD)) {
					$this->ajaxReturn($this->noPermission("新增供应商档案"));
					return;
				}
			}
			
			$params = array(
					"id" => I("post.id"),
					"code" => strtoupper(I("post.code")),
					"name" => I("post.name"),
					"address" => I("post.address"),
					"addressShipping" => I("post.addressShipping"),
					"contact01" => I("post.contact01"),
					"mobile01" => I("post.mobile01"),
					"tel01" => I("post.tel01"),
					"qq01" => I("post.qq01"),
					"contact02" => I("post.contact02"),
					"mobile02" => I("post.mobile02"),
					"tel02" => I("post.tel02"),
					"qq02" => I("post.qq02"),
					"bankName" => I("post.bankName"),
					"bankAccount" => I("post.bankAccount"),
					"tax" => I("post.tax"),
					"fax" => I("post.fax"),
					"taxRate" => I("post.taxRate"),
					"note" => I("post.note"),
					"categoryId" => I("post.categoryId"),
					"initPayables" => I("post.initPayables"),
					"initPayablesDT" => I("post.initPayablesDT")
			);
			$ss = new SupplierService();
			$this->ajaxReturn($ss->editSupplier($params));
		}
	}

	/**
	 * 删除供应商档案
	 */
	public function deleteSupplier() {
		if (IS_POST) {
			$us = new UserService();
			if (! $us->hasPermission(FIdConst::SUPPLIER_DELETE)) {
				$this->ajaxReturn($this->noPermission("删除供应商档案"));
				return;
			}
			
			$params = array(
					"id" => I("post.id")
			);
			$ss = new SupplierService();
			$this->ajaxReturn($ss->deleteSupplier($params));
		}
	}

	/**
	 * 供应商自定义字段，查询数据
	 */
	public function queryData() {
		if (IS_POST) {
			$queryKey = I("post.queryKey");
			$ss = new SupplierService();
			$this->ajaxReturn($ss->queryData($queryKey));
		}
	}

	/**
	 * 获得某个供应商的信息
	 */
	public function supplierInfo() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			$ss = new SupplierService();
			$this->ajaxReturn($ss->supplierInfo($params));
		}
	}

	/**
	 * 所有的上级分类
	 */
	public function allParentCategories() {
		if (IS_POST) {
			$service = new SupplierService();
			$this->ajaxReturn($service->allParentCategories());
		}
	}

	/**
	 * 某个供应商分类的详细信息
	 */
	public function categoryInfo() {
		if (IS_POST) {
			$params = [
					"id" => I("post.id")
			];
			
			$service = new SupplierService();
			$this->ajaxReturn($service->categoryInfo($params));
		}
	}
}
