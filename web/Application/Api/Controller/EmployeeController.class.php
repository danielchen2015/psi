<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019\3\9 0009
 * Time: 2:30
 */

namespace Api\Controller;

use Api\Model\OrgModel;
use Think\Controller\RestController;

class EmployeeController extends RestController
{
    /**
     * 企业所有门店、部门列表
     */
    public function companyList()
    {
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $list = $Model->query("SELECT id, NAME FROM `t_org`");
        $StoreOrderModel = new OrgModel();
        echo $StoreOrderModel->api($list);
        exit;
    }

    /**
     * 查询所有的记录
     */
    public function lists()
    {
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $list = $Model->query("SELECT * FROM t_employee ORDER BY id limit 0, 1000");
        $StoreOrderModel = new OrgModel();
        echo $StoreOrderModel->api($list);
        exit;
    }

    /**
     * 查询手机号、身份证号、姓名、门店
     * @param $mobile
     * @param $idcard
     * @param $name
     */
    public function search($mobile, $idcard, $name, $company_id)
    {
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $sql = "SELECT * FROM t_employee where 1=1";
        if (!empty($company_id)) {
            $sql = $sql . " and company_id = '" . $company_id . "'";
        }
        if (!empty($mobile)) {
            $sql = $sql . " and contactno like '%" . $mobile . "%'";
        }
        if (!empty($idcard)) {
            $sql = $sql . " and id_card like '%" . $idcard . "%'";
        }
        if (!empty($name)) {
            $sql = $sql . " and name like '%" . $name . "%'";
        }
        $sql = $sql . " ORDER BY id limit 0, 1000";
        $list = $Model->query($sql);
        $StoreOrderModel = new OrgModel();
        echo $StoreOrderModel->api($list);
        exit;
    }

}