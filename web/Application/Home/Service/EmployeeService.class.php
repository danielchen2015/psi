<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019\5\8 0008
 * Time: 22:29
 */

namespace Home\Service;

use Home\Common\DemoConst;
use Home\Common\FIdConst;
use Home\DAO\EmployeeDAO;

/**
 * 员工Service
 *
 * @author 陈涛
 */
class EmployeeService extends PSIBaseExService
{
    private $LOG_CATEGORY = "员工管理";

    //经理、领班、训练员、员工
    public $level = [
        '经理',
        '领班',
        '训练员',
        '员工'
    ];

    //前厅、后厨、司机、办公室人员、仓管、其他
    public $job = [
        '前厅',
        '后厨',
        '司机',
        '办公室人员',
        '仓管',
        '其他'
    ];

    //小学、初中、高中、中专、大专、本科
    public $degree = [
        '小学',
        '初中',
        '高中',
        '中专',
        '大专',
        '本科'
    ];

    //外地农村、外地城镇、本地农村、本地城镇
    public $holds = [
        '外地农村',
        '外地城镇',
        '本地农村',
        '本地城镇'
    ];


    /**
     * 查询员工数据域列表
     */
    public function queryData($queryData)
    {
        if ($this->isNotOnline()) {
            return $this->emptyResult();
        }

        $params = array(
            "id" => $queryData['id'],
            "emplyee_no" => $queryData['emplyee_no'],
            "name" => $queryData['name'],
            "id_card" => $queryData['id_card']
        );

        $dao = new EmployeeDAO($this->db());
//        $resData = $dao->queryData($params);
        return $dao->queryData($params);
    }

    /**
     * @param $queryData
     * @return array
     * 编辑员工信息
     */
    public function editEmployee($queryData)
    {
        $dao = new EmployeeDAO($this->db());
        return $dao->editEmployee($queryData);

    }

    /**
     * @param $queryData
     * @return array
     * 编辑员工信息
     */
    public function addEmployee($queryData)
    {
        $dao = new EmployeeDAO($this->db());
        return $dao->addEmployee($queryData);

    }

    /**
     * @param $id
     * 删除员工信息
     */
    public function deleteEmployee($id)
    {
        $dao = new EmployeeDAO($this->db());
        return $dao->deleteEmployee($id);
    }

}