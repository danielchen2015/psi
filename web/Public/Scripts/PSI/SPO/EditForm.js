/**
 * 门店订货单 - 新增或编辑界面
 * 
 * @author 李静波
 */
Ext.define("PSI.SPO.EditForm", {
	extend : "PSI.AFX.BaseDialogForm",

	config : {},

	/**
	 * 初始化组件
	 */
	initComponent : function() {
		var me = this;
		me.__readOnly = false;
		var entity = me.getEntity();
		this.adding = entity == null;

		var title = entity == null ? "新建门店订货单" : "编辑门店订货单";
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
			defaultFocus : "editSupplier",
			tbar : [{
						id : "buttonSelectPCTemplate",
						text : "选择采购模板",
						handler : me.onSelectPCTemplate,
						scope : me
					}, "-", {
						text : "保存",
						id : "buttonSave",
						iconCls : "PSI-button-ok",
						handler : me.onOK,
						scope : me
					}, "-", {
						text : "取消",
						id : "buttonCancel",
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
						bodyPadding : 10,
						items : [me.getGoodsGrid()]
					}, {
						region : "north",
						id : "editForm",
						layout : {
							type : "table",
							columns : 4
						},
						height : 100,
						bodyPadding : 10,
						border : 0,
						items : [{
									xtype : "hidden",
									id : "PSI_PurchaseOrder_POEditForm_hiddenId",
									value : entity == null ? null : entity
											.get("id")
								}, {
									xtype : "hidden",
									id : "PSI_PurchaseOrder_POEditForm_hiddenPCTemplateId"
								}, {
									id : "PSI_PurchaseOrder_POEditForm_editPCRef",
									colspan : 2,
									width : 430,
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "合同号",
									readOnly : true,
									xtype : "textfield"
								}, {
									id : "PSI_PurchaseOrder_POEditForm_editPCTemplateRef",
									colspan : 2,
									width : 430,
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "模板编号",
									readOnly : true,
									xtype : "textfield"
								}, {
									id : "PSI_PurchaseOrder_POEditForm_editRef",
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "单号",
									xtype : "displayfield",
									value : "<span style='color:red'>保存后自动生成</span>",
									width : 200
								}, {
									id : "PSI_PurchaseOrder_POEditForm_editIsTodayOrder",
									xtype : "combo",
									queryMode : "local",
									editable : false,
									valueField : "id",
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "订货类型",
									margin : "5, 0, 0, 0",
									store : Ext.create("Ext.data.ArrayStore", {
												fields : ["id", "text"],
												data : [[0, "正常订单"], [1, "补单"]]
											}),
									value : 0
								}, {
									id : "PSI_PurchaseOrder_POEditForm_editSupplier",
									colspan : 2,
									width : 430,
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "供应商",
									readOnly : true,
									xtype : "textfield"
								}, {
									id : "PSI_PurchaseOrder_POEditForm_editBizUser",
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
									id : "PSI_PurchaseOrder_POEditForm_editBillMemo",
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "备注",
									xtype : "textfield",
									colspan : 3,
									width : 645,
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

		me.__editorList = ["PSI_PurchaseOrder_POEditForm_editIsTodayOrder",
				"PSI_PurchaseOrder_POEditForm_editBizUser",
				"PSI_PurchaseOrder_POEditForm_editBillMemo"];

		me.hiddenId = Ext.getCmp("PSI_PurchaseOrder_POEditForm_hiddenId");
		me.editPCRef = Ext.getCmp("PSI_PurchaseOrder_POEditForm_editPCRef");
		me.editPCTemplateRef = Ext
				.getCmp("PSI_PurchaseOrder_POEditForm_editPCTemplateRef");
		me.editRef = Ext.getCmp("PSI_PurchaseOrder_POEditForm_editRef");
		me.editIsTodayOrder = Ext
				.getCmp("PSI_PurchaseOrder_POEditForm_editIsTodayOrder");
		me.editSupplier = Ext
				.getCmp("PSI_PurchaseOrder_POEditForm_editSupplier");
		me.editBizUser = Ext.getCmp("PSI_PurchaseOrder_POEditForm_editBizUser");
		me.editBillMemo = Ext
				.getCmp("PSI_PurchaseOrder_POEditForm_editBillMemo");

		me.hiddenPCTemplateId = Ext
				.getCmp("PSI_PurchaseOrder_POEditForm_hiddenPCTemplateId");

		me.buttonSelectPCTemplate = Ext.getCmp("buttonSelectPCTemplate");
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
					url : PSI.Const.BASE_URL + "Home/SPO/spoBillInfo",
					params : {
						id : me.hiddenId.getValue()
					},
					method : "POST",
					callback : function(options, success, response) {
						el.unmask();

						if (success) {
							var data = Ext.JSON.decode(response.responseText);

							if (data.ref) {
								me.editPCRef.setValue(data.pcBillRef);
								me.editPCTemplateRef
										.setValue(data.pcTemplateRef);
								me.editRef.setValue(data.ref);
								me.editSupplier.setValue(data.supplierName);
								me.editBillMemo.setValue(data.billMemo);
								me.editIsTodayOrder
										.setValue(parseInt(data.isTodayOrder));
								me.editIsTodayOrder.setReadOnly(true);

								me.buttonSelectPCTemplate.setDisabled(true);
							} else {
								// 新建
								me.onSelectPCTemplate();
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
		Ext.getBody().mask("正在保存中...");
		Ext.Ajax.request({
			url : PSI.Const.BASE_URL + "Home/SPO/editSPOBill",
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
			me.getGoodsGrid().focus();
			me.__cellEditing.startEdit(0, 4);
		}
	},

	getGoodsGrid : function() {
		var me = this;
		if (me.__goodsGrid) {
			return me.__goodsGrid;
		}
		var modelName = "PSIPOBillDetail_EditForm";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "goodsId", "goodsCode", "goodsName",
							"goodsSpec", "unitId", "unitName", "goodsCount", {
								name : "goodsMoney",
								type : "float"
							}, "goodsPrice", "memo"]
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
						}
					}
				});

		me.__goodsGrid = Ext.create("Ext.grid.Panel", {
					viewConfig : {
						enableTextSelection : true
					},
					features : [{
								ftype : "summary"
							}],
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
								header : "采购数量",
								dataIndex : "goodsCount",
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
								header : "单位",
								dataIndex : "unitName",
								menuDisabled : true,
								sortable : false,
								draggable : false,
								width : 80
							}, {
								header : "采购单价",
								dataIndex : "goodsPrice",
								menuDisabled : true,
								sortable : false,
								draggable : false,
								align : "right",
								xtype : "numbercolumn",
								width : 100,
								summaryRenderer : function() {
									return "采购金额合计";
								}
							}, {
								header : "采购金额",
								dataIndex : "goodsMoney",
								menuDisabled : true,
								sortable : false,
								draggable : false,
								align : "right",
								xtype : "numbercolumn",
								width : 120,
								summaryType : "sum"
							}, {
								header : "备注",
								dataIndex : "memo",
								menuDisabled : true,
								sortable : false,
								draggable : false,
								editor : {
									xtype : "textfield"
								},
								width : 150
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
			me.__cellEditing.startEdit(e.rowIdx, 1);
		} else if (fieldName == "goodsCount") {
			if (goods.get(fieldName) != oldValue) {
				me.calcMoney(goods);
			}
		}
	},

	calcMoney : function(goods) {
		if (!goods) {
			return;
		}

		goods.set("goodsMoney", goods.get("goodsCount")
						* goods.get("goodsPrice"));
	},

	getSaveData : function() {
		var me = this;

		var result = {
			id : me.hiddenId.getValue(),
			pcTemplateId : me.hiddenPCTemplateId.getValue(),
			bizUserId : me.editBizUser.getIdValue(),
			billMemo : me.editBillMemo.getValue(),
			isTodayOrder : me.editIsTodayOrder.getValue(),
			items : []
		};

		var store = me.getGoodsGrid().getStore();
		for (var i = 0; i < store.getCount(); i++) {
			var item = store.getAt(i);
			result.items.push({
						id : item.get("id"),
						goodsId : item.get("goodsId"),
						unitId : item.get("unitId"),
						goodsCount : item.get("goodsCount"),
						goodsPrice : item.get("goodsPrice"),
						goodsMoney : item.get("goodsMoney"),
						memo : item.get("memo")
					});
		}

		return Ext.JSON.encode(result);
	},

	setBillReadonly : function() {
		var me = this;
		me.__readonly = true;
		me.setTitle("<span style='font-size:160%;'>查看采购订单</span>");

		Ext.getCmp("buttonSave").setDisabled(true);
		Ext.getCmp("buttonCancel").setText("关闭");
		me.editDealDate.setReadOnly(true);
		me.editBizUser.setReadOnly(true);
		me.editBillMemo.setReadOnly(true);
	},

	onSelectPCTemplate : function() {
		var form = Ext.create("PSI.SPO.SelectPCTemplateForm", {
					parentForm : this
				});

		form.show();
	},

	/**
	 * 获取采购模板详细信息，在POSelectPCTemplateForm的onOK中回调本方法
	 * 
	 * @param {string}
	 *            id 采购模板主表id
	 */
	getPCTemplateInfo : function(id) {
		var me = this;
		var el = me.getEl() || Ext.getBody();
		el.mask(PSI.Const.LOADING);
		Ext.Ajax.request({
					url : PSI.Const.BASE_URL
							+ "Home/Purchase/getPCTemplateInfoForPOBill",
					params : {
						id : id
					},
					method : "POST",
					callback : function(options, success, response) {
						if (success) {
							var data = Ext.JSON.decode(response.responseText);

							me.hiddenPCTemplateId.setValue(id);
							me.editPCRef.setValue(data.contractRef);
							me.editPCTemplateRef.setValue(data.templateRef);
							me.editSupplier.setValue(data.supplierName);

							var store = me.getGoodsGrid().getStore();
							store.removeAll();
							store.add(data.items);
						}

						el.unmask();
					}
				});
	}
});