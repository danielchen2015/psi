/**
 * 消耗单 - 新增或编辑界面
 * 
 * @author 李静波
 */
Ext.define("PSI.US.EditForm", {
	extend : "PSI.AFX.BaseDialogForm",

	/**
	 * 初始化组件
	 */
	initComponent : function() {
		var me = this;
		var entity = me.getEntity();
		this.adding = entity == null;

		var title = entity == null ? "新建消耗单" : "编辑消耗单";
		title = me.formatTitle(title);
		var iconCls = entity == null ? "PSI-button-add" : "PSI-button-edit";

		Ext.apply(me, {
			header : {
				title : title,
				height : 40,
				iconCls : iconCls
			},
			maximized : true,
			width : 1000,
			height : 600,
			layout : "border",
			tbar : [{
						id : "USBillEditForm_buttonSelectTemplate",
						text : "选择模板",
						handler : me.onSelectTemplate,
						scope : me
					}, "-", {
						text : "保存",
						id : "USBillEditForm_buttonSave",
						iconCls : "PSI-button-ok",
						handler : me.onOK,
						scope : me
					}, "-", {
						text : "取消",
						id : "USBillEditForm_buttonCancel",
						handler : function() {
							if (me.__readonly) {
								me.close();
								return;
							}

							PSI.MsgBox.confirm("请确认是否取消当前操作？", function() {
										me.close();
									});
						},
						scope : me
					}],
			items : [{
						region : "center",
						layout : "fit",
						border : 0,
						bodyPadding : 5,
						items : [me.getGoodsGrid()]
					}, {
						region : "north",
						id : "editForm",
						layout : {
							type : "table",
							columns : 4
						},
						height : 60,
						bodyPadding : 10,
						border : 0,
						items : [{
									xtype : "hidden",
									id : "USBillEditForm_hiddenId",
									value : entity == null ? null : entity
											.get("id")
								}, {
									id : "USBillEditForm_editRef",
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "单号",
									xtype : "displayfield",
									value : "<span style='color:red'>保存后自动生成</span>"
								}, {
									id : "USBillEditForm_editWarehouse",
									fieldLabel : "仓库",
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									xtype : "psi_warehousefield",
									fid : "3005",
									allowBlank : false,
									blankText : "没有输入仓库",
									beforeLabelTextTpl : PSI.Const.REQUIRED,
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "USBillEditForm_editBizUser",
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "业务员",
									xtype : "psi_userfield",
									allowBlank : false,
									blankText : "没有输入业务员",
									beforeLabelTextTpl : PSI.Const.REQUIRED,
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "USBillEditForm_editBizDT",
									fieldLabel : "业务日期",
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									allowBlank : false,
									blankText : "没有输入业务日期",
									beforeLabelTextTpl : PSI.Const.REQUIRED,
									xtype : "datefield",
									format : "Y-m-d",
									value : new Date(),
									name : "bizDT",
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "USBillEditForm_editBillMemo",
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "备注",
									xtype : "textfield",
									colspan : 3,
									width : 710,
									listeners : {
										specialkey : {
											fn : me.onLastEditSpecialKey,
											scope : me
										}
									}
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

		me.hiddenId = Ext.getCmp("USBillEditForm_hiddenId");

		me.buttonSelectTemplate = Ext
				.getCmp("USBillEditForm_buttonSelectTemplate");
		me.buttonSave = Ext.getCmp("USBillEditForm_buttonSave");
		me.buttonCancel = Ext.getCmp("USBillEditForm_buttonCancel");

		me.editRef = Ext.getCmp("USBillEditForm_editRef");
		me.editWarehouse = Ext.getCmp("USBillEditForm_editWarehouse");
		me.editBizUser = Ext.getCmp("USBillEditForm_editBizUser");
		me.editBizDT = Ext.getCmp("USBillEditForm_editBizDT");
		me.editBillMemo = Ext.getCmp("USBillEditForm_editBillMemo");

		me.__editorList = ["USBillEditForm_editWarehouse",
				"USBillEditForm_editBizUser", "USBillEditForm_editBizDT",
				"USBillEditForm_editBillMemo"];
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

		var id = me.hiddenId.getValue();
		if (!id) {
			me.editRef.focus();
			me.getGoodsGrid().getStore().add({});
			// 新建消耗单的第一步是选择模板
			me.onSelectTemplate();
			return;
		}

		var el = me.getEl() || Ext.getBody();
		el.mask(PSI.Const.LOADING);
		Ext.Ajax.request({
					url : PSI.Const.BASE_URL + "Home/US/usBillInfo",
					params : {
						id : me.hiddenId.getValue()
					},
					method : "POST",
					callback : function(options, success, response) {
						el.unmask();

						if (success) {
							var data = Ext.JSON.decode(response.responseText);

							if (data.ref) {
								me.editRef.setValue(data.ref);
								me.editWarehouse.setIdValue(data.warehouseId);
								me.editWarehouse.setValue(data.warehouseName);
								me.editBizUser.setIdValue(data.bizUserId);
								me.editBizUser.setValue(data.bizUserName);
								me.editBizDT.setValue(data.bizDT);
								me.editBillMemo.setValue(data.billMemo);

								me.buttonSelectTemplate.setDisabled(true);
								me.__billId = data.ustemplateId;

								me.editRef.focus();
							}

							var store = me.getGoodsGrid().getStore();
							store.removeAll();
							if (data.items) {
								store.add(data.items);
							}
						}
					}
				});
	},

	onSelectTemplate : function() {
		var me = this;
		var form = Ext.create("PSI.US.SelectUSTemplateForm", {
					parentForm : me
				});
		form.show();
	},

	onOK : function() {
		var me = this;

		Ext.getBody().mask("正在保存中...");
		Ext.Ajax.request({
			url : PSI.Const.BASE_URL + "Home/US/editUSBill",
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

	onEditSpecialKey : function(field, e) {
		if (e.getKey() === e.ENTER) {
			var me = this;
			var id = field.getId();
			for (var i = 0; i < me.__editorList.length; i++) {
				var editorId = me.__editorList[i];
				if (id === editorId) {
					var edit = Ext.getCmp(me.__editorList[i + 1]);
					edit.focus();
					edit.setValue(edit.getValue());
				}
			}
		}
	},

	onLastEditSpecialKey : function(field, e) {
		var me = this;
		me.getGoodsGrid().focus();
		me.__cellEditing.startEdit(0, 5);
	},

	getGoodsGrid : function() {
		var me = this;
		if (me.__goodsGrid) {
			return me.__goodsGrid;
		}
		var modelName = "PSIUSBill_EditForm";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "goodsId", "goodsCode", "goodsName",
							"goodsSpec", "unitName", "checkCount", "saleCount",
							"lostCount", "memo"]
				});
		var store = Ext.create("Ext.data.Store", {
					autoLoad : false,
					model : modelName,
					data : []
				});

		me.__cellEditing = Ext.create("PSI.UX.CellEditing", {
					clicksToEdit : 1,
					listeners : {
						beforeedit : {
							fn : me.cellEditingBeforeEdit,
							scope : me
						},
						edit : {
							fn : me.cellEditingAfterEdit,
							scope : me
						}
					}
				});

		me.__goodsGrid = Ext.create("Ext.grid.Panel", {
					viewConfig : {
						enableTextSelection : true
					},
					plugins : [me.__cellEditing],
					columnLines : true,
					columns : [{
								xtype : "rownumberer"
							}, {
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
							}, {
								header : "盘点量",
								dataIndex : "checkCount",
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
								header : "贩卖量",
								dataIndex : "saleCount",
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
								header : "半成品损耗量",
								dataIndex : "lostCount",
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
								header : "备注",
								dataIndex : "memo",
								menuDisabled : true,
								sortable : false,
								draggable : false,
								width : 200,
								editor : {
									xtype : "textfield"
								}
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

	cellEditingBeforeEdit : function(editor, e) {
		var me = this;
		if (me.__readonly) {
			return false;
		}
	},

	cellEditingAfterEdit : function(editor, e) {
		var me = this;

		if (me.__readonly) {
			return;
		}

		var fieldName = e.field;
		var goods = e.record;
		var oldValue = e.originalValue;
		if (fieldName == "memo") {
			var store = me.getGoodsGrid().getStore();
			e.rowIdx += 1;
			me.getGoodsGrid().getSelectionModel().select(e.rowIdx);
			me.__cellEditing.startEdit(e.rowIdx, 5);
		}
	},

	getSaveData : function() {
		var me = this;

		var result = {
			id : me.hiddenId.getValue(),
			billMemo : me.editBillMemo.getValue(),
			ustemplateId : me.__billId,
			warehouseId : me.editWarehouse.getIdValue(),
			bizUserId : me.editBizUser.getIdValue(),
			bizDT : Ext.Date.format(me.editBizDT.getValue(), "Y-m-d"),
			items : []
		};

		var store = me.getGoodsGrid().getStore();
		for (var i = 0; i < store.getCount(); i++) {
			var item = store.getAt(i);
			result.items.push({
						goodsId : item.get("goodsId"),
						checkCount : item.get("checkCount"),
						saleCount : item.get("saleCount"),
						lostCount : item.get("lostCount"),
						memo : item.get("memo")
					});
		}

		return Ext.JSON.encode(result);
	},

	// psi_goodsfield回调
	__setGoodsInfo : function(data) {
		var me = this;
		var item = me.getGoodsGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return;
		}
		var goods = item[0];

		goods.set("goodsId", data.id);
		goods.set("goodsCode", data.code);
		goods.set("goodsName", data.name);
		goods.set("unitName", data.unitName);
		goods.set("goodsSpec", data.spec);
	},

	// PSI.US.SelectUSTemplateForm中回调本方法
	getUSTemplateInfo : function(id) {
		var me = this;
		me.__billId = id;
		var el = me.getEl() || Ext.getBody();
		el.mask(PSI.Const.LOADING);
		Ext.Ajax.request({
					url : PSI.Const.BASE_URL
							+ "Home/US/getUSTemplateInfoForUSBill",
					params : {
						id : id
					},
					method : "POST",
					callback : function(options, success, response) {
						if (success) {
							var data = Ext.JSON.decode(response.responseText);

							var store = me.getGoodsGrid().getStore();
							store.removeAll();
							store.add(data.items);

							me.editBizUser.setIdValue(data.bizUserId);
							me.editBizUser.setValue(data.bizUserName);
							me.editWarehouse.focus();
						}

						el.unmask();
					}
				});
	}
});