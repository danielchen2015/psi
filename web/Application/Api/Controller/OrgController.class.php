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
        $list = $Model->query("SELECT id, bill_memo FROM t_pc_template WHERE bill_status = 1000");
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
        $list = $Model->query("SELECT t.goods_id, g.name, t.unit_id, (SELECT NAME FROM t_goods_unit AS u WHERE u.id = t.unit_id) AS unit_name, t.show_order, t.company_id FROM t_pc_template_detail AS t LEFT JOIN t_goods AS g ON g.id = t.goods_id WHERE pctemplate_id = '" . $id . "' ORDER BY show_order");
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
    public function orderAdd($data)
    {
        //$data = $GLOBALS['HTTP_RAW_POST_DATA'];

        $postData = json_decode($data, true);

        $StoreOrderModel = new OrgModel();
        $StoreOrderModel->addSPOBill($postData);
        echo $StoreOrderModel->api('订货成功！', 200);
        exit;

    }


}