<?php

namespace Home\Controller;

use Think\Controller;
use Home\Service\FIdService;
use Home\Service\BizlogService;
use Home\Service\UserService;
use Home\Common\FIdConst;
use Home\Service\MainMenuService;

/**
 * 主菜单Controller
 *
 * @author 李静波
 *        
 */
class MainMenuController extends Controller {

	/**
	 * 页面跳转
	 */
	public function navigateTo() {
		$this->assign("uri", __ROOT__ . "/");
		
		$fid = I("get.fid");
		
		// $t == 1的时候，是从常用功能链接点击而来的
		$t = I("get.t");
		
		$fidService = new FIdService();
		$fidService->insertRecentFid($fid);
		$fidName = $fidService->getFIdName($fid);
		if ($fidName) {
			// 记录业务日志
			
			$bizLogService = new BizlogService();
			
			if ($t == "1") {
				$bizLogService->insertBizlog("通过常用功能进入模块：" . $fidName, "常用功能");
			} else {
				$bizLogService->insertBizlog("通过主菜单进入模块：" . $fidName);
			}
		}
		if (! $fid) {
			redirect(__ROOT__ . "/Home");
		}
		
		switch ($fid) {
			case FIdConst::ABOUT :
				// 修改我的密码
				redirect(__ROOT__ . "/Home/About/index");
				break;
			case FIdConst::RELOGIN :
				// 重新登录
				$us = new UserService();
				$us->clearLoginUserInSession();
				redirect(__ROOT__ . "/Home");
				break;
			case FIdConst::CHANGE_MY_PASSWORD :
				// 修改我的密码
				redirect(__ROOT__ . "/Home/User/changeMyPassword");
				break;
			case FIdConst::USR_MANAGEMENT :
				// 用户管理
				redirect(__ROOT__ . "/Home/User");
				break;
            case FIdConst::EMPLOYEE_MANAGEMENT:
                // 员工管理 add by daniel 2019/05/08
                redirect(__ROOT__ . "/Home/Employee");
                break;
			case FIdConst::PERMISSION_MANAGEMENT :
				// 权限管理
				redirect(__ROOT__ . "/Home/Permission");
				break;
			case FIdConst::BIZ_LOG :
				// 业务日志
				redirect(__ROOT__ . "/Home/Bizlog");
				break;
			case FIdConst::WAREHOUSE :
				// 基础数据 - 仓库
				redirect(__ROOT__ . "/Home/Warehouse");
				break;
			case FIdConst::SUPPLIER :
				// 基础数据 - 供应商档案
				redirect(__ROOT__ . "/Home/Supplier");
				break;
			case FIdConst::GOODS :
				// 基础数据 - 商品
				redirect(__ROOT__ . "/Home/Goods");
				break;
			case FIdConst::GOODS_UNIT :
				// 基础数据 - 商品计量单位
				redirect(__ROOT__ . "/Home/Goods/unitIndex");
				break;
			case FIdConst::INVENTORY_INIT :
				// 库存建账
				redirect(__ROOT__ . "/Home/Inventory/initIndex");
				break;
			case FIdConst::PURCHASE_WAREHOUSE :
				// 采购入库
				redirect(__ROOT__ . "/Home/Purchase/pwbillIndex");
				break;
			case FIdConst::INVENTORY_QUERY :
				// 库存账查询
				redirect(__ROOT__ . "/Home/Inventory/inventoryQuery");
				break;
			case FIdConst::PAYABLES :
				// 应付账款管理
				redirect(__ROOT__ . "/Home/Funds/payIndex");
				break;
			case FIdConst::BIZ_CONFIG :
				// 业务设置
				redirect(__ROOT__ . "/Home/BizConfig");
				break;
			case FIdConst::INVENTORY_TRANSFER :
				// 库间调拨
				redirect(__ROOT__ . "/Home/InvTransfer");
				break;
			case FIdConst::INVENTORY_CHECK :
				// 库存盘点
				redirect(__ROOT__ . "/Home/InvCheck");
				break;
			case FIdConst::PURCHASE_REJECTION :
				// 采购退货出库
				redirect(__ROOT__ . "/Home/PurchaseRej");
				break;
			case FIdConst::PURCHASE_ORDER :
				// 采购订单
				redirect(__ROOT__ . "/Home/Purchase/pobillIndex");
				break;
			case FIdConst::SALE_ORDER :
				// 销售订单
				redirect(__ROOT__ . "/Home/Sale/soIndex");
				break;
			case FIdConst::GOODS_BRAND :
				// 基础数据 - 商品品牌
				redirect(__ROOT__ . "/Home/Goods/brandIndex");
				break;
			case FIdConst::SCM_PURCHASE_CONTRACT :
				// 供应合同
				redirect(__ROOT__ . "/Home/PurchaseContract/pcbillIndex");
				break;
			case FIdConst::SCM_SPO :
				// 门店订货
				redirect(__ROOT__ . "/Home/SPO/index");
				break;
			case FIdConst::SCM_LOGISTICS_DISTRIBUTION :
				// 向门店发货
				redirect(__ROOT__ . "/Home/LD/index");
				break;
			case FIdConst::SCM_STORE_REVGOODS :
				// 门店收货
				redirect(__ROOT__ . "/Home/LD/srgIndex");
				break;
			case FIdConst::SCM_STORE_US_TEMPLATE_MANAGEMENT :
				// 门店盘点模板管理
				redirect(__ROOT__ . "/Home/US/templateIndex");
				break;
			case FIdConst::SCM_STORE_US :
				// 门店盘点
				redirect(__ROOT__ . "/Home/US/usbillIndex");
				break;
			default :
				redirect(__ROOT__ . "/Home");
		}
	}

	/**
	 * 返回生成主菜单的JSON数据
	 * 目前只能处理到生成三级菜单的情况
	 */
	public function mainMenuItems() {
		if (IS_POST) {
			$ms = new MainMenuService();
			
			$this->ajaxReturn($ms->mainMenuItems());
		}
	}

	/**
	 * 常用功能
	 */
	public function recentFid() {
		if (IS_POST) {
			$fidService = new FIdService();
			$data = $fidService->recentFid();
			
			$this->ajaxReturn($data);
		}
	}
}
