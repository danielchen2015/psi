/**
 * 向门店发货 - 主界面
 * 
 * @author 李静波
 */
Ext.define("PSI.LD.MainForm", {
	extend : "PSI.AFX.BaseMainExForm",

	config : {
		permission : null
	},

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
											items : me.getMainGrid()
										}, {
											region : "center",
											layout : "fit",
											border : 0,
											items : me.getDetailGrid()
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
					text : "新建物流单",
					scope : me,
					handler : me.onAddBill,
					hidden : me.getPermission().add == "0",
					id : "buttonAdd"
				}, {
					hidden : me.getPermission().add == "0",
					xtype : "tbseparator"
				}, {
					text : "编辑物流单",
					scope : me,
					handler : me.onEditBill,
					hidden : me.getPermission().edit == "0",
					id : "buttonEdit"
				}, {
					hidden : me.getPermission().edit == "0",
					xtype : "tbseparator"
				}, {
					text : "删除物流单",
					scope : me,
					handler : me.onDeleteBill,
					hidden : me.getPermission().del == "0",
					id : "buttonDelete"
				}, {
					xtype : "tbseparator",
					hidden : me.getPermission().del == "0"
				}, {
					text : "发货提交出库",
					scope : me,
					handler : me.onCommit,
					hidden : me.getPermission().commit == "0",
					id : "buttonCommit"
				}, {
					xtype : "tbseparator",
					hidden : me.getPermission().commit == "0"
				}, {
					text : "门店退货提交入库",
					scope : me,
					handler : me.onCommitRej,
					hidden : me.getPermission().commitRej == "0",
					id : "buttonCommitRej"
				}, {
					xtype : "tbseparator",
					hidden : me.getPermission().commitRej == "0"
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
						data : [[-1, "全部"], [0, "待发货出库"], [1000, "已发货出库待收货"],
								[2000, "已全部收货"], [3000, "部分收货并退货待入库"],
								[4000, "退货已入库"]]
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
			id : "editQueryOrg",
			xtype : "psi_orgwithdataorgfield",
			parentCmp : me,
			labelAlign : "right",
			labelSeparator : "",
			labelWidth : 60,
			margin : "5, 0, 0, 0",
			fieldLabel : "收货门店",
			colspan : 2,
			width : 430
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

		var modelName = "PSIPOBill";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "ref", "fromOrgName", "fromWarehouseName",
							"toOrgName", "toWarehouseName", "inputUserName",
							"bizUserName", "billStatus", "dateCreated",
							"billMemo", "spoBillRef", "outType", "bizDT"]
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
						url : me.URL("Home/LD/ldbillList"),
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
								width : 160,
								renderer : function(value) {
									if (value == 0) {
										return "<span style='color:red'>待发货出库</span>";
									} else if (value == 1000) {
										return "已发货出库待收货";
									} else if (value == 2000) {
										return "已全部收货";
									} else if (value == 3000) {
										return "部分收货并退货待入库";
									} else if (value == 4000) {
										return "退货已入库";
									} else {
										return "";
									}
								}
							}, {
								header : "物流单号",
								dataIndex : "ref",
								width : 200,
								menuDisabled : true,
								sortable : false
							}, {
								header : "门店订货单号",
								dataIndex : "spoBillRef",
								menuDisabled : true,
								sortable : false,
								width : 200
							}, {
								header : "收货门店",
								dataIndex : "toOrgName",
								width : 200,
								menuDisabled : true,
								sortable : false
							}, {
								header : "收货仓库",
								dataIndex : "toWarehouseName",
								width : 200,
								menuDisabled : true,
								sortable : false
							}, {
								header : "发货组织机构",
								dataIndex : "fromOrgName",
								width : 200,
								menuDisabled : true,
								sortable : false
							}, {
								header : "发货仓库",
								dataIndex : "fromWarehouseName",
								width : 200,
								menuDisabled : true,
								sortable : false
							}, {
								header : "出库方式",
								dataIndex : "outType",
								menuDisabled : true,
								sortable : false,
								width : 300,
								renderer : function(value) {
									if (value == 0) {
										return "物资严格按保质期对应出库";
									} else if (value == 1) {
										return "物资按编码对应先进先出法出库(忽略保质期)";
									} else {
										return "";
									}
								}
							}, {
								header : "业务员",
								dataIndex : "bizUserName",
								menuDisabled : true,
								sortable : false
							}, {
								header : "业务日期",
								dataIndex : "bizDT",
								menuDisabled : true,
								sortable : false,
								width : 100
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
								width : 200
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
							fn : me.getPermission().edit == "1"
									? me.onEditBill
									: Ext.emptyFn,
							scope : me
						}
					}
				});

		return me.__mainGrid;
	},

	/**
	 * 采购订单明细记录
	 */
	getDetailGrid : function() {
		var me = this;
		if (me.__detailGrid) {
			return me.__detailGrid;
		}

		var modelName = "PSIPOBillDetail";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "goodsCode", "goodsName", "goodsSpec",
							"goodsCount", "unitName", "rejCount", "revCount",
							"qcBeginDT", "qcEndDT", "qcDays", "qcSN", "factor",
							"factorType", "skuGoodsCount", "skuUnitName"]
				});
		var store = Ext.create("Ext.data.Store", {
					autoLoad : false,
					model : modelName,
					data : []
				});

		me.__detailGrid = Ext.create("Ext.grid.Panel", {
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
								header : "发货出库数量",
								dataIndex : "goodsCount",
								menuDisabled : true,
								sortable : false,
								draggable : false,
								align : "right",
								width : 120
							}, {
								header : "门店退货数量",
								dataIndex : "rejCount",
								menuDisabled : true,
								sortable : false,
								align : "right",
								width : 150
							}, {
								header : "门店实收数量",
								dataIndex : "revCount",
								menuDisabled : true,
								sortable : false,
								align : "right",
								width : 150
							}, {
								header : "单位",
								dataIndex : "unitName",
								menuDisabled : true,
								sortable : false,
								width : 60
							}, {
								header : "转换率",
								dataIndex : "factor",
								menuDisabled : true,
								sortable : false,
								draggable : false,
								width : 80,
								align : "right"
							}, {
								header : "转换率类型",
								dataIndex : "factorType",
								menuDisabled : true,
								sortable : false,
								draggable : false,
								width : 90,
								renderer : function(value) {
									return value == 1 ? "浮动转换率" : "固定转换率";
								}
							}, {
								header : "转换后发货出库数量",
								dataIndex : "skuGoodsCount",
								menuDisabled : true,
								sortable : false,
								draggable : false,
								width : 140
							}, {
								header : "SKU单位",
								dataIndex : "skuUnitName",
								menuDisabled : true,
								sortable : false,
								draggable : false,
								width : 90
							}],
					store : store
				});

		return me.__detailGrid;
	},

	/**
	 * 刷新采购订单主表记录
	 */
	refreshMainGrid : function(id) {
		var me = this;

		Ext.getCmp("buttonEdit").setDisabled(true);
		Ext.getCmp("buttonDelete").setDisabled(true);
		Ext.getCmp("buttonCommit").setDisabled(true);

		var gridDetail = me.getDetailGrid();
		gridDetail.setTitle("物流单明细");
		gridDetail.getStore().removeAll();

		Ext.getCmp("pagingToobar").doRefresh();
		me.__lastId = id;
	},

	/**
	 * 新增物流单
	 */
	onAddBill : function() {
		var me = this;

		var form = Ext.create("PSI.LD.LDEditForm", {
					parentForm : me
				});
		form.show();
	},

	/**
	 * 编辑物流单
	 */
	onEditBill : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("没有选择要编辑的物流单");
			return;
		}
		var bill = item[0];

		var form = Ext.create("PSI.LD.LDEditForm", {
					parentForm : me,
					entity : bill
				});
		form.show();
	},

	/**
	 * 删除物流单
	 */
	onDeleteBill : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("请选择要删除的物流单");
			return;
		}

		var bill = item[0];

		if (bill.get("billStatus") > 0) {
			me.showInfo("当前物流单已经提交出库，不能删除");
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

		var info = "请确认是否删除物流单: <span style='color:red'>" + bill.get("ref")
				+ "</span>";
		var funcConfirm = function() {
			var el = Ext.getBody();
			el.mask("正在删除中...");
			var r = {
				url : me.URL("Home/LD/deleteLDBill"),
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
		me.getDetailGrid().setTitle("物流单明细");
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			Ext.getCmp("buttonEdit").setDisabled(true);
			Ext.getCmp("buttonDelete").setDisabled(true);
			Ext.getCmp("buttonCommit").setDisabled(true);

			return;
		}
		var bill = item[0];
		var commited = bill.get("billStatus") >= 1000;

		var buttonEdit = Ext.getCmp("buttonEdit");
		buttonEdit.setDisabled(false);
		if (commited) {
			buttonEdit.setText("查看物流单");
		} else {
			buttonEdit.setText("编辑物流单");
		}

		Ext.getCmp("buttonDelete").setDisabled(commited);
		Ext.getCmp("buttonCommit").setDisabled(commited);

		var commitedRej = bill.get("billStatus") != 3000;
		Ext.getCmp("buttonCommitRej").setDisabled(commitedRej);

		me.refreshDetailGrid();
	},

	/**
	 * 刷新明细记录
	 */
	refreshDetailGrid : function(id) {
		var me = this;
		me.getDetailGrid().setTitle("物流单明细");
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return;
		}
		var bill = item[0];

		var grid = me.getDetailGrid();
		grid.setTitle("单号: " + bill.get("ref"));
		var el = grid.getEl();
		el.mask(PSI.Const.LOADING);

		var r = {
			url : me.URL("Home/LD/ldBillDetailList"),
			params : {
				id : bill.get("id")
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
		};
		me.ajax(r);
	},

	/**
	 * 提交出库
	 */
	onCommit : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("没有选择要提交的物流单");
			return;
		}
		var bill = item[0];

		if (bill.get("billStatus") > 0) {
			me.showInfo("当前物流单已经提交，不能再次提交");
			return;
		}

		var detailCount = me.getDetailGrid().getStore().getCount();
		if (detailCount == 0) {
			me.showInfo("当前物流单没有录入物资明细，不能提交");
			return;
		}

		var info = "请确认是否提交出库单号: <span style='color:red'>" + bill.get("ref")
				+ "</span> 的物流单?";
		var id = bill.get("id");

		var funcConfirm = function() {
			var el = Ext.getBody();
			el.mask("正在提交中...");
			var r = {
				url : me.URL("Home/LD/commitLDBill"),
				params : {
					id : id
				},
				callback : function(options, success, response) {
					el.unmask();

					if (success) {
						var data = me.decodeJSON(response.responseText);
						if (data.success) {
							me.showInfo("成功完成出库操作", function() {
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
		me.confirm(info, funcConfirm);
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
		Ext.getCmp("editQueryFromDT").setValue(null);
		Ext.getCmp("editQueryToDT").setValue(null);
		Ext.getCmp("editQueryOrg").setIdValue(null);
		Ext.getCmp("editQueryOrg").setValue(null);

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

		var orgId = Ext.getCmp("editQueryOrg").getIdValue();
		if (orgId) {
			result.orgId = orgId;
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
	},

	onCommitRej : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("没有选择要提交的物流单");
			return;
		}
		var bill = item[0];

		if (bill.get("billStatus") < 2000) {
			me.showInfo("当前物流单还没有收货");
			return;
		}
		if (bill.get("billStatus") == 2000) {
			me.showInfo("当前物流单是全部收货，不需要做退货入库业务");
			return;
		}
		if (bill.get("billStatus") > 3000) {
			me.showInfo("当前物流单已经退货提交入库了，不能再次提交");
			return;
		}

		var info = "请确认是否提交入库单号为: <span style='color:red'>" + bill.get("ref")
				+ "</span> 的物流单?";
		var id = bill.get("id");

		var funcConfirm = function() {
			var el = Ext.getBody();
			el.mask("正在提交中...");
			var r = {
				url : me.URL("Home/LD/commitLDBillRej"),
				params : {
					id : id
				},
				callback : function(options, success, response) {
					el.unmask();

					if (success) {
						var data = me.decodeJSON(response.responseText);
						if (data.success) {
							me.showInfo("成功完成退货入库操作", function() {
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
		me.confirm(info, funcConfirm);
	}
});