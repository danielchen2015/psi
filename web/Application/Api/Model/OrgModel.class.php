<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019\3\4 0004
 * Time: 1:15
 */

namespace Api\Model;


use Think\Model;

class OrgModel extends Model
{

    public function api($data = '', $code = 200)
    {
        $result['data'] = $data;
        $result['code'] = $code;
        if ($code != 200) $result['msg'] = $data;

        return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param $goods_id
     * 根据商品编号，得到商品价格
     */
    public function getGoodPrice($goods_id)
    {

    }

    public function addSPOBill($data)
    {
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $itemTotalPrice = 0.00;

        $pcTemplateId = $data['template_id'];
        $userId = $data['user_id'];
        $items = $data['items'];

        $poBillId = $this->newId();

        $supplier_id = $Model->query("SELECT id FROM `t_supplier` LIMIT 1")[0]["id"];

        $companyId = $Model->query("SELECT org_id FROM `t_user` WHERE id = '" . $data['user_id'] . "' LIMIT 1")[0]["org_id"];

        $data_org = $Model->query("SELECT data_org FROM `t_user` WHERE id = '" . $data['user_id'] . "' LIMIT 1")[0]["data_org"];

        if ($companyId == "4D74E1E4-A129-11E4-9B6A-782BCBD7746B") {
            $poBillRef = $this->genNewBillRef($companyId);
        } else {
            $pre = $Model->query("SELECT org_code FROM t_org WHERE id = '" . $companyId . "'")[0]["org_code"];
            $mid = date("Ymd");
            $sufLength = 3;
            $suf = str_pad("", $sufLength, "0", STR_PAD_LEFT);
            $poBillRef = $pre . '-' . $mid . $suf . range(0, 10000, 1)[0] . '-DHD';
        }

//        echo $supplier_id . "<br/>";
//        echo $companyId . "<br/>";
//        echo $data_org . "<br/>";
//        echo $pre . "<br/>";
//        echo $poBillRef . "<br/>";
//        exit;

        for ($i = 0; $i <= count($items); $i++) {
            $goodsId = $items[$i]['goods_id'];
            $count = $items[$i]['goods_count'];
            $itemPrice = $Model->query("SELECT cost_price_checkups FROM `t_goods` WHERE id = '" . $goodsId . "'");
            $itemTotalPrice = $itemTotalPrice + floatval($count * $itemPrice[0]['cost_price_checkups']);
        }

        $sql = "insert into t_spo_bill(id, ref, bill_status, deal_date, biz_dt, org_id, biz_user_id,
					goods_money, input_user_id, supplier_id,
					bill_memo, date_created, data_org, company_id, pctemplate_id,is_today_order)
				values ('%s', '%s', %d, '%s', '%s', '%s','%s',
					%f, '%s', '%s',
					'%s', '%s', '%s', '%s','%s',%d)";
        $rc = $Model->execute($sql, $poBillId, $poBillRef, 0, date("Y-m-d H:i:s"), date("Y-m-d H:i:s"), $companyId, $userId,
            $itemTotalPrice, $userId, $supplier_id,
            '小程序订购', date("Y-m-d H:i:s"), $data_org, '4D74E1E4-A129-11E4-9B6A-782BCBD7746B', $pcTemplateId, 0);
        if ($rc === false) {
            return null;
        }

        // 明细表
        for ($j = 0; $j <= count($items); $j++) {

            $goodsId = $items[$j]['goods_id'];
            $goods_count = $items[$j]['goods_count'];
            $show_order = $items[$j]['show_order'];

            $unitId = $items[$j]['unit_id'];

            $itemPrice = $Model->query("SELECT cost_price_checkups FROM `t_goods` WHERE id = '" . $goodsId . "'");
            $goodsMoney = floatval($items[$j]['goods_count'] * $itemPrice[0]['cost_price_checkups']);

            $id = $this->newId();
//            echo $goodsId . "<br/>";
//            echo $unitId . "<br/>";
//            exit;
            $sql = "insert into t_spo_bill_detail(id, spobill_id, pctemplate_detail_id, show_order,  goods_id, unit_id, goods_count,
						goods_money, goods_price,  pw_count, left_count,
						date_created, data_org, company_id, memo)
					values ('%s', '%s', '%s', %d, '%s', '%s', %d,
					    %f, %f, %f, %d,
						'%s', '%s', '%s', '%s')";
            $rcc = $Model->execute($sql, $id, $poBillId, $pcTemplateId, $show_order, $goodsId, $unitId, $goods_count,
                $goodsMoney, $itemPrice[0]['cost_price_checkups'], $goods_count, 0,
                date("Y-m-d H:i:s"), $data_org, '4D74E1E4-A129-11E4-9B6A-782BCBD7746B', "小程序订购");
            if ($rcc === false) {
                return null;
            }

        }

        // 关联门店订货单和采购订单
        $sql = "insert into t_spo_po (spo_id, po_id) values ('%s', '%s')";
        $rc = $Model->execute($sql, $id, $poBillId);
        if ($rc === false) {
            return null;
        }

        // 操作成功
        return null;


    }

    /**
     * 生成全局唯一Id （UUID）
     *
     * @return string
     */
    public function newId()
    {
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表

        $data = $Model->query("select UUID() as uuid");

        return strtoupper($data[0]["uuid"]);
    }

    /**
     * 生成新的采购订单号
     *
     * @param string $companyId
     * @return string
     */
    public function genNewBillRef($companyId)
    {
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表

        $pre = $this->getPOBillRefPre($companyId);

        $mid = date("Ymd");

        $sql = "select ref from t_po_bill where ref like '%s' order by ref desc limit 1";
        $data = $Model->query($sql, $pre . $mid . "%");
        $sufLength = 3;
        $suf = str_pad("1", $sufLength, "0", STR_PAD_LEFT);
        if ($data) {
            $ref = $data[0]["ref"];
            $nextNumber = intval(substr($ref, strlen($pre . $mid))) + 1;
            $suf = str_pad($nextNumber, $sufLength, "0", STR_PAD_LEFT);
        }

        return $pre . $mid . $suf;
    }

    /**
     * 获得采购订单单号前缀
     *
     * @param string $companyId
     * @return string
     */
    public function getPOBillRefPre($companyId)
    {
        $result = "PO";

        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表

        $id = "9003-01";
        $sql = "select value from t_config
				where id = '%s' and company_id = '%s' ";
        $data = $Model->query($sql, $id, $companyId);
        if ($data) {
            $result = $data[0]["value"];

            if ($result == null || $result == "") {
                $result = "PO";
            }
        }

        return $result;
    }

}