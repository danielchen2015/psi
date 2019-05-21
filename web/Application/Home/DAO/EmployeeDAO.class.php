<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019\5\8 0008
 * Time: 22:36
 */

namespace Home\DAO;

use Home\Common\FIdConst;
use Home\Common\DemoConst;

/**
 * 员工 DAO
 *
 * @author 陈涛
 */
class EmployeeDAO extends PSIBaseExDAO
{

    /**
     * 查询数据，用于员工自定义字段
     *
     * @param array $params
     * @return array
     */
    public function queryData($params)
    {
        $db = $this->db;

        $sql = "SELECT * FROM t_employee where 1=1";
        if (!empty($params)) {
            if (!empty($params['id'])) {
                $sql .= " and id = " . $params['id'];
            }
            if (!empty($params['emplyee_no'])) {
                $sql .= " and emplyee_no = '" . $params['id'] . "'";
            }
            if (!empty($params['name'])) {
                $sql .= " and name = '" . $params['name'] . "'";
            }
            if (!empty($params['id_card'])) {
                $sql .= " and id_card = '" . $params['id_card'] . "'";
            }
        }

        $sql .= " order by id desc limit 20";

        $dataList = $db->query($sql);

        $sql = "select count(*) as cnt from t_employee where 1=1";
        if (!empty($params)) {
            if (!empty($params['id'])) {
                $sql .= " and id = " . $params['id'];
            }
            if (!empty($params['emplyee_no'])) {
                $sql .= " and emplyee_no = '" . $params['id'] . "'";
            }
            if (!empty($params['name'])) {
                $sql .= " and name = '" . $params['name'] . "'";
            }
            if (!empty($params['id_card'])) {
                $sql .= " and id_card = '" . $params['id_card'] . "'";
            }
        }

        $data = $db->query($sql);
        $cnt = $data[0]["cnt"];

//        return [
//            "dataList" => $dataList,
//            "totalCount" => $cnt
//        ];
        return $dataList;

    }

    /**
     * @param $params
     * @return array
     * 编辑员工信息
     */
    public function editEmployee($params)
    {
        $db = $this->db;

        $id = $params["id"];
        $name = trim($params["name"]);
        $emplyee_no = trim($params["emplyee_no"]);
        $level = trim($params["level"]);
        $job = trim($params["job"]);
        $gender = trim($params["gender"]);
        $starttime = trim($params["starttime"]);
        $jobtime = trim($params["jobtime"]);
        $birthday = trim($params["birthday"]);
        $age = trim($params["age"]);
        $contactno = trim($params["contactno"]);
        $degree = trim($params["degree"]);
        $married = trim($params["married"]);
        $healthstart = trim($params["healthstart"]);
        $healthend = trim($params["healthend"]);
        $origin = trim($params["origin"]);
        $address = trim($params["address"]);
        $id_card = trim($params["id_card"]);
        $id_card_end = trim($params["id_card_end"]);
        $holds = trim($params["holds"]);
        $sales = trim($params["sales"]);
        $sales_start = trim($params["sales_start"]);
        $sales_end = trim($params["sales_end"]);
        $salary_start = trim($params["salary_start"]);
        $jixiao = trim($params["jixiao"]);
        $quanqin = trim($params["quanqin"]);
        $account_name = trim($params["account_name"]);
        $account_no = trim($params["account_no"]);
        $referee = trim($params["referee"]);
        $retire = trim($params["retire"]);
        $level_date = trim($params["level_date"]);
        $company_id = trim($params["company_id"]);
        $user_id = trim($params["user_id"]);

        $sql = "update t_employee
					set name = '%s', emplyee_no = '%s', level = '%s', job = '%s', gender = '%s', starttime = '%s', jobtime = '%s', birthday = '%s',
					    age = '%s', contactno = '%s', degree = '%s', married = '%s', healthstart = '%s', healthend = '%s', origin = '%s', address = '%s', id_card = '%s',
					    id_card_end = '%s', holds = '%s', sales = '%s', sales_start = '%s', sales_end = '%s', salary_start = '%s', jixiao = '%s', quanqin = '%s', account_name = '%s',
					    account_no = '%s', referee = '%s', retire = '%s', level_date = '%s', company_id = '%s', user_id = '%s'
					where id = %d ";
        $rc = $db->execute($sql, $name, $emplyee_no, $level, $job, $gender, $starttime, $jobtime, $birthday,
            $age, $contactno, $degree, $married, $healthstart, $healthend, $origin, $address, $id_card,
            $id_card_end, $holds, $sales, $sales_start, $sales_end, $salary_start, $jixiao, $quanqin, $account_name,
            $account_no, $referee, $retire, $level_date, $company_id, $user_id, $id);

        if ($rc === false) {
            return $this->sqlError(__METHOD__, __LINE__);
        }

    }

