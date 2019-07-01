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
     * 入库
     * @param $data
     */
    public function updateLDBillForSRG($data)
    {
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表

        $orderId = $data['orderId'];
        $companyId = $data['company_id'];

        $b = $this->getLDBillById($data['orderId']);
        if (!$b) {
            return "要编辑的物流单不存在";
        }
        $billStatus = $b["billStatus"];
        if ($billStatus > 1000) {
            return "当前物流单已经收货入库，不能再编辑";
        }

        $ref = $b["ref"];

        $data_org = $Model->query("SELECT data_org FROM `t_user` WHERE id = '" . $data['user_id'] . "' LIMIT 1")[0]["data_org"];

        // 发货明细
        $items = $data['items'];

        $dataScale = $this->getGoodsCountDecNumber($companyId);
        $fmt = "decimal(19, " . $dataScale . ")";

        $ref = $this->genNewBillRef($companyId);

        // 明细表
        foreach ($items as $i => $item) {
            $detailId = $item["id"];
            // 发货数量
            $revGoodsCount = $item["ld_goods_count"];
            // 转换率
            $sql = "select factor from t_ld_bill_detail where id = '%s' ";
            $factordata = $Model->query($sql, $detailId);

            $factor = $factordata[0]["factor"];

            $sql = "select convert(goods_count, $fmt) as goods_count
					from t_ld_bill_detail where spobilldetail_id = '%s' ";
            $data = $Model->query($sql, $detailId);
            if (!$data) {
                continue;
            }
            $goodsCount = $data[0]["goods_count"];
            if ($revGoodsCount > $goodsCount) {
                $recordIndex = $i + 1;
                return "第{$recordIndex}条记录收货数量超过了发货数量";
            }

            $rejGoodsCount = $goodsCount - $revGoodsCount;
            $revSkuGoodsCount = $revGoodsCount * $factor;
            $rejSkuGoodsCount = $rejGoodsCount * $factor;

            $sql = "update t_ld_bill_detail
						set rev_edit_flag = 1, rev_factor_type = factor_type, rej_factor_type = factor_type,
							rev_factor = %f, rej_factor = %f, rev_goods_count = convert(%f, $fmt),
							rev_sku_goods_count = convert(%f, $fmt), 
							rej_goods_count = convert(%f, $fmt),
							rej_sku_goods_count = convert(%f, $fmt)
					where spobilldetail_id = '%s' ";

            $rc = $Model->execute($sql, $factor, $factor, $revGoodsCount, $revSkuGoodsCount,
                $rejGoodsCount, $rejSkuGoodsCount, $detailId);
            if ($rc === false) {
                return "更新物流单失败";
            }
        }

        return "";

    }

    public function addSPOBill($data)
    {
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $itemTotalPrice = 0.00;

        $pcTemplateId = $data['template_id'];
        $userId = $data['user_id'];
        $items = $data['items'];
        $memo = $data['memo'];

        $poBillId = $this->newId();

        $supplier_id = $Model->query("SELECT id FROM `t_supplier` LIMIT 1")[0]["id"];

        $companyId = $data['company_id'];//$Model->query("SELECT org_id FROM `t_user` WHERE id = '" . $data['user_id'] . "' LIMIT 1")[0]["org_id"];

        $data_org = $Model->query("SELECT data_org FROM `t_user` WHERE id = '" . $data['user_id'] . "' LIMIT 1")[0]["data_org"];

        if ($companyId == "4D74E1E4-A129-11E4-9B6A-782BCBD7746B") {
            $poBillRef = $this->genNewBillRef($companyId);
        } else {
            $pre = $Model->query("SELECT org_code FROM t_org WHERE id = '" . $companyId . "'")[0]["org_code"];
            $mid = date("Ymd");
//            $sufLength = 3;
//            $suf = str_pad("", $sufLength, "0", STR_PAD_LEFT);
//            $poBillRef = $pre . '-' . $mid . $suf . range(0, 10000, 1)[0] . '-DHD';
            $sql = "select ref from t_spo_bill where ref like '%s' order by date_created desc limit 1";
            $data = $Model->query($sql, $pre . '-' . $mid . "%");
            $sufLength = 4;
            $suf = str_pad("1", $sufLength, "0", STR_PAD_LEFT);
            if (!empty($data[0]["ref"])) {
                $ref = $data[0]["ref"];
                $nextNumber = intval(substr($ref, strlen($pre . '-' . $mid))) + 1;
                $suf = str_pad($nextNumber, $sufLength, "0", STR_PAD_LEFT);
            }

            $poBillRef = $pre . '-' . $mid . $suf . '-DHD';

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
            $memo, date("Y-m-d H:i:s"), $data_org, '4D74E1E4-A129-11E4-9B6A-782BCBD7746B', $pcTemplateId, 0);
        if ($rc === false) {
            return null;
        }

        // 明细表
        for ($j = 0; $j <= count($items); $j++) {

            $goodsId = $items[$j]['goods_id'];
            $goods_count = $items[$j]['goods_count'];
            $show_order = $items[$j]['show_order'];

            $unitId = $items[$j]['unit_id'];

            if ($goods_count > 0) {
                $itemPrice = $Model->query("SELECT cost_price_checkups FROM `t_goods` WHERE id = '" . $goodsId . "'");
                $goodsMoney = floatval($items[$j]['goods_count'] * $itemPrice[0]['cost_price_checkups']);

                $id = $this->newId();

                $sql = "insert into t_spo_bill_detail(id, spobill_id, pctemplate_detail_id, show_order,  goods_id, unit_id, goods_count,
						goods_money, goods_price,  pw_count, left_count,
						date_created, data_org, company_id, memo)
					values ('%s', '%s', '%s', %d, '%s', '%s', %d,
					    %f, %f, %f, %d,
						'%s', '%s', '%s', '%s')";
                $rcc = $Model->execute($sql, $id, $poBillId, $pcTemplateId, $show_order, $goodsId, $unitId, $goods_count,
                    $goodsMoney, $itemPrice[0]['cost_price_checkups'], 0, $goods_count,
                    date("Y-m-d H:i:s"), $data_org, '4D74E1E4-A129-11E4-9B6A-782BCBD7746B', "");
                if ($rcc === false) {
                    return null;
                }
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
     * 提交入库 - 门店收货
     *
     * @param array $params
     */
    public function commitLDBillForSRG($data)
    {
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表

        $orderId = $data['orderId'];
        $companyId = $data['company_id'];

        // 物流单主表id
        $sql = "select id, ref, bill_status, to_warehouse_id, biz_user_id, biz_dt, spobill_id 
				from t_ld_bill where spobill_id = '%s' ";
        $data = $Model->query($sql, $orderId);
        if (!$data) {
            return "要提交入库的物流单不存在";
        }
        $v = $data[0];
        $ref = $v["ref"];
        $billStatus = $v["bill_status"];
        $bizUserId = $v["biz_user_id"];
        $bizDT = $v["biz_dt"];
        $id = $v["id"];
        if ($billStatus > 1000) {
            return "物流单[单号：{$ref}]已经提交入库了，不能再次提交";
        }
        // 入库仓库id
        $warehouseId = $v["to_warehouse_id"];
        // 门店订货单id
        $spoBillId = $v["spobill_id"];

        // 判断是否录入过收货数据了
        $sql = "select count(*) as cnt from t_ld_bill_detail where rev_edit_flag = 0
					and ldbill_id = '%s' ";
        $data = $Model->query($sql, $id);
        $cnt = $data[0]["cnt"];
        if ($cnt > 0) {
            return "当前物流单还没有录入过收货数据，不能提交入库";
        }

        $dataScale = $this->getGoodsCountDecNumber($companyId);
        $fmt = "decimal(19, " . $dataScale . ")";

        // 物资明细
        $sql = "select id, goods_id, qc_begin_dt, qc_end_dt, qc_days, qc_sn,
					convert(rev_sku_goods_count, $fmt) as rev_sku_goods_count,
					inv_goods_price, inv_goods_money,
					convert(goods_count, $fmt) as goods_count,
					convert(rev_goods_count, $fmt) as rev_goods_count,
					convert(rej_goods_count, $fmt) as rej_goods_count,
					factor, spobilldetail_id
				from t_ld_bill_detail
				where ldbill_id = '%s'
				order by show_order";
        $items = $Model->query($sql, $id);

        $refType = "物流单门店收货入库";
        foreach ($items as $i => $v) {
            $detailId = $v["id"];
            $spoBillDetailId = $v["spobilldetail_id"];
            $goodsId = $v["goods_id"];

            $qcBeginDT = $v["qc_begin_dt"];
            $qcEndDT = $v["qc_end_dt"];
            $qcDays = $v["qc_days"];
            $qcSN = $v["qc_sn"];
            $revSkuGodsCount = $v["rev_goods_count"];
            $invGoodsPrice = $v["rev_goods_price"];
            $invGoodsMoney = $v["rev_goods_money"];
            $goodsCount = $v["goods_count"];
            $revGoodsCount = $v["rev_goods_count"];
            $factor = $v["factor"];
            $rejGoodsCount = $v["rej_goods_count"];

            if ($goodsCount < $revGoodsCount) {
                $recordIndex = $i + 1;
                return "第{$recordIndex}条记录中收货数量大于发货数量";
            }
            $goodsMoney = $invGoodsMoney;
            if ($revGoodsCount < $goodsCount) {
                // 有退货
                $goodsMoney = $invGoodsPrice * $revSkuGodsCount;
            }

            if ($goodsMoney > $invGoodsMoney) {
                $goodsMoney = $invGoodsMoney;
            }
            $goodsPrice = $goodsMoney / $revSkuGodsCount;
            $rc = $this->inAction($companyId, $warehouseId, $goodsId, $qcBeginDT, $qcDays,
                $qcEndDT, $qcSN, $revSkuGodsCount, $goodsMoney, $goodsPrice, $bizDT, $bizUserId,
                $ref, $refType);
            if ($rc) {
                return $rc;
            }

            // 更新退货的存货金额和单价
            $rejMoney = $invGoodsMoney - $goodsMoney;
            $rejPrice = $invGoodsPrice;
            if ($rejGoodsCount > 0) {
                $rejPrice = $rejMoney / ($rejGoodsCount * $factor);
            }

            $sql = "update t_ld_bill_detail
						set rev_goods_price = %f, rev_goods_money = %f,
							rej_goods_price = %f, rej_goods_money = %f
					where id = '%s' ";
            $rc = $Model->execute($sql, $goodsPrice, $goodsMoney, $rejPrice, $rejMoney, $detailId);
            if ($rc === false) {
                return "";
            }

            // 更新门店订货单明细中的收货信息
            $sql = "select convert(goods_count, $fmt) as goods_count, 
						convert(pw_count, $fmt) as pw_count, 
						convert(left_count, $fmt) as left_count
					from t_spo_bill_detail
					where id = '%s' ";
            $data = $Model->query($sql, $spoBillDetailId);
            if ($data) {
                $v = $data[0];
                $orderTotalCount = $v["goods_count"];
                $orderPWCount = $v["pw_count"];
                $orderLeftCount = $v["left_count"];

                $orderPWCount += $revGoodsCount;
                $orderLeftCount = $orderTotalCount - $orderPWCount;
                $sql = "update t_spo_bill_detail
						set pw_count = convert(%f, $fmt),
							left_count = convert(%f, $fmt)
						where id = '%s' ";
                $rc = $Model->execute($sql, $orderPWCount, $orderLeftCount, $spoBillDetailId);
                if ($rc === false) {
                    return "";
                }
            }
        }

        // 更新门店订货单单据状态
        $sql = "select count(*) as cnt from t_spo_bill_detail
				where spobill_id = '%s' and convert(left_count, $fmt) > 0 ";
        $data = $Model->query($sql, $spoBillId);
        $cnt = $data[0]["cnt"];
        $billStatus = 1000;
        if ($cnt > 0) {
            // 部分入库
            $billStatus = 2000;
        } else {
            // 全部入库
            $billStatus = 3000;
        }
        $sql = "update t_spo_bill
				set bill_status = %d
				where id = '%s' ";
        $rc = $Model->execute($sql, $billStatus, $spoBillId);
        if ($rc === false) {
            return "";
        }

        // 更新物流单单据状态
        $sql = "select count(*) as cnt 
				from t_ld_bill_detail
				where ldbill_id = '%s' and rej_goods_count > 0";
        $data = $Model->query($sql, $id);
        // 是否有退货
        $hasRej = $data[0]["cnt"] > 0;

        $billStatus = 2000; // 全部收货
        if ($hasRej) {
            $billStatus = 3000; // 部分收货并退货待入库
        }
        $sql = "update t_ld_bill set bill_status = %d where id = '%s' ";
        $rc = $Model->execute($sql, $billStatus, $id);
        if ($rc === false) {
            return "";
        }

        // 操作成功
        $params["ref"] = $ref;
        return "";
    }

    /**
     * 门店退货提交入库
     *
     * @param array $params
     */
    public function commitLDBillRej($data)
    {
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表

        $orderId = $data['orderId'];
        $companyId = $data['company_id'];

        // 物流单主表id
        $sql = "select id, ref, bill_status, to_warehouse_id, biz_user_id, biz_dt, spobill_id 
				from t_ld_bill where spobill_id = '%s' ";
        $data = $Model->query($sql, $orderId);
        if (!$data) {
            return "要提交入库的物流单不存在";
        }
        $v = $data[0];
        $ref = $v["ref"];
        $billStatus = $v["bill_status"];
        $bizUserId = $v["biz_user_id"];
        $bizDT = $v["biz_dt"];
        $id = $v["id"];
        if ($billStatus > 3000) {
            return "物流单[单号：{$ref}]已经提交入库了，不能再次提交";
        }
        // 入库仓库id
        $warehouseId = $v["from_warehouse_id"];

        $dataScale = $this->getGoodsCountDecNumber($companyId);
        $fmt = "decimal(19, " . $dataScale . ")";

        // 物资明细
        $sql = "select id, goods_id, qc_begin_dt, qc_end_dt, qc_days, qc_sn,
					convert(rej_sku_goods_count, $fmt) as rej_sku_goods_count,
					rej_goods_price, rej_goods_money,
					factor
				from t_ld_bill_detail
				where ldbill_id = '%s'
				order by show_order";
        $items = $Model->query($sql, $id);

        $refType = "物流单门店退货入库";
        foreach ($items as $i => $v) {
            $detailId = $v["id"];
            $goodsId = $v["goods_id"];

            $qcBeginDT = $v["qc_begin_dt"];
            $qcEndDT = $v["qc_end_dt"];
            $qcDays = $v["qc_days"];
            $qcSN = $v["qc_sn"];
            $rejSkuGoodsCount = $v["rej_sku_goods_count"];
            $rejGoodsPrice = $v["rej_goods_price"];
            $rejGoodsMoney = $v["rej_goods_money"];
            $factor = $v["factor"];

            $goodsMoney = $rejGoodsMoney;
            $goodsPrice = $goodsMoney / $rejSkuGoodsCount;
            $rc = $this->inAction($companyId, $warehouseId, $goodsId, $qcBeginDT, $qcDays,
                $qcEndDT, $qcSN, $rejSkuGoodsCount, $goodsMoney, $goodsPrice, $bizDT, $bizUserId,
                $ref, $refType);
            if ($rc) {
                return $rc;
            }
        }

        // 更新单据状态: 4000 - 退货已入库
        $sql = "update t_ld_bill set bill_status = 4000 where id = '%s' ";
        $rc = $Model->execute($sql, $id);
        if ($rc === false) {
            return "";
        }

        // 操作成功
        $params["ref"] = $ref;
        return "";
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

    /**
     * @param $id
     * @return array|null
     */
    public function getLDBillById($id)
    {

        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表

        $sql = "select ref, bill_status from t_ld_bill where spobill_id = '%s' ";
        $data = $Model->query($sql, $id);
        if (!$data) {
            return null;
        } else {
            $v = $data[0];
            return [
                "ref" => $v["ref"],
                "billStatus" => $v["bill_status"]
            ];
        }
    }

    /**
     * 获得商品数量小数位数
     *
     * @param string $companyId
     * @return int
     */
    public function getGoodsCountDecNumber($companyId)
    {
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表

        $result = "0";

        $id = "9002-03";
        $sql = "select value from t_config
				where id = '%s' and company_id = '%s' ";
        $data = $Model->query($sql, $id, $companyId);
        if ($data) {
            $result = $data[0]["value"];

            if ($result == null || $result == "") {
                $result = "1";
            }
        }

        $r = (int)$result;

        // 商品数量小数位数范围：0~8位
        if ($r < 0) {
            $r = 0;
        }
        if ($r > 8) {
            $r = 8;
        }

        return $r;
    }

    /**
     * 判断日期是否是正确的Y-m-d格式
     *
     * @param string $date
     * @return boolean true: 是正确的格式
     */
    protected function dateIsValid($date)
    {
        $dt = strtotime($date);
        if (!$dt) {
            return false;
        }

        return date("Y-m-d", $dt) == $date;
    }

    /**
     * 入库
     */
    public function inAction($companyId, $warehouseId, $goodsId, $qcBeginDT, $qcDays, $qcEndDT, $qcSN, $goodsCount, $goodsMoney, $goodsPrice, $bizDT, $bizUserId, $ref, $refType)
    {
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表

        $dataScale = $this->getGoodsCountDecNumber($companyId);
        $fmt = "decimal(19, " . $dataScale . ")";

        // 总账
        $balanceCount = 0;
        $balanceMoney = 0;
        $balancePrice = (float)0;
        $sql = "select convert(in_count, $fmt) as in_count, in_money, balance_count, balance_money
				from t_inventory
				where warehouse_id = '%s' and goods_id = '%s' ";
        $data = $Model->query($sql, $warehouseId, $goodsId);
        if ($data) {
            // 之前已经发生过入库业务了
            $inCount = $data[0]["in_count"];
            $inMoney = floatval($data[0]["in_money"]);
            $balanceCount = $data[0]["balance_count"];
            $balanceMoney = floatval($data[0]["balance_money"]);

            $inCount += $goodsCount;
            $inMoney += $goodsMoney;
            $inPrice = $inMoney / $inCount;

            $balanceCount += $goodsCount;
            $balanceMoney += $goodsMoney;
            $balancePrice = $balanceMoney / $balanceCount;

            $sql = "update t_inventory
					set in_count = convert(%f, $fmt), in_price = %f, in_money = %f,
						balance_count = convert(%f, $fmt), balance_price = %f, balance_money = %f
					where warehouse_id = '%s' and goods_id = '%s' ";
            $rc = $Model->execute($sql, $inCount, $inPrice, $inMoney, $balanceCount, $balancePrice,
                $balanceMoney, $warehouseId, $goodsId);
            if ($rc === false) {
                return "";
            }
        } else {
            // 首次入库
            $inCount = $goodsCount;
            $inMoney = $goodsMoney;
            $inPrice = $inMoney / $inCount;
            $balanceCount = $goodsCount;
            $balanceMoney = $goodsMoney;
            $balancePrice = $balanceMoney / $balanceCount;

            $sql = "insert into t_inventory (in_count, in_price, in_money, balance_count,
						balance_price, balance_money, warehouse_id, goods_id)
					values (convert(%f, $fmt), %f, %f, convert(%f, $fmt), %f, %f, '%s', '%s')";
            $rc = $Model->execute($sql, $inCount, $inPrice, $inMoney, $balanceCount, $balancePrice,
                $balanceMoney, $warehouseId, $goodsId);
            if ($rc === false) {
                return "";
            }
        }

        // 明细账
        $sql = "insert into t_inventory_detail (in_count, in_price, in_money, balance_count,
					balance_price, balance_money, warehouse_id, goods_id, biz_date,
					biz_user_id, date_created, ref_number, ref_type)
				values (convert(%f, $fmt), %f, %f, convert(%f, $fmt), %f, %f, '%s', '%s', '%s', '%s',
					now(), '%s', '%s')";
        $rc = $Model->execute($sql, $goodsCount, $goodsPrice, $goodsMoney, $balanceCount,
            $balancePrice, $balanceMoney, $warehouseId, $goodsId, $bizDT, $bizUserId, $ref,
            $refType);
        if ($rc === false) {
            return "";
        }

        // 总账 -SKU细化到保质期
        $balanceCount = 0;
        $balanceMoney = 0;
        $balancePrice = (float)0;

        if ($qcDays < 0) {
            $qcDays = 0;
        }
        if (!$this->dateIsValid($qcBeginDT)) {
            $qcBeginDT = "";
        }

        if (!$qcBeginDT) {
            // 没有保质期的SKU在数据库中存储为1970-01-01
            $qcBeginDT = "1970-01-01";
            $qcDays = 0;
            $qcEndDT = $qcBeginDT;
        } else {
            $qcEndDT = date("Y-m-d", strtotime($qcBeginDT . " +$qcDays day"));
        }

        // 库存总账
        $sql = "select convert(in_count, $fmt) as in_count, in_money, balance_count, balance_money
				from t_inventory_fifo
				where warehouse_id = '%s' and goods_id = '%s'
					and qc_begin_dt = '%s' and qc_days = %d and qc_sn = '%s' ";
        $data = $Model->query($sql, $warehouseId, $goodsId, $qcBeginDT, $qcDays, $qcSN);
        if ($data) {
            // 已经发生过入库业务
            $inCount = $data[0]["in_count"];
            $inMoney = $data[0]["in_money"];
            $balanceCount = $data[0]["balance_count"];
            $balanceMoney = $data[0]["balance_money"];

            $inCount += $goodsCount;
            $inMoney += $goodsMoney;
            $inPrice = $inMoney / $inCount;

            $balanceCount += $goodsCount;
            $balanceMoney += $goodsMoney;
            $balancePrice = $balanceMoney / $balanceCount;

            $sql = "update t_inventory_fifo
					set in_count = convert(%f, $fmt), in_price = %f, in_money = %f,
						balance_count = convert(%f, $fmt), balance_price = %f, balance_money = %f
					where warehouse_id = '%s' and goods_id = '%s'
						and qc_begin_dt = '%s' and qc_days = %d and qc_sn = '%s' ";
            $rc = $Model->execute($sql, $inCount, $inPrice, $inMoney, $balanceCount, $balancePrice,
                $balanceMoney, $warehouseId, $goodsId, $qcBeginDT, $qcDays, $qcSN);
            if ($rc === false) {
                return "";
            }
        } else {
            // 首次入库
            $inCount = $goodsCount;
            $inMoney = $goodsMoney;
            $inPrice = $inMoney / $inCount;
            $balanceCount = $goodsCount;
            $balanceMoney = $goodsMoney;
            $balancePrice = $balanceMoney / $balanceCount;

            $sql = "insert into t_inventory_fifo (in_count, in_price, in_money, balance_count,
						balance_price, balance_money, warehouse_id, goods_id,
						qc_begin_dt, qc_days, qc_end_dt, qc_sn)
					values (convert(%f, $fmt), %f, %f, convert(%f, $fmt), %f, %f, '%s', '%s',
						'%s', %d, '%s', '%s')";
            $rc = $Model->execute($sql, $inCount, $inPrice, $inMoney, $balanceCount, $balancePrice,
                $balanceMoney, $warehouseId, $goodsId, $qcBeginDT, $qcDays, $qcEndDT, $qcSN);
            if ($rc === false) {
                return "";
            }
        }

        // 总账 - SKU细化到保质期
        $sql = "insert into t_inventory_fifo_detail(in_count, in_price, in_money, balance_count,
					balance_price, balance_money, warehouse_id, goods_id, date_created,
					qc_begin_dt, qc_days, qc_end_dt, qc_sn,
					biz_date, biz_user_id, ref_type, ref_number)
				values (convert(%f, $fmt), %f, %f, convert(%f, $fmt),
					%f, %f, '%s', '%s', now(),
					'%s', %d, '%s', '%s',
					'%s', '%s', '%s', '%s')";
        $rc = $Model->execute($sql, $goodsCount, $goodsPrice, $goodsMoney, $balanceCount,
            $balancePrice, $balanceMoney, $warehouseId, $goodsId, $qcBeginDT, $qcDays, $qcEndDT,
            $qcSN, $bizDT, $bizUserId, $refType, $ref);
        if ($rc === false) {
            return "";
        }

        // 操作成功
        return null;
    }

}