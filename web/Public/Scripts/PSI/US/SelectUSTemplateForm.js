/**
 * 消耗单-选择消耗单模板
 */
Ext.define("PSI.US.SelectUSTemplateForm", {
	extend : "PSI.AFX.BaseDialogForm",

	initComponent : function() {
		var me = this;
		Ext.apply(me, {
					title : "选择消耗单模板",
					width : 1200,
					height : 600,
					layout : "border",
					items : [{
								region : "center",
								border : 0,
								bodyPadding : 10,
								layout : "border",
								items : [{
											region : "west",
											width : 450,
											layout : "fit",
											split : true,
											items : [me.getUSTemplateGrid()]
										}, {
											region : "center",
											layout : "fit",
											items : [me
													.getUSTemplateDetailGrid()]
										}]
							}, {
								region : "north",
								border : 0,
								layout : {
									type : "table",
									columns : 4
								},
								height : 110,
								bodyPadding : 5,
								items : [{
											html : "<h1>选择消耗单模板</h1>",
											border : 0,
											colspan : 4
										}, {
											id : "SelectUSTemplateForm_editRef",
											xtype : "textfield",
											labelAlign : "right",
											labelSeparator : "",
											fieldLabel : "模板编号"
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

		me.editRef = Ext.getCmp("SelectUSTemplateForm_editRef");
	},

	onWndShow : function() {
		var me = this;
	},

	onOK : function() {
		var me = this;

		var item = me.getUSTemplateGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			PSI.MsgBox.showInfo("请选择消耗单模板");
			return;
		}
		var bill = item[0];
		me.close();

		var parentForm = me.getParentForm();
		if (parentForm) {
			var f = parentForm.getUSTemplateInfo;
			if (f) {
				f.call(parentForm, bill.get("id"));
			}
		}
	},

	getUSTemplateGrid : function() {
		var me = this;

		if (me.__billGrid) {
			return me.__billGrid;
		}

		var modelName = "PSIUSBill_USTemplateSelectForm";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "ref", "billMemo"]
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
								+ "Home/US/selectUSTemplateList",
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
			title : "模板列表",
			columns : [{
						header : "模板编号",
						dataIndex : "ref",
						menuDisabled : true,
						sortable : false,
						width : 200
					}, {
						header : "备注",
						dataIndex : "billMemo",
						menuDisabled : true,
						sortable : false,
						width : 200
					}],
			listeners : {
				select : {
					fn : me.onUSTemplateGridSelect,
					scope : me
				},
				itemdblclick : {
					fn : me.onOK,
					scope : me
				}
			},
			store : store,
			bbar : [{
						id : "usbill_selectform_pagingToobar",
						xtype : "pagingtoolbar",
						border : 0,
						store : store
					}, "-", {
						xtype : "displayfield",
						value : "每页显示"
					}, {
						id : "usbill_selectform_comboCountPerPage",
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
											.getCmp("usbill_selectform_comboCountPerPage")
											.getValue();
									store.currentPage = 1;
									Ext
											.getCmp("usbill_selectform_pagingToobar")
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

	getUSTemplateDetailGrid : function() {
		var me = this;
		if (me.__usTemplateDetailGrid) {
			return me.__usTemplateDetailGrid;
		}

		var modelName = "SelectUSTemplateForm_BillDetail";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "goodsCode", "goodsName", "goodsSpec",
							"unitName", "saleCount", "lostCount"]
				});
		var store = Ext.create("Ext.data.Store", {
					autoLoad : false,
					model : modelName,
					data : []
				});

		me.__usTemplateDetailGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					title : "消耗单模板物资明细",
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
								header : "贩卖量",
								dataIndex : "saleCount",
								menuDisabled : true,
								sortable : false,
								align : "right"
							}, {
								header : "半成品损耗",
								dataIndex : "lostCount",
								menuDisabled : true,
								sortable : false,
								align : "right"
							}, {
								header : "单位",
								dataIndex : "unitName",
								menuDisabled : true,
								sortable : false,
								width : 80
							}],
					store : store
				});

		return me.__usTemplateDetailGrid;
	},

	onQuery : function() {
		Ext.getCmp("usbill_selectform_pagingToobar").doRefresh();

		this.refreshDetailGrid();
	},

	getQueryParam : function() {
		var me = this;

		var result = {};

		var ref = me.editRef.getValue();
		if (ref) {
			result.ref = ref;
		}

		return result;
	},

	onClearQuery : function() {
		var me = this;

		me.editRef.setValue(null);

		me.onQuery();
	},

	onUSTemplateGridSelect : function() {
		var me = this;
		me.getUSTemplateDetailGrid().setTitle("消耗单模板物资明细");

		me.refreshDetailGrid();
	},

	refreshDetailGrid : function() {
		var me = this;
		me.getUSTemplateDetailGrid().setTitle("消耗单模板物资明细");
		me.getUSTemplateDetailGrid().getStore().removeAll();
		var item = me.getUSTemplateGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return;
		}
		var bill = item[0];

		var grid = me.getUSTemplateDetailGrid();
		grid.setTitle("模板编号: " + bill.get("ref"));
		var el = grid.getEl();
		el.mask(PSI.Const.LOADING);

		var r = {
			url : me.URL("Home/US/usTemplateDetailListForUSBill"),
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