/**
 * 采购入库单-选择采购订单界面
 */
Ext.define("PSI.Purchase.PWSelectPOBillForm", {
	extend : "PSI.AFX.BaseDialogForm",

	config : {
		pobillId : null
	},

	initComponent : function() {
		var me = this;
		Ext.apply(me, {
					title : "选择采购订单",
					width : 1000,
					height : 600,
					layout : "border",
					items : [{
								region : "center",
								border : 0,
								bodyPadding : 10,
								layout : "border",
								items : [{
											region : "north",
											height : "50%",
											layout : "fit",
											split : true,
											items : [me.getPOBillGrid()]
										}, {
											region : "center",
											layout : "fit",
											items : [me.getPOBillDetailGrid()]
										}]
							}, {
								region : "north",
								border : 0,
								layout : {
									type : "table",
									columns : 4
								},
								height : 130,
								bodyPadding : 10,
								items : [{
											html : "<h1>选择要入库的采购订单</h1>",
											border : 0,
											colspan : 4
										}, {
											id : "editPORef",
											xtype : "textfield",
											labelAlign : "right",
											labelSeparator : "",
											fieldLabel : "采购订单单号"
										}, {
											id : "editPOSupplier",
											xtype : "psi_supplierfield",
											labelAlign : "right",
											labelSeparator : "",
											fieldLabel : "供应商",
											colspan : 3
										}, {
											id : "editPOFromDT",
											xtype : "datefield",
											format : "Y-m-d",
											labelAlign : "right",
											labelSeparator : "",
											fieldLabel : "业务日期（起）",
											width : 260
										}, {
											id : "editPOToDT",
											xtype : "datefield",
											format : "Y-m-d",
											labelAlign : "right",
											labelSeparator : "",
											fieldLabel : "业务日期（止）",
											width : 260
										}, {
											xtype : "container",
											items : [{
														xtype : "button",
														text : "查询",
														width : 100,
														margin : "0 0 0 40",
														iconCls : "PSI-button-refresh",
														handler : me.onQuery,
														scope : me
													}, {
														xtype : "button",
														text : "清空查询条件",
														width : 100,
														margin : "0, 0, 0, 10",
														handler : me.onClearQuery,
														scope : me
													}]
										}]
							}],
					listeners : {
						show : {
							fn : me.onWndShow,
							scope : me
						}
					},
					buttons : [{
								text : "选择",
								iconCls : "PSI-button-ok",
								formBind : true,
								handler : me.onOK,
								scope : me
							}, {
								text : me.getPobillId()
										? "取消"
										: "不选择采购订单，创建零星采购入库单",
								handler : function() {
									if (!me.getPobillId()) {
										// 零星采购入库
										me.getParentForm().onCancelSelectPOBill
												.call(me.getParentForm());
									}
									me.close();
								},
								scope : me
							}]
				});

		me.callParent(arguments);
	},
	onWndShow : function() {
		var me = this;
	},

	onOK : function() {
		var me = this;

		var item = me.getPOBillGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			PSI.MsgBox.showInfo("请选择采购订单");
			return;
		}
		var bill = item[0];
		me.close();
		me.getParentForm().getPOBillInfo(bill.get("id"));
	},

	getPOBillGrid : function() {
		var me = this;

		if (me.__billGrid) {
			return me.__billGrid;
		}

		var modelName = "PSIPWBill_POSelectForm";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "ref", "dealDate", "supplierName",
							"inputUserName", "bizUserName", "goodsMoney"]
				});
		var store = Ext.create("Ext.data.Store", {
					autoLoad : false,
					model : modelName,
					data : [],
					pageSize : 20,
					proxy : {
						type : "ajax",
						actionMethods : {
							read : "POST"
						},
						url : PSI.Const.BASE_URL
								+ "Home/Purchase/selectPOBillListForPWBill",
						reader : {
							root : 'dataList',
							totalProperty : 'totalCount'
						}
					}
				});
		store.on("beforeload", function() {
					store.proxy.extraParams = me.getQueryParam();
				});

		me.__billGrid = Ext.create("Ext.grid.Panel", {
			cls : "PSI",
			columnLines : true,
			columns : [Ext.create("Ext.grid.RowNumberer", {
								text : "序号",
								width : 50
							}), {
						header : "单号",
						dataIndex : "ref",
						width : 110,
						menuDisabled : true,
						sortable : false
					}, {
						header : "交货日期",
						dataIndex : "dealDate",
						menuDisabled : true,
						sortable : false
					}, {
						header : "供应商",
						dataIndex : "supplierName",
						width : 200,
						menuDisabled : true,
						sortable : false
					}, {
						header : "采购金额",
						dataIndex : "goodsMoney",
						menuDisabled : true,
						sortable : false,
						align : "right",
						xtype : "numbercolumn",
						width : 80
					}, {
						header : "业务员",
						dataIndex : "bizUserName",
						menuDisabled : true,
						sortable : false
					}, {
						header : "录单人",
						dataIndex : "inputUserName",
						menuDisabled : true,
						sortable : false
					}],
			listeners : {
				select : {
					fn : me.onPOBillGridSelect,
					scope : me
				},
				itemdblclick : {
					fn : me.onOK,
					scope : me
				}
			},
			store : store,
			bbar : [{
						id : "pobill_selectform_pagingToobar",
						xtype : "pagingtoolbar",
						border : 0,
						store : store
					}, "-", {
						xtype : "displayfield",
						value : "每页显示"
					}, {
						id : "pobill_selectform_comboCountPerPage",
						xtype : "combobox",
						editable : false,
						width : 60,
						store : Ext.create("Ext.data.ArrayStore", {
									fields : ["text"],
									data : [["20"], ["50"], ["100"], ["300"],
											["1000"]]
								}),
						value : 20,
						listeners : {
							change : {
								fn : function() {
									store.pageSize = Ext
											.getCmp("pobill_selectform_comboCountPerPage")
											.getValue();
									store.currentPage = 1;
									Ext
											.getCmp("pobill_selectform_pagingToobar")
											.doRefresh();
								},
								scope : me
							}
						}
					}, {
						xtype : "displayfield",
						value : "条记录"
					}]
		});

		return me.__billGrid;
	},

	getPOBillDetailGrid : function() {
		var me = this;
		if (me.__pobillDetailGrid) {
			return me.__pobillDetailGrid;
		}

		var modelName = "PWSelectPOBillForm_PSIPOBillDetail";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "goodsCode", "goodsName", "goodsSpec",
							"unitName", "goodsCount", "goodsMoney",
							"goodsPrice", "memo", "leftCount"]
				});
		var store = Ext.create("Ext.data.Store", {
					autoLoad : false,
					model : modelName,
					data : []
				});

		me.__pobillDetailGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					title : "采购订单明细",
					viewConfig : {
						enableTextSelection : true
					},
					columnLines : true,
					columns : [Ext.create("Ext.grid.RowNumberer", {
										text : "序号",
										width : 40
									}), {
								header : "商品编码",
								dataIndex : "goodsCode",
								menuDisabled : true,
								sortable : false,
								width : 120
							}, {
								header : "商品名称",
								dataIndex : "goodsName",
								menuDisabled : true,
								sortable : false,
								width : 200
							}, {
								header : "规格型号",
								dataIndex : "goodsSpec",
								menuDisabled : true,
								sortable : false,
								width : 200
							}, {
								header : "采购数量",
								width : 120,
								dataIndex : "goodsCount",
								menuDisabled : true,
								sortable : false,
								align : "right"
							}, {
								header : "未入库数量",
								width : 120,
								dataIndex : "leftCount",
								menuDisabled : true,
								sortable : false,
								align : "right"
							}, {
								header : "单位",
								dataIndex : "unitName",
								menuDisabled : true,
								sortable : false,
								width : 60
							}, {
								header : "采购单价",
								dataIndex : "goodsPrice",
								menuDisabled : true,
								sortable : false,
								align : "right",
								xtype : "numbercolumn",
								width : 150
							}, {
								header : "采购金额",
								dataIndex : "goodsMoney",
								menuDisabled : true,
								sortable : false,
								align : "right",
								xtype : "numbercolumn",
								width : 150
							}, {
								header : "备注",
								dataIndex : "memo",
								menuDisabled : true,
								sortable : false,
								width : 200
							}],
					store : store
				});

		return me.__pobillDetailGrid;
	},

	onQuery : function() {
		Ext.getCmp("pobill_selectform_pagingToobar").doRefresh();

		this.refreshDetailGrid();
	},

	getQueryParam : function() {
		var result = {};

		var ref = Ext.getCmp("editPORef").getValue();
		if (ref) {
			result.ref = ref;
		}

		var supplierId = Ext.getCmp("editPOSupplier").getIdValue();
		if (supplierId) {
			result.supplierId = supplierId;
		}

		var fromDT = Ext.getCmp("editPOFromDT").getValue();
		if (fromDT) {
			result.fromDT = Ext.Date.format(fromDT, "Y-m-d");
		}

		var toDT = Ext.getCmp("editPOToDT").getValue();
		if (toDT) {
			result.toDT = Ext.Date.format(toDT, "Y-m-d");
		}

		return result;
	},

	onClearQuery : function() {
		Ext.getCmp("editPORef").setValue(null);
		Ext.getCmp("editPOSupplier").clearIdValue();
		Ext.getCmp("editPOWarehouse").clearIdValue();
		Ext.getCmp("editPOFromDT").setValue(null);
		Ext.getCmp("editPOToDT").setValue(null);

		this.onQuery();
	},

	onPOBillGridSelect : function() {
		var me = this;
		me.getPOBillDetailGrid().setTitle("采购订单明细");

		me.refreshDetailGrid();
	},

	refreshDetailGrid : function() {
		var me = this;
		me.getPOBillDetailGrid().setTitle("采购订单明细");
		me.getPOBillDetailGrid().getStore().removeAll();
		var item = me.getPOBillGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return;
		}
		var bill = item[0];

		var grid = me.getPOBillDetailGrid();
		grid.setTitle("单号: " + bill.get("ref") + " 供应商: "
				+ bill.get("supplierName"));
		var el = grid.getEl();
		el.mask(PSI.Const.LOADING);

		var r = {
			url : me.URL("Home/Purchase/poBillDetailListForPWBillSelectPOBill"),
			params : {
				id : bill.get("id")
			},
			callback : function(options, success, response) {
				var store = grid.getStore();

				store.removeAll();

				if (success) {
					var data = me.decodeJSON(response.responseText);
					store.add(data);
				}

				el.unmask();
			}
		};

		me.ajax(r);
	}
});