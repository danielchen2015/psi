/**
 * 供应合同 - 新增或编辑界面
 * 
 * @author 李静波
 */
Ext.define("PSI.PurchaseContract.PCEditForm", {
	extend : "PSI.AFX.BaseDialogForm",

	config : {
		showAddGoodsButton : "0"
	},

	/**
	 * 初始化组件
	 */
	initComponent : function() {
		var me = this;
		me.__readOnly = false;
		var entity = me.getEntity();
		this.adding = entity == null;

		var title = entity == null ? "新建供应合同" : "编辑供应合同";
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
								text : "保存",
								id : "PCEditForm_buttonSave",
								iconCls : "PSI-button-ok",
								handler : me.onOK,
								scope : me
							}, "-", {
								text : "取消",
								id : "PCEditForm_buttonCancel",
								handler : function() {
									if (me.__readonly) {
										me.close();
										return;
									}

									PSI.MsgBox.confirm("请确认是否取消当前操作？",
											function() {
												me.close();
											});
								},
								scope : me
							}],
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
								height : 90,
								bodyPadding : 10,
								border : 0,
								items : [{
									xtype : "hidden",
									id : "PCEditForm_hiddenId",
									value : entity == null ? null : entity
											.get("id")
								}, {
									id : "PCEditForm_editRef",
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "合同号",
									allowBlank : false,
									blankText : "没有输入合同号",
									beforeLabelTextTpl : PSI.Const.REQUIRED,
									xtype : "textfield",
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "PCEditForm_editBizDT",
									fieldLabel : "合同签订日",
									labelWidth : 80,
									labelAlign : "right",
									labelSeparator : "",
									allowBlank : false,
									blankText : "没有输入合同签订日",
									beforeLabelTextTpl : PSI.Const.REQUIRED,
									xtype : "datefield",
									format : "Y-m-d",
									value : new Date(),
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "PCEditForm_editFromDT",
									fieldLabel : "合同日期(起)",
									labelWidth : 100,
									labelAlign : "right",
									labelSeparator : "",
									allowBlank : false,
									blankText : "没有输入合同开始日",
									beforeLabelTextTpl : PSI.Const.REQUIRED,
									xtype : "datefield",
									format : "Y-m-d",
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "PCEditForm_editToDT",
									fieldLabel : "合同日期(止)",
									labelWidth : 100,
									labelAlign : "right",
									labelSeparator : "",
									allowBlank : false,
									blankText : "没有输入合同结束日",
									beforeLabelTextTpl : PSI.Const.REQUIRED,
									xtype : "datefield",
									format : "Y-m-d",
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "PCEditForm_editSupplier",
									colspan : 2,
									width : 450,
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									xtype : "psi_supplierfield",
									fieldLabel : "供应商",
									allowBlank : false,
									blankText : "没有输入供应商",
									beforeLabelTextTpl : PSI.Const.REQUIRED,
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									},
									showAddButton : true,
									callbackScope : me
								}, {
									id : "PCEditForm_editBizUser",
									labelWidth : 100,
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
									},
									colspan : 2
								}, {
									id : "PCEditForm_editBillMemo",
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

		me.hiddenId = Ext.getCmp("PCEditForm_hiddenId");

		me.buttonSave = Ext.getCmp("PCEditForm_buttonSave");
		me.buttonCancel = Ext.getCmp("PCEditForm_buttonCancel");

		me.editRef = Ext.getCmp("PCEditForm_editRef");
		me.editBizDT = Ext.getCmp("PCEditForm_editBizDT");
		me.editFromDT = Ext.getCmp("PCEditForm_editFromDT");
		me.editToDT = Ext.getCmp("PCEditForm_editToDT");
		me.editSupplier = Ext.getCmp("PCEditForm_editSupplier");
		me.editBizUser = Ext.getCmp("PCEditForm_editBizUser");
		me.editBillMemo = Ext.getCmp("PCEditForm_editBillMemo");

		me.__editorList = ["PCEditForm_editRef", "PCEditForm_editBizDT",
				"PCEditForm_editFromDT", "PCEditForm_editToDT",
				"PCEditForm_editSupplier", "PCEditForm_editBizUser",
				"PCEditForm_editBillMemo"];
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

		me.editRef.focus();

		var el = me.getEl() || Ext.getBody();
		el.mask(PSI.Const.LOADING);
		Ext.Ajax.request({
					url : PSI.Const.BASE_URL
							+ "Home/PurchaseContract/pcBillInfo",
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
								me.editBizDT.setValue(data.bizDT);
								me.editFromDT.setValue(data.fromDT);
								me.editToDT.setValue(data.toDT);
								me.editSupplier.setIdValue(data.supplierId);
								me.editSupplier.setValue(data.supplierName);
								me.editBillMemo.setValue(data.billMemo);
							}

							me.editBizUser.setIdValue(data.bizUserId);
							me.editBizUser.setValue(data.bizUserName);

							var store = me.getGoodsGrid().getStore();
							store.removeAll();
							if (data.items) {
								store.add(data.items);
							}
							if (store.getCount() == 0) {
								store.add({});
							}

							if (data.billStatus && data.billStatus != 0) {
								me.setBillReadonly();
							}
						}
					}
				});
	},

	onOK : function() {
		var me = this;

		var ref = me.editRef.getValue();
		if (!ref) {
			PSI.MsgBox.showInfo("没有输入合同号", function() {
						me.editRef.focus();
					});
			return;
		}
		var bizDT = me.editBizDT.getValue();
		if (!bizDT) {
			PSI.MsgBox.showInfo("没有输入合同签订日", function() {
						me.editBizDT.focus();
					});
			return;
		}
		var fromDT = me.editFromDT.getValue();
		if (!fromDT) {
			PSI.MsgBox.showInfo("没有输入合同开始日期", function() {
						me.editFromDT.focus();
					});
			return;
		}
		var toDT = me.editToDT.getValue();
		if (!toDT) {
			PSI.MsgBox.showInfo("没有输入合同结束日期", function() {
						me.editToDT.focus();
					});
			return;
		}
		var supplierId = me.editSupplier.getIdValue();
		if (!supplierId) {
			PSI.MsgBox.showInfo("没有输入供应商", function() {
						me.editSupplier.focus();
					});
			return;
		}
		var bizUserId = me.editBizUser.getIdValue();
		if (!bizUserId) {
			PSI.MsgBox.showInfo("没有输入业务员", function() {
						me.editBizUser.focus();
					});
			return;
		}

		Ext.getBody().mask("正在保存中...");
		Ext.Ajax.request({
			url : PSI.Const.BASE_URL + "Home/PurchaseContract/editPCBill",
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
		if (this.__readonly) {
			return;
		}

		if (e.getKey() == e.ENTER) {
			var me = this;
			var store = me.getGoodsGrid().getStore();
			if (store.getCount() == 0) {
				store.add({});
			}
			me.getGoodsGrid().focus();
			me.__cellEditing.startEdit(0, 1);
		}
	},

	getGoodsGrid : function() {
		var me = this;
		if (me.__goodsGrid) {
			return me.__goodsGrid;
		}
		var modelName = "PSIPCBill_EditForm";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "goodsId", "goodsCode", "goodsName",
							"goodsSpec", "unitId", "unitName", "goodsPrice",
							"memo"]
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

		me.__unitEditor = Ext.create("PSI.Goods.UnitField", {
					parentCmp : me
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
								draggable : false,
								editor : {
									xtype : "psi_goods_with_purchaseprice_field",
									parentCmp : me,
									billType : "t_pc_bill",
									showAddButton : true
								}
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
								width : 80,
								editor : me.__unitEditor
							}, {
								header : "采购单价",
								dataIndex : "goodsPrice",
								menuDisabled : true,
								sortable : false,
								draggable : false,
								align : "right",
								xtype : "numbercolumn",
								width : 100,
								editor : {
									xtype : "numberfield",
									hideTrigger : true
								}
							}, {
								header : "备注",
								dataIndex : "memo",
								menuDisabled : true,
								sortable : false,
								draggable : false,
								editor : {
									xtype : "textfield"
								},
								width : 120
							}, {
								header : "",
								id : "columnActionDelete",
								align : "center",
								menuDisabled : true,
								draggable : false,
								width : 40,
								xtype : "actioncolumn",
								items : [{
									icon : PSI.Const.BASE_URL
											+ "Public/Images/icons/delete.png",
									tooltip : "删除当前记录",
									handler : function(grid, row) {
										var store = grid.getStore();
										store.remove(store.getAt(row));
										if (store.getCount() == 0) {
											store.add({});
										}
									},
									scope : me
								}]
							}, {
								header : "",
								id : "columnActionAdd",
								align : "center",
								menuDisabled : true,
								draggable : false,
								width : 40,
								xtype : "actioncolumn",
								items : [{
									icon : PSI.Const.BASE_URL
											+ "Public/Images/icons/insert.png",
									tooltip : "在当前记录之前插入新记录",
									handler : function(grid, row) {
										var store = grid.getStore();
										store.insert(row, [{}]);
									},
									scope : me
								}]
							}, {
								header : "",
								id : "columnActionAppend",
								align : "center",
								menuDisabled : true,
								draggable : false,
								width : 40,
								xtype : "actioncolumn",
								items : [{
									icon : PSI.Const.BASE_URL
											+ "Public/Images/icons/add.png",
									tooltip : "在当前记录之后新增记录",
									handler : function(grid, row) {
										var store = grid.getStore();
										store.insert(row + 1, [{}]);
									},
									scope : me
								}]
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
		goods.set("unitId", data.unitId);
		goods.set("unitName", data.unitName);
		goods.set("goodsSpec", data.spec);
	},

	__setGoodsUnitInfo : function(data) {
		var me = this;
		var item = me.getGoodsGrid().getSelectionModel().getSelection();
		if (item == null || item.length != 1) {
			return;
		}
		var goods = item[0];
		goods.set("unitId", data.id);
		goods.set("unitName", data.name);
	},

	cellEditingBeforeEdit : function(editor, e) {
		var me = this;
		var fieldName = e.field;
		if (fieldName == "unitName") {
			var goods = e.record;
			me.__unitEditor.setGoodsId(goods.get("goodsId"));
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
			if (e.rowIdx == store.getCount() - 1) {
				me.__unitEditor.setGoodsId(null);
				store.add({});
			}
			e.rowIdx += 1;
			me.getGoodsGrid().getSelectionModel().select(e.rowIdx);
			me.__cellEditing.startEdit(e.rowIdx, 1);
		}
	},

	getSaveData : function() {
		var me = this;

		var result = {
			id : me.hiddenId.getValue(),
			ref : me.editRef.getValue(),
			bizDT : Ext.Date.format(me.editBizDT.getValue(), "Y-m-d"),
			fromDT : Ext.Date.format(me.editFromDT.getValue(), "Y-m-d"),
			toDT : Ext.Date.format(me.editToDT.getValue(), "Y-m-d"),
			supplierId : me.editSupplier.getIdValue(),
			bizUserId : me.editBizUser.getIdValue(),
			billMemo : me.editBillMemo.getValue(),
			items : []
		};

		var store = me.getGoodsGrid().getStore();
		for (var i = 0; i < store.getCount(); i++) {
			var item = store.getAt(i);
			result.items.push({
						id : item.get("id"),
						goodsId : item.get("goodsId"),
						unitId : item.get("unitId"),
						goodsPrice : item.get("goodsPrice"),
						memo : item.get("memo")
					});
		}

		return Ext.JSON.encode(result);
	},

	setBillReadonly : function() {
		var me = this;
		me.__readonly = true;
		me.setTitle("<span style='font-size:160%;'>查看供应合同</span>");

		me.buttonSave.setDisabled(true);
		me.buttonCancel.setText("关闭");

		me.editRef.setReadOnly(true);
		me.editBizDT.setReadOnly(true);
		me.editFromDT.setReadOnly(true);
		me.editToDT.setReadOnly(true);
		me.editSupplier.setReadOnly(true);
		me.editBizUser.setReadOnly(true);
		me.editBillMemo.setReadOnly(true);

		Ext.getCmp("columnActionDelete").hide();
		Ext.getCmp("columnActionAdd").hide();
		Ext.getCmp("columnActionAppend").hide();
	}
});