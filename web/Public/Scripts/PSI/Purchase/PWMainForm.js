/**
 * 采购入库 - 主界面
 */
Ext.define("PSI.Purchase.PWMainForm", {
	extend : "PSI.AFX.BaseMainExForm",

	config : {
		permission : null
	},

	initComponent : function() {
		var me = this;

		Ext.apply(me, {
					tbar : me.getToolbarCmp(),
					items : [{
								id : "panelQueryCmp",
								region : "north",
								height : 65,
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
											border : 0,
											items : [me.getDetailGrid()]
										}]
							}]
				});

		me.callParent(arguments);

		me.refreshMainGrid();
	},

	getToolbarCmp : function() {
		var me = this;
		return [{
					text : "新建采购入库单",
					hidden : me.getPermission().add == "0",
					id : "buttonAdd",
					scope : me,
					handler : me.onAddBill
				}, {
					hidden : me.getPermission().add == "0",
					xtype : "tbseparator"
				}, {
					text : "编辑采购入库单",
					hidden : me.getPermission().edit == "0",
					scope : me,
					handler : me.onEditBill,
					id : "buttonEdit"
				}, {
					hidden : me.getPermission().edit == "0",
					xtype : "tbseparator"
				}, {
					text : "删除采购入库单",
					hidden : me.getPermission().del == "0",
					scope : me,
					handler : me.onDeleteBill,
					id : "buttonDelete"
				}, {
					hidden : me.getPermission().del == "0",
					xtype : "tbseparator"
				}, {
					text : "提交入库",
					hidden : me.getPermission().commit == "0",
					scope : me,
					handler : me.onCommit,
					id : "buttonCommit"
				}, {
					hidden : me.getPermission().commit == "0",
					xtype : "tbseparator"
				}, {
					text : "关闭",
					handler : function() {
						me.closeWindow();
					}
				}];
	},

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
						data : [[-1, "全部"], [0, "待入库"], [1000, "已入库"],
								[2000, "已退货"]]
					}),
			value : -1
		}, {
			id : "editQueryRef",
			labelWidth : 60,
			labelAlign : "right",
			labelSeparator : "",
			fieldLabel : "单号",
			margin : "5, 0, 0, 0",
			xtype : "textfield"
		}, {
			id : "editQueryFromDT",
			xtype : "datefield",
			margin : "5, 0, 0, 0",
			format : "Y-m-d",
			labelAlign : "right",
			labelSeparator : "",
			fieldLabel : "业务日期（起）"
		}, {
			id : "editQueryToDT",
			xtype : "datefield",
			margin : "5, 0, 0, 0",
			format : "Y-m-d",
			labelAlign : "right",
			labelSeparator : "",
			fieldLabel : "业务日期（止）"
		}, {
			id : "editQuerySupplier",
			xtype : "psi_supplierfield",
			parentCmp : me,
			labelAlign : "right",
			labelSeparator : "",
			labelWidth : 60,
			margin : "5, 0, 0, 0",
			fieldLabel : "供应商"
		}, {
			id : "editQueryWarehouse",
			xtype : "psi_warehousefield",
			parentCmp : me,
			labelAlign : "right",
			labelSeparator : "",
			labelWidth : 60,
			margin : "5, 0, 0, 0",
			fieldLabel : "仓库"
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
					}, {
						xtype : "button",
						text : "隐藏查询条件栏",
						width : 130,
						height : 26,
						iconCls : "PSI-button-hide",
						margin : "5 0 0 10",
						handler : function() {
							Ext.getCmp("panelQueryCmp").collapse();
						},
						scope : me
					}]
		}];
	},

	getMainGrid : function() {
		var me = this;
		if (me.__mainGrid) {
			return me.__mainGrid;
		}

		var modelName = "PSIPWBill";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "ref", "bizDate", "supplierName",
							"warehouseName", "inputUserName", "bizUserName",
							"billStatus", "amount", "dateCreated", "billMemo"]
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
						url : PSI.Const.BASE_URL + "Home/Purchase/pwbillList",
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
								width : 60,
								renderer : function(value) {
									if (value == "待入库") {
										return "<span style='color:red'>"
												+ value + "</span>";
									} else if (value == "已退货") {
										return "<span style='color:blue'>"
												+ value + "</span>";
									} else {
										return value;
									}
								}
							}, {
								header : "入库单号",
								dataIndex : "ref",
								width : 110,
								menuDisabled : true,
								sortable : false
							}, {
								header : "业务日期",
								dataIndex : "bizDate",
								menuDisabled : true,
								sortable : false
							}, {
								header : "供应商",
								dataIndex : "supplierName",
								width : 300,
								menuDisabled : true,
								sortable : false
							}, {
								header : "采购金额",
								dataIndex : "amount",
								menuDisabled : true,
								sortable : false,
								align : "right",
								xtype : "numbercolumn",
								width : 150,
								hidden : me.getPermission().viewPrice == "0"
							}, {
								header : "入库仓库",
								dataIndex : "warehouseName",
								menuDisabled : true,
								sortable : false
							}, {
								header : "业务员",
								dataIndex : "bizUserName",
								menuDisabled : true,
								sortable : false
							}, {
								header : "制单人",
								dataIndex : "inputUserName",
								menuDisabled : true,
								sortable : false
							}, {
								header : "制单时间",
								dataIndex : "dateCreated",
								menuDisabled : true,
								sortable : false,
								width : 140
							}, {
								header : "备注",
								dataIndex : "billMemo",
								menuDisabled : true,
								sortable : false,
								width : 150
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
						},
						itemdblclick : {
							fn : me.onEditBill,
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

		var modelName = "PSIPWBillDetail";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "goodsCode", "goodsName", "goodsSpec",
							"qcBeginDT", "qcEndDT", "qcDays", "qcSN",
							"unitName", "goodsCount", "skuUnitName", "factor",
							"factorType", "skuGoodsCount", "goodsMoney",
							"goodsPrice", "memo"]
				});
		var store = Ext.create("Ext.data.Store", {
					autoLoad : false,
					model : modelName,
					data : []
				});

		me.__detailGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					header : {
						height : 30,
						title : me.formatGridHeaderTitle("采购入库单明细")
					},
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
								header : "生产日期",
								dataIndex : "qcBeginDT",
								menuDisabled : true,
								sortable : false,
								width : 90
							}, {
								header : "保质期",
								dataIndex : "qcDays",
								menuDisabled : true,
								sortable : false,
								width : 90
							}, {
								header : "到期日期",
								dataIndex : "qcEndDT",
								menuDisabled : true,
								sortable : false,
								width : 90
							}, {
								header : "批号",
								dataIndex : "qcSN",
								menuDisabled : true,
								sortable : false,
								width : 90
							}, {
								header : "采购数量",
								width : 120,
								dataIndex : "goodsCount",
								menuDisabled : true,
								sortable : false,
								align : "right"
							}, {
								header : "采购单位",
								dataIndex : "unitName",
								menuDisabled : true,
								sortable : false,
								width : 80
							}, {
								header : "库存SKU数量",
								width : 120,
								dataIndex : "skuGoodsCount",
								menuDisabled : true,
								sortable : false,
								align : "right"
							}, {
								header : "库存SKU单位",
								dataIndex : "skuUnitName",
								menuDisabled : true,
								sortable : false,
								width : 90
							}, {
								header : "采购数量转换率",
								width : 120,
								dataIndex : "factor",
								menuDisabled : true,
								sortable : false,
								align : "right"
							}, {
								header : "转换率类型",
								dataIndex : "factorType",
								menuDisabled : true,
								sortable : false,
								width : 90,
								renderer : function(value) {
									return value == 1 ? "浮动转换率" : "固定转换率";
								}
							}, {
								header : "采购单价",
								dataIndex : "goodsPrice",
								menuDisabled : true,
								sortable : false,
								align : "right",
								xtype : "numbercolumn",
								width : 150,
								hidden : me.getPermission().viewPrice == "0"
							}, {
								header : "采购金额",
								dataIndex : "goodsMoney",
								menuDisabled : true,
								sortable : false,
								align : "right",
								xtype : "numbercolumn",
								width : 150,
								hidden : me.getPermission().viewPrice == "0"
							}, {
								header : "备注",
								dataIndex : "memo",
								menuDisabled : true,
								sortable : false,
								width : 200
							}],
					store : store
				});

		return me.__detailGrid;
	},

	refreshMainGrid : function(id) {
		var me = this;

		Ext.getCmp("buttonEdit").setDisabled(true);
		Ext.getCmp("buttonDelete").setDisabled(true);
		Ext.getCmp("buttonCommit").setDisabled(true);

		var gridDetail = me.getDetailGrid();
		gridDetail.setTitle(me.formatGridHeaderTitle("采购入库单明细"));
		gridDetail.getStore().removeAll();

		Ext.getCmp("pagingToobar").doRefresh();
		me.__lastId = id;
	},

	/**
	 * 新增采购入库单
	 */
	onAddBill : function() {
		var me = this;

		var form = Ext.create("PSI.Purchase.PWEditForm", {
					parentForm : me,
					showAddGoodsButton : me.getPermission().showAddGoodsButton
				});
		form.show();
	},

	/**
	 * 编辑采购入库单
	 */
	onEditBill : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("没有选择要编辑的采购入库单");
			return;
		}
		var bill = item[0];

		var form = Ext.create("PSI.Purchase.PWEditForm", {
					parentForm : me,
					entity : bill,
					showAddGoodsButton : me.getPermission().showAddGoodsButton,
					viewPrice : me.getPermission().viewPrice == "1"
				});
		form.show();
	},

	/**
	 * 删除采购入库单
	 */
	onDeleteBill : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("请选择要删除的采购入库单");
			return;
		}

		var bill = item[0];

		if (bill.get("billStatus") == "已入库") {
			me.showInfo("当前采购入库单已经提交入库，不能删除");
			return;
		}

		var store = me.getMainGrid().getStore();
		var index = store.findExact("id", bill.get("id"));
		index--;
		var preIndex = null;
		var preItem = store.getAt(index);
		if (preItem) {
			preIndex = preItem.get("id");
		}

		var info = "请确认是否删除采购入库单: <span style='color:red'>" + bill.get("ref")
				+ "</span>";
		var confirmFunc = function() {
			var el = Ext.getBody();
			el.mask("正在删除中...");

			var r = {
				url : me.URL("Home/Purchase/deletePWBill"),
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
		me.confirm(info, confirmFunc);
	},

	onMainGridSelect : function() {
		var me = this;
		me.getDetailGrid().setTitle(me.formatGridHeaderTitle("采购入库单明细"));
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			Ext.getCmp("buttonEdit").setDisabled(true);
			Ext.getCmp("buttonDelete").setDisabled(true);
			Ext.getCmp("buttonCommit").setDisabled(true);

			return;
		}
		var bill = item[0];
		var commited = bill.get("billStatus") == "已入库";

		var buttonEdit = Ext.getCmp("buttonEdit");
		buttonEdit.setDisabled(false);
		if (commited) {
			buttonEdit.setText("查看采购入库单");
		} else {
			buttonEdit.setText("编辑采购入库单");
		}

		Ext.getCmp("buttonDelete").setDisabled(commited);
		Ext.getCmp("buttonCommit").setDisabled(commited);

		me.refreshDetailGrid();
	},

	refreshDetailGrid : function(id) {
		var me = this;
		me.getDetailGrid().setTitle(me.formatGridHeaderTitle("采购入库单明细"));
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return;
		}
		var bill = item[0];

		var grid = me.getDetailGrid();
		grid.setTitle(me.formatGridHeaderTitle("单号: " + bill.get("ref")
				+ " 供应商: " + bill.get("supplierName") + " 入库仓库: "
				+ bill.get("warehouseName")));
		var el = grid.getEl();
		el.mask(PSI.Const.LOADING);
		me.ajax({
					url : me.URL("Home/Purchase/pwBillDetailList"),
					params : {
						pwBillId : bill.get("id")
					},
					callback : function(options, success, response) {
						var store = grid.getStore();

						store.removeAll();

						if (success) {
							var data = me.decodeJSON(response.responseText);
							store.add(data);

							if (store.getCount() > 0) {
								if (id) {
									var r = store.findExact("id", id);
									if (r != -1) {
										grid.getSelectionModel().select(r);
									}
								}
							}
						}

						el.unmask();
					}
				});
	},

	/**
	 * 提交采购入库单
	 */
	onCommit : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("没有选择要提交的采购入库单");
			return;
		}
		var bill = item[0];

		if (bill.get("billStatus") == "已入库") {
			me.showInfo("当前采购入库单已经提交入库，不能再次提交");
			return;
		}

		var detailCount = me.getDetailGrid().getStore().getCount();
		if (detailCount == 0) {
			me.showInfo("当前采购入库单没有录入商品明细，不能提交");
			return;
		}

		var info = "请确认是否提交单号: <span style='color:red'>" + bill.get("ref")
				+ "</span> 的采购入库单?";
		var id = bill.get("id");
		var confirmFunc = function() {
			var el = Ext.getBody();
			el.mask("正在提交中...");
			var r = {
				url : me.URL("Home/Purchase/commitPWBill"),
				params : {
					id : id
				},
				callback : function(options, success, response) {
					el.unmask();

					if (success) {
						var data = me.decodeJSON(response.responseText);
						if (data.success) {
							me.showInfo("成功完成提交操作", function() {
										me.refreshMainGrid(id);
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
		me.confirm(info, confirmFunc);
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

	onQuery : function() {
		var me = this;

		me.getMainGrid().getStore().currentPage = 1;
		me.refreshMainGrid();
	},

	onClearQuery : function() {
		var me = this;

		Ext.getCmp("editQueryBillStatus").setValue(-1);
		Ext.getCmp("editQueryRef").setValue(null);
		Ext.getCmp("editQueryFromDT").setValue(null);
		Ext.getCmp("editQueryToDT").setValue(null);
		Ext.getCmp("editQuerySupplier").clearIdValue();
		Ext.getCmp("editQueryWarehouse").clearIdValue();
		Ext.getCmp("editQueryPaymentType").setValue(-1);

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

		var supplierId = Ext.getCmp("editQuerySupplier").getIdValue();
		if (supplierId) {
			result.supplierId = supplierId;
		}

		var warehouseId = Ext.getCmp("editQueryWarehouse").getIdValue();
		if (warehouseId) {
			result.warehouseId = warehouseId;
		}

		var fromDT = Ext.getCmp("editQueryFromDT").getValue();
		if (fromDT) {
			result.fromDT = Ext.Date.format(fromDT, "Y-m-d");
		}

		var toDT = Ext.getCmp("editQueryToDT").getValue();
		if (toDT) {
			result.toDT = Ext.Date.format(toDT, "Y-m-d");
		}

		return result;
	}
});