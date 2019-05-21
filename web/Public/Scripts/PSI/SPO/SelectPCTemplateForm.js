/**
 * 门店订货单-选择采购模板
 */
Ext.define("PSI.SPO.SelectPCTemplateForm", {
	extend : "PSI.AFX.BaseDialogForm",

	initComponent : function() {
		var me = this;
		Ext.apply(me, {
					title : "选择采购模板",
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
											items : [me.getPCTemplateGrid()]
										}, {
											region : "center",
											layout : "fit",
											items : [me
													.getPCTemplateDetailGrid()]
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
											html : "<h1>选择采购模板</h1>",
											border : 0,
											colspan : 4
										}, {
											id : "POSelectPCTemplateForm_editRef",
											xtype : "textfield",
											labelAlign : "right",
											labelSeparator : "",
											fieldLabel : "采购模板编号"
										}, {
											id : "POSelectPCTemplateForm_editSupplier",
											xtype : "psi_supplierfield",
											labelAlign : "right",
											labelSeparator : "",
											fieldLabel : "供应商"
										}, {
											id : "POSelectPCTemplateForm_editGoods",
											xtype : "psi_goodsfield",
											labelAlign : "right",
											labelSeparator : "",
											fieldLabel : "物资"
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

		me.editRef = Ext.getCmp("POSelectPCTemplateForm_editRef");
		me.editSupplier = Ext.getCmp("POSelectPCTemplateForm_editSupplier");
		me.editGoods = Ext.getCmp("POSelectPCTemplateForm_editGoods");
	},

	onWndShow : function() {
		var me = this;
	},

	onOK : function() {
		var me = this;

		var item = me.getPCTemplateGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			PSI.MsgBox.showInfo("请选择采购模板");
			return;
		}
		var bill = item[0];
		me.close();
		me.getParentForm().getPCTemplateInfo(bill.get("id"));
	},

	getPCTemplateGrid : function() {
		var me = this;

		if (me.__billGrid) {
			return me.__billGrid;
		}

		var modelName = "PSIPOBill_PCTemplateSelectForm";
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
				url : PSI.Const.BASE_URL + "Home/Purchase/selectPCTemplateList",
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
					fn : me.onPCTemplateGridSelect,
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

	getPCTemplateDetailGrid : function() {
		var me = this;
		if (me.__pcTemplateDetailGrid) {
			return me.__pcTemplateDetailGrid;
		}

		var modelName = "POSelectPCTemplateForm_BillDetail";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "goodsCode", "goodsName", "goodsSpec",
							"unitName", "unitId"]
				});
		var store = Ext.create("Ext.data.Store", {
					autoLoad : false,
					model : modelName,
					data : []
				});

		me.__pcTemplateDetailGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					title : "采购模板物资明细",
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

	onQuery : function() {
		Ext.getCmp("pobill_selectform_pagingToobar").doRefresh();

		this.refreshDetailGrid();
	},

	getQueryParam : function() {
		var me = this;

		var result = {};

		var ref = me.editRef.getValue();
		if (ref) {
			result.ref = ref;
		}

		var supplierId = me.editSupplier.getIdValue();
		if (supplierId) {
			result.supplierId = supplierId;
		}

		var goodsCode = me.editGoods.getValue();
		if (goodsCode) {
			result.goodsCode = goodsCode;
		}

		return result;
	},

	onClearQuery : function() {
		var me = this;

		me.editRef.setValue(null);
		me.editSupplier.clearIdValue();
		me.editGoods.setValue(null);

		me.onQuery();
	},

	onPCTemplateGridSelect : function() {
		var me = this;
		me.getPCTemplateDetailGrid().setTitle("采购模板物资明细");

		me.refreshDetailGrid();
	},

	refreshDetailGrid : function() {
		var me = this;
		me.getPCTemplateDetailGrid().setTitle("采购模板物资明细");
		me.getPCTemplateDetailGrid().getStore().removeAll();
		var item = me.getPCTemplateGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return;
		}
		var bill = item[0];

		var grid = me.getPCTemplateDetailGrid();
		grid.setTitle("模板编号: " + bill.get("ref"));
		var el = grid.getEl();
		el.mask(PSI.Const.LOADING);

		var r = {
			url : me.URL("Home/Purchase/pcTemplateDetailListForPOBill"),
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