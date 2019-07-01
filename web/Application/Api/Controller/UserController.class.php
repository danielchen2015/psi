<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019\5\21 0021
 * Time: 16:34
 */

namespace Api\Controller;

use Api\Model\OrgModel;
use http\Env\Request;
use Think\Controller\RestController;

class UserController extends RestController
{
    /**
     * @param $username
     * @param $password
     * 返回信息
     */
    public function Login($username, $password)
    {
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $User = $Model->query("SELECT id, NAME, org_id, org_code, py,gender,data_org, company_id, (SELECT NAME FROM t_org AS o WHERE o.id = u.org_id) AS org_name FROM t_user AS u WHERE enabled = 1 and login_name = '" . $username . "' and password = '" . md5($password) . "' ");
        $StoreOrderModel = new OrgModel();
        echo $StoreOrderModel->api($User);
        exit;
    }

}