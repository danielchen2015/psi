/**
 * 库存账查询 - 主界面
 */
Ext.define("PSI.Inventory.InvQueryMainForm", {
	extend : "PSI.AFX.BaseMainExForm",

	initComponent : function() {
		var me = this;

		Ext.define("PSIWarehouse", {
					extend : "Ext.data.Model",
					fields : ["id", "code", "name"]
				});

		Ext.define("PSIInventory", {
					extend : "Ext.data.Model",
					fields : ["id", "goodsId", "goodsCode", "goodsName",
							"goodsSpec", "unitName", "inCount", "inPrice",
							"inMoney", "outCount", "outPrice", "outMoney",
							"balanceCount", "balancePrice", "balanceMoney"]
				});

		Ext.define("PSIInventoryDetail", {
					extend : "Ext.data.Model",
					fields : ["id", "goodsCode", "goodsName", "goodsSpec",
							"unitName", "inCount", "inPrice", "inMoney",
							"outCount", "outPrice", "outMoney", "balanceCount",
							"balancePrice", "balanceMoney", "bizDT",
							"bizUserName", "refType", "refNumber"]
				});

		Ext.apply(me, {
			tbar : [{
						xtype : "displayfield",
						value : "查询条件: 物资编码"
					}, {
						cls : "PSI-toolbox",
						xtype : "textfield",
						id : "editQueryCode",
						listeners : {
							specialkey : {
								fn : me.onQueryEditSpecialKey,
								scope : me
							}
						}
					}, {
						xtype : "displayfield",
						value : "物资名称"
					}, {
						cls : "PSI-toolbox",
						xtype : "textfield",
						id : "editQueryName",
						listeners : {
							specialkey : {
								fn : me.onQueryEditSpecialKey,
								scope : me
							}
						}
					}, {
						xtype : "displayfield",
						value : "规格型号"
					}, {
						cls : "PSI-toolbox",
						xtype : "textfield",
						id : "editQuerySpec",
						listeners : {
							specialkey : {
								fn : me.onLastQueryEditSpecialKey,
								scope : me
							}
						}
					}, {
						text : "查询",
						iconCls : "PSI-button-refresh",
						handler : me.onQueryGoods,
						scope : me
					}, "-", {
						text : "清空查询条件",
						handler : me.onClearQuery,
						scope : me
					}, "-", {
						text : "关闭",
						handler : function() {
							me.closeWindow();
						}
					}],
			items : [{
						region : "north",
						height : 2,
						border : 0,
						bodyStyle : "background-color:#f5f5f5"
					}, {
						id : "panelWarehouse",
						region : "west",
						layout : "fit",
						border : 0,
						width : 200,
						split : true,
						collapsible : true,
						header : false,
						items : [me.getWarehouseGrid()]
					}, {
						region : "center",
						layout : "fit",
						border : 0,
						items : [{
							cls : "PSI",
							region : "south",
							layout : "fit",
							border : 0,
							xtype : "tabpanel",
							items : [{
								title : "SKU按物资编码查看",
								layout : "border",
								border : 0,
								items : [{
											region : "north",
											height : "60%",
											layout : "fit",
											border : 0,
											split : true,
											items : [me.getInventoryGrid()]
										}, {
											region : "center",
											layout : "fit",
											border : 0,
											items : [me
													.getInventoryDetailGrid()]
										}]
							}, {
								title : "SKU按保质期查看",
								layout : "border",
								border : 0,
								items : [{
											region : "north",
											height : "60%",
											layout : "fit",
											split : true,
											border : 0,
											items : [me.getQcInventoryGrid()]
										}, {
											region : "center",
											layout : "fit",
											border : 0,
											items : [me
													.getQcInventoryDetailGrid()]
										}]
							}]
						}]
					}]
		});

		me.callParent(arguments);

		me.__queryEditNameList = ["editQueryCode", "editQueryName",
				"editQuerySpec"];

		me.refreshWarehouseGrid();
	},

	getWarehouseGrid : function() {
		var me = this;
		if (me.__warehouseGrid) {
			return me.__warehouseGrid;
		}

		me.__warehouseGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					header : {
						height : 30,
						title : me.formatGridHeaderTitle("仓库")
					},
					tools : [{
								type : "close",
								handler : function() {
									Ext.getCmp("panelWarehouse").collapse();
								}
							}],
					columnLines : true,
					columns : [{
								header : "仓库编码",
								dataIndex : "code",
								menuDisabled : true,
								sortable : false,
								width : 80
							}, {
								header : "仓库名称",
								dataIndex : "name",
								menuDisabled : true,
								sortable : false,
								flex : 1
							}],
					store : Ext.create("Ext.data.Store", {
								model : "PSIWarehouse",
								autoLoad : false,
								data : []
							}),
					listeners : {
						select : {
							fn : me.onWarehouseGridSelect,
							scope : me
						}
					}
				});

		return me.__warehouseGrid;
	},

	refreshWarehouseGrid : function() {
		var grid = this.getWarehouseGrid();
		var el = grid.getEl() || Ext.getBody();
		el.mask(PSI.Const.LOADING);
		Ext.Ajax.request({
					url : PSI.Const.BASE_URL + "Home/Inventory/warehouseList",
					method : "POST",
					callback : function(options, success, response) {
						var store = grid.getStore();

						store.removeAll();

						if (success) {
							var data = Ext.JSON.decode(response.responseText);
							store.add(data);
						}

						el.unmask();
					}
				});
	},

	getInventoryGrid : function() {
		var me = this;
		if (me.__inventoryGrid) {
			return me.__inventoryGrid;
		}

		var store = Ext.create("Ext.data.Store", {
					model : "PSIInventory",
					pageSize : 20,
					proxy : {
						type : "ajax",
						actionMethods : {
							read : "POST"
						},
						url : PSI.Const.BASE_URL
								+ "Home/Inventory/inventoryList",
						reader : {
							root : 'dataList',
							totalProperty : 'totalCount'
						}
					},
					autoLoad : false,
					data : []
				});

		store.on("beforeload", function() {
					store.proxy.extraParams = me.getInventoryGridParam();
				});

		me.__inventoryGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					header : {
						height : 30,
						title : me.formatGridHeaderTitle("总账")
					},
					viewConfig : {
						enableTextSelection : true
					},
					bbar : ["->", {
								xtype : "pagingtoolbar",
								border : 0,
								store : store
							}],
					columnLines : true,
					columns : [{
								header : "物资编码",
								dataIndex : "goodsCode",
								menuDisabled : true,
								sortable : false
							}, {
								header : "物资名称",
								dataIndex : "goodsName",
								menuDisabled : true,
								sortable : false
							}, {
								header : "规格型号",
								dataIndex : "goodsSpec",
								menuDisabled : true,
								sortable : false
							}, {
								header : "单位",
								dataIndex : "unitName",
								menuDisabled : true,
								sortable : false,
								width : 60
							}, {
								header : "入库数量",
								align : "right",
								dataIndex : "inCount",
								menuDisabled : true,
								sortable : false
							}, {
								header : "平均入库成本单价",
								align : "right",
								xtype : "numbercolumn",
								dataIndex : "inPrice",
								menuDisabled : true,
								sortable : false,
								width : 130
							}, {
								header : "入库成本总金额",
								align : "right",
								xtype : "numbercolumn",
								dataIndex : "inMoney",
								menuDisabled : true,
								sortable : false,
								width : 120
							}, {
								header : "出库数量",
								align : "right",
								dataIndex : "outCount",
								menuDisabled : true,
								sortable : false
							}, {
								header : "平均出库成本单价",
								align : "right",
								xtype : "numbercolumn",
								dataIndex : "outPrice",
								menuDisabled : true,
								sortable : false,
								width : 130
							}, {
								header : "出库成本总金额",
								align : "right",
								xtype : "numbercolumn",
								dataIndex : "outMoney",
								menuDisabled : true,
								sortable : false,
								width : 120
							}, {
								header : "余额数量",
								align : "right",
								dataIndex : "balanceCount",
								menuDisabled : true,
								sortable : false
							}, {
								header : "余额平均单价",
								align : "right",
								xtype : "numbercolumn",
								dataIndex : "balancePrice",
								menuDisabled : true,
								sortable : false
							}, {
								header : "余额总金额",
								align : "right",
								xtype : "numbercolumn",
								dataIndex : "balanceMoney",
								menuDisabled : true,
								sortable : false
							}],
					store : store,
					listeners : {
						select : {
							fn : me.onInventoryGridSelect,
							scope : me
						}
					}
				});

		return me.__inventoryGrid;
	},

	getWarehouseIdParam : function() {
		var item = this.getWarehouseGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return null;
		}

		var warehouse = item[0];
		return warehouse.get("id");
	},

	getGoodsIdParam : function() {
		var item = this.getInventoryGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return null;
		}

		var inventory = item[0];
		return inventory.get("goodsId");
	},

	getQcGoodsIdParam : function() {
		var item = this.getQcInventoryGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return null;
		}

		var inventory = item[0];
		return inventory.get("goodsId");
	},

	getQcDetailQueryParam : function() {
		var me = this;

		var item = me.getQcInventoryGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return {};
		}

		var inv = item[0];

		return {
			warehouseId : me.getWarehouseIdParam(),
			goodsId : inv.get("goodsId"),
			qcBeginDT : inv.get("qcBeginDT"),
			qcDays : inv.get("qcDays"),
			qcSN : inv.get("qcSN"),
			dtFrom : Ext.Date
					.format(Ext.getCmp("dtQcFrom").getValue(), "Y-m-d"),
			dtTo : Ext.Date.format(Ext.getCmp("dtQcTo").getValue(), "Y-m-d")
		};
	},

	getInventoryDetailGrid : function() {
		var me = this;
		if (me.__inventoryDetailGrid) {
			return me.__inventoryDetailGrid;
		}

		var store = Ext.create("Ext.data.Store", {
			model : "PSIInventoryDetail",
			pageSize : 20,
			proxy : {
				type : "ajax",
				actionMethods : {
					read : "POST"
				},
				url : PSI.Const.BASE_URL + "Home/Inventory/inventoryDetailList",
				reader : {
					root : 'details',
					totalProperty : 'totalCount'
				}
			},
			autoLoad : false,
			data : []
		});

		store.on("beforeload", function() {
					Ext.apply(store.proxy.extraParams, {
								warehouseId : me.getWarehouseIdParam(),
								goodsId : me.getGoodsIdParam(),
								dtFrom : Ext.Date.format(Ext.getCmp("dtFrom")
												.getValue(), "Y-m-d"),
								dtTo : Ext.Date.format(Ext.getCmp("dtTo")
												.getValue(), "Y-m-d")
							});
				});

		me.__inventoryDetailGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					title : "明细账",
					viewConfig : {
						enableTextSelection : true
					},
					tbar : [{
								xtype : "displayfield",
								value : "业务日期 从"
							}, {
								id : "dtFrom",
								xtype : "datefield",
								format : "Y-m-d",
								width : 90
							}, {
								xtype : "displayfield",
								value : " 到 "
							}, {
								id : "dtTo",
								xtype : "datefield",
								format : "Y-m-d",
								width : 90,
								value : new Date()
							}, " ", {
								text : "查询",
								iconCls : "PSI-button-refresh",
								handler : me.onQuery,
								scope : me
							}, "->", {
								xtype : "pagingtoolbar",
								border : 0,
								store : store
							}],
					columnLines : true,
					columns : [Ext.create("Ext.grid.RowNumberer", {
										text : "序号",
										width : 40
									}), {
								header : "商品编码",
								dataIndex : "goodsCode",
								menuDisabled : true,
								sortable : false
							}, {
								header : "商品名称",
								dataIndex : "goodsName",
								menuDisabled : true,
								sortable : false
							}, {
								header : "规格型号",
								dataIndex : "goodsSpec",
								menuDisabled : true,
								sortable : false
							}, {
								header : "商品单位",
								dataIndex : "unitName",
								menuDisabled : true,
								sortable : false,
								width : 80
							}, {
								header : "入库数量",
								dataIndex : "inCount",
								align : "right",
								menuDisabled : true,
								sortable : false
							}, {
								header : "入库成本单价",
								dataIndex : "inPrice",
								align : "right",
								xtype : "numbercolumn",
								menuDisabled : true,
								sortable : false
							}, {
								header : "入库成本金额",
								dataIndex : "inMoney",
								align : "right",
								xtype : "numbercolumn",
								menuDisabled : true,
								sortable : false
							}, {
								header : "出库数量",
								dataIndex : "outCount",
								align : "right",
								menuDisabled : true,
								sortable : false
							}, {
								header : "出库成本单价",
								dataIndex : "outPrice",
								align : "right",
								xtype : "numbercolumn",
								menuDisabled : true,
								sortable : false
							}, {
								header : "出库成本金额",
								dataIndex : "outMoney",
								align : "right",
								xtype : "numbercolumn",
								menuDisabled : true,
								sortable : false
							}, {
								header : "余额数量",
								dataIndex : "balanceCount",
								align : "right",
								menuDisabled : true,
								sortable : false
							}, {
								header : "余额单价",
								dataIndex : "balancePrice",
								align : "right",
								xtype : "numbercolumn",
								menuDisabled : true,
								sortable : false
							}, {
								header : "余额金额",
								dataIndex : "balanceMoney",
								align : "right",
								xtype : "numbercolumn",
								menuDisabled : true,
								sortable : false
							}, {
								header : "业务日期",
								dataIndex : "bizDT",
								menuDisabled : true,
								sortable : false,
								width : 80
							}, {
								header : "业务员",
								dataIndex : "bizUserName",
								menuDisabled : true,
								sortable : false,
								width : 80
							}, {
								header : "业务类型",
								dataIndex : "refType",
								menuDisabled : true,
								sortable : false,
								width : 120
							}, {
								header : "业务单号",
								dataIndex : "refNumber",
								menuDisabled : true,
								sortable : false,
								width : 120,
								renderer : function(value, md, record) {
									return "<a href='"
											+ PSI.Const.BASE_URL
											+ "Home/Bill/viewIndex?fid=2003&refType="
											+ encodeURIComponent(record
													.get("refType"))
											+ "&ref="
											+ encodeURIComponent(record
													.get("refNumber"))
											+ "' target='_blank'>" + value
											+ "</a>";
								}
							}],
					store : store
				});

		var dt = new Date();
		dt.setDate(dt.getDate() - 7);
		Ext.getCmp("dtFrom").setValue(dt);

		return me.__inventoryDetailGrid;
	},

	onWarehouseGridSelect : function() {
		var me = this;

		me.refreshInventoryGrid();
		me.refreshQcInventoryGrid();
	},

	getInventoryGridParam : function() {
		var me = this;
		var item = me.getWarehouseGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return {};
		}

		var warehouse = item[0];
		var result = {
			warehouseId : warehouse.get("id")
		};

		var code = Ext.getCmp("editQueryCode").getValue();
		if (code) {
			result.code = code;
		}

		var name = Ext.getCmp("editQueryName").getValue();
		if (name) {
			result.name = name;
		}

		var spec = Ext.getCmp("editQuerySpec").getValue();
		if (spec) {
			result.spec = spec;
		}

		return result;
	},

	refreshInventoryGrid : function() {
		var me = this;
		me.getInventoryDetailGrid().getStore().removeAll();

		var item = me.getWarehouseGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return;
		}

		var warehouse = item[0];

		var grid = me.getInventoryGrid();
		grid.setTitle(me.formatGridHeaderTitle("仓库 [" + warehouse.get("name")
				+ "] 的总账"));

		grid.getStore().loadPage(1);
	},

	onInventoryGridSelect : function() {
		this.getInventoryDetailGrid().getStore().loadPage(1);
	},

	onQcInventoryGridSelect : function() {
		this.getQcInventoryDetailGrid().getStore().loadPage(1);
	},

	onQuery : function() {
		var dtTo = Ext.getCmp("dtTo").getValue();
		if (dtTo == null) {
			Ext.getCmp("dtTo").setValue(new Date());
		}

		var dtFrom = Ext.getCmp("dtFrom").getValue();
		if (dtFrom == null) {
			var dt = new Date();
			dt.setDate(dt.getDate() - 7);
			Ext.getCmp("dtFrom").setValue(dt);
		}

		this.getInventoryDetailGrid().getStore().loadPage(1);
	},

	onQueryEditSpecialKey : function(field, e) {
		if (e.getKey() === e.ENTER) {
			var me = this;
			var id = field.getId();
			for (var i = 0; i < me.__queryEditNameList.length - 1; i++) {
				var editorId = me.__queryEditNameList[i];
				if (id === editorId) {
					var edit = Ext.getCmp(me.__queryEditNameList[i + 1]);
					edit.focus();
					edit.setValue(edit.getValue());
				}
			}
		}
	},

	onLastQueryEditSpecialKey : function(field, e) {
		if (e.getKey() === e.ENTER) {
			this.onQueryGoods();
		}
	},

	onClearQuery : function() {
		var nameList = this.__queryEditNameList;
		for (var i = 0; i < nameList.length; i++) {
			var name = nameList[i];
			var edit = Ext.getCmp(name);
			if (edit) {
				edit.setValue(null);
			}
		}

		this.onQueryGoods();
	},

	onQueryGoods : function() {
		var me = this;

		me.refreshInventoryGrid();
		me.refreshQcInventoryGrid();
	},

	getQcInventoryGrid : function() {
		var me = this;
		if (me.__qcinventoryGrid) {
			return me.__qcinventoryGrid;
		}

		var modelName = "PSIQcInventory";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "goodsId", "goodsCode", "goodsName",
							"goodsSpec", "unitName", "inCount", "inPrice",
							"inMoney", "outCount", "outPrice", "outMoney",
							"balanceCount", "balancePrice", "balanceMoney",
							"qcBeginDT", "qcEndDT", "qcDays", "qcSN"]
				});

		var store = Ext.create("Ext.data.Store", {
					model : modelName,
					pageSize : 20,
					proxy : {
						type : "ajax",
						actionMethods : {
							read : "POST"
						},
						url : PSI.Const.BASE_URL
								+ "Home/Inventory/inventoryQcList",
						reader : {
							root : 'dataList',
							totalProperty : 'totalCount'
						}
					},
					autoLoad : false,
					data : []
				});

		store.on("beforeload", function() {
					store.proxy.extraParams = me.getQcInventoryGridParam();
				});

		me.__qcinventoryGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					header : {
						height : 30,
						title : me.formatGridHeaderTitle("总账")
					},
					viewConfig : {
						enableTextSelection : true
					},
					bbar : ["->", {
								xtype : "pagingtoolbar",
								border : 0,
								store : store
							}],
					columnLines : true,
					columns : [{
								header : "物资编码",
								dataIndex : "goodsCode",
								menuDisabled : true,
								sortable : false
							}, {
								header : "物资名称",
								dataIndex : "goodsName",
								menuDisabled : true,
								sortable : false
							}, {
								header : "规格型号",
								dataIndex : "goodsSpec",
								menuDisabled : true,
								sortable : false
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
								header : "单位",
								dataIndex : "unitName",
								menuDisabled : true,
								sortable : false,
								width : 60
							}, {
								header : "入库数量",
								align : "right",
								dataIndex : "inCount",
								menuDisabled : true,
								sortable : false
							}, {
								header : "平均入库成本单价",
								align : "right",
								xtype : "numbercolumn",
								dataIndex : "inPrice",
								menuDisabled : true,
								sortable : false,
								width : 130
							}, {
								header : "入库成本总金额",
								align : "right",
								xtype : "numbercolumn",
								dataIndex : "inMoney",
								menuDisabled : true,
								sortable : false,
								width : 120
							}, {
								header : "出库数量",
								align : "right",
								dataIndex : "outCount",
								menuDisabled : true,
								sortable : false
							}, {
								header : "平均出库成本单价",
								align : "right",
								xtype : "numbercolumn",
								dataIndex : "outPrice",
								menuDisabled : true,
								sortable : false,
								width : 130
							}, {
								header : "出库成本总金额",
								align : "right",
								xtype : "numbercolumn",
								dataIndex : "outMoney",
								menuDisabled : true,
								sortable : false,
								width : 120
							}, {
								header : "余额数量",
								align : "right",
								dataIndex : "balanceCount",
								menuDisabled : true,
								sortable : false
							}, {
								header : "余额平均单价",
								align : "right",
								xtype : "numbercolumn",
								dataIndex : "balancePrice",
								menuDisabled : true,
								sortable : false
							}, {
								header : "余额总金额",
								align : "right",
								xtype : "numbercolumn",
								dataIndex : "balanceMoney",
								menuDisabled : true,
								sortable : false
							}],
					store : store,
					listeners : {
						select : {
							fn : me.onQcInventoryGridSelect,
							scope : me
						}
					}
				});

		return me.__qcinventoryGrid;
	},

	getQcInventoryDetailGrid : function() {
		var me = this;
		if (me.__qcinventoryDetailGrid) {
			return me.__qcinventoryDetailGrid;
		}

		var modelName = "PSIQcInventoryDetail";

		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "goodsCode", "goodsName", "goodsSpec",
							"unitName", "inCount", "inPrice", "inMoney",
							"outCount", "outPrice", "outMoney", "balanceCount",
							"balancePrice", "balanceMoney", "bizDT",
							"bizUserName", "refType", "refNumber", "qcBeginDT",
							"qcEndDT", "qcDays", "qcSN"]
				});

		var store = Ext.create("Ext.data.Store", {
					model : modelName,
					pageSize : 20,
					proxy : {
						type : "ajax",
						actionMethods : {
							read : "POST"
						},
						url : PSI.Const.BASE_URL
								+ "Home/Inventory/inventoryQcDetailList",
						reader : {
							root : 'details',
							totalProperty : 'totalCount'
						}
					},
					autoLoad : false,
					data : []
				});

		store.on("beforeload", function() {
					Ext.apply(store.proxy.extraParams, me
									.getQcDetailQueryParam());
				});

		me.__qcinventoryDetailGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					title : "明细账",
					viewConfig : {
						enableTextSelection : true
					},
					tbar : [{
								xtype : "displayfield",
								value : "业务日期 从"
							}, {
								id : "dtQcFrom",
								xtype : "datefield",
								format : "Y-m-d",
								width : 90
							}, {
								xtype : "displayfield",
								value : " 到 "
							}, {
								id : "dtQcTo",
								xtype : "datefield",
								format : "Y-m-d",
								width : 90,
								value : new Date()
							}, " ", {
								text : "查询",
								iconCls : "PSI-button-refresh",
								handler : me.onQcQuery,
								scope : me
							}, "->", {
								xtype : "pagingtoolbar",
								border : 0,
								store : store
							}],
					columnLines : true,
					columns : [Ext.create("Ext.grid.RowNumberer", {
										text : "序号",
										width : 40
									}), {
								header : "物资编码",
								dataIndex : "goodsCode",
								menuDisabled : true,
								sortable : false
							}, {
								header : "物资名称",
								dataIndex : "goodsName",
								menuDisabled : true,
								sortable : false
							}, {
								header : "规格型号",
								dataIndex : "goodsSpec",
								menuDisabled : true,
								sortable : false
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
								header : "单位",
								dataIndex : "unitName",
								menuDisabled : true,
								sortable : false,
								width : 80
							}, {
								header : "入库数量",
								dataIndex : "inCount",
								align : "right",
								menuDisabled : true,
								sortable : false
							}, {
								header : "入库成本单价",
								dataIndex : "inPrice",
								align : "right",
								xtype : "numbercolumn",
								menuDisabled : true,
								sortable : false
							}, {
								header : "入库成本金额",
								dataIndex : "inMoney",
								align : "right",
								xtype : "numbercolumn",
								menuDisabled : true,
								sortable : false
							}, {
								header : "出库数量",
								dataIndex : "outCount",
								align : "right",
								menuDisabled : true,
								sortable : false
							}, {
								header : "出库成本单价",
								dataIndex : "outPrice",
								align : "right",
								xtype : "numbercolumn",
								menuDisabled : true,
								sortable : false
							}, {
								header : "出库成本金额",
								dataIndex : "outMoney",
								align : "right",
								xtype : "numbercolumn",
								menuDisabled : true,
								sortable : false
							}, {
								header : "余额数量",
								dataIndex : "balanceCount",
								align : "right",
								menuDisabled : true,
								sortable : false
							}, {
								header : "余额单价",
								dataIndex : "balancePrice",
								align : "right",
								xtype : "numbercolumn",
								menuDisabled : true,
								sortable : false
							}, {
								header : "余额金额",
								dataIndex : "balanceMoney",
								align : "right",
								xtype : "numbercolumn",
								menuDisabled : true,
								sortable : false
							}, {
								header : "业务日期",
								dataIndex : "bizDT",
								menuDisabled : true,
								sortable : false,
								width : 80
							}, {
								header : "业务员",
								dataIndex : "bizUserName",
								menuDisabled : true,
								sortable : false,
								width : 80
							}, {
								header : "业务类型",
								dataIndex : "refType",
								menuDisabled : true,
								sortable : false,
								width : 120
							}, {
								header : "业务单号",
								dataIndex : "refNumber",
								menuDisabled : true,
								sortable : false,
								width : 120,
								renderer : function(value, md, record) {
									return "<a href='"
											+ PSI.Const.BASE_URL
											+ "Home/Bill/viewIndex?fid=2003&refType="
											+ encodeURIComponent(record
													.get("refType"))
											+ "&ref="
											+ encodeURIComponent(record
													.get("refNumber"))
											+ "' target='_blank'>" + value
											+ "</a>";
								}
							}],
					store : store
				});

		var dt = new Date();
		dt.setDate(dt.getDate() - 7);
		Ext.getCmp("dtQcFrom").setValue(dt);

		return me.__qcinventoryDetailGrid;
	},

	getQcInventoryGridParam : function() {
		var me = this;
		var item = me.getWarehouseGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return {};
		}

		var warehouse = item[0];
		var result = {
			warehouseId : warehouse.get("id")
		};

		var code = Ext.getCmp("editQueryCode").getValue();
		if (code) {
			result.code = code;
		}

		var name = Ext.getCmp("editQueryName").getValue();
		if (name) {
			result.name = name;
		}

		var spec = Ext.getCmp("editQuerySpec").getValue();
		if (spec) {
			result.spec = spec;
		}

		return result;
	},

	onQcQuery : function() {
		var dtTo = Ext.getCmp("dtQcTo").getValue();
		if (dtTo == null) {
			Ext.getCmp("dtQcTo").setValue(new Date());
		}

		var dtFrom = Ext.getCmp("dtQcFrom").getValue();
		if (dtFrom == null) {
			var dt = new Date();
			dt.setDate(dt.getDate() - 7);
			Ext.getCmp("dtQcFrom").setValue(dt);
		}

		this.getQcInventoryDetailGrid().getStore().loadPage(1);
	},

	refreshQcInventoryGrid : function() {
		var me = this;
		me.getQcInventoryDetailGrid().getStore().removeAll();

		var item = me.getWarehouseGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return;
		}

		var warehouse = item[0];

		var grid = me.getQcInventoryGrid();
		grid.setTitle(me.formatGridHeaderTitle("仓库 [" + warehouse.get("name")
				+ "] 的总账"));

		grid.getStore().loadPage(1);
	}

});