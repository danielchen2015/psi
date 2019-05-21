/**
 * 单位组设置界面
 */
Ext.define("PSI.Goods.UnitGroupEditForm", {
	extend : "PSI.AFX.BaseDialogForm",

	/**
	 * 初始化组件
	 */
	initComponent : function() {
		var me = this;
		var entity = me.getEntity();
		me.adding = entity == null;

		var modelName = "UnitGroupEditForm_PSIGoodsUnit";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "name"]
				});
		var unitStore = Ext.create("Ext.data.Store", {
					model : modelName,
					autoLoad : false,
					data : []
				});
		me.unitStore = unitStore;

		var buttons = [];
		if (!entity) {
			buttons.push({
						text : "保存并继续新增",
						formBind : true,
						handler : function() {
							me.onOK(true);
						},
						scope : me
					});
		}

		buttons.push({
					text : "保存",
					formBind : true,
					iconCls : "PSI-button-ok",
					handler : function() {
						me.onOK(false);
					},
					scope : me
				}, {
					text : entity == null ? "关闭" : "取消",
					handler : function() {
						me.close();
					},
					scope : me
				});

		var t = entity == null ? "商品组 - 新增" : "商品组 - 编辑";

		// 基本单位名称
		me.unitName = me.getParentForm().editUnit.getRawValue();

		Ext.apply(me, {
			header : {
				title : me.formatTitle(t),
				height : 40
			},
			width : 400,
			height : 280,
			layout : "border",
			items : [{
						region : "center",
						border : 0,
						id : "PSI_Goods_UnitGroupEditForm_editForm",
						xtype : "form",
						layout : {
							type : "table",
							columns : 1
						},
						height : "100%",
						bodyPadding : 5,
						defaultType : 'textfield',
						fieldDefaults : {
							labelWidth : 60,
							labelAlign : "right",
							labelSeparator : "",
							msgTarget : 'side',
							width : 370,
							margin : "5"
						},
						items : [{
									id : "PSI_Goods_GoodsGroupEditForm_editUnit",
									xtype : "combo",
									fieldLabel : "计量单位",
									allowBlank : false,
									blankText : "没有输入计量单位",
									beforeLabelTextTpl : PSI.Const.REQUIRED,
									valueField : "id",
									displayField : "name",
									store : unitStore,
									queryMode : "local",
									editable : false,
									labelWidth : 70,
									labelAlign : "right",
									labelSeparator : "",
									msgTarget : 'side',
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										},
										change : {
											fn : me.onEditUnitChange,
											scope : me
										}
									}
								}, {
									id : "PSI_Goods_UnitGroupEditForm_editFactor",
									xtype : "numberfield",
									fieldLabel : "转换率",
									allowBlank : false,
									blankText : "没有输入转换率",
									beforeLabelTextTpl : PSI.Const.REQUIRED,
									hideTrigger : true,
									listeners : {
										specialkey : {
											fn : me.onEditPYSpecialKey,
											scope : me
										},
										change : {
											fn : me.onEditFactorChange,
											scope : me
										}
									}
								}, {
									id : "PSI_Goods_GoodsGroupEditForm_editFactorType",
									xtype : "combo",
									queryMode : "local",
									editable : false,
									valueField : "id",
									labelWidth : 70,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "转换率类型",
									store : Ext.create("Ext.data.ArrayStore", {
												fields : ["id", "text"],
												data : [[0, "固定转换率"],
														[1, "浮动转换率"]]
											}),
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									},
									value : 0
								}, {
									labelWidth : 70,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "基本单位",
									xtype : "displayfield",
									value : me.unitName
								}, {
									id : "PSI_Goods_GoodsGroupEditForm_editMemo",
									labelWidth : 70,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "说明",
									xtype : "displayfield"
								}],
						buttons : buttons
					}],
			listeners : {
				show : {
					fn : me.onWndShow,
					scope : me
				}
			}
		});

		me.callParent(arguments);

		me.editUnit = Ext.getCmp("PSI_Goods_GoodsGroupEditForm_editUnit");
		me.editFactor = Ext.getCmp("PSI_Goods_UnitGroupEditForm_editFactor");
		me.editFactorType = Ext
				.getCmp("PSI_Goods_GoodsGroupEditForm_editFactorType");

		me.editMemo = Ext.getCmp("PSI_Goods_GoodsGroupEditForm_editMemo");
	},

	setUnitFactorMemo : function() {
		var me = this;

		me.editMemo.setValue(null);

		var baseUnitName = me.unitName;

		var unitName = me.editUnit.getRawValue();
		if (!unitName) {
			return;
		}

		var factor = me.editFactor.getValue();
		if (!factor) {
			return;
		}

		var memo = "1" + unitName + " = " + factor + baseUnitName;
		me.editMemo.setValue(memo);
	},

	onOK : function(thenAdd) {
		var me = this;
		var store = me.getParentForm().getUnitGroupGrid().getStore();

		if (me.adding) {
			// 新增
			var index = store.findExact("id", me.editUnit.getValue());
			if (index > -1) {
				var info = Ext.String.format("[{0}]已经添加到单位组中了", me.editUnit
								.getRawValue());
				PSI.MsgBox.showInfo(info);
				return;
			}

			store.add({
						id : me.editUnit.getValue(),
						name : me.editUnit.getRawValue(),
						factor : me.editFactor.getValue(),
						factorType : me.editFactorType.getRawValue(),
						factorTypeValue : me.editFactorType.getValue(),
						memo : me.editMemo.getValue()
					});
		} else {
			// 编辑
			var entity = me.getEntity();
			entity.set("id", me.editUnit.getValue());
			entity.set("name", me.editUnit.getRawValue());
			entity.set("factor", me.editFactor.getValue());
			entity.set("factorType", me.editFactorType.getRawValue());
			entity.set("factorTypeValue", me.editFactorType.getValue());
			entity.set("memo", me.editMemo.getValue());
		}

		if (thenAdd) {
			me.clearEditValue();
			me.editUnit.focus();
		} else {
			me.close();
		}
	},

	clearEditValue : function() {
		var me = this;
		me.editUnit.setValue(null);
		me.editUnit.clearInvalid();
		me.editFactor.setValue(null);
		me.editFactor.clearInvalid();
		me.editFactorType.setValue(null);
		me.editFactorType.clearInvalid();
	},

	onWndShow : function() {
		var me = this;

		me.editUnit.focus();

		var unitStore = me.unitStore;
		var el = me.getEl();
		el.mask(PSI.Const.LOADING);
		me.ajax({
					url : me.URL("/Home/Goods/allUnits"),
					params : {},
					callback : function(options, success, response) {
						unitStore.removeAll();

						if (success) {
							var data = Ext.JSON.decode(response.responseText);
							unitStore.add(data);
							if (!me.adding) {
								// 编辑
								var entity = me.getEntity();
								me.editUnit.setValue(entity.get("id"));
								me.editFactor.setValue(entity.get("factor"));
								me.editFactorType.setValue(parseInt(entity
										.get("factorTypeValue")));
								me.setUnitFactorMemo();
							}
						}

						el.unmask();
					}
				});
	},

	onEditUnitChange : function() {
		this.setUnitFactorMemo();
	},

	onEditFactorChange : function() {
		this.setUnitFactorMemo();
	}
});