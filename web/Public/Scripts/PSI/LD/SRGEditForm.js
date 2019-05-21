/**
 * 物流单 - 门店收货编辑界面
 */
Ext.define("PSI.LD.SRGEditForm", {
	extend : "PSI.AFX.BaseDialogForm",

	initComponent : function() {
		var me = this;
		me.__readonly = false;
		var entity = me.getEntity();
		this.adding = entity == null;

		var title = "物流单 - 录入收货数据";
		title = me.formatTitle(title);
		var iconCls = entity == null ? "PSI-button-add" : "PSI-button-edit";

		Ext.apply(me, {
			header : {
				title : title,
				height : 40,
				iconCls : iconCls
			},
			maximized : true,
			width : 1200,
			height : 600,
			tbar : [{
						text : "保存",
						iconCls : "PSI-button-ok",
						handler : me.onOK,
						scope : me,
						id : "buttonSave"
					}, "-", {
						text : "取消",
						handler : function() {
							if (me.__readonly) {
								me.close();
								return;
							}
							PSI.MsgBox.confirm("请确认是否取消当前操作?", function() {
										me.close();
									});
						},
						scope : me,
						id : "buttonCancel"
					}],
			layout : "border",
			items : [{
						region : "center",
						layout : "fit",
						border : 0,
						bodyPadding : 10,
						items : [me.getGoodsGrid()]
					}, {
						region : "north",
						id : "editForm",
						layout : {
							type : "table",
							columns : 4
						},
						height : 140,
						bodyPadding : 10,
						border : 0,
						items : [{
									xtype : "hidden",
									id : "hiddenId",
									name : "id",
									value : entity == null ? null : entity
											.get("id")
								}, {
									id : "editRef",
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "单号",
									xtype : "displayfield",
									value : "<span style='color:red'>保存后自动生成</span>",
									colspan : 2
								}, {
									id : "editSupplier",
									xtype : "displayfield",
									fieldLabel : "供应商",
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									colspan : 2,
									width : 430,
									readOnly : true
								}, {
									id : "editFromWarehouse",
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "出库仓库",
									xtype : "psi_warehousefield",
									colspan : 2,
									width : 430,
									readOnly : true
								}, {
									id : "editFromOrg",
									xtype : "psi_orgwithdataorgfield",
									labelAlign : "right",
									labelSeparator : "",
									labelWidth : 60,
									fieldLabel : "发货组织",
									colspan : 2,
									width : 430,
									readOnly : true
								}, {
									id : "editToWarehouse",
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "收货仓库",
									xtype : "psi_warehousefield",
									colspan : 2,
									width : 430,
									readOnly : true
								}, {
									id : "editToOrg",
									xtype : "psi_orgwithdataorgfield",
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "收货门店",
									labelWidth : 60,
									colspan : 2,
									width : 430,
									readOnly : true
								}, {
									id : "editBizDT",
									fieldLabel : "业务日期",
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									xtype : "datefield",
									format : "Y-m-d",
									value : new Date(),
									name : "bizDT",
									readOnly : true
								}, {
									id : "editBizUser",
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "业务员",
									xtype : "psi_userfield",
									readOnly : true
								}, {
									id : "editBillMemo",
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "备注",
									xtype : "textfield",
									colspan : 2,
									width : 430,
									readOnly : true
								}]
					}],
			listeners : {
				show : {
					fn : me.onWndShow,
					scope : me
				},
				close : {
					fn : me.onWndClose,
					scope : me
				}
			}
		});

		me.callParent(arguments);

		me.hiddenId = Ext.getCmp("hiddenId");

		me.editSupplier = Ext.getCmp("editSupplier");
		me.editRef = Ext.getCmp("editRef");
		me.editFromWarehouse = Ext.getCmp("editFromWarehouse");
		me.editFromOrg = Ext.getCmp("editFromOrg");
		me.editToWarehouse = Ext.getCmp("editToWarehouse");
		me.editToOrg = Ext.getCmp("editToOrg");
		me.editBizDT = Ext.getCmp("editBizDT");
		me.editBizUser = Ext.getCmp("editBizUser");
		me.editOutType = Ext.getCmp("editOutType");
		me.editBillMemo = Ext.getCmp("editBillMemo");
	},

	onWindowBeforeUnload : function(e) {
		return (window.event.returnValue = e.returnValue = '确认离开当前页面？');
	},

	onWndClose : function() {
		Ext.get(window).un('beforeunload', this.onWindowBeforeUnload);
	},

	onWndShow : function() {
		Ext.get(window).on('beforeunload', this.onWindowBeforeUnload);

		var me = this;

		var el = me.getEl() || Ext.getBody();
		el.mask(PSI.Const.LOADING);
		Ext.Ajax.request({
					url : PSI.Const.BASE_URL + "Home/LD/ldBillInfoForSRG",
					params : {
						id : me.hiddenId.getValue()
					},
					method : "POST",
					callback : function(options, success, response) {
						el.unmask();

						if (success) {
							var data = Ext.JSON.decode(response.responseText);

							me.editRef.setValue(data.ref);
							me.editSupplier.setValue(data.supplierName
									+ " 门店订货单单号：" + data.spoBillRef);

							me.editFromWarehouse
									.setIdValue(data.fromWarehouseId);
							me.editFromWarehouse
									.setValue(data.fromWarehouseName);
							me.editToWarehouse.setIdValue(data.toWarehouseId);
							me.editToWarehouse.setValue(data.toWarehouseName);
							me.editToWarehouse.setReadOnly(true);

							me.editFromOrg.setIdValue(data.fromOrgId);
							me.editFromOrg.setValue(data.fromOrgName);
							me.editToOrg.setIdValue(data.toOrgId);
							me.editToOrg.setValue(data.toOrgName);

							me.editBizUser.setIdValue(data.bizUserId);
							me.editBizUser.setValue(data.bizUserName);
							me.editBizDT.setValue(data.bizDT);

							me.__billId = data.spoBillId;

							var store = me.getGoodsGrid().getStore();
							store.removeAll();
							store.add(data.items);

							if (data.billStatus && data.billStatus > 2000) {
								me.setBillReadonly();
							}
						}
					}
				});
	},

	onOK : function() {
		var me = this;

		Ext.getBody().mask("正在保存中...");
		Ext.Ajax.request({
			url : PSI.Const.BASE_URL + "Home/LD/editLDBillForSRG",
			method : "POST",
			params : {
				jsonStr : me.getSaveData()
			},
			callback : function(options, success, response) {
				Ext.getBody().unmask();

				if (success) {
					var data = Ext.JSON.decode(response.responseText);
					if (data.success) {
						PSI.MsgBox.showInfo("成功保存数据", function() {
									me.close();
									me.getParentForm().refreshMainGrid(data.id);
								});
					} else {
						PSI.MsgBox.showInfo(data.msg);
					}
				}
			}
		});

	},

	getGoodsGrid : function() {
		var me = this;
		if (me.__goodsGrid) {
			return me.__goodsGrid;
		}
		var modelName = "PSIPRBillDetail_EditForm";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "goodsId", "goodsCode", "goodsName",
							"goodsSpec", "unitId", "unitName", "goodsCount",
							"qcBeginDT", "qcEndDT", "qcDays", "qcSN", "factor",
							"factorType", "skuGoodsCount", "skuUnitName",
							"skuUnitId", "spoBillDetailId", "revGoodsCount",
							"rejGoodsCount"]
				});
		var store = Ext.create("Ext.data.Store", {
					autoLoad : false,
					model : modelName,
					data : []
				});

		me.__cellEditing = Ext.create("PSI.UX.CellEditing", {
					clicksToEdit : 1,
					listeners : {
						edit : {
							fn : me.cellEditingAfterEdit,
							scope : me
						},
						beforeedit : {
							fn : me.cellEditingBeforeEdit,
							scope : me
						}
					}
				});

		me.__goodsGrid = Ext.create("Ext.grid.Panel", {
					viewConfig : {
						enableTextSelection : true,
						markDirty : false
					},
					plugins : [me.__cellEditing],
					columnLines : true,
					columns : [Ext.create("Ext.grid.RowNumberer", {
										text : "",
										width : 30
									}), {
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
								header : "生产日期",
								dataIndex : "qcBeginDT",
								menuDisabled : true,
								sortable : false,
								width : 90,
								renderer : Ext.util.Format
										.dateRenderer('Y-m-d')
							}, {
								header : "保质期",
								dataIndex : "qcDays",
								menuDisabled : true,
								sortable : false
							}, {
								header : "到期日期",
								dataIndex : "qcEndDT",
								menuDisabled : true,
								sortable : false,
								renderer : Ext.util.Format
										.dateRenderer('Y-m-d')
							}, {
								header : "批号",
								dataIndex : "qcSN",
								menuDisabled : true,
								sortable : false,
								width : 90
							}, {
								header : "发货数量",
								dataIndex : "goodsCount",
								menuDisabled : true,
								sortable : false,
								draggable : false,
								align : "right",
								width : 100
							}, {
								header : "收货数量",
								dataIndex : "revGoodsCount",
								menuDisabled : true,
								sortable : false,
								draggable : false,
								align : "right",
								width : 100,
								editor : {
									xtype : "numberfield",
									allowDecimals : PSI.Const.GC_DEC_NUMBER > 0,
									decimalPrecision : PSI.Const.GC_DEC_NUMBER,
									minValue : 0,
									hideTrigger : true
								}
							}, {
								header : "退货数量",
								dataIndex : "rejGoodsCount",
								menuDisabled : true,
								sortable : false,
								draggable : false,
								align : "right",
								width : 100
							}, {
								header : "单位",
								dataIndex : "unitName",
								menuDisabled : true,
								sortable : false,
								draggable : false,
								width : 60
							}, {
								header : "转换率",
								dataIndex : "factor",
								menuDisabled : true,
								sortable : false,
								draggable : false,
								width : 60,
								editor : {
									xtype : "numberfield",
									allowDecimals : true,
									decimalPrecision : 2,
									minValue : 0,
									hideTrigger : true
								}
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
								header : "转换后入库数量",
								dataIndex : "skuGoodsCount",
								menuDisabled : true,
								sortable : false,
								draggable : false,
								width : 120
							}, {
								header : "SKU单位",
								dataIndex : "skuUnitName",
								menuDisabled : true,
								sortable : false,
								draggable : false,
								width : 90
							}],
					store : store,
					listeners : {
						cellclick : function() {
							return !me.__readonly;
						}
					}
				});

		return me.__goodsGrid;
	},

	cellEditingBeforeEdit : function(editor, e, eOpts) {
		var me = this;
		if (me.__readonly) {
			return false;
		}
		var fieldName = e.field;
		var goods = e.record;
		if (fieldName == "factor") {
			var factorType = goods.get("factorType");
			return factorType == 1;
		}

		return true;
	},

	cellEditingAfterEdit : function(editor, e) {
		var me = this;

		var fieldName = e.field;
		var goods = e.record;
		var oldValue = e.originalValue;

		if (fieldName == "revGoodsCount") {
			if (goods.get(fieldName) != oldValue) {
				me.calcSkuGoodsCount(goods);
			}
		} else if (fieldName == "factor") {
			if (goods.get(fieldName) != oldValue) {
				me.calcSkuGoodsCount(goods);
			}
		}
	},

	calcSkuGoodsCount : function(goods) {
		if (!goods) {
			return;
		}

		goods.set("skuGoodsCount", goods.get("revGoodsCount")
						* goods.get("factor"));
		goods.set("rejGoodsCount", goods.get("goodsCount")
						- goods.get("revGoodsCount"));
	},

	getSaveData : function() {
		var me = this;

		var result = {
			id : Ext.getCmp("hiddenId").getValue(),
			items : []
		};

		var store = me.getGoodsGrid().getStore();
		for (var i = 0; i < store.getCount(); i++) {
			var item = store.getAt(i);
			result.items.push({
						id : item.get("id"),
						revGoodsCount : item.get("revGoodsCount"),
						factor : item.get("factor")
					});
		}

		return Ext.JSON.encode(result);
	},

	setBillReadonly : function() {
		var me = this;
		me.__readonly = true;
		me.setTitle("<span style='font-size:160%;'>查看物流单</span>");
		Ext.getCmp("buttonSave").setDisabled(true);
		Ext.getCmp("buttonCancel").setText("关闭");
	}
});