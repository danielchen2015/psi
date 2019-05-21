<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019\5\8 0008
 * Time: 22:26
 */

namespace Home\Controller;

use Home\Common\FIdConst;
use Home\Service\BizConfigService;
use Home\Service\EmployeeService;

/**
 * 员工管理Controller
 *
 * @author 陈涛
 *
 */
class EmployeeController extends PSIBaseController
{
    /**
     * 员工管理-主页面
     */
    public function index()
    {
        $params = array();

        $employee = new EmployeeService();

        $this->initVar();
        $this->assign("title", "员工管理");
        $this->assign("pAddUser", 1);
        $this->assign("pEditUser", 1);
        $this->assign("pDeleteUser", 1);

        if (IS_POST) {
            $params = array(
                "emplyee_no" => I("post.emplyee_no"),
                "name" => I("post.name"),
                "id_card" => I("post.id_card")
            );
        }

        $this->assign("data", $employee->queryData($params));

        $this->display();

    }

    /**
     * 获得员工列表
     */
    public function lists()
    {
        if (IS_POST) {
            $us = new EmployeeService();
            $params = array(
                "emplyee_no" => I("post.emplyee_no"),
                "name" => I("post.name"),
                "id_card" => I("post.id_card")
            );

            $this->ajaxReturn($us->queryData($params));
        }
    }

    /**
     * 编辑员工信息
     */
    public function edit()
    {
        $this->initVar();
        $id = I('id');

        $params = array(
            "id" => $id
        );

        $employee = new EmployeeService();

        $this->assign("profile", $employee->queryData($params)[0]);
        $this->assign('level', $employee->level);
        $this->assign('job', $employee->job);
        $this->assign('degree', $employee->degree);
        $this->assign('holds', $employee->holds);

        if (IS_POST) {
            //保存
            $data = I('post.');
            if (!empty($data['id'])) {
                $employee->editEmployee($data);
                $this->redirect('../Home/Employee/index');
            } else {
                $employee->addEmployee($data);
            }
        }

        $this->display();
    }

    /**
     * 添加
     */
    public function add()
    {

        $this->initVar();

        $employee = new EmployeeService();

        $this->assign('level', $employee->level);
        $this->assign('job', $employee->job);
        $this->assign('degree', $employee->degree);
        $this->assign('holds', $employee->holds);

        if (IS_POST) {
            //保存
            $data = I('post.');
            $employee->addEmployee($data);
            $this->redirect('../Home/Employee/index');
        }
        $this->display('edit');
    }

    /**
     * 删除
     */
    public function delete()
    {

        $id = I('employeeId');
        if (IS_POST) {
            //删除
            $employee = new EmployeeService();
            $employee->deleteEmployee($id);
            $this->redirect('../Home/Employee/index');
        }

    }

}