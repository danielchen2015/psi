<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019\3\4 0004
 * Time: 0:22
 */

namespace Api\Controller;

use Api\Model\OrgModel;
use http\Env\Request;
use Think\Controller\RestController;

class OrgController extends RestController
{
    /**
     * 门店列表
     */
    public function storeList()
    {
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $list = $Model->query("SELECT id, NAME FROM `t_org` WHERE org_type = 200");
        $StoreOrderModel = new OrgModel();
        echo $StoreOrderModel->api($list);
        exit;
    }

    /**
     * 获取此门店的采购模板
     */
    public function templateList()
    {
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $list = $Model->query("SELECT id,full_name AS bill_memo FROM t_goods_category WHERE parent_id IS NULL");
        $StoreOrderModel = new OrgModel();
        echo $StoreOrderModel->api($list);
        exit;
    }

    /**
     * 获取此模板的详细物品列表
     * @param $id
     */
    public function templateDetails($id)
    {
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $list = $Model->query("SELECT id AS goods_id, g.name, g.unit_id, (SELECT NAME FROM t_goods_unit AS u WHERE u.id = g.unit_id) AS unit_name, use_qc AS show_order, g.company_id, 0 as goods_count FROM t_goods AS g WHERE category_id = '" . $id . "' ORDER BY g.code");
        $StoreOrderModel = new OrgModel();
        echo $StoreOrderModel->api($list);
        exit;
    }

    /**
     * 获取此搜索物品列表
     * @param $py
     */
    public function goodsList($py)
    {
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $list = $Model->query("SELECT id AS goods_id, NAME, unit_id, (SELECT NAME FROM t_goods_unit AS u WHERE u.id = g.unit_id) AS unit_name, use_qc AS show_order, company_id, 0 as goods_count FROM t_goods AS g WHERE g.py LIKE '%" . $py . "%' limit 10");
        $StoreOrderModel = new OrgModel();
        echo $StoreOrderModel->api($list);
        exit;
    }

    /**
     * 获取物品详情
     * @param $id
     */
    public function goodsDetails($goodname)
    {
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $list = $Model->query("SELECT id AS goods_id, g.name, g.unit_id, (SELECT NAME FROM t_goods_unit AS u WHERE u.id = g.unit_id) AS unit_name, use_qc AS show_order, company_id, 0 as goods_count FROM t_goods AS g WHERE g.name = '" . $goodname . "'");
        $StoreOrderModel = new OrgModel();
        echo $StoreOrderModel->api($list);
        exit;
    }

    /**
     * 获取供应商列表
     */
    public function supplierList()
    {
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $list = $Model->query("SELECT id,NAME,CODE FROM t_supplier");
        $StoreOrderModel = new OrgModel();
        echo $StoreOrderModel->api($list);
        exit;
    }

    public function pdtemplateList()
    {
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $list = $Model->query("SELECT id,ref,input_user_id,bill_status,data_org,company_id FROM t_us_template ORDER BY date_created");
        $StoreOrderModel = new OrgModel();
        echo $StoreOrderModel->api($list);
        exit;
    }

    /**
     * 提交订单, 传JSON格式
     * ex:
     * {
     *        "template_id": "BFA909BC-4100-11E9-88F5-00FF8088E341",
     *        "company_id": "4D74E1E4-A129-11E4-9B6A-782BCBD7746B",
     *        "user_id":1,
     *        "items": [{
     *                "goods_id": 1,
     *                "unit_id": 1,
     *                "goods_count": 2.00,
     *                "show_order": 0
     *            },
     *            {
     *                "goods_id": 2,
     *                "unit_id": 2,
     *                "goods_count": 3.00,
     *                "show_order": 1
     *            },
     *            {
     *                "goods_id": 3,
     *                "unit_id": 3,
     *                "goods_count": 4.00,
     *                "show_order": 2
     *            }
     *        ]
     * }
     * @param $data
     */
    public function orderAdd()
    {
        //$data = $GLOBALS['HTTP_RAW_POST_DATA'];
        $data = I('post.data');
        $data = str_replace('&quot;', '"', $data);

        $postData = json_decode($data, true);

        $StoreOrderModel = new OrgModel();
        $StoreOrderModel->addSPOBill($postData);
        echo $StoreOrderModel->api('订货成功！', 200);
        exit;

    }

