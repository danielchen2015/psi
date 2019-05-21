/**
 * 选择物资
 */
Ext.define("PSI.PurchaseContract.SelectGoodsForm", {
			extend : "PSI.AFX.BaseDialogForm",

			config : {
				parentForm : null,
				pcBillId : null
			},

			title : "选择物资",
			width : 800,
			height : 500,
			modal : true,
			layout : "fit",

			initComponent : function() {
				var me = this;
				var modelName = "PSIPurchaseContractTemplate_SelectGoodsForm";
				Ext.define(modelName, {
							extend : "Ext.data.Model",
							fields : ["id", "goodsId", "goodsCode",
									"goodsName", "goodsSpec", "unitId", "unitName",
									"pcDetailId"]
						});

				var userStore = Ext.create("Ext.data.Store", {
							model : modelName,
							autoLoad : false,
							data : []
						});

				var grid = Ext.create("Ext.grid.Panel", {
							cls : "PSI",
							header : {
								height : 30,
								title : me.formatGridHeaderTitle("物资明细")
							},
							padding : 5,
							selModel : {
								mode : "MULTI"
							},
							selType : "checkboxmodel",
							store : userStore,
							columnLines : true,
							columns : [{
										header : "物资编码",
										dataIndex : "goodsCode",
										menuDisabled : true,
										sortable : false,
										draggable : false
									}, {
										header : "物资名称",
										dataIndex : "goodsName",
										menuDisabled : true,
										sortable : false,
										draggable : false,
										width : 200
									}, {
										header : "规格型号",
										dataIndex : "goodsSpec",
										menuDisabled : true,
										sortable : false,
										draggable : false,
										width : 200
									}, {
										header : "单位",
										dataIndex : "unitName",
										menuDisabled : true,
										sortable : false,
										draggable : false,
										width : 80
									}]
						});

				me.__grid = grid;

				Ext.apply(me, {
							items : [grid],
							buttons : [{
										text : "确定",
										formBind : true,
										iconCls : "PSI-button-ok",
										handler : me.onOK,
										scope : me
									}, {
										text : "取消",
										handler : function() {
											me.close();
										},
										scope : me
									}],
							listeners : {
								show : {
									fn : me.onWndShow,
									scope : me
								}
							}
						});

				me.callParent(arguments);
			},

			onWndShow : function() {
				var me = this;
				var store = me.__grid.getStore();
				store.removeAll();

				var el = me.getEl() || Ext.getBody();
				el.mask("数据加载中...");
				Ext.Ajax.request({
							url : PSI.Const.BASE_URL
									+ "Home/PurchaseContract/selectGoodsForPCTemplate",
							params : {
								pcbillId : me.getPcBillId()
							},
							method : "POST",
							callback : function(options, success, response) {
								if (success) {
									var data = Ext.JSON
											.decode(response.responseText);
									store.add(data);
								}

								el.unmask();
							}
						});
			},

			onOK : function() {
				var me = this;

				var grid = me.__grid;

				var items = grid.getSelectionModel().getSelection();
				if (items == null || items.length == 0) {
					PSI.MsgBox.showInfo("没有选择物资");

					return;
				}

				if (me.getParentForm()) {
					me.getParentForm().setSelectedGoods(items);
				}

				me.close();
			}
		});