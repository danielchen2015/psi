/**
 * 采购入库单 - 新增或编辑界面
 */
Ext.define("PSI.Purchase.PWEditForm", {
	extend : "PSI.AFX.BaseDialogForm",
	config : {
		genBill : false,
		showAddGoodsButton : "0",
		viewPrice : true
	},

	initComponent : function() {
		var me = this;
		me.__pobillId = null;
		me.__readOnly = false;
		var entity = me.getEntity();
		me.adding = entity == null;

		var title = entity == null ? "新建采购入库单" : "编辑采购入库单";
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
						id : "buttonPWEditFormSelectPOBill",
						text : "选择采购订单",
						handler : me.onSelectPOBill,
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

							me.confirm("请确认是否取消当前操作？", function() {
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
							columns : 2
						},
						height : 120,
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
									value : "<span style='color:red'>保存后自动生成</span>"
								}, {
									id : "editBizDT",
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
											fn : me.onEditBizDTSpecialKey,
											scope : me
										}
									}
								}, {
									id : "editSupplier",
									colspan : 2,
									width : 430,
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
											fn : me.onEditSupplierSpecialKey,
											scope : me
										}
									},
									showAddButton : true
								}, {
									id : "editWarehouse",
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "入库仓库",
									xtype : "psi_warehousefield",
									fid : "2001",
									allowBlank : false,
									blankText : "没有输入入库仓库",
									beforeLabelTextTpl : PSI.Const.REQUIRED,
									listeners : {
										specialkey : {
											fn : me.onEditWarehouseSpecialKey,
											scope : me
										}
									}
								}, {
									id : "editBizUser",
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
											fn : me.onEditBizUserSpecialKey,
											scope : me
										}
									}
								}, {
									id : "editBillMemo",
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "备注",
									xtype : "textfield",
									listeners : {
										specialkey : {
											fn : me.onEditBillMemoSpecialKey,
											scope : me
										}
									},
									colspan : 2,
									width : 430
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

		me.editRef = Ext.getCmp("editRef");
		me.editBizDT = Ext.getCmp("editBizDT");
		me.editSupplier = Ext.getCmp("editSupplier");
		me.editWarehouse = Ext.getCmp("editWarehouse");
		me.editBizUser = Ext.getCmp("editBizUser");
		me.editBillMemo = Ext.getCmp("editBillMemo");

		me.editHiddenId = Ext.getCmp("hiddenId");

		me.columnActionDelete = Ext.getCmp("columnActionDelete");
		me.columnActionAdd = Ext.getCmp("columnActionAdd");
		me.columnActionAppend = Ext.getCmp("columnActionAppend");

		me.columnGoodsCode = Ext.getCmp("columnGoodsCode");
		me.columnGoodsPrice = Ext.getCmp("columnGoodsPrice");
		me.columnGoodsMoney = Ext.getCmp("columnGoodsMoney");

		me.buttonSave = Ext.getCmp("buttonSave");
		me.buttonCancel = Ext.getCmp("buttonCancel");

		me.buttonSelectPOBill = Ext.getCmp("buttonPWEditFormSelectPOBill");
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
		me.ajax({
			url : me.URL("Home/Purchase/pwBillInfo"),
			params : {
				id : me.editHiddenId.getValue()
			},
			callback : function(options, success, response) {
				el.unmask();

				if (success) {
					var data = me.decodeJSON(response.responseText);
					me.editBillMemo.setValue(data.billMemo);

					if (!data.genBill) {
						me.columnGoodsCode.setEditor({
									xtype : "psi_goods_with_purchaseprice_field",
									parentCmp : me,
									billType : "t_pw_bill",
									showAddButton : me.getShowAddGoodsButton() == "1"
								});
					} else {
						me.buttonSelectPOBill.setDisabled(true);
						me.editSupplier.setReadOnly(true);
						me.columnActionDelete.hide();
						me.columnActionAdd.hide();
						me.columnActionAppend.hide();
					}

					if (data.ref) {
						me.editRef.setValue(data.ref);
					} else {
						// 新建采购入库单第一步是选择采购订单
						me.onSelectPOBill();
					}

					me.editSupplier.setIdValue(data.supplierId);
					me.editSupplier.setValue(data.supplierName);

					me.editWarehouse.setIdValue(data.warehouseId);
					me.editWarehouse.setValue(data.warehouseName);

					me.editBizUser.setIdValue(data.bizUserId);
					me.editBizUser.setValue(data.bizUserName);
					if (data.bizDT) {
						me.editBizDT.setValue(data.bizDT);
					}

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
		var r = {
			url : me.URL("Home/Purchase/editPWBill"),
			params : {
				jsonStr : me.getSaveData()
			},
			callback : function(options, success, response) {
				Ext.getBody().unmask();

				if (success) {
					var data = me.decodeJSON(response.responseText);
					if (data.success) {
						me.showInfo("成功保存数据", function() {
									me.close();
									var pf = me.getParentForm();
									if (pf) {
										pf.refreshMainGrid(data.id);
									}
								});
					} else {
						me.showInfo(data.msg);
					}
				}
			}
		};
		me.ajax(r);
	},

	onEditBizDTSpecialKey : function(field, e) {
		var me = this;

		if (e.getKey() == e.ENTER) {
			me.editSupplier.focus();
		}
	},

	onEditSupplierSpecialKey : function(field, e) {
		var me = this;

		if (e.getKey() == e.ENTER) {
			me.editWarehouse.focus();
		}
	},

	onEditWarehouseSpecialKey : function(field, e) {
		var me = this;

		if (e.getKey() == e.ENTER) {
			me.editBizUser.focus();
		}
	},

	onEditBizUserSpecialKey : function(field, e) {
		var me = this;

		if (me.__readonly) {
			return;
		}

		if (e.getKey() == e.ENTER) {
			me.editBillMemo.focus();
		}
	},

	onEditBillMemoSpecialKey : function(field, e) {
		var me = this;

		if (me.__readonly) {
			return;
		}

		if (e.getKey() == e.ENTER) {
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
		var modelName = "PSIPWBillDetail_EditForm";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "goodsId", "goodsCode", "goodsName",
							"goodsSpec", "unitId", "unitName", "goodsCount", {
								name : "goodsMoney",
								type : "float"
							}, "goodsPrice", "memo", "poBillDetailId",
							"factor", "factorType", "skuUnitId",
							"skuGoodsCount", "skuUnitName", "qcBeginDT",
							"qcEndDT", "qcDays", "qcSN", "useQC"]
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

		me.__unitEditor = Ext.create("PSI.Goods.UnitField", {
					parentCmp : me
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
						header : "商品编码",
						dataIndex : "goodsCode",
						menuDisabled : true,
						sortable : false,
						draggable : false,
						id : "columnGoodsCode"
					}, {
						header : "商品名称",
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
						width : 100
					}, {
						header : "生产日期",
						dataIndex : "qcBeginDT",
						menuDisabled : true,
						sortable : false,
						draggable : false,
						editor : {
							xtype : "datefield",
							format : "Y-m-d"
						},
						renderer : Ext.util.Format.dateRenderer('Y-m-d')
					}, {
						header : "保质期(天)",
						dataIndex : "qcDays",
						menuDisabled : true,
						sortable : false,
						draggable : false,
						width : 80,
						editor : {
							xtype : "numberfield",
							hideTrigger : true
						}
					}, {
						header : "到期日期",
						dataIndex : "qcEndDT",
						menuDisabled : true,
						sortable : false,
						draggable : false,
						editor : {
							xtype : "datefield",
							format : "Y-m-d"
						},
						renderer : Ext.util.Format.dateRenderer('Y-m-d')
					}, {
						header : "批号",
						dataIndex : "qcSN",
						menuDisabled : true,
						sortable : false,
						draggable : false,
						width : 90,
						editor : {
							xtype : "textfield"
						}
					}, {
						header : "入库数量",
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
						header : "采购单位",
						dataIndex : "unitName",
						menuDisabled : true,
						sortable : false,
						draggable : false,
						width : 90,
						editor : me.__unitEditor
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
					}, {
						header : "采购单价",
						dataIndex : "goodsPrice",
						menuDisabled : true,
						sortable : false,
						draggable : false,
						align : "right",
						xtype : "numbercolumn",
						width : 100,
						id : "columnGoodsPrice",
						summaryRenderer : function() {
							return "采购金额合计";
						},
						editor : {
							xtype : "numberfield",
							hideTrigger : true
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
						id : "columnGoodsMoney",
						summaryType : "sum",
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
						width : 200,
						editor : {
							xtype : "textfield"
						}
					}, {
						header : "",
						id : "columnActionDelete",
						align : "center",
						menuDisabled : true,
						draggable : false,
						width : 50,
						xtype : "actioncolumn",
						items : [{
									icon : me
											.URL("Public/Images/icons/delete.png"),
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
						width : 50,
						xtype : "actioncolumn",
						items : [{
									icon : me
											.URL("Public/Images/icons/insert.png"),
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
						width : 50,
						xtype : "actioncolumn",
						items : [{
									icon : me
											.URL("Public/Images/icons/add.png"),
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

		goods.set("useQC", data.useQC);
		goods.set("qcDays", data.qcDays);
		goods.set("skuUnitId", data.skuUnitId);
		goods.set("skuUnitName", data.skuUnitName);
		goods.set("factor", data.factor);
		goods.set("factorType", data.factorType);

		// 设置建议采购价
		goods.set("goodsPrice", data.purchasePrice);

		me.calcMoney(goods);
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
		goods.set("factor", data.factor);
		goods.set("factorType", data.factorType);
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

		if (fieldName == "qcBeginDT" || fieldName == "qcEndDT"
				|| fieldName == "qcDays" || fieldName == "qcSN") {
			return goods.get("useQC") == 1;
		}

		if (fieldName == "goodsCode") {
			if (me.__pobillId) {
				return false;
			}
		}

		if (fieldName == "unitName") {
			if (me.__pobillId) {
				return false;
			}
			me.__unitEditor.setGoodsId(goods.get("goodsId"));
		}

		if (fieldName == "goodsPrice" || fieldName == "goodsMoney") {
			if (me.__pobillId) {
				return false;
			}
		}

		return true;
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
				if (!me.__pobillId) {
					store.add({});
				}
			}
			e.rowIdx += 1;
			me.getGoodsGrid().getSelectionModel().select(e.rowIdx);
			me.__cellEditing.startEdit(e.rowIdx, 1);
		} else if (fieldName == "goodsMoney") {
			if (goods.get(fieldName) != (new Number(oldValue)).toFixed(2)) {
				me.calcPrice(goods);
			}
		} else if (fieldName == "goodsCount") {
			if (goods.get(fieldName) != oldValue) {
				me.calcMoney(goods);
				me.calcSkuGoodsCount(goods);
			}
		} else if (fieldName == "goodsPrice") {
			if (goods.get(fieldName) != (new Number(oldValue)).toFixed(2)) {
				me.calcMoney(goods);
			}
		} else if (fieldName == "factor") {
			me.calcSkuGoodsCount(goods);
		} else if (fieldName == "qcBeginDT") {
			if (goods.get(fieldName) != oldValue) {
				me.calcQcEndDT(goods);
			}
		} else if (fieldName == "qcDays") {
			if (goods.get(fieldName) != oldValue) {
				me.calcQcEndDT(goods);
			}
		} else if (fieldName == "qcEndDT") {
			if (goods.get(fieldName) != oldValue) {
				me.calcQcDays(goods);
			}
		}
	},

	calcQcEndDT : function(goods) {
		if (!goods) {
			return;
		}
		var qcBeginDT = goods.get("qcBeginDT");

		if (!qcBeginDT) {
			return;
		}

		var expiration = goods.get("qcDays");
		var end = Ext.Date.add(qcBeginDT, Ext.Date.DAY, expiration);
		goods.set("qcEndDT", end);
	},

	calcQcDays : function(goods) {
		if (!goods) {
			return;
		}
		var qcBeginDT = goods.get("qcBeginDT");
		var qcEndDT = goods.get("qcEndDT");

		if (!qcEndDT) {
			return;
		}

		if (qcEndDT) {
			if (!qcBeginDT) {
				return;
			}

		}

		var b = Ext.util.Format.date(qcBeginDT, 'Y-m-d')
		var begin = new Date(b);
		var e = Ext.util.Format.date(qcEndDT, 'Y-m-d')
		var end = new Date(e);

		var delta = Math.floor((end.getTime() - begin.getTime())
				/ (24 * 3600 * 1000));;
		goods.set("qcDays", delta);
	},

	calcSkuGoodsCount : function(goods) {
		if (!goods) {
			return;
		}

		goods.set("skuGoodsCount", goods.get("goodsCount")
						* goods.get("factor"));
	},

	calcMoney : function(goods) {
		if (!goods) {
			return;
		}

		goods.set("goodsMoney", goods.get("goodsCount")
						* goods.get("goodsPrice"));
	},

	calcPrice : function(goods) {
		if (!goods) {
			return;
		}

		var goodsCount = goods.get("goodsCount");
		if (goodsCount && goodsCount != 0) {
			goods.set("goodsPrice", goods.get("goodsMoney")
							/ goods.get("goodsCount"));
		}
	},

	getSaveData : function() {
		var me = this;

		var result = {
			id : me.editHiddenId.getValue(),
			bizDT : Ext.Date.format(me.editBizDT.getValue(), "Y-m-d"),
			supplierId : me.editSupplier.getIdValue(),
			warehouseId : me.editWarehouse.getIdValue(),
			bizUserId : me.editBizUser.getIdValue(),
			pobillId : me.__pobillId,
			billMemo : me.editBillMemo.getValue(),
			items : []
		};

		var store = me.getGoodsGrid().getStore();
		for (var i = 0; i < store.getCount(); i++) {
			var item = store.getAt(i);
			result.items.push({
						id : item.get("id"),
						goodsId : item.get("goodsId"),
						goodsCount : item.get("goodsCount"),
						goodsPrice : item.get("goodsPrice"),
						goodsMoney : item.get("goodsMoney"),
						memo : item.get("memo"),
						poBillDetailId : item.get("poBillDetailId"),
						unitId : item.get("unitId"),
						factor : item.get("factor"),
						factorType : item.get("factorType"),
						skuUnitId : item.get("skuUnitId"),
						skuGoodsCount : item.get("skuGoodsCount"),
						qcBeginDT : item.get("qcBeginDT"),
						qcEndDT : item.get("qcEndDT"),
						qcDays : item.get("qcDays"),
						qcSN : item.get("qcSN")
					});
		}

		return Ext.JSON.encode(result);
	},

	setBillReadonly : function() {
		var me = this;
		me.__readonly = true;
		me.setTitle("<span style='font-size:160%;'>查看采购入库单</span>");
		me.buttonSave.setDisabled(true);
		me.buttonCancel.setText("关闭");
		me.editBizDT.setReadOnly(true);
		me.editSupplier.setReadOnly(true);
		me.editWarehouse.setReadOnly(true);
		me.editBizUser.setReadOnly(true);
		me.editBillMemo.setReadOnly(true);
		me.columnActionDelete.hide();
		me.columnActionAdd.hide();
		me.columnActionAppend.hide();

		me.buttonSelectPOBill.setDisabled(true);
	},

	onSelectPOBill : function() {
		var me = this;

		var form = Ext.create("PSI.Purchase.PWSelectPOBillForm", {
					parentForm : me,
					pobillId : me.__pobillId
				});

		form.show();
	},

	onCancelSelectPOBill : function() {
		var me = this;
		me.__pobillId = null;
		me.buttonSelectPOBill.setDisabled(true);
	},

	getPOBillInfo : function(id) {
		var me = this;
		me.__pobillId = id;
		var el = me.getEl() || Ext.getBody();
		el.mask(PSI.Const.LOADING);
		Ext.Ajax.request({
					url : PSI.Const.BASE_URL
							+ "Home/Purchase/getPOBillInfoForPWBill",
					params : {
						id : id
					},
					method : "POST",
					callback : function(options, success, response) {
						if (success) {
							var data = Ext.JSON.decode(response.responseText);

							me.editSupplier.setReadOnly(true);
							me.editSupplier.setValue(data.supplierName);
							me.editSupplier.setIdValue(data.supplierId);

							var store = me.getGoodsGrid().getStore();
							store.removeAll();
							store.add(data.items);

							me.columnActionDelete.hide();
							me.columnActionAdd.hide();
							me.columnActionAppend.hide();
						}

						el.unmask();
					}
				});
	}
});