    /**
     * 查询订单列表
     * @param $org_id
     * @param $supplier_id
     * @param $from_date
     * @param $to_date
     * @param $user_id
     */
    public function orderList($org_id, $supplier_id, $from_date, $to_date, $user_id)
    {
        $from_date = date("Y-m-d ", $from_date);
        $to_date = date("Y-m-d ", $to_date);
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $sql = "SELECT b.id,b.ref,(SELECT NAME FROM t_org AS o WHERE o.id = b.org_id) AS org_name, (SELECT NAME FROM t_supplier AS s WHERE s.id = b.supplier_id) AS supplier_name,b.date_created AS gettime,u.name AS getuser,(SELECT NAME FROM t_warehouse AS w WHERE w.id = l.to_warehouse_id) AS getorgname,l.date_created AS fromtime,(SELECT NAME FROM t_user AS us WHERE us.id = l.input_user_id) AS fromuser,(SELECT NAME FROM t_warehouse AS w WHERE w.id = l.from_warehouse_id) AS fromorgname FROM t_spo_bill AS b LEFT JOIN t_user AS u ON b.input_user_id = u.id LEFT JOIN t_ld_bill AS l ON b.id = l.spobill_id where 1=1";
        if (!empty($org_id)) {
            $sql = $sql . " and b.org_id = '" . $org_id . "'";
        }
        if (!empty($supplier_id)) {
            $sql = $sql . " and b.supplier_id = '" . $supplier_id . "'";
        }
        if (!empty($from_date) && !empty($to_date)) {
            $sql = $sql . " and b.date_created BETWEEN '" . $from_date . " 00:00:00' AND '" . $to_date . " 23:59:59'";
        }
        if (!empty($user_id)) {
            $sql = $sql . " and b.input_user_id = '" . $user_id . "'";
        }
        $sql = $sql . " order by b.date_created desc";

        $list = $Model->query($sql);
        $StoreOrderModel = new OrgModel();
        echo $StoreOrderModel->api($list);
        exit;
    }

    /**
     * 根据订单查询订单详情
     * @param $order_id
     */
    public function orderDetails($orderId)
    {
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $sql = "SELECT DISTINCT d.id, d.goods_id, b.goods_name, s.goods_count, b.goods_count AS ld_goods_count,(s.goods_count - b.goods_count) AS cha_count, b.show_order FROM t_spo_bill_detail AS d LEFT JOIN v_ld_bill_detail AS b ON d.spobill_id = b.spobill_id LEFT JOIN t_spo_bill_detail AS s ON d.spobill_id = s.spobill_id where d.goods_id = b.goods_id AND d.goods_id = s.goods_id";
        if (!empty($orderId)) {
            $sql = $sql . " and d.spobill_id = '" . $orderId . "'";
        }
        $sql = $sql . " order by b.show_order";

        $list = $Model->query($sql);
        $StoreOrderModel = new OrgModel();
        echo $StoreOrderModel->api($list);
        exit;
    }

    /**
     * 提交入库订单, 传JSON格式
     * ex:
     * {
     *        "id": "4D74E1E4-A129-11E4-9B6A-782BCBD7746B",
     *        "user_id":1,
     *        "items": [{
     *                "goods_id": 1,
     *                "unit_id": 1,
     *                "goods_count": 2.00,
     *                "show_order": 0
     *            },
     *            {
     *                "goods_id": 2,
     *                "unit_id": 2,
     *                "goods_count": 3.00,
     *                "show_order": 1
     *            },
     *            {
     *                "goods_id": 3,
     *                "unit_id": 3,
     *                "goods_count": 4.00,
     *                "show_order": 2
     *            }
     *        ]
     * }
     * @param $data
     */
    public function orderIn()
    {
        //$data = $GLOBALS['HTTP_RAW_POST_DATA'];
        $data = I('post.data');
        $data = str_replace('&quot;', '"', $data);

        $postData = json_decode($data, true);

        $StoreOrderModel = new OrgModel();
        //第一步：更新物流单
        $updateMsg = $StoreOrderModel->updateLDBillForSRG($postData);
        if ($updateMsg == "") {
            //第二步：门店收货
            $commitMsg = $StoreOrderModel->commitLDBillForSRG($postData);
            if ($commitMsg == "") {
                //第三步:门店退货提交入库
//                $commitRej = $StoreOrderModel->commitLDBillRej($postData);
//                if ($commitRej == "") {
//                    echo $StoreOrderModel->api('入库成功！', 200);
//                    exit;
//                } else {
//                    echo $StoreOrderModel->api('入库失败,' . $commitRej, 400);
//                    exit;
//                }
                echo $StoreOrderModel->api('入库成功！', 200);
                exit;
            } else {
                echo $StoreOrderModel->api('入库失败,' . $commitMsg, 400);
                exit;
            }
        } else {
            echo $StoreOrderModel->api('入库失败,' . $updateMsg, 400);
            exit;
        }

    }


}