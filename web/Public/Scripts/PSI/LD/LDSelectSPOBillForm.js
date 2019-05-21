/**
 * 物流单-选择门店订货单界面
 */
Ext.define("PSI.LD.LDSelectSPOBillForm", {
	extend : "PSI.AFX.BaseDialogForm",

	initComponent : function() {
		var me = this;
		Ext.apply(me, {
					title : "选择门店订货单",
					width : 1200,
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
											items : [me.getSPOBillGrid()]
										}, {
											region : "center",
											layout : "fit",
											items : [me.getSPOBillDetailGrid()]
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
											html : "<h1>选择要发货的门店订货单</h1>",
											border : 0,
											colspan : 4
										}, {
											id : "editSPORef",
											xtype : "textfield",
											labelAlign : "right",
											labelSeparator : "",
											fieldLabel : "门店订货单单号"
										}, {
											id : "editSPOSupplier",
											xtype : "psi_supplierfield",
											labelAlign : "right",
											labelSeparator : "",
											fieldLabel : "供应商"
										}, {
											id : "editSPOFromDT",
											xtype : "datefield",
											format : "Y-m-d",
											labelAlign : "right",
											labelSeparator : "",
											fieldLabel : "交货日期（起）",
											width : 200
										}, {
											id : "editSPOToDT",
											xtype : "datefield",
											format : "Y-m-d",
											labelAlign : "right",
											labelSeparator : "",
											fieldLabel : "交货日期（止）",
											width : 200
										}, {
											id : "editSPOOrg",
											xtype : "psi_orgwithdataorgfield",
											labelAlign : "right",
											labelSeparator : "",
											fieldLabel : "订货门店"
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
								text : "取消",
								handler : function() {
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

		var item = me.getSPOBillGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			PSI.MsgBox.showInfo("请选择门店订货单");
			return;
		}
		var bill = item[0];
		me.close();
		me.getParentForm().getSPOBillInfo(bill.get("id"));
	},

	getSPOBillGrid : function() {
		var me = this;

		if (me.__billGrid) {
			return me.__billGrid;
		}

		var modelName = "PSILDBill_SPOSelectForm";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "ref", "dealDate", "supplierName",
							"inputUserName", "bizUserName", "orgName",
							"billMemo"]
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
						url : PSI.Const.BASE_URL + "Home/LD/selectSPOBillList",
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
						sortable : false,
						width : 200
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
						header : "订货门店",
						dataIndex : "orgName",
						menuDisabled : true,
						sortable : false
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
					}, {
						header : "备注",
						dataIndex : "billMemo",
						menuDisabled : true,
						sortable : false,
						width : 200
					}],
			listeners : {
				select : {
					fn : me.onSPOBillGridSelect,
					scope : me
				},
				itemdblclick : {
					fn : me.onOK,
					scope : me
				}
			},
			store : store,
			bbar : [{
						id : "ldbill_selectform_pagingToobar",
						xtype : "pagingtoolbar",
						border : 0,
						store : store
					}, "-", {
						xtype : "displayfield",
						value : "每页显示"
					}, {
						id : "ldbill_selectform_comboCountPerPage",
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
											.getCmp("ldbill_selectform_comboCountPerPage")
											.getValue();
									store.currentPage = 1;
									Ext
											.getCmp("ldbill_selectform_pagingToobar")
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

	getSPOBillDetailGrid : function() {
		var me = this;
		if (me.__spobillDetailGrid) {
			return me.__spobillDetailGrid;
		}

		var modelName = "LDSelectSPOBillForm_PSISPOBillDetail";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "goodsCode", "goodsName", "goodsSpec",
							"unitName", "goodsCount", "goodsMoney",
							"goodsPrice", "pwCount", "leftCount", "memo"]
				});
		var store = Ext.create("Ext.data.Store", {
					autoLoad : false,
					model : modelName,
					data : []
				});

		me.__spobillDetailGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					title : "门店订货单明细",
					viewConfig : {
						enableTextSelection : true
					},
					columnLines : true,
					columns : [Ext.create("Ext.grid.RowNumberer", {
										text : "序号",
										width : 40
									}), {
								header : "物资编码",
								dataIndex : "goodsCode",
								menuDisabled : true,
								sortable : false,
								width : 120
							}, {
								header : "物资名称",
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
								dataIndex : "goodsCount",
								menuDisabled : true,
								sortable : false,
								align : "right"
							}, {
								header : "入库数量",
								dataIndex : "pwCount",
								menuDisabled : true,
								sortable : false,
								align : "right"
							}, {
								header : "未入库数量",
								dataIndex : "leftCount",
								menuDisabled : true,
								sortable : false,
								align : "right",
								renderer : function(value) {
									if (value > 0) {
										return "<span style='color:red'>"
												+ value + "</span>";
									} else {
										return value;
									}
								}
							}, {
								header : "单位",
								dataIndex : "unitName",
								menuDisabled : true,
								sortable : false,
								width : 80
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
								width : 120
							}],
					store : store
				});

		return me.__spobillDetailGrid;
	},

	onQuery : function() {
		Ext.getCmp("ldbill_selectform_pagingToobar").doRefresh();

		this.refreshDetailGrid();
	},

	getQueryParam : function() {
		var result = {};

		var ref = Ext.getCmp("editSPORef").getValue();
		if (ref) {
			result.ref = ref;
		}

		var supplierId = Ext.getCmp("editSPOSupplier").getIdValue();
		if (supplierId) {
			result.supplierId = supplierId;
		}

		var orgId = Ext.getCmp("editSPOOrg").getIdValue();
		if (orgId) {
			result.orgId = orgId;
		}

		var fromDT = Ext.getCmp("editSPOFromDT").getValue();
		if (fromDT) {
			result.fromDT = Ext.Date.format(fromDT, "Y-m-d");
		}

		var toDT = Ext.getCmp("editSPOToDT").getValue();
		if (toDT) {
			result.toDT = Ext.Date.format(toDT, "Y-m-d");
		}

		return result;
	},

	onClearQuery : function() {
		Ext.getCmp("editSPORef").setValue(null);
		Ext.getCmp("editSPOSupplier").clearIdValue();
		Ext.getCmp("editSPOOrg").setIdValue(null);
		Ext.getCmp("editSPOOrg").setValue(null);
		Ext.getCmp("editSPOFromDT").setValue(null);
		Ext.getCmp("editSPOToDT").setValue(null);

		this.onQuery();
	},

	onSPOBillGridSelect : function() {
		var me = this;
		me.getSPOBillDetailGrid().setTitle("门店订货单明细");

		me.refreshDetailGrid();
	},

	refreshDetailGrid : function() {
		var me = this;
		me.getSPOBillDetailGrid().setTitle("门店订货单明细");
		me.getSPOBillDetailGrid().getStore().removeAll();
		var item = me.getSPOBillGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return;
		}
		var bill = item[0];

		var grid = me.getSPOBillDetailGrid();
		grid.setTitle("单号: " + bill.get("ref") + " 供应商: "
				+ bill.get("supplierName"));
		var el = grid.getEl();
		el.mask(PSI.Const.LOADING);

		var r = {
			url : me.URL("Home/LD/spoBillDetailList"),
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