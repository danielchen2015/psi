/**
 * 门店盘点模板管理 - 主界面
 * 
 * @author 李静波
 */
Ext.define("PSI.US.USTMainForm", {
	extend : "PSI.AFX.BaseMainExForm",

	/**
	 * 初始化组件
	 */
	initComponent : function() {
		var me = this;

		Ext.apply(me, {
					tbar : me.getToolbarCmp(),
					items : [{
								id : "panelQueryCmp",
								region : "north",
								height : 35,
								layout : "fit",
								border : 0,
								header : false,
								collapsible : true,
								collapseMode : "mini",
								layout : {
									type : "table",
									columns : 4
								},
								items : me.getQueryCmp()
							}, {
								region : "center",
								layout : "border",
								border : 0,
								items : [{
											region : "north",
											height : "40%",
											split : true,
											layout : "fit",
											border : 0,
											items : [me.getMainGrid()]
										}, {
											region : "center",
											layout : "fit",
											xtype : "tabpanel",
											border : 0,
											items : [me.getDetailGrid(),
													me.getUSTemplateOrgGrid()]
										}]
							}]
				});

		me.callParent(arguments);

		me.refreshMainGrid();
	},

	/**
	 * 工具栏
	 */
	getToolbarCmp : function() {
		var me = this;
		return [{
					text : "新建消耗单模板",
					scope : me,
					handler : me.onAddBill,
					id : "buttonAdd"
				}, {
					xtype : "tbseparator"
				}, {
					text : "编辑消耗单模板",
					scope : me,
					handler : me.onEditBill,
					id : "buttonEdit"
				}, {
					xtype : "tbseparator"
				}, {
					text : "删除消耗单模板",
					scope : me,
					handler : me.onDeleteBill,
					id : "buttonDelete"
				}, {
					xtype : "tbseparator",
					id : "tbseparator1"
				}, {
					text : "关闭",
					handler : function() {
						me.closeWindow();
					}
				}];
	},

	/**
	 * 查询条件
	 */
	getQueryCmp : function() {
		var me = this;
		return [{
					id : "editQueryBillStatus",
					xtype : "combo",
					queryMode : "local",
					editable : false,
					valueField : "id",
					labelWidth : 60,
					labelAlign : "right",
					labelSeparator : "",
					fieldLabel : "状态",
					margin : "5, 0, 0, 0",
					store : Ext.create("Ext.data.ArrayStore", {
								fields : ["id", "text"],
								data : [[-1, "全部"], [1000, "启用"], [0, "停用"]]
							}),
					value : -1
				}, {
					id : "editQueryRef",
					labelWidth : 60,
					labelAlign : "right",
					labelSeparator : "",
					fieldLabel : "模板编号",
					margin : "5, 0, 0, 0",
					xtype : "textfield"
				}, {
					xtype : "container",
					items : [{
								xtype : "button",
								text : "查询",
								width : 100,
								height : 26,
								margin : "5 0 0 10",
								handler : me.onQuery,
								scope : me
							}, {
								xtype : "button",
								text : "清空查询条件",
								width : 100,
								height : 26,
								margin : "5, 0, 0, 10",
								handler : me.onClearQuery,
								scope : me
							}]
				}, {
					xtype : "container",
					items : [{
								xtype : "button",
								iconCls : "PSI-button-hide",
								text : "隐藏查询条件栏",
								width : 130,
								height : 26,
								margin : "5 0 0 10",
								handler : function() {
									Ext.getCmp("panelQueryCmp").collapse();
								},
								scope : me
							}]
				}];
	},

	/**
	 * 采购订单主表
	 */
	getMainGrid : function() {
		var me = this;
		if (me.__mainGrid) {
			return me.__mainGrid;
		}

		var modelName = "PSIUSTemplate";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "ref", "inputUserName", "billStatus",
							"dateCreated", "billMemo"]
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
						url : me.URL("Home/US/usTemplateList"),
						reader : {
							root : 'dataList',
							totalProperty : 'totalCount'
						}
					}
				});
		store.on("beforeload", function() {
					store.proxy.extraParams = me.getQueryParam();
				});
		store.on("load", function(e, records, successful) {
					if (successful) {
						me.gotoMainGridRecord(me.__lastId);
					}
				});

		me.__mainGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					viewConfig : {
						enableTextSelection : true
					},
					border : 1,
					columnLines : true,
					columns : [{
								xtype : "rownumberer",
								width : 50
							}, {
								header : "状态",
								dataIndex : "billStatus",
								menuDisabled : true,
								sortable : false,
								width : 100,
								renderer : function(value) {
									if (value == 0) {
										return "<span style='color:red'>停用</span>";
									} else if (value == 1000) {
										return "启用";
									} else {
										return "";
									}
								}
							}, {
								header : "模板编号",
								dataIndex : "ref",
								width : 200,
								menuDisabled : true,
								sortable : false
							}, {
								header : "编制人",
								dataIndex : "inputUserName",
								menuDisabled : true,
								sortable : false
							}, {
								header : "编制时间",
								dataIndex : "dateCreated",
								menuDisabled : true,
								sortable : false,
								width : 140
							}, {
								header : "备注",
								dataIndex : "billMemo",
								menuDisabled : true,
								sortable : false,
								width : 300
							}],
					store : store,
					bbar : ["->", {
								id : "pagingToobar",
								xtype : "pagingtoolbar",
								border : 0,
								store : store
							}, "-", {
								xtype : "displayfield",
								value : "每页显示"
							}, {
								id : "comboCountPerPage",
								xtype : "combobox",
								editable : false,
								width : 60,
								store : Ext.create("Ext.data.ArrayStore", {
											fields : ["text"],
											data : [["20"], ["50"], ["100"],
													["300"], ["1000"]]
										}),
								value : 20,
								listeners : {
									change : {
										fn : function() {
											store.pageSize = Ext
													.getCmp("comboCountPerPage")
													.getValue();
											store.currentPage = 1;
											Ext.getCmp("pagingToobar")
													.doRefresh();
										},
										scope : me
									}
								}
							}, {
								xtype : "displayfield",
								value : "条记录"
							}],
					listeners : {
						select : {
							fn : me.onMainGridSelect,
							scope : me
						}
					}
				});

		return me.__mainGrid;
	},

	getDetailGrid : function() {
		var me = this;
		if (me.__detailGrid) {
			return me.__detailGrid;
		}

		var modelName = "PSIUSTemplateDetail";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "goodsCode", "goodsName", "goodsSpec",
							"unitName", "saleCount", "lostCount", "memo"]
				});
		var store = Ext.create("Ext.data.Store", {
					autoLoad : false,
					model : modelName,
					data : []
				});

		me.__detailGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					title : "模板明细",
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
							}, {
								header : "备注",
								dataIndex : "memo",
								menuDisabled : true,
								sortable : false,
								width : 150
							}],
					store : store
				});

		return me.__detailGrid;
	},

	getUSTemplateOrgGrid : function() {
		var me = this;
		if (me.__usTemplateOrgGrid) {
			return me.__usTemplateOrgGrid;
		}

		var modelName = "PSIUSTemplateOrg_MainForm";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "orgName", "orgType"]
				});
		var store = Ext.create("Ext.data.Store", {
					autoLoad : false,
					model : modelName,
					data : []
				});

		me.__usTemplateOrgGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					title : "消耗单模板使用组织机构",
					viewConfig : {
						enableTextSelection : true
					},
					columnLines : true,
					columns : [{
								header : "组织机构",
								dataIndex : "orgName",
								menuDisabled : true,
								sortable : false,
								width : 280
							}, {
								header : "性质",
								dataIndex : "orgType",
								menuDisabled : true,
								sortable : false
							}],
					store : store
				});

		return me.__usTemplateOrgGrid;
	},

	refreshMainGrid : function(id) {
		var me = this;

		var gridDetail = me.getDetailGrid();
		gridDetail.setTitle("门店消耗单模板明细");
		gridDetail.getStore().removeAll();

		Ext.getCmp("pagingToobar").doRefresh();
		me.__lastId = id;
	},

	onAddBill : function() {
		var me = this;

		var form = Ext.create("PSI.US.USTEditForm", {
					parentForm : me
				});
		form.show();
	},

	onEditBill : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("没有选择要编辑的门店消耗单模板");
			return;
		}
		var bill = item[0];

		var form = Ext.create("PSI.US.USTEditForm", {
					parentForm : me,
					entity : bill
				});
		form.show();
	},

	onDeleteBill : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("请选择要删除的门店消耗单模板");
			return;
		}

		var bill = item[0];

		var store = me.getMainGrid().getStore();
		var index = store.findExact("id", bill.get("id"));
		index--;
		var preIndex = null;
		var preItem = store.getAt(index);
		if (preItem) {
			preIndex = preItem.get("id");
		}

		var info = "请确认是否删除门店消耗单模板: <span style='color:red'>" + bill.get("ref")
				+ "</span>";
		var funcConfirm = function() {
			var el = Ext.getBody();
			el.mask("正在删除中...");
			var r = {
				url : me.URL("Home/US/deleteUSTemplate"),
				params : {
					id : bill.get("id")
				},
				callback : function(options, success, response) {
					el.unmask();

					if (success) {
						var data = me.decodeJSON(response.responseText);
						if (data.success) {
							me.showInfo("成功完成删除操作", function() {
										me.refreshMainGrid(preIndex);
									});
						} else {
							me.showInfo(data.msg);
						}
					} else {
						me.showInfo("网络错误");
					}
				}
			};
			me.ajax(r);
		};

		me.confirm(info, funcConfirm);
	},

	onMainGridSelect : function() {
		var me = this;
		me.getDetailGrid().setTitle("门店消耗单模板明细");
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			Ext.getCmp("buttonEdit").setDisabled(true);
			Ext.getCmp("buttonDelete").setDisabled(true);

			return;
		}

		me.refreshDetailGrid();
		me.refreshOrgGrid();
	},

	refreshDetailGrid : function(id) {
		var me = this;
		me.getDetailGrid().setTitle("消耗单模板明细");
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return;
		}
		var bill = item[0];

		var grid = me.getDetailGrid();
		grid.setTitle("模板编号: " + bill.get("ref"));
		var el = grid.getEl();
		el && el.mask(PSI.Const.LOADING);

		var r = {
			url : me.URL("Home/US/usTemplateDetailList"),
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

				el && el.unmask();
			}
		};
		me.ajax(r);
	},

	refreshOrgGrid : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return;
		}
		var bill = item[0];

		var grid = me.getUSTemplateOrgGrid();
		var el = grid.getEl();
		el && el.mask(PSI.Const.LOADING);

		var r = {
			url : me.URL("Home/US/usTemplateOrgList"),
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

				el && el.unmask();
			}
		};
		me.ajax(r);
	},

	gotoMainGridRecord : function(id) {
		var me = this;
		var grid = me.getMainGrid();
		grid.getSelectionModel().deselectAll();
		var store = grid.getStore();
		if (id) {
			var r = store.findExact("id", id);
			if (r != -1) {
				grid.getSelectionModel().select(r);
			} else {
				grid.getSelectionModel().select(0);
			}
		} else {
			grid.getSelectionModel().select(0);
		}
	},

	/**
	 * 查询
	 */
	onQuery : function() {
		var me = this;

		me.getMainGrid().getStore().currentPage = 1;
		me.refreshMainGrid();
	},

	/**
	 * 清除查询条件
	 */
	onClearQuery : function() {
		var me = this;

		Ext.getCmp("editQueryBillStatus").setValue(-1);
		Ext.getCmp("editQueryRef").setValue(null);

		me.onQuery();
	},

	getQueryParam : function() {
		var me = this;

		var result = {
			billStatus : Ext.getCmp("editQueryBillStatus").getValue()
		};

		var ref = Ext.getCmp("editQueryRef").getValue();
		if (ref) {
			result.ref = ref;
		}

		return result;
	}
});