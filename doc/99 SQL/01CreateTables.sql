/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

DROP TABLE IF EXISTS `t_biz_log`;
CREATE TABLE IF NOT EXISTS `t_biz_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `date_created` datetime DEFAULT NULL,
  `info` varchar(1000) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `log_category` varchar(50) NOT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `ip_from` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `t_fid`;
CREATE TABLE IF NOT EXISTS `t_fid` (
  `fid` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_menu_item`;
CREATE TABLE IF NOT EXISTS `t_menu_item` (
  `id` varchar(255) NOT NULL,
  `caption` varchar(255) NOT NULL,
  `fid` varchar(255) DEFAULT NULL,
  `parent_id` varchar(255) DEFAULT NULL,
  `show_order` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_org`;
CREATE TABLE IF NOT EXISTS `t_org` (
  `id` varchar(255) NOT NULL,
  `full_name` varchar(1000) NOT NULL,
  `name` varchar(255) NOT NULL,
  `org_code` varchar(255) NOT NULL,
  `parent_id` varchar(255) DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `org_type` int(11) DEFAULT NULL,
  `create_user_id` varchar(255) DEFAULT NULL,
  `create_dt` datetime DEFAULT NULL,
  `update_user_id` varchar(255) DEFAULT NULL,
  `update_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_permission`;
CREATE TABLE IF NOT EXISTS `t_permission` (
  `id` varchar(255) NOT NULL,
  `fid` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `py` varchar(255) DEFAULT NULL,
  `show_order` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_recent_fid`;
CREATE TABLE IF NOT EXISTS `t_recent_fid` (
  `fid` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `click_count` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_role`;
CREATE TABLE IF NOT EXISTS `t_role` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_role_permission`;
CREATE TABLE IF NOT EXISTS `t_role_permission` (
  `role_id` varchar(255) DEFAULT NULL,
  `permission_id` varchar(255) DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_role_user`;
CREATE TABLE IF NOT EXISTS `t_role_user` (
  `role_id` varchar(255) DEFAULT NULL,
  `user_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_user`;
CREATE TABLE IF NOT EXISTS `t_user` (
  `id` varchar(255) NOT NULL,
  `enabled` int(11) NOT NULL,
  `login_name` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `org_id` varchar(255) NOT NULL,
  `org_code` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `py` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `birthday` varchar(255) DEFAULT NULL,
  `id_card_number` varchar(255) DEFAULT NULL,
  `tel` varchar(255) DEFAULT NULL,
  `tel02` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_warehouse`;
CREATE TABLE IF NOT EXISTS `t_warehouse` (
  `id` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `inited` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `py` varchar(255) DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `org_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_supplier`;
CREATE TABLE IF NOT EXISTS `t_supplier` (
  `id` varchar(255) NOT NULL,
  `category_id` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact01` varchar(255) DEFAULT NULL,
  `qq01` varchar(255) DEFAULT NULL,
  `tel01` varchar(255) DEFAULT NULL,
  `mobile01` varchar(255) DEFAULT NULL,
  `contact02` varchar(255) DEFAULT NULL,
  `qq02` varchar(255) DEFAULT NULL,
  `tel02` varchar(255) DEFAULT NULL,
  `mobile02` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `address_shipping` varchar(255) DEFAULT NULL,
  `address_receipt` varchar(255) DEFAULT NULL,
  `py` varchar(255) DEFAULT NULL,
  `init_receivables` decimal(19,2) DEFAULT NULL, 
  `init_receivables_dt` datetime DEFAULT NULL, 
  `init_payables` decimal(19,2) DEFAULT NULL, 
  `init_payables_dt` datetime DEFAULT NULL, 
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account` varchar(255) DEFAULT NULL,
  `tax_number` varchar(255) DEFAULT NULL,
  `fax` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `tax_rate` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_supplier_category`;
CREATE TABLE IF NOT EXISTS `t_supplier_category` (
  `id` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `parent_id` varchar(255) DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `full_name` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_goods`;
CREATE TABLE IF NOT EXISTS `t_goods` (
  `id` varchar(255) NOT NULL,
  `category_id` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `spec` varchar(255) NOT NULL,
  `unit_id` varchar(255) NOT NULL,
  `py` varchar(255) DEFAULT NULL,
  `bar_code` varchar(255) DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `memo` varchar(500) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `brand_id` varchar(255) DEFAULT NULL,
  `ABC_category` varchar(255) NOT NULL,
  `cost_price_checkups` decimal(19,2) NOT NULL,
  `purchase_price_upper` decimal(19,2) NOT NULL,
  `use_qc` int(11) NOT NULL,
  `qc_days` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_goods_category`;
CREATE TABLE IF NOT EXISTS `t_goods_category` (
  `id` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `parent_id` varchar(255) DEFAULT NULL,
  `full_name` varchar(1000) DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_goods_unit`;
CREATE TABLE IF NOT EXISTS `t_goods_unit` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `py` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `t_inventory`;
CREATE TABLE IF NOT EXISTS `t_inventory` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `balance_count` decimal(19,8) NOT NULL,
  `balance_money` decimal(19,2) NOT NULL,
  `balance_price` decimal(19,2) NOT NULL,
  `goods_id` varchar(255) NOT NULL,
  `in_count` decimal(19,8) DEFAULT NULL,
  `in_money` decimal(19,2) DEFAULT NULL,
  `in_price` decimal(19,2) DEFAULT NULL,
  `out_count` decimal(19,8) DEFAULT NULL,
  `out_money` decimal(19,2) DEFAULT NULL,
  `out_price` decimal(19,2) DEFAULT NULL,
  `afloat_count` decimal(19,8) DEFAULT NULL,
  `afloat_money` decimal(19,2) DEFAULT NULL,
  `afloat_price` decimal(19,2) DEFAULT NULL,
  `warehouse_id` varchar(255) NOT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `t_inventory_detail`;
CREATE TABLE IF NOT EXISTS `t_inventory_detail` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `balance_count` decimal(19,8) NOT NULL,
  `balance_money` decimal(19,2) NOT NULL,
  `balance_price` decimal(19,2) NOT NULL,
  `biz_date` datetime NOT NULL,
  `biz_user_id` varchar(255) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `goods_id` varchar(255) NOT NULL,
  `in_count` decimal(19,8) DEFAULT NULL,
  `in_money` decimal(19,2) DEFAULT NULL,
  `in_price` decimal(19,2) DEFAULT NULL,
  `out_count` decimal(19,8) DEFAULT NULL,
  `out_money` decimal(19,2) DEFAULT NULL,
  `out_price` decimal(19,2) DEFAULT NULL,
  `ref_number` varchar(255) DEFAULT NULL,
  `ref_type` varchar(255) NOT NULL,
  `warehouse_id` varchar(255) NOT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


DROP TABLE IF EXISTS `t_pw_bill`;
CREATE TABLE IF NOT EXISTS `t_pw_bill` (
  `id` varchar(255) NOT NULL,
  `bill_status` int(11) NOT NULL,
  `biz_dt` datetime NOT NULL,
  `biz_user_id` varchar(255) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `goods_money` decimal(19,2) NOT NULL,
  `input_user_id` varchar(255) NOT NULL,
  `ref` varchar(255) NOT NULL,
  `supplier_id` varchar(255) NOT NULL,
  `warehouse_id` varchar(255) NOT NULL,
  `payment_type` int(11) NOT NULL DEFAULT 0,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `expand_by_bom` int(11) NOT NULL DEFAULT 0,
  `bill_memo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_pw_bill_detail`;
CREATE TABLE IF NOT EXISTS `t_pw_bill_detail` (
  `id` varchar(255) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `goods_id` varchar(255) NOT NULL,
  `goods_count` decimal(19,8) NOT NULL,
  `goods_money` decimal(19,2) NOT NULL,
  `goods_price` decimal(19,2) NOT NULL,
  `pwbill_id` varchar(255) NOT NULL,
  `show_order` int(11) NOT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `memo` varchar(1000) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `pobilldetail_id` varchar(255) DEFAULT NULL,
  `qc_begin_dt` datetime DEFAULT NULL,
  `qc_end_dt` datetime DEFAULT NULL,
  `qc_days` int(11) DEFAULT NULL,
  `qc_sn` varchar(255) DEFAULT NULL,
  `unit_id` varchar(255) NOT NULL,
  `factor` decimal(19,2) NOT NULL,
  `factor_type` int(11) NOT NULL,
  `sku_goods_count` decimal(19,8) NOT NULL,
  `sku_goods_unit_id` varchar(255) NOT NULL,
  `rej_goods_count` decimal(19,8) NOT NULL,
  `rev_goods_count` decimal(19,8) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `t_payables`;
CREATE TABLE IF NOT EXISTS `t_payables` (
  `id` varchar(255) NOT NULL,
  `act_money` decimal(19,2) NOT NULL,
  `balance_money` decimal(19,2) NOT NULL,
  `ca_id` varchar(255) NOT NULL,
  `ca_type` varchar(255) NOT NULL,
  `pay_money` decimal(19,2) NOT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_payables_detail`;
CREATE TABLE IF NOT EXISTS `t_payables_detail` (
  `id` varchar(255) NOT NULL,
  `act_money` decimal(19,2) NOT NULL,
  `balance_money` decimal(19,2) NOT NULL,
  `ca_id` varchar(255) NOT NULL,
  `ca_type` varchar(255) NOT NULL,
  `biz_date` datetime DEFAULT NULL,
  `date_created` datetime DEFAULT NULL,
  `pay_money` decimal(19,2) NOT NULL,
  `ref_number` varchar(255) NOT NULL,
  `ref_type` varchar(255) NOT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `t_payment`;
CREATE TABLE IF NOT EXISTS `t_payment` (
  `id` varchar(255) NOT NULL,
  `act_money` decimal(19,2) NOT NULL,
  `biz_date` datetime NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `input_user_id` varchar(255) NOT NULL,
  `pay_user_id` varchar(255) NOT NULL,
  `bill_id` varchar(255) NOT NULL,
  `ref_type` varchar(255) NOT NULL,
  `ref_number` varchar(255) NOT NULL,
  `remark` varchar(255) NOT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `t_it_bill`;
CREATE TABLE IF NOT EXISTS `t_it_bill` (
  `id` varchar(255) NOT NULL,
  `bill_status` int(11) NOT NULL,
  `bizdt` datetime NOT NULL,
  `biz_user_id` varchar(255) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `input_user_id` varchar(255) NOT NULL,
  `ref` varchar(255) NOT NULL,
  `from_warehouse_id` varchar(255) NOT NULL,
  `to_warehouse_id` varchar(255) NOT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_it_bill_detail`;
CREATE TABLE IF NOT EXISTS `t_it_bill_detail` (
  `id` varchar(255) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `goods_id` varchar(255) NOT NULL,
  `goods_count` decimal(19,8) NOT NULL,
  `show_order` int(11) NOT NULL,
  `itbill_id` varchar(255) NOT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_ic_bill`;
CREATE TABLE IF NOT EXISTS `t_ic_bill` (
  `id` varchar(255) NOT NULL,
  `bill_status` int(11) NOT NULL,
  `bizdt` datetime NOT NULL,
  `biz_user_id` varchar(255) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `input_user_id` varchar(255) NOT NULL,
  `ref` varchar(255) NOT NULL,
  `warehouse_id` varchar(255) NOT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `bill_memo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_ic_bill_detail`;
CREATE TABLE IF NOT EXISTS `t_ic_bill_detail` (
  `id` varchar(255) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `goods_id` varchar(255) NOT NULL,
  `goods_count` decimal(19,8) NOT NULL,
  `goods_money` decimal(19,2) NOT NULL,
  `show_order` int(11) NOT NULL,
  `icbill_id` varchar(255) NOT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `memo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_pr_bill`;
CREATE TABLE IF NOT EXISTS `t_pr_bill` (
  `id` varchar(255) NOT NULL,
  `bill_status` int(11) NOT NULL,
  `bizdt` datetime NOT NULL,
  `biz_user_id` varchar(255) NOT NULL,
  `supplier_id` varchar(255) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `input_user_id` varchar(255) NOT NULL,
  `inventory_money` decimal(19,2) DEFAULT NULL,
  `ref` varchar(255) NOT NULL,
  `rejection_money` decimal(19,2) DEFAULT NULL,
  `warehouse_id` varchar(255) NOT NULL,
  `pw_bill_id` varchar(255) NOT NULL,
  `receiving_type` int(11) NOT NULL DEFAULT 2,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `out_type` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_pr_bill_detail`;
CREATE TABLE IF NOT EXISTS `t_pr_bill_detail` (
  `id` varchar(255) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `goods_id` varchar(255) NOT NULL,
  `goods_count` decimal(19,8) NOT NULL,
  `goods_money` decimal(19,2) NOT NULL,
  `goods_price` decimal(19,2) NOT NULL,
  `inventory_money` decimal(19,2) NOT NULL,
  `inventory_price` decimal(19,2) NOT NULL,
  `rejection_goods_count` decimal(19,8) NOT NULL,
  `rejection_goods_price` decimal(19,2) NOT NULL,
  `rejection_money` decimal(19,2) NOT NULL,
  `show_order` int(11) NOT NULL,
  `prbill_id` varchar(255) NOT NULL,
  `pwbilldetail_id` varchar(255) NOT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `qc_begin_dt` datetime DEFAULT NULL,
  `qc_end_dt` datetime DEFAULT NULL,
  `qc_days` int(11) DEFAULT NULL,
  `qc_sn` varchar(255) DEFAULT NULL,
  `unit_id` varchar(255) NOT NULL,
  `factor` decimal(19,2) NOT NULL,
  `factor_type` int(11) NOT NULL,
  `sku_goods_count` decimal(19,8) NOT NULL,
  `sku_goods_unit_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `t_config`;
CREATE TABLE IF NOT EXISTS `t_config` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `note` varchar(255) NOT NULL,
  `show_order` int(11) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_psi_db_version`;
CREATE TABLE IF NOT EXISTS `t_psi_db_version` (
  `db_version` varchar(255) NOT NULL,
  `update_dt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_goods_si`;
CREATE TABLE IF NOT EXISTS `t_goods_si` (
  `id` varchar(255) NOT NULL,
  `goods_id` varchar(255) NOT NULL,
  `warehouse_id` varchar(255) NOT NULL,
  `safety_inventory` decimal(19,2) NOT NULL,
  `inventory_upper` decimal(19,2) DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `t_po_bill`;
CREATE TABLE IF NOT EXISTS `t_po_bill` (
  `id` varchar(255) NOT NULL,
  `ref` varchar(255) NOT NULL,
  `supplier_id` varchar(255) NOT NULL,
  `pctemplate_id` varchar(255) NOT NULL,
  `org_id` varchar(255) NOT NULL,
  `biz_user_id` varchar(255) NOT NULL,
  `biz_dt` datetime NOT NULL,
  `input_user_id` varchar(255) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `bill_status` int(11) NOT NULL,
  `goods_money` decimal(19,2) NOT NULL,
  `deal_date` datetime NOT NULL,
  `deal_address` varchar(255) DEFAULT NULL,
  `confirm_user_id` varchar(255) DEFAULT NULL,
  `confirm_date` datetime DEFAULT NULL,
  `bill_memo` varchar(255) DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_po_bill_detail`;
CREATE TABLE IF NOT EXISTS `t_po_bill_detail` (
  `id` varchar(255) NOT NULL,
  `pobill_id` varchar(255) NOT NULL,
  `pctemplate_detail_id` varchar(255) NOT NULL,
  `show_order` int(11) NOT NULL,
  `goods_id` varchar(255) NOT NULL,
  `unit_id` varchar(255) NOT NULL,
  `goods_count` decimal(19,8) NOT NULL,
  `goods_money` decimal(19,2) NOT NULL,
  `goods_price` decimal(19,2) NOT NULL,
  `pw_count` decimal(19,8) NOT NULL,
  `left_count` decimal(19,8) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `memo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_po_pw`;
CREATE TABLE IF NOT EXISTS `t_po_pw` (
  `po_id` varchar(255) NOT NULL,
  `pw_id` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_role_permission_dataorg`;
CREATE TABLE IF NOT EXISTS `t_role_permission_dataorg` (
  `role_id` varchar(255) DEFAULT NULL,
  `permission_id` varchar(255) DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_inventory_fifo`;
CREATE TABLE IF NOT EXISTS `t_inventory_fifo` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `balance_count` decimal(19,8) NOT NULL,
  `balance_money` decimal(19,2) NOT NULL,
  `balance_price` decimal(19,2) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `goods_id` varchar(255) NOT NULL,
  `qc_begin_dt` datetime DEFAULT NULL,
  `qc_end_dt` datetime DEFAULT NULL,
  `qc_days` int(11) DEFAULT NULL,
  `qc_sn` varchar(255) DEFAULT NULL,
  `in_count` decimal(19,8) DEFAULT NULL,
  `in_money` decimal(19,2) DEFAULT NULL,
  `in_price` decimal(19,2) DEFAULT NULL,
  `out_count` decimal(19,8) DEFAULT NULL,
  `out_money` decimal(19,2) DEFAULT NULL,
  `out_price` decimal(19,2) DEFAULT NULL,
  `in_ref` varchar(255) DEFAULT NULL,
  `in_ref_type` varchar(255) NOT NULL,
  `warehouse_id` varchar(255) NOT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `pwbilldetail_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `t_inventory_fifo_detail`;
CREATE TABLE IF NOT EXISTS `t_inventory_fifo_detail` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `balance_count` decimal(19,8) NOT NULL,
  `balance_money` decimal(19,2) NOT NULL,
  `balance_price` decimal(19,2) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `goods_id` varchar(255) NOT NULL,
  `qc_begin_dt` datetime DEFAULT NULL,
  `qc_end_dt` datetime DEFAULT NULL,
  `qc_days` int(11) DEFAULT NULL,
  `qc_sn` varchar(255) DEFAULT NULL,
  `in_count` decimal(19,8) DEFAULT NULL,
  `in_money` decimal(19,2) DEFAULT NULL,
  `in_price` decimal(19,2) DEFAULT NULL,
  `out_count` decimal(19,8) DEFAULT NULL,
  `out_money` decimal(19,2) DEFAULT NULL,
  `out_price` decimal(19,2) DEFAULT NULL,
  `warehouse_id` varchar(255) NOT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `pwbilldetail_id` varchar(255) DEFAULT NULL,
  `wsbilldetail_id` varchar(255) DEFAULT NULL,
  `ref_number` varchar(255) DEFAULT NULL,
  `ref_type` varchar(255) NOT NULL,
  `biz_date` datetime NOT NULL,
  `biz_user_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


DROP TABLE IF EXISTS `t_goods_brand`;
CREATE TABLE IF NOT EXISTS `t_goods_brand` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `parent_id` varchar(255) DEFAULT NULL,
  `full_name` varchar(1000) DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `think_session`;
CREATE TABLE `think_session` (
  `session_id` varchar(255) NOT NULL,
  `session_expire` int(11) NOT NULL,
  `session_data` blob,
  UNIQUE KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `t_pc_bill`;
CREATE TABLE IF NOT EXISTS `t_pc_bill` (
  `id` varchar(255) NOT NULL,
  `ref` varchar(255) NOT NULL,
  `supplier_id` varchar(255) NOT NULL,
  `biz_user_id` varchar(255) NOT NULL,
  `biz_dt` datetime NOT NULL,
  `input_user_id` varchar(255) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `bill_status` int(11) NOT NULL,
  `from_dt` datetime NOT NULL,
  `to_dt` datetime NOT NULL,
  `confirm_user_id` varchar(255) DEFAULT NULL,
  `confirm_date` datetime DEFAULT NULL,
  `bill_memo` varchar(255) DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_pc_bill_detail`;
CREATE TABLE IF NOT EXISTS `t_pc_bill_detail` (
  `id` varchar(255) NOT NULL,
  `pcbill_id` varchar(255) NOT NULL,
  `show_order` int(11) NOT NULL,
  `goods_id` varchar(255) NOT NULL,
  `unit_id` varchar(255) NOT NULL,
  `goods_price` decimal(19,2) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `memo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_pc_template`;
CREATE TABLE IF NOT EXISTS `t_pc_template` (
  `id` varchar(255) NOT NULL,
  `ref` varchar(255) NOT NULL,
  `pcbill_id` varchar(255) NOT NULL,
  `input_user_id` varchar(255) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `bill_status` int(11) NOT NULL,
  `bill_memo` varchar(255) DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_pc_template_detail`;
CREATE TABLE IF NOT EXISTS `t_pc_template_detail` (
  `id` varchar(255) NOT NULL,
  `pctemplate_id` varchar(255) NOT NULL,
  `pcbill_detail_id` varchar(255) NOT NULL,
  `show_order` int(11) NOT NULL,
  `goods_id` varchar(255) NOT NULL,
  `unit_id` varchar(255) NOT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `memo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_pc_template_org`;
CREATE TABLE IF NOT EXISTS `t_pc_template_org` (
  `id` varchar(255) NOT NULL,
  `pctemplate_id` varchar(255) NOT NULL,
  `org_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_spo_bill`;
CREATE TABLE IF NOT EXISTS `t_spo_bill` (
  `id` varchar(255) NOT NULL,
  `ref` varchar(255) NOT NULL,
  `supplier_id` varchar(255) NOT NULL,
  `pctemplate_id` varchar(255) NOT NULL,
  `org_id` varchar(255) NOT NULL,
  `biz_user_id` varchar(255) NOT NULL,
  `biz_dt` datetime NOT NULL,
  `input_user_id` varchar(255) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `bill_status` int(11) NOT NULL,
  `goods_money` decimal(19,2) NOT NULL,
  `deal_date` datetime NOT NULL,
  `deal_address` varchar(255) DEFAULT NULL,
  `confirm_user_id` varchar(255) DEFAULT NULL,
  `confirm_date` datetime DEFAULT NULL,
  `bill_memo` varchar(255) DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `is_today_order` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_spo_bill_detail`;
CREATE TABLE IF NOT EXISTS `t_spo_bill_detail` (
  `id` varchar(255) NOT NULL,
  `spobill_id` varchar(255) NOT NULL,
  `pctemplate_detail_id` varchar(255) NOT NULL,
  `show_order` int(11) NOT NULL,
  `goods_id` varchar(255) NOT NULL,
  `unit_id` varchar(255) NOT NULL,
  `goods_count` decimal(19,8) NOT NULL,
  `goods_money` decimal(19,2) NOT NULL,
  `goods_price` decimal(19,2) NOT NULL,
  `pw_count` decimal(19,8) NOT NULL,
  `left_count` decimal(19,8) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `memo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_spo_po`;
CREATE TABLE IF NOT EXISTS `t_spo_po` (
  `spo_id` varchar(255) NOT NULL,
  `po_id` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_goods_unit_default`;
CREATE TABLE IF NOT EXISTS `t_goods_unit_default` (
  `id` varchar(255) NOT NULL,
  `goods_id` varchar(255) NOT NULL,
  `unit_id` varchar(255) NOT NULL,
  `bill_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_goods_unit_group`;
CREATE TABLE IF NOT EXISTS `t_goods_unit_group` (
  `id` varchar(255) NOT NULL,
  `goods_id` varchar(255) NOT NULL,
  `unit_id` varchar(255) NOT NULL,
  `factor` decimal(19,2) NOT NULL,
  `factor_type` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_ld_bill`;
CREATE TABLE IF NOT EXISTS `t_ld_bill` (
  `id` varchar(255) NOT NULL,
  `ref` varchar(255) NOT NULL,
  `from_warehouse_id` varchar(255) NOT NULL,
  `to_warehouse_id` varchar(255) NOT NULL,
  `from_org_id` varchar(255) NOT NULL,
  `to_org_id` varchar(255) NOT NULL,
  `biz_user_id` varchar(255) NOT NULL,
  `biz_dt` datetime NOT NULL,
  `input_user_id` varchar(255) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `bill_status` int(11) NOT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `bill_memo` varchar(255) DEFAULT NULL,
  `spobill_id` varchar(255) NOT NULL,
  `out_type` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_ld_bill_detail`;
CREATE TABLE IF NOT EXISTS `t_ld_bill_detail` (
  `id` varchar(255) NOT NULL,
  `ldbill_id` varchar(255) NOT NULL,
  `show_order` int(11) NOT NULL,
  `goods_id` varchar(255) NOT NULL,
  `goods_count` decimal(19,8) NOT NULL,
  `unit_id` varchar(255) NOT NULL,
  `rej_goods_count` decimal(19,8) NOT NULL,
  `rev_goods_count` decimal(19,8) NOT NULL,
  `factor` decimal(19,2) NOT NULL,
  `factor_type` int(11) NOT NULL,
  `sku_goods_count` decimal(19,8) NOT NULL,
  `sku_goods_unit_id` varchar(255) NOT NULL,
  `qc_begin_dt` datetime DEFAULT NULL,
  `qc_end_dt` datetime DEFAULT NULL,
  `qc_days` int(11) DEFAULT NULL,
  `qc_sn` varchar(255) DEFAULT NULL,
  `date_created` datetime DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `memo` varchar(1000) DEFAULT NULL,
  `spobilldetail_id` varchar(255) DEFAULT NULL,
  `rev_factor` decimal(19,2) DEFAULT NULL,
  `rev_factor_type` int(11) DEFAULT NULL,
  `rev_sku_goods_count` decimal(19,8) DEFAULT NULL,
  `rej_factor` decimal(19,2) DEFAULT NULL,
  `rej_factor_type` int(11) DEFAULT NULL,
  `rej_sku_goods_count` decimal(19,8) DEFAULT NULL,
  `rev_edit_flag` int(11) DEFAULT 0,
  `inv_goods_price` decimal(19,2) DEFAULT NULL,
  `inv_goods_money` decimal(19,2) DEFAULT NULL,
  `rev_goods_price` decimal(19,2) DEFAULT NULL,
  `rev_goods_money` decimal(19,2) DEFAULT NULL,
  `rej_goods_price` decimal(19,2) DEFAULT NULL,
  `rej_goods_money` decimal(19,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_us_template`;
CREATE TABLE IF NOT EXISTS `t_us_template` (
  `id` varchar(255) NOT NULL,
  `ref` varchar(255) NOT NULL,
  `input_user_id` varchar(255) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `bill_status` int(11) NOT NULL,
  `bill_memo` varchar(255) DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_us_template_detail`;
CREATE TABLE IF NOT EXISTS `t_us_template_detail` (
  `id` varchar(255) NOT NULL,
  `ustemplate_id` varchar(255) NOT NULL,
  `show_order` int(11) NOT NULL,
  `goods_id` varchar(255) NOT NULL,
  `check_count` decimal(19,8) DEFAULT NULL,
  `sale_count` decimal(19,8) DEFAULT NULL,
  `lost_count` decimal(19,8) DEFAULT NULL,
  `calc_count` decimal(19,8) DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `memo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_us_template_org`;
CREATE TABLE IF NOT EXISTS `t_us_template_org` (
  `id` varchar(255) NOT NULL,
  `ustemplate_id` varchar(255) NOT NULL,
  `org_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_us_bill`;
CREATE TABLE IF NOT EXISTS `t_us_bill` (
  `id` varchar(255) NOT NULL,
  `ref` varchar(255) NOT NULL,
  `biz_user_id` varchar(255) NOT NULL,
  `biz_dt` datetime NOT NULL,
  `input_user_id` varchar(255) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `bill_status` int(11) NOT NULL,
  `bill_memo` varchar(255) DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `org_id` varchar(255) NOT NULL,
  `ustemplate_id` varchar(255) NOT NULL,
  `warehouse_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `t_us_bill_detail`;
CREATE TABLE IF NOT EXISTS `t_us_bill_detail` (
  `id` varchar(255) NOT NULL,
  `usbill_id` varchar(255) NOT NULL,
  `show_order` int(11) NOT NULL,
  `goods_id` varchar(255) NOT NULL,
  `check_count` decimal(19,8) DEFAULT NULL,
  `sale_count` decimal(19,8) DEFAULT NULL,
  `lost_count` decimal(19,8) DEFAULT NULL,
  `calc_count` decimal(19,8) DEFAULT NULL,
  `data_org` varchar(255) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `memo` varchar(255) DEFAULT NULL,
  `inv_goods_price` decimal(19,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
