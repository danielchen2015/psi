/**
 * 库存建账 - 新增或编辑界面
 */
Ext.define("PSI.Inventory.InitInventoryEditForm", {
	extend : "Ext.window.Window",

	config : {
		parentForm : null,
		warehouse : null,
		entity : null
	},

	/**
	 * 初始化组件
	 */
	initComponent : function() {
		var me = this;

		var entity = me.getEntity();

		me.adding = entity == null;

		var buttons = [];
		if (!entity) {
			var btn = {
				text : "保存并继续新增",
				formBind : true,
				handler : function() {
					me.onOK(true);
				},
				scope : me
			};

			buttons.push(btn);
		}

		var btn = {
			text : "保存",
			formBind : true,
			iconCls : "PSI-button-ok",
			handler : function() {
				me.onOK(false);
			},
			scope : me
		};
		buttons.push(btn);

		var btn = {
			text : entity == null ? "关闭" : "取消",
			handler : function() {
				me.close();
			},
			scope : me
		};
		buttons.push(btn);

		Ext.apply(me, {
					title : entity == null ? "新增建账信息" : "编辑建账信息",
					modal : true,
					resizable : false,
					onEsc : Ext.emptyFn,
					width : 650,
					height : 300,
					layout : "fit",
					listeners : {
						show : {
							fn : me.onWndShow,
							scope : me
						},
						close : {
							fn : me.onWndClose,
							scope : me
						}
					},
					items : [{
								id : "editForm",
								xtype : "form",
								layout : {
									type : "table",
									columns : 2
								},
								height : "100%",
								bodyPadding : 5,
								defaultType : 'textfield',
								fieldDefaults : {
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									msgTarget : 'side',
									width : 300,
									margin : "5"
								},
								items : [{
											xtype : "displayfield",
											fieldLabel : "仓库",
											value : me.getWarehouse()
													.get("name"),
											colspan : 2
										}, {
											xtype : "hidden",
											name : "warehouseId",
											value : me.getWarehouse().get("id")
										}, {
											xtype : "hidden",
											name : "goodsId",
											id : "editGoodsId"
										}, {
											id : "editCode",
											fieldLabel : "商品编码",
											xtype : "psi_goodsfield",
											parentCmp : me,
											allowBlank : false,
											blankText : "没有输入商品编码",
											beforeLabelTextTpl : PSI.Const.REQUIRED,
											colspan : 2,
											listeners : {
												specialkey : {
													fn : me.onEditSpecialKey,
													scope : me
												}
											}
										}, {
											id : "editName",
											xtype : "displayfield",
											fieldLabel : "商品名称"
										}, {
											id : "editSpec",
											xtype : "displayfield",
											fieldLabel : "规格型号"
										}, {
											id : "editQcBeginDT",
											name : "qcBeginDT",
											xtype : "datefield",
											format : "Y-m-d",
											fieldLabel : "生产日期",
											listeners : {
												specialkey : {
													fn : me.onEditSpecialKey,
													scope : me
												}
											}
										}, {
											id : "editExpiration",
											name : "qcDays",
											xtype : "numberfield",
											hideTrigger : true,
											allowDecimals : false,
											fieldLabel : "保质期(天)",
											width : 120,
											listeners : {
												specialkey : {
													fn : me.onEditSpecialKey,
													scope : me
												}
											}
										}, {
											id : "editQcEndDT",
											name : "qcEndDT",
											xtype : "datefield",
											format : "Y-m-d",
											fieldLabel : "到期日期",
											listeners : {
												specialkey : {
													fn : me.onEditSpecialKey,
													scope : me
												}
											}
										}, {
											id : "editQcSN",
											name : "qcSN",
											xtype : "textfield",
											fieldLabel : "批号",
											listeners : {
												specialkey : {
													fn : me.onEditSpecialKey,
													scope : me
												}
											}
										}, {
											id : "editCount",
											name : "count",
											fieldLabel : "期初数量",
											beforeLabelTextTpl : PSI.Const.REQUIRED,
											xtype : "numberfield",
											allowDecimals : false,
											hideTrigger : true,
											listeners : {
												specialkey : {
													fn : me.onEditSpecialKey,
													scope : me
												},
												focus : {
													fn : me.onEditCountFocus,
													scope : me
												}
											}
										}, {
											id : "editUnit",
											xtype : "displayfield",
											fieldLabel : "计量单位",
											value : ""
										}, {
											id : "editMoney",
											name : "money",
											fieldLabel : "期初金额",
											xtype : "numberfield",
											allowDecimals : true,
											hideTrigger : true,
											beforeLabelTextTpl : PSI.Const.REQUIRED,
											listeners : {
												specialkey : {
													fn : me.onLastEditSpecialKey,
													scope : me
												}
											}
										}],
								buttons : buttons
							}]
				});

		me.callParent(arguments);

		me.__editorList = ["editCode", "editQcBeginDT", "editExpiration",
				"editQcEndDT", "editQcSN", "editCount", "editMoney"];

	},

	calcDate : function() {
		var qcBeginDT = Ext.getCmp("editQcBeginDT").getValue();

		if (!qcBeginDT) {
			return;
		}

		var expiration = Ext.getCmp("editExpiration").getValue();
		if (expiration > 0) {
			var end = Ext.Date.add(qcBeginDT, Ext.Date.DAY, expiration);

			Ext.getCmp("editQcEndDT").setValue(end);
		}
	},

	calcDate2 : function() {
		var qcBeginDT = Ext.getCmp("editQcBeginDT").getValue();
		var qcEndDT = Ext.getCmp("editQcEndDT").getValue();
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
		Ext.getCmp("editExpiration").setValue(delta);
	},

	/**
	 * 保存
	 */
	onOK : function(thenAdd) {
		var me = this;

		var qcBeginDT = Ext.getCmp("editQcBeginDT").getValue();
		var qcEndDT = Ext.getCmp("editQcEndDT").getValue();
		if (qcEndDT) {
			if (!qcBeginDT) {
				PSI.MsgBox.showInfo("没有输入生产日期", function() {
							Ext.getCmp("editQcBeginDT").focus();
						});
				return;
			}

			if (qcEndDT < qcBeginDT) {
				PSI.MsgBox.showInfo("到期日期不能小于生产日期", function() {
							Ext.getCmp("editQcBeginDT").focus();
						});
				return;
			}

			me.calcDate2();
		}

		var f = Ext.getCmp("editForm");
		var el = f.getEl();
		el.mask(PSI.Const.SAVING);
		var sf = {
			url : PSI.Const.BASE_URL + "Home/InitInventory/editInitInv",
			method : "POST",
			success : function(form, action) {
				me.__lastId = action.result.id;

				el.unmask();

				PSI.MsgBox.tip("数据保存成功");
				me.focus();
				if (thenAdd) {
					me.clearEdit();
				} else {
					me.close();
				}
			},
			failure : function(form, action) {
				el.unmask();
				PSI.MsgBox.showInfo(action.result.msg, function() {
							Ext.getCmp("editCode").focus();
						});
			}
		};
		f.submit(sf);
	},

	onEditSpecialKey : function(field, e) {
		if (e.getKey() === e.ENTER) {
			var me = this;
			var id = field.getId();
			for (var i = 0; i < me.__editorList.length; i++) {
				var editorId = me.__editorList[i];
				if (id === editorId) {
					if (id == "editExpiration") {
						me.calcDate();
						me.onEditCountFocus();
					} else if (id == "editQcEndDT") {
						me.calcDate2();
					}
					var edit = Ext.getCmp(me.__editorList[i + 1]);
					edit.focus();
					edit.setValue(edit.getValue());
				}
			}
		}
	},

	onLastEditSpecialKey : function(field, e) {
		var me = this;

		if (e.getKey() == e.ENTER) {
			var f = Ext.getCmp("editForm");
			if (f.getForm().isValid()) {
				me.onOK(me.adding);
			}
		}
	},

	clearEdit : function() {
		var me = this;

		Ext.getCmp("editCode").focus();

		var editors = ["editCode", "editName", "editSpec", "editQcBeginDT",
				"editExpiration", "editQcEndDT", "editQcSN", "editCount",
				"editUnit", "editMoney"];
		for (var i = 0; i < editors.length; i++) {
			var edit = Ext.getCmp(editors[i]);
			if (edit) {
				edit.setValue(null);
				edit.clearInvalid();
			}
		}
	},

	onWndClose : function() {
		var me = this;
		me.getParentForm().freshInvGrid();
	},

	onWndShow : function() {
		var editCode = Ext.getCmp("editCode");
		editCode.focus();
		editCode.setValue(editCode.getValue());
	},

	__setGoodsInfo : function(data) {
		var me = this;
		me.useQC = data.useQC;

		Ext.getCmp("editGoodsId").setValue(data.id);
		Ext.getCmp("editName").setValue(data.name);
		Ext.getCmp("editSpec").setValue(data.spec);
		Ext.getCmp("editUnit").setValue(data.unitName);
		Ext.getCmp("editExpiration").setValue(data.qcDays);
	},

	onEditCountFocus : function() {
		var me = this;
		var count = Ext.getCmp("editCount").getValue();
		if (count != null) {
			return;
		}

		var warehouseId = me.getWarehouse().get("id");
		var goodsId = Ext.getCmp("editGoodsId").getValue();
		var qcBeginDT = Ext.Date.format(Ext.getCmp("editQcBeginDT").getValue(),
				"Y-m-d");
		var expiration = Ext.getCmp("editExpiration").getValue();

		var qcSN = Ext.getCmp("editQcSN").getValue();

		var el = Ext.getBody();
		el.mask(PSI.Const.LOADING);
		var r = {
			url : PSI.Const.BASE_URL + "Home/InitInventory/queryData",
			params : {
				warehouseId : warehouseId,
				goodsId : goodsId,
				qcBeginDT : qcBeginDT,
				qcDays : expiration,
				qcSN : qcSN
			},
			method : "POST",
			callback : function(options, success, response) {
				el.unmask();
				if (success) {
					var data = Ext.JSON.decode(response.responseText);
					if (data.success) {
						Ext.getCmp("editCount").setValue(data.count);
						Ext.getCmp("editMoney").setValue(data.money);
					}
				}
			}
		};

		Ext.Ajax.request(r);
	}
});