    /**
     * 新增
     */
    public function addEmployee(& $params)
    {
        $db = $this->db;

        $name = trim($params["name"]);
        $emplyee_no = trim($params["emplyee_no"]);
        $level = trim($params["level"]);
        $job = trim($params["job"]);
        $gender = trim($params["gender"]);
        $starttime = trim($params["starttime"]);
        $jobtime = trim($params["jobtime"]);
        $birthday = trim($params["birthday"]);
        $age = trim($params["age"]);
        $contactno = trim($params["contactno"]);
        $degree = trim($params["degree"]);
        $married = trim($params["married"]);
        $healthstart = trim($params["healthstart"]);
        $healthend = trim($params["healthend"]);
        $origin = trim($params["origin"]);
        $address = trim($params["address"]);
        $id_card = trim($params["id_card"]);
        $id_card_end = trim($params["id_card_end"]);
        $holds = trim($params["holds"]);
        $sales = trim($params["sales"]);
        $sales_start = trim($params["sales_start"]);
        $sales_end = trim($params["sales_end"]);
        $salary_start = trim($params["salary_start"]);
        $jixiao = trim($params["jixiao"]);
        $quanqin = trim($params["quanqin"]);
        $account_name = trim($params["account_name"]);
        $account_no = trim($params["account_no"]);
        $referee = trim($params["referee"]);
        $retire = trim($params["retire"]);
        $level_date = trim($params["level_date"]);
        $company_id = trim($params["company_id"]);
        $user_id = trim($params["user_id"]);

        $sql = "insert into t_employee (name, emplyee_no, level, job, gender, starttime, jobtime, birthday,
                    age, contactno, degree, married, healthstart, healthend, origin, address, id_card,
                    id_card_end, holds, sales, sales_start, sales_end, salary_start, jixiao, quanqin, account_name,
                    account_no, referee, retire, level_date, company_id, user_id,createtime)
					values ('%s', '%s', '%s', '%s', '%s', '%s', '%s','%s',
					'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
					'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
					'%s', '%s', '%s', '%s', '%s', '%s', '%s') ";
        $rc = $db->execute($sql, $name, $emplyee_no, $level, $job, $gender, $starttime, $jobtime, $birthday,
            $age, $contactno, $degree, $married, $healthstart, $healthend, $origin, $address, $id_card,
            $id_card_end, $holds, $sales, $sales_start, $sales_end, $salary_start, $jixiao, $quanqin, $account_name,
            $account_no, $referee, $retire, $level_date, $company_id, $user_id, date('Y-m-d H:i:s', time()));
        if ($rc === false) {
            return $this->sqlError(__METHOD__, __LINE__);
        }

        // 操作成功
        return null;
    }

    /**
     * 删除
     */
    public function deleteEmployee($id)
    {
        $db = $this->db;

        $sql = "delete from t_employee where id = $id";
        $rc = $db->execute($sql, $id);
        if ($rc === false) {
            return $this->sqlError(__METHOD__, __LINE__);
        }

        // 操作成功
        return null;
    }

}