/**
 * 物资 - 主界面
 * 
 * @author 李静波
 */
Ext.define("PSI.Goods.MainForm", {
	extend : "PSI.AFX.BaseMainExForm",

	config : {
		pAddCategory : null,
		pEditCategory : null,
		pDeleteCategory : null,
		pAddGoods : null,
		pEditGoods : null,
		pDeleteGoods : null,
		pGoodsSI : null
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
								border : 0,
								height : 65,
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
									region : "center",
									xtype : "panel",
									layout : "border",
									border : 0,
									items : [{
												region : "center",
												layout : "fit",
												border : 0,
												items : [me.getMainGrid()]
											}, {
												region : "south",
												layout : "fit",
												height : 200,
												split : true,
												collapsible : true,
												collapseMode : "mini",
												header : false,
												xtype : "tabpanel",
												border : 0,
												items : [me.getUnitGroupGrid(),
														me.getSIGrid()]
											}]
								}, {
									id : "panelCategory",
									xtype : "panel",
									region : "west",
									layout : "fit",
									width : 430,
									split : true,
									collapsible : true,
									header : false,
									border : 0,
									items : [me.getCategoryGrid()]
								}]
							}]
				});

		me.callParent(arguments);

		me.queryTotalGoodsCount();

		me.__queryEditNameList = ["editQueryCode", "editQueryName",
				"editQuerySpec", "editQueryBarCode"];
	},

	getToolbarCmp : function() {
		var me = this;

		return [{
					text : "新增物资分类",
					disabled : me.getPAddCategory() == "0",
					handler : me.onAddCategory,
					scope : me
				}, {
					text : "编辑物资分类",
					disabled : me.getPEditCategory() == "0",
					handler : me.onEditCategory,
					scope : me
				}, {
					text : "删除物资分类",
					disabled : me.getPDeleteCategory() == "0",
					handler : me.onDeleteCategory,
					scope : me
				}, "-", {
					text : "新增物资",
					disabled : me.getPAddGoods() == "0",
					handler : me.onAddGoods,
					scope : me
				}, {
					text : "修改物资",
					disabled : me.getPEditGoods() == "0",
					handler : me.onEditGoods,
					scope : me
				}, {
					text : "删除物资",
					disabled : me.getPDeleteGoods() == "0",
					handler : me.onDeleteGoods,
					scope : me
				}, "-", {
					text : "关闭",
					handler : function() {
						me.closeWindow();
					}
				}];
	},

	getQueryCmp : function() {
		var me = this;
		return [{
					id : "editQueryCode",
					labelWidth : 60,
					labelAlign : "right",
					labelSeparator : "",
					fieldLabel : "物资编码",
					margin : "5, 0, 0, 0",
					xtype : "textfield",
					listeners : {
						specialkey : {
							fn : me.onQueryEditSpecialKey,
							scope : me
						}
					}
				}, {
					id : "editQueryName",
					labelWidth : 60,
					labelAlign : "right",
					labelSeparator : "",
					fieldLabel : "品名",
					margin : "5, 0, 0, 0",
					xtype : "textfield",
					listeners : {
						specialkey : {
							fn : me.onQueryEditSpecialKey,
							scope : me
						}
					}
				}, {
					id : "editQuerySpec",
					labelWidth : 60,
					labelAlign : "right",
					labelSeparator : "",
					fieldLabel : "规格型号",
					margin : "5, 0, 0, 0",
					xtype : "textfield",
					listeners : {
						specialkey : {
							fn : me.onQueryEditSpecialKey,
							scope : me
						}
					}
				}, {
					xtype : "container",
					items : [{
								xtype : "button",
								text : "查询",
								width : 100,
								height : 26,
								margin : "5, 0, 0, 20",
								handler : me.onQuery,
								scope : me
							}, {
								xtype : "button",
								text : "清空查询条件",
								width : 100,
								height : 26,
								margin : "5, 0, 0, 5",
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
				}, {
					id : "editQueryBarCode",
					labelWidth : 60,
					labelAlign : "right",
					labelSeparator : "",
					fieldLabel : "条形码",
					margin : "5, 0, 0, 0",
					xtype : "textfield",
					listeners : {
						specialkey : {
							fn : me.onLastQueryEditSpecialKey,
							scope : me
						}
					}
				}];
	},

	getMainGrid : function() {
		var me = this;
		if (me.__mainGrid) {
			return me.__mainGrid;
		}
		var modelName = "PSIGoods";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "code", "name", "spec", "ABC", "unitId",
							"unitName", "categoryId", "costPriceCheckups",
							"purchasePriceUpper", "barCode", "memo", "dataOrg",
							"brandFullName", "py", "useQC", "qcDays"]
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
						url : me.URL("Home/Goods/goodsList"),
						reader : {
							root : 'goodsList',
							totalProperty : 'totalCount'
						}
					}
				});

		store.on("beforeload", function() {
					store.proxy.extraParams = me.getQueryParam();
				});
		store.on("load", function(e, records, successful) {
					if (successful) {
						me.refreshCategoryCount();
						me.gotoGoodsGridRecord(me.__lastId);
					}
				});

		me.__mainGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					viewConfig : {
						enableTextSelection : true
					},
					header : {
						height : 30,
						title : me.formatGridHeaderTitle("物资列表")
					},
					bbar : ["->", {
								id : "pagingToolbar",
								border : 0,
								xtype : "pagingtoolbar",
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
											Ext.getCmp("pagingToolbar")
													.doRefresh();
										},
										scope : me
									}
								}
							}, {
								xtype : "displayfield",
								value : "条记录"
							}],
					columnLines : true,
					columns : [Ext.create("Ext.grid.RowNumberer", {
										text : "序号",
										width : 40
									}), {
								header : "物资编码",
								dataIndex : "code",
								menuDisabled : true,
								sortable : false
							}, {
								header : "品名",
								dataIndex : "name",
								menuDisabled : true,
								sortable : false,
								width : 300
							}, {
								header : "规格型号",
								dataIndex : "spec",
								menuDisabled : true,
								sortable : false,
								width : 200
							}, {
								header : "ABC分类",
								dataIndex : "ABC",
								menuDisabled : true,
								sortable : false
							}, {
								header : "基本单位",
								dataIndex : "unitName",
								menuDisabled : true,
								sortable : false,
								width : 100
							}, {
								header : "品牌",
								dataIndex : "brandFullName",
								menuDisabled : true,
								sortable : false,
								width : 120
							}, {
								header : "考核成本价",
								dataIndex : "costPriceCheckups",
								menuDisabled : true,
								sortable : false,
								align : "right",
								xtype : "numbercolumn"
							}, {
								header : "最高进货价",
								dataIndex : "purchasePriceUpper",
								menuDisabled : true,
								sortable : false,
								align : "right",
								xtype : "numbercolumn"
							}, {
								header : "启用保质期管理",
								dataIndex : "useQC",
								menuDisabled : true,
								sortable : false,
								width : 120
							}, {
								header : "保质期(天)",
								dataIndex : "qcDays",
								menuDisabled : true,
								sortable : false
							}, {
								header : "条形码",
								dataIndex : "barCode",
								menuDisabled : true,
								sortable : false
							}, {
								header : "备注",
								dataIndex : "memo",
								menuDisabled : true,
								sortable : false,
								width : 300
							}, {
								header : "拼音助记码",
								dataIndex : "py",
								menuDisabled : true,
								sortable : false
							}, {
								header : "数据域",
								dataIndex : "dataOrg",
								menuDisabled : true,
								sortable : false
							}],
					store : store,
					listeners : {
						itemdblclick : {
							fn : me.onEditGoods,
							scope : me
						},
						select : {
							fn : me.onGoodsSelect,
							scope : me
						}
					}
				});

		return me.__mainGrid;
	},

	/**
	 * 新增物资分类
	 */
	onAddCategory : function() {
		var me = this;

		var form = Ext.create("PSI.Goods.CategoryEditForm", {
					parentForm : me
				});

		form.show();
	},

	/**
	 * 编辑物资分类
	 */
	onEditCategory : function() {
		var me = this;

		var item = this.categoryGrid.getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("请选择要编辑的物资分类");
			return;
		}

		var category = item[0];

		var form = Ext.create("PSI.Goods.CategoryEditForm", {
					parentForm : me,
					entity : category
				});

		form.show();
	},

	/**
	 * 删除物资分类
	 */
	onDeleteCategory : function() {
		var me = this;
		var item = me.categoryGrid.getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("请选择要删除的物资分类");
			return;
		}

		var category = item[0];

		var store = me.categoryGrid.getStore();

		var info = "请确认是否删除物资分类: <span style='color:red'>"
				+ category.get("text") + "</span>";

		me.confirm(info, function() {
			var el = Ext.getBody();
			el.mask("正在删除中...");
			me.ajax({
						url : me.URL("Home/Goods/deleteCategory"),
						params : {
							id : category.get("id")
						},
						callback : function(options, success, response) {
							el.unmask();

							if (success) {
								var data = me.decodeJSON(response.responseText);
								if (data.success) {
									me.tip("成功完成删除操作")
									me.freshCategoryGrid();
								} else {
									me.showInfo(data.msg);
								}
							} else {
								me.showInfo("网络错误");
							}
						}

					});
		});
	},

	/**
	 * 刷新物资分类Grid
	 */
	freshCategoryGrid : function(id) {
		var me = this;
		var store = me.getCategoryGrid().getStore();
		store.load();
	},

	/**
	 * 刷新物资Grid
	 */
	freshGoodsGrid : function() {
		var me = this;
		var item = me.getCategoryGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.getMainGrid().setTitle(me.formatGridHeaderTitle("物资列表"));
			return;
		}

		Ext.getCmp("pagingToolbar").doRefresh()
	},

	onCategoryGridSelect : function() {
		var me = this;
		me.getSIGrid().setTitle("物资安全库存");
		me.getSIGrid().getStore().removeAll();

		me.getUnitGroupGrid().setTitle("单位组");
		me.getUnitGroupGrid().getStore().removeAll();

		me.getMainGrid().getStore().currentPage = 1;

		me.freshGoodsGrid();
	},

	/**
	 * 新增物资
	 */
	onAddGoods : function() {
		var me = this;

		if (me.getCategoryGrid().getStore().getCount() == 0) {
			me.showInfo("没有物资分类，请先新增物资分类");
			return;
		}

		var form = Ext.create("PSI.Goods.GoodsEditForm", {
					parentForm : me
				});

		form.show();
	},

	/**
	 * 编辑物资
	 */
	onEditGoods : function() {
		var me = this;
		if (me.getPEditGoods() == "0") {
			return;
		}

		var item = me.getCategoryGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("请选择物资分类");
			return;
		}

		var category = item[0];

		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("请选择要编辑的物资");
			return;
		}

		var goods = item[0];
		goods.set("categoryId", category.get("id"));
		var form = Ext.create("PSI.Goods.GoodsEditForm", {
					parentForm : me,
					entity : goods
				});

		form.show();
	},

	/**
	 * 删除物资
	 */
	onDeleteGoods : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("请选择要删除的物资");
			return;
		}

		var goods = item[0];

		var store = me.getMainGrid().getStore();
		var index = store.findExact("id", goods.get("id"));
		index--;
		var preItem = store.getAt(index);
		if (preItem) {
			me.__lastId = preItem.get("id");
		}

		var info = "请确认是否删除物资: <span style='color:red'>" + goods.get("name")
				+ " " + goods.get("spec") + "</span>";

		me.confirm(info, function() {
			var el = Ext.getBody();
			el.mask("正在删除中...");
			me.ajax({
						url : me.URL("Home/Goods/deleteGoods"),
						params : {
							id : goods.get("id")
						},
						callback : function(options, success, response) {
							el.unmask();

							if (success) {
								var data = me.decodeJSON(response.responseText);
								if (data.success) {
									me.tip("成功完成删除操作");
									me.freshGoodsGrid();
								} else {
									me.showInfo(data.msg);
								}
							} else {
								me.showInfo("网络错误");
							}
						}

					});
		});
	},

	gotoCategoryGridRecord : function(id) {
		var me = this;
		var grid = me.getCategoryGrid();
		var store = grid.getStore();
		if (id) {
			var r = store.findExact("id", id);
			if (r != -1) {
				grid.getSelectionModel().select(r);
			} else {
				grid.getSelectionModel().select(0);
			}
		}
	},

	gotoGoodsGridRecord : function(id) {
		var me = this;
		var grid = me.getMainGrid();
		var store = grid.getStore();
		if (id) {
			var r = store.findExact("id", id);
			if (r != -1) {
				grid.getSelectionModel().select(r);
			} else {
				grid.getSelectionModel().select(0);
			}
		}
	},

	refreshCategoryCount : function() {
		var me = this;
		var item = me.getCategoryGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return;
		}
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
			this.onQuery();
		}
	},

	getQueryParamForCategory : function() {
		var me = this;
		var result = {};

		if (Ext.getCmp("editQueryCode") == null) {
			return result;
		}

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

		var barCode = Ext.getCmp("editQueryBarCode").getValue();
		if (barCode) {
			result.barCode = barCode;
		}

		return result;
	},

	getQueryParam : function() {
		var me = this;
		var item = me.getCategoryGrid().getSelectionModel().getSelection();
		var categoryId;
		if (item == null || item.length != 1) {
			categoryId = null;
		} else {
			categoryId = item[0].get("id");
		}

		var result = {
			categoryId : categoryId
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

		var barCode = Ext.getCmp("editQueryBarCode").getValue();
		if (barCode) {
			result.barCode = barCode;
		}

		return result;
	},

	/**
	 * 查询
	 */
	onQuery : function() {
		var me = this;

		me.getMainGrid().getStore().removeAll();
		me.getSIGrid().setTitle("物资安全库存");
		me.getSIGrid().getStore().removeAll();

		me.queryTotalGoodsCount();

		me.freshCategoryGrid();
	},

	/**
	 * 清除查询条件
	 */
	onClearQuery : function() {
		var me = this;
		var nameList = me.__queryEditNameList;
		for (var i = 0; i < nameList.length; i++) {
			var name = nameList[i];
			var edit = Ext.getCmp(name);
			if (edit) {
				edit.setValue(null);
			}
		}

		me.onQuery();
	},

	/**
	 * 安全库存Grid
	 */
	getSIGrid : function() {
		var me = this;
		if (me.__siGrid) {
			return me.__siGrid;
		}

		var modelName = "PSIGoodsSafetyInventory";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "warehouseCode", "warehouseName",
							"safetyInventory", "inventoryCount", "unitName",
							"inventoryUpper"]
				});

		me.__siGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					viewConfig : {
						enableTextSelection : true
					},
					title : "物资安全库存",
					tbar : [{
								text : "设置物资安全库存",
								disabled : me.getPGoodsSI() == "0",
								iconCls : "PSI-button-commit",
								handler : me.onSafetyInventory,
								scope : me
							}],
					columnLines : true,
					columns : [{
								header : "仓库编码",
								dataIndex : "warehouseCode",
								width : 80,
								menuDisabled : true,
								sortable : false
							}, {
								header : "仓库名称",
								dataIndex : "warehouseName",
								width : 100,
								menuDisabled : true,
								sortable : false
							}, {
								header : "库存上限",
								dataIndex : "inventoryUpper",
								width : 120,
								menuDisabled : true,
								sortable : false,
								align : "right",
								xtype : "numbercolumn",
								format : "0"
							}, {
								header : "安全库存量",
								dataIndex : "safetyInventory",
								width : 120,
								menuDisabled : true,
								sortable : false,
								align : "right",
								xtype : "numbercolumn",
								format : "0"
							}, {
								header : "当前库存",
								dataIndex : "inventoryCount",
								width : 120,
								menuDisabled : true,
								sortable : false,
								align : "right",
								xtype : "numbercolumn",
								format : "0"
							}, {
								header : "基本单位",
								dataIndex : "unitName",
								width : 80,
								menuDisabled : true,
								sortable : false
							}],
					store : Ext.create("Ext.data.Store", {
								model : modelName,
								autoLoad : false,
								data : []
							}),
					listeners : {
						itemdblclick : {
							fn : me.onSafetyInventory,
							scope : me
						}
					}
				});

		return me.__siGrid;
	},

	onGoodsSelect : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.getSIGrid().setTitle("物资安全库存");
			me.getUnitGroupGrid().setTitle("单位组");
			return;
		}

		me.refreshGoodsUnitGroup();

		me.refreshGoodsSI();
	},

	refreshGoodsUnitGroup : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return;
		}

		var goods = item[0];
		var info = goods.get("code") + " " + goods.get("name") + " "
				+ goods.get("spec");

		var grid = me.getUnitGroupGrid();
		grid.setTitle("物资[" + info + "]的单位组");

		var el = grid.getEl() || Ext.getBody();
		el.mask(PSI.Const.LOADING);
		me.ajax({
					url : me.URL("Home/Goods/goodsUnitGroupList"),
					method : "POST",
					params : {
						id : goods.get("id")
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
				});
	},

	refreshGoodsSI : function() {
		var me = this;
		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return;
		}

		var goods = item[0];
		var info = goods.get("code") + " " + goods.get("name") + " "
				+ goods.get("spec");

		var grid = me.getSIGrid();
		grid.setTitle("物资[" + info + "]的安全库存");

		var el = grid.getEl() || Ext.getBody();
		el.mask(PSI.Const.LOADING);
		me.ajax({
					url : me.URL("Home/Goods/goodsSafetyInventoryList"),
					method : "POST",
					params : {
						id : goods.get("id")
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
				});
	},

	/**
	 * 设置安全库存
	 */
	onSafetyInventory : function() {
		var me = this;
		if (me.getPGoodsSI() == "0") {
			return;
		}

		var item = me.getMainGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			me.showInfo("请选择要设置安全库存的物资");
			return;
		}

		var goods = item[0];

		var form = Ext.create("PSI.Goods.SafetyInventoryEditForm", {
					parentForm : me,
					entity : goods
				});

		form.show();
	},

	/**
	 * 导入物资资料
	 */
	onImportGoods : function() {
		var form = Ext.create("PSI.Goods.GoodsImportForm", {
					parentForm : this
				});

		form.show();
	},

	/**
	 * 物资分类Grid
	 */
	getCategoryGrid : function() {
		var me = this;
		if (me.__categoryGrid) {
			return me.__categoryGrid;
		}

		var modelName = "PSIGoodsCategory";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "text", "fullName", "code", "cnt", "leaf",
							"children"]
				});

		var store = Ext.create("Ext.data.TreeStore", {
			model : modelName,
			proxy : {
				type : "ajax",
				actionMethods : {
					read : "POST"
				},
				url : me.URL("Home/Goods/allCategories")
			},
			listeners : {
				beforeload : {
					fn : function() {
						store.proxy.extraParams = me.getQueryParamForCategory();
					},
					scope : me
				}
			}

		});

		store.on("load", me.onCategoryStoreLoad, me);

		me.__categoryGrid = Ext.create("Ext.tree.Panel", {
					cls : "PSI",
					header : {
						height : 30,
						title : me.formatGridHeaderTitle("物资分类")
					},
					store : store,
					rootVisible : false,
					useArrows : true,
					viewConfig : {
						loadMask : true
					},
					tools : [{
								type : "close",
								handler : function() {
									Ext.getCmp("panelCategory").collapse();
								}
							}],
					bbar : [{
								id : "fieldTotalGoodsCount",
								xtype : "displayfield",
								value : "共用物资0种"
							}],
					columns : {
						defaults : {
							sortable : false,
							menuDisabled : true,
							draggable : false
						},
						items : [{
									xtype : "treecolumn",
									text : "分类",
									dataIndex : "text",
									width : 220
								}, {
									text : "编码",
									dataIndex : "code",
									width : 100
								}, {
									text : "物资种类数",
									dataIndex : "cnt",
									align : "right",
									width : 100,
									renderer : function(value) {
										return value == 0 ? "" : value;
									}
								}]
					},
					listeners : {
						select : {
							fn : function(rowModel, record) {
								me.onCategoryTreeNodeSelect(record);
							},
							scope : me
						}
					}
				});

		me.categoryGrid = me.__categoryGrid;

		return me.__categoryGrid;
	},

	onCategoryStoreLoad : function() {
		var me = this;
		var tree = me.getCategoryGrid();
		var root = tree.getRootNode();
		if (root) {
			var node = root.firstChild;
			if (node) {
				// me.onOrgTreeNodeSelect(node);
			}
		}
	},

	onCategoryTreeNodeSelect : function(record) {
		if (!record) {
			me.getMainGrid().setTitle(me.formatGridHeaderTitle("物资列表"));
			return;
		}

		var me = this;

		var title = "属于物资分类 [" + record.get("fullName") + "] 的物资列表";
		me.getMainGrid().setTitle(me.formatGridHeaderTitle(title));

		me.onCategoryGridSelect();
	},

	queryTotalGoodsCount : function() {
		var me = this;
		me.ajax({
					url : me.URL("Home/Goods/getTotalGoodsCount"),
					params : me.getQueryParamForCategory(),
					callback : function(options, success, response) {

						if (success) {
							var data = me.decodeJSON(response.responseText);
							Ext.getCmp("fieldTotalGoodsCount").setValue("共有物资"
									+ data.cnt + "种");
						}
					}
				});
	},

	getUnitGroupGrid : function() {
		var me = this;
		if (me.__ugGrid) {
			return me.__ugGrid;
		}

		var modelName = "PSIGoodsUnitGroup";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "name", "factor", "factorType", "memo"]
				});

		me.__ugGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					viewConfig : {
						enableTextSelection : true
					},
					title : "单位组",
					columnLines : true,
					columns : [{
								header : "单位",
								dataIndex : "name",
								width : 150,
								menuDisabled : true,
								sortable : false
							}, {
								header : "转换率",
								dataIndex : "factor",
								width : 120,
								menuDisabled : true,
								sortable : false,
								align : "right"
							}, {
								header : "转换率类型",
								dataIndex : "factorType",
								width : 120,
								menuDisabled : true,
								sortable : false
							}, {
								header : "转换率说明",
								dataIndex : "memo",
								width : 250,
								menuDisabled : true,
								sortable : false
							}],
					store : Ext.create("Ext.data.Store", {
								model : modelName,
								autoLoad : false,
								data : []
							})
				});

		return me.__ugGrid;
	}
});