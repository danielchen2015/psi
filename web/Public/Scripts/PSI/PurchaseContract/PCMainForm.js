/**
 * 供应合同 - 主界面
 * 
 * @author 李静波
 */
Ext.define("PSI.PurchaseContract.PCMainForm", {
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
											items : [me.getMainGrid()]
										}, {
											region : "center",
											layout : "fit",
											border : 0,
											xtype : "tabpanel",
											items : [me.getDetailGrid(),
													me.getPCTemplatePanel()]
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
					text : "新建供应合同",
					scope : me,
					handler : me.onAddBill,
					id : "buttonAdd"
				}, {
					xtype : "tbseparator"
				}, {
					text : "编辑供应合同",
					scope : me,
					handler : me.onEditBill,
					id : "buttonEdit"
				}, {
					xtype : "tbseparator"
				}, {
					text : "删除供应合同",
					scope : me,
					handler : me.onDeleteBill,
					id : "buttonDelete"
				}, {
					xtype : "tbseparator",
					id : "tbseparator1"
				}, {
					text : "审核",
					scope : me,
					handler : me.onCommit,
					id : "buttonCommit"
				}, {
					text : "取消审核",
					scope : me,
					handler : me.onCancelConfirm,
					id : "buttonCancelConfirm"
				}, {
					xtype : "tbseparator",
					id : "tbseparator2"
				}, {
					text : "关闭合同",
					id : "buttonCloseBill",
					menu : [{
								text : "关闭供应合同",
								iconCls : "PSI-button-commit",
								scope : me,
								handler : me.onClosePC
							}, "-", {
								text : "取消供应合同关闭状态",
								iconCls : "PSI-button-cancelconfirm",
								scope : me,
								handler : me.onCancelClosedPC
							}]
				}, {
					xtype : "tbseparator"
				}, {
					text : "采购模板",
					menu : [{
								text : "新建采购模板",
								scope : me,
								handler : me.onAddPCTemplate
							}, {
								text : "编辑采购模板",
								scope : me,
								handler : me.onEditPCTemplate
							}, {
								text : "删除采购模板",
								scope : me,
								handler : me.onDeletePCTemplate
							}]
				}, "-", {
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
						data : [[-1, "全部"], [0, "待审核"], [1000, "已审核"],
								[4000, "关闭"]]
					}),
			value : -1
		}, {
			id : "editQueryRef",
			labelWidth : 60,
			labelAlign : "right",
			labelSeparator : "",
			fieldLabel : "合同号",
			margin : "5, 0, 0, 0",
			xtype : "textfield"
		}, {
			id : "editQueryFromDT",
			xtype : "datefield",
			margin : "5, 0, 0, 0",
			format : "Y-m-d",
			labelAlign : "right",
			labelSeparator : "",
			fieldLabel : "合同日期（起）"
		}, {
			id : "editQueryToDT",
			xtype : "datefield",
			margin : "5, 0, 0, 0",
			format : "Y-m-d",
			labelAlign : "right",
			labelSeparator : "",
			fieldLabel : "合同日期（止）"
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

	getPCTemplatePanel : function() {
		var me = this;

		return {
			title : "采购模板",
			border : 0,
			layout : "border",
			items : [{
						region : "west",
						width : 300,
						layout : "fit",
						border : 0,
						split : true,
						items : [me.getPCTemplateGrid()]
					}, {
						region : "center",
						layout : "border",
						border : 0,
						items : [{
									region : "center",
									layout : "fit",
									border : 0,
									items : [me.getPCTemplateDetailGrid()]
								}, {
									region : "east",
									width : 300,
									border : 0,
									layout : "fit",
									split : true,
									items : [me.getPCTemplateOrgGrid()]
								}]
					}]
		};
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
			fields : ["id", "ref", "supplierName", "inputUserName", "bizDT",
					"fromDT", "toDT", "bizUserName", "billStatus",
					"dateCreated", "confirmUserName", "confirmDate", "billMemo"]
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
						url : me.URL("Home/PurchaseContract/pcbillList"),
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
										return "<span style='color:red'>待审核</span>";
									} else if (value == 1000) {
										return "已审核";
									} else if (value == 4000) {
										return "关闭";
									} else {
										return "";
									}
								}
							}, {
								header : "合同号",
								dataIndex : "ref",
								width : 110,
								menuDisabled : true,
								sortable : false
							}, {
								header : "合同签订日",
								dataIndex : "bizDT",
								menuDisabled : true,
								sortable : false
							}, {
								header : "供应商",
								dataIndex : "supplierName",
								width : 300,
								menuDisabled : true,
								sortable : false
							}, {
								header : "合同日期（起）",
								dataIndex : "fromDT",
								menuDisabled : true,
								sortable : false,
								width : 110
							}, {
								header : "合同日期（止）",
								dataIndex : "toDT",
								menuDisabled : true,
								sortable : false,
								width : 110
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
								header : "审核人",
								dataIndex : "confirmUserName",
								menuDisabled : true,
								sortable : false
							}, {
								header : "审核时间",
								dataIndex : "confirmDate",
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

		var modelName = "PSIPCBillDetail";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "goodsCode", "goodsName", "goodsSpec",
							"unitName", "goodsPrice", "memo"]
				});
		var store = Ext.create("Ext.data.Store", {
					autoLoad : false,
					model : modelName,
					data : []
				});

		me.__detailGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					title : "供应合同明细",
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
								header : "备注",
								dataIndex : "memo",
								menuDisabled : true,
								sortable : false,
								width : 120
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
		Ext.getCmp("buttonCancelConfirm").setDisabled(true);

		var gridDetail = me.getDetailGrid();
		gridDetail.setTitle("供应合同明细");
		gridDetail.getStore().removeAll();

		Ext.getCmp("pagingToobar").doRefresh();
		me.__lastId = id;
	},

	/**
	 * 新增供应合同
	 */
	onAddBill : function() {
		var me = this;

		var form = Ext.create("PSI.PurchaseContract.PCEditForm", {
					parentForm : me
				});
		form.show();
	},

	/**
	 * 编辑供应合同
	 */
	onEditBill : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("没有选择要编辑的供应合同");
			return;
		}
		var bill = item[0];

		var form = Ext.create("PSI.PurchaseContract.PCEditForm", {
					parentForm : me,
					entity : bill
				});
		form.show();
	},

	/**
	 * 删除供应合同
	 */
	onDeleteBill : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("请选择要删除的供应合同");
			return;
		}

		var bill = item[0];

		if (bill.get("billStatus") > 0) {
			me.showInfo("当前供应合同已经审核，不能删除");
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

		var info = "请确认是否删除供应合同: <span style='color:red'>" + bill.get("ref")
				+ "</span>";
		var funcConfirm = function() {
			var el = Ext.getBody();
			el.mask("正在删除中...");
			var r = {
				url : me.URL("Home/PurchaseContract/deletePCBill"),
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
										me.refreshPCTemplateGrid();
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
		me.getDetailGrid().setTitle("供应合同明细");
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			Ext.getCmp("buttonEdit").setDisabled(true);
			Ext.getCmp("buttonDelete").setDisabled(true);
			Ext.getCmp("buttonCommit").setDisabled(true);
			Ext.getCmp("buttonCancelConfirm").setDisabled(true);

			return;
		}
		var bill = item[0];
		var commited = bill.get("billStatus") >= 1000;

		var buttonEdit = Ext.getCmp("buttonEdit");
		buttonEdit.setDisabled(false);
		if (commited) {
			buttonEdit.setText("查看供应合同");
		} else {
			buttonEdit.setText("编辑供应合同");
		}

		Ext.getCmp("buttonDelete").setDisabled(commited);
		Ext.getCmp("buttonCommit").setDisabled(commited);
		Ext.getCmp("buttonCancelConfirm").setDisabled(!commited);

		me.refreshDetailGrid();
		me.refreshPCTemplateGrid();
	},

	/**
	 * 刷新供应合同明细记录
	 */
	refreshDetailGrid : function(id) {
		var me = this;
		me.getDetailGrid().setTitle("供应合同明细");
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return;
		}
		var bill = item[0];

		var grid = me.getDetailGrid();
		var title = "合同号: " + bill.get("ref") + " 供应商: "
				+ bill.get("supplierName");
		grid.setTitle(title);
		var el = grid.getEl();
		el.mask(PSI.Const.LOADING);

		var r = {
			url : me.URL("Home/PurchaseContract/pcBillDetailList"),
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
	 * 审核供应合同
	 */
	onCommit : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("没有选择要审核的供应合同");
			return;
		}
		var bill = item[0];

		if (bill.get("billStatus") > 0) {
			me.showInfo("当前供应合同已经审核，不能再次审核");
			return;
		}

		var detailCount = me.getDetailGrid().getStore().getCount();
		if (detailCount == 0) {
			me.showInfo("当前供应合同没有录入物资明细，不能审核");
			return;
		}

		var info = "请确认是否审核供应合同: <span style='color:red'>" + bill.get("ref")
				+ "</span> ?";
		var id = bill.get("id");

		var funcConfirm = function() {
			var el = Ext.getBody();
			el.mask("正在提交中...");
			var r = {
				url : me.URL("Home/PurchaseContract/commitPCBill"),
				params : {
					id : id
				},
				callback : function(options, success, response) {
					el.unmask();

					if (success) {
						var data = me.decodeJSON(response.responseText);
						if (data.success) {
							me.showInfo("成功完成审核操作", function() {
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

	/**
	 * 取消审核
	 */
	onCancelConfirm : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("没有选择要取消审核的供应合同");
			return;
		}
		var bill = item[0];

		if (bill.get("billStatus") == 0) {
			me.showInfo("当前供应合同还没有审核，无法取消审核");
			return;
		}
		if (bill.get("billStatus") == 4000) {
			me.showInfo("当前供应合同已经关闭，无法取消审核");
			return;
		}

		var info = "请确认是否取消审核供应合同 <span style='color:red'>" + bill.get("ref")
				+ "</span> ?";
		var id = bill.get("id");
		var funcConfirm = function() {
			var el = Ext.getBody();
			el.mask("正在提交中...");
			var r = {
				url : me.URL("Home/PurchaseContract/cancelConfirmPCBill"),
				params : {
					id : id
				},
				callback : function(options, success, response) {
					el.unmask();

					if (success) {
						var data = me.decodeJSON(response.responseText);
						if (data.success) {
							me.showInfo("成功完成取消审核操作", function() {
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
		Ext.getCmp("editQuerySupplier").clearIdValue();

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

	onClosePC : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("没有选择要关闭的供应合同");
			return;
		}
		var bill = item[0];

		var info = "请确认是否关闭单号: <span style='color:red'>" + bill.get("ref")
				+ "</span> 的供应合同?";
		var id = bill.get("id");

		var funcConfirm = function() {
			var el = Ext.getBody();
			el.mask("正在提交中...");
			var r = {
				url : me.URL("Home/PurchaseContract/closePCBill"),
				params : {
					id : id
				},
				callback : function(options, success, response) {
					el.unmask();

					if (success) {
						var data = me.decodeJSON(response.responseText);
						if (data.success) {
							me.showInfo("成功关闭供应合同", function() {
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

	onCancelClosedPC : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("没有选择要取消关闭状态的供应合同");
			return;
		}
		var bill = item[0];

		var info = "请确认是否取消供应合同: <span style='color:red'>" + bill.get("ref")
				+ "</span> 的关闭状态?";
		var id = bill.get("id");

		var funcConfirm = function() {
			var el = Ext.getBody();
			el.mask("正在提交中...");
			var r = {
				url : me.URL("Home/PurchaseContract/cancelClosedPCBill"),
				params : {
					id : id
				},
				callback : function(options, success, response) {
					el.unmask();

					if (success) {
						var data = me.decodeJSON(response.responseText);
						if (data.success) {
							me.showInfo("成功取消供应合同关闭状态", function() {
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

	getPCTemplateGrid : function() {
		var me = this;
		if (me.__pctemplateGrid) {
			return me.__pctemplateGrid;
		}

		var modelName = "PSIPCTemplate";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "ref", "billMemo", "billStatus"]
				});
		var store = Ext.create("Ext.data.Store", {
					autoLoad : false,
					model : modelName,
					data : []
				});

		me.__pctemplateGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					viewConfig : {
						enableTextSelection : true
					},
					title : "模板列表",
					columnLines : true,
					columns : [{
								header : "状态",
								dataIndex : "billStatus",
								menuDisabled : true,
								sortable : false,
								width : 80
							}, {
								header : "模板编号",
								dataIndex : "ref",
								menuDisabled : true,
								sortable : false,
								width : 200
							}, {
								header : "备注",
								dataIndex : "billMemo",
								menuDisabled : true,
								sortable : false
							}],
					store : store,
					listeners : {
						select : {
							fn : me.onPCTemplateGridSelect,
							scope : me
						}
					}
				});

		return me.__pctemplateGrid;
	},

	getPCTemplateDetailGrid : function() {
		var me = this;
		if (me.__pcTemplateDetailGrid) {
			return me.__pcTemplateDetailGrid;
		}

		var modelName = "PSIPCTemplateDetail";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "goodsCode", "goodsName", "goodsSpec",
							"unitName"]
				});
		var store = Ext.create("Ext.data.Store", {
					autoLoad : false,
					model : modelName,
					data : []
				});

		me.__pcTemplateDetailGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					title : "采购模板明细",
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
								header : "单位",
								dataIndex : "unitName",
								menuDisabled : true,
								sortable : false,
								width : 80
							}],
					store : store
				});

		return me.__pcTemplateDetailGrid;
	},

	getPCTemplateOrgGrid : function() {
		var me = this;
		if (me.__pcTemplateOrgGrid) {
			return me.__pcTemplateOrgGrid;
		}

		var modelName = "PSIPCTemplateOrg";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "orgName", "orgType"]
				});
		var store = Ext.create("Ext.data.Store", {
					autoLoad : false,
					model : modelName,
					data : []
				});

		me.__pcTemplateOrgGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					title : "采购模板使用组织机构",
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

		return me.__pcTemplateOrgGrid;
	},

	onAddPCTemplate : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("没有选择供应合同");
			return;
		}
		var pcBill = item[0];

		var billStatus = pcBill.get("billStatus");
		if (billStatus < 1000) {
			me.showInfo("供应合同还没有审核，不能制作采购模板");
			return;
		}

		if (billStatus > 1000) {
			me.showInfo("供应合同已经关闭，不能制作采购模板");
			return;
		}

		var form = Ext.create("PSI.PurchaseContract.PCTemplateEditForm", {
					parentForm : me,
					pcBill : pcBill
				});
		form.show();
	},

	onEditPCTemplate : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("没有选择供应合同");
			return;
		}
		var pcBill = item[0];

		var billStatus = pcBill.get("billStatus");
		if (billStatus < 1000) {
			me.showInfo("供应合同还没有审核，不能制作采购模板");
			return;
		}

		if (billStatus > 1000) {
			me.showInfo("供应合同已经关闭，不能制作采购模板");
			return;
		}

		var item = me.getPCTemplateGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("没有选择要编辑采购模板");
			return;
		}
		var template = item[0];

		var form = Ext.create("PSI.PurchaseContract.PCTemplateEditForm", {
					parentForm : me,
					pcBill : pcBill,
					entity : template
				});
		form.show();
	},

	resetPCTemplateGridTitle : function() {
		var me = this;
		me.getPCTemplateDetailGrid().setTitle("采购模板物资明细");
		me.getPCTemplateOrgGrid().setTitle("组织机构");
	},

	onDeletePCTemplate : function() {
		var me = this;
		var item = me.getPCTemplateGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("没有选择要删除采购模板");
			return;
		}
		var bill = item[0];

		var store = me.getPCTemplateGrid().getStore();
		var index = store.findExact("id", bill.get("id"));
		index--;
		var preIndex = null;
		var preItem = store.getAt(index);
		if (preItem) {
			preIndex = preItem.get("id");
		}

		var info = "请确认是否删除采购模板: <span style='color:red'>" + bill.get("ref")
				+ "</span>";
		var funcConfirm = function() {
			var el = Ext.getBody();
			el.mask("正在删除中...");
			var r = {
				url : me.URL("Home/PurchaseContract/deletePCTemplate"),
				params : {
					id : bill.get("id")
				},
				callback : function(options, success, response) {
					el.unmask();

					if (success) {
						var data = me.decodeJSON(response.responseText);
						if (data.success) {
							me.resetPCTemplateGridTitle();
							me.getPCTemplateDetailGrid().getStore().removeAll();
							me.getPCTemplateOrgGrid().getStore().removeAll();

							me.showInfo("成功完成删除操作", function() {
										me.refreshPCTemplateGrid(preIndex);
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

	refreshPCTemplateGrid : function(id) {
		var me = this;

		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.getPCTemplateGrid().getStore().removeAll();
			me.getPCTemplateDetailGrid().getStore().removeAll();
			me.getPCTemplateOrgGrid().getStore().removeAll();
			me.resetPCTemplateGridTitle();
			return;
		}
		var bill = item[0];

		var grid = me.getPCTemplateGrid();
		var el = grid.getEl() || Ext.getBody();
		el.mask(PSI.Const.LOADING);

		var r = {
			url : me.URL("Home/PurchaseContract/pcTemplateList"),
			params : {
				pcBillId : bill.get("id")
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

	onPCTemplateGridSelect : function() {
		var me = this;

		me.refreshPCTemplateDetailGrid();
	},

	refreshPCTemplateDetailGrid : function() {
		var me = this;
		var item = me.getPCTemplateGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.resetPCTemplateGridTitle();
			return;
		}
		var bill = item[0];

		me.getPCTemplateDetailGrid().setTitle("采购模板[" + bill.get("ref")
				+ "]物资明细");
		me.getPCTemplateOrgGrid().setTitle("可以使用采购模板[" + bill.get("ref")
				+ "]的组织机构");

		var grid = me.getPCTemplateDetailGrid() || Ext.getBody();
		var gridOrg = me.getPCTemplateOrgGrid();
		var el = grid.getEl();
		el.mask(PSI.Const.LOADING);

		var r = {
			url : me.URL("Home/PurchaseContract/pcTemplateDetailInfo"),
			params : {
				id : bill.get("id")
			},
			callback : function(options, success, response) {
				var store = grid.getStore();

				store.removeAll();

				gridOrg.getStore().removeAll();

				if (success) {
					var data = me.decodeJSON(response.responseText);
					store.add(data.items);

					gridOrg.getStore().add(data.orgs);
				}

				el.unmask();
			}
		};
		me.ajax(r);
	}
});