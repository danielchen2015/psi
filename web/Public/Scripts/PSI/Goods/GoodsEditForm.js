/**
 * 物资 - 新建或编辑界面
 */
Ext.define("PSI.Goods.GoodsEditForm", {
	extend : "PSI.AFX.BaseDialogForm",

	/**
	 * 初始化组件
	 */
	initComponent : function() {
		var me = this;
		var entity = me.getEntity();

		var modelName = "PSIGoodsUnit";
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

		me.adding = entity == null;

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

		var t = entity == null ? "新增物资" : "编辑物资";
		var f = entity == null
				? "edit-form-create.png"
				: "edit-form-update.png";
		var logoHtml = "<img style='float:left;margin:10px 20px 0px 10px;width:48px;height:48px;' src='"
				+ PSI.Const.BASE_URL
				+ "Public/Images/"
				+ f
				+ "'></img>"
				+ "<h2 style='color:#196d83'>"
				+ t
				+ "</h2>"
				+ "<p style='color:#196d83'>标记 <span style='color:red;font-weight:bold'>*</span>的是必须录入数据的字段</p>";;

		Ext.apply(me, {
			header : {
				title : me.formatTitle(PSI.Const.PROD_NAME),
				height : 40
			},
			width : 900,
			height : 450,
			layout : "border",
			items : [{
						region : "north",
						border : 0,
						height : 90,
						html : logoHtml
					}, {
						id : "PSI_Goods_GoodsEditForm_editForm",
						xtype : "form",
						region : "center",
						layout : "fit",
						items : [{
									layout : "fit",
									border : 0,
									xtype : "tabpanel",
									id : "PSI_Goods_GoodsEditForm_tabPanelMain",
									items : [{
												title : "常规",
												border : 0,
												xtype : "panel",
												layout : {
													type : "table",
													columns : 4
												},
												margin : "20, 0, 0, 0",
												bodyPadding : 5,
												defaultType : 'textfield',
												items : me.getEditInputCmp()
											}, {
												title : "单位组",
												layout : "fit",
												items : [me.getUnitGroupGrid()]
											}]
								}],
						buttons : buttons
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

		me.tabPanelMain = Ext.getCmp("PSI_Goods_GoodsEditForm_tabPanelMain");

		me.editForm = Ext.getCmp("PSI_Goods_GoodsEditForm_editForm");
		me.editCategory = Ext.getCmp("PSI_Goods_GoodsEditForm_editCategory");
		me.editCategoryId = Ext
				.getCmp("PSI_Goods_GoodsEditForm_editCategoryId");
		me.editCode = Ext.getCmp("PSI_Goods_GoodsEditForm_editCode");
		me.editName = Ext.getCmp("PSI_Goods_GoodsEditForm_editName");
		me.editPY = Ext.getCmp("PSI_Goods_GoodsEditForm_editPY");
		me.editSpec = Ext.getCmp("PSI_Goods_GoodsEditForm_editSpec");
		me.editUnit = Ext.getCmp("PSI_Goods_GoodsEditForm_editUnit");

		me.editABC = Ext.getCmp("PSI_Goods_GoodsEditForm_editABC");
		me.editBarCode = Ext.getCmp("PSI_Goods_GoodsEditForm_editBarCode");
		me.editMemo = Ext.getCmp("PSI_Goods_GoodsEditForm_editMemo");

		me.editCostPriceCheckups = Ext
				.getCmp("PSI_Goods_GoodsEditForm_editCostPriceCheckups");
		me.editPurchasePriceUpper = Ext
				.getCmp("PSI_Goods_GoodsEditForm_editPurchasePriceUpper");
		me.editBrand = Ext.getCmp("PSI_Goods_GoodsEditForm_editBrand");
		me.editBrandId = Ext.getCmp("PSI_Goods_GoodsEditForm_editBrandId");
		me.editUseQc = Ext.getCmp("PSI_Goods_GoodsEditForm_editUseQc");
		me.editQcDays = Ext.getCmp("PSI_Goods_GoodsEditForm_editQcDays");

		me.hiddenUnitGroup = Ext
				.getCmp("PSI_Goods_GoodsEditForm_hiddenUnitGroup");

		me.editLastAdd = Ext.getCmp("PSI_Goods_GoodsEditForm_editLastAdd");

		me.__editorList = [me.editCategory, me.editCode, me.editName,
				me.editPY, me.editSpec, me.editUnit, me.editABC,
				me.editBarCode, me.editCostPriceCheckups,
				me.editPurchasePriceUpper, me.editBrand, me.editUseQc,
				me.editQcDays, me.editMemo];
	},

	getEditInputCmp : function() {
		var me = this;

		var entity = me.getEntity();

		var unitStore = me.unitStore;
		var selectedCategory = null;
		var defaultCategoryId = null;

		if (me.getParentForm()) {
			var selectedCategory = me.getParentForm().getCategoryGrid()
					.getSelectionModel().getSelection();
			var defaultCategoryId = null;
			if (selectedCategory != null && selectedCategory.length > 0) {
				defaultCategoryId = selectedCategory[0].get("id");
			}
		} else {
			// 当 me.getParentForm() == null的时候，本窗体是在其他地方被调用
			// 例如：业务单据中选择物资的界面中，也可以新增物资
		}

		return [{
					xtype : "hidden",
					name : "id",
					value : entity == null ? null : entity.get("id")
				}, {
					xtype : "hidden",
					name : "unitGroup",
					id : "PSI_Goods_GoodsEditForm_hiddenUnitGroup"
				}, {
					id : "PSI_Goods_GoodsEditForm_editCategory",
					xtype : "psi_goodscategoryfield",
					fieldLabel : "物资分类",
					allowBlank : false,
					blankText : "没有输入物资分类",
					beforeLabelTextTpl : PSI.Const.REQUIRED,
					labelWidth : 70,
					labelAlign : "right",
					labelSeparator : "",
					msgTarget : 'side',
					listeners : {
						specialkey : {
							fn : me.onEditSpecialKey,
							scope : me
						}
					}
				}, {
					id : "PSI_Goods_GoodsEditForm_editCategoryId",
					name : "categoryId",
					xtype : "hidden",
					value : defaultCategoryId
				}, {
					id : "PSI_Goods_GoodsEditForm_editCode",
					fieldLabel : "物资编码",
					width : 205,
					allowBlank : false,
					blankText : "没有输入物资编码",
					beforeLabelTextTpl : PSI.Const.REQUIRED,
					name : "code",
					value : entity == null ? null : entity.get("code"),
					labelWidth : 70,
					labelAlign : "right",
					labelSeparator : "",
					msgTarget : 'side',
					listeners : {
						specialkey : {
							fn : me.onEditSpecialKey,
							scope : me
						}
					}
				}, {
					id : "PSI_Goods_GoodsEditForm_editName",
					fieldLabel : "品名",
					colspan : 2,
					width : 430,
					allowBlank : false,
					blankText : "没有输入品名",
					beforeLabelTextTpl : PSI.Const.REQUIRED,
					name : "name",
					value : entity == null ? null : entity.get("name"),
					labelWidth : 70,
					labelAlign : "right",
					labelSeparator : "",
					msgTarget : 'side',
					listeners : {
						specialkey : {
							fn : me.onEditSpecialKey,
							scope : me
						}
					}
				}, {
					id : "PSI_Goods_GoodsEditForm_editPY",
					fieldLabel : "拼音助记码",
					name : "py",
					value : entity == null ? null : entity.get("name"),
					labelWidth : 70,
					labelAlign : "right",
					labelSeparator : "",
					listeners : {
						specialkey : {
							fn : me.onEditSpecialKey,
							scope : me
						}
					}
				}, {
					xtype : "displayfield",
					value : "如果助记码不输入后台会自动生成"
				}, {
					id : "PSI_Goods_GoodsEditForm_editSpec",
					fieldLabel : "规格型号",
					colspan : 2,
					width : 430,
					name : "spec",
					value : entity == null ? null : entity.get("spec"),
					labelWidth : 70,
					labelAlign : "right",
					labelSeparator : "",
					msgTarget : 'side',
					listeners : {
						specialkey : {
							fn : me.onEditSpecialKey,
							scope : me
						}
					}
				}, {
					id : "PSI_Goods_GoodsEditForm_editUnit",
					xtype : "combo",
					fieldLabel : "基本单位",
					allowBlank : false,
					blankText : "没有输入基本单位",
					beforeLabelTextTpl : PSI.Const.REQUIRED,
					valueField : "id",
					displayField : "name",
					store : unitStore,
					queryMode : "local",
					editable : false,
					name : "unitId",
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
							fn : me.onUnitChange,
							scope : me
						}
					},
					colspan : 2
				}, {
					id : "PSI_Goods_GoodsEditForm_editABC",
					name : "abc",
					xtype : "combo",
					queryMode : "local",
					editable : false,
					valueField : "id",
					labelWidth : 70,
					labelAlign : "right",
					labelSeparator : "",
					fieldLabel : "ABC分类",
					beforeLabelTextTpl : PSI.Const.REQUIRED,
					store : Ext.create("Ext.data.ArrayStore", {
								fields : ["id", "text"],
								data : [["A", "A类"], ["B", "B类"], ["C", "C类"]]
							}),
					listeners : {
						specialkey : {
							fn : me.onEditSpecialKey,
							scope : me
						}
					},
					value : "A"
				}, {
					id : "PSI_Goods_GoodsEditForm_editBarCode",
					fieldLabel : "条形码",
					width : 205,
					name : "barCode",
					value : entity == null ? null : entity.get("barCode"),
					labelWidth : 70,
					labelAlign : "right",
					labelSeparator : "",
					msgTarget : 'side',
					listeners : {
						specialkey : {
							fn : me.onEditSpecialKey,
							scope : me
						}
					}
				}, {
					id : "PSI_Goods_GoodsEditForm_editCostPriceCheckups",
					fieldLabel : "考核成本价",
					name : "costPriceCheckups",
					labelWidth : 70,
					labelAlign : "right",
					labelSeparator : "",
					msgTarget : 'side',
					xtype : "numberfield",
					hideTrigger : true,
					listeners : {
						specialkey : {
							fn : me.onEditSpecialKey,
							scope : me
						}
					},
					width : 205
				}, {
					id : "PSI_Goods_GoodsEditForm_editPurchasePriceUpper",
					fieldLabel : "最高进货价",
					name : "purchasePriceUpper",
					labelWidth : 70,
					labelAlign : "right",
					labelSeparator : "",
					msgTarget : 'side',
					xtype : "numberfield",
					hideTrigger : true,
					listeners : {
						specialkey : {
							fn : me.onEditSpecialKey,
							scope : me
						}
					},
					width : 205
				}, {
					id : "PSI_Goods_GoodsEditForm_editBrandId",
					xtype : "hidden",
					name : "brandId"
				}, {
					id : "PSI_Goods_GoodsEditForm_editBrand",
					fieldLabel : "品牌",
					name : "brandName",
					xtype : "PSI_goods_brand_field",
					colspan : 2,
					width : 430,
					labelWidth : 70,
					labelAlign : "right",
					labelSeparator : "",
					msgTarget : 'side',
					listeners : {
						specialkey : {
							fn : me.onEditSpecialKey,
							scope : me
						}
					}
				}, {
					id : "PSI_Goods_GoodsEditForm_editUseQc",
					name : "useQc",
					xtype : "combo",
					queryMode : "local",
					editable : false,
					valueField : "id",
					labelWidth : 70,
					labelAlign : "right",
					labelSeparator : "",
					fieldLabel : "保质期管理",
					store : Ext.create("Ext.data.ArrayStore", {
								fields : ["id", "text"],
								data : [[0, "不启用"], [1, "启用"]]
							}),
					listeners : {
						specialkey : {
							fn : me.onEditSpecialKey,
							scope : me
						},
						change : {
							fn : me.onEditUseQcChange,
							scope : me
						}
					},
					value : 1
				}, {
					id : "PSI_Goods_GoodsEditForm_editQcDays",
					fieldLabel : "保质期(天)",
					name : "qcDays",
					labelWidth : 70,
					labelAlign : "right",
					labelSeparator : "",
					msgTarget : 'side',
					xtype : "numberfield",
					hideTrigger : true,
					listeners : {
						specialkey : {
							fn : me.onEditSpecialKey,
							scope : me
						}
					},
					width : 205,
					value : 1
				}, {
					fieldLabel : "备注",
					name : "memo",
					id : "PSI_Goods_GoodsEditForm_editMemo",
					value : entity == null ? null : entity.get("memo"),
					labelWidth : 70,
					labelAlign : "right",
					labelSeparator : "",
					msgTarget : 'side',
					listeners : {
						specialkey : {
							fn : me.onEditSpecialKey,
							scope : me
						}
					},
					colspan : 2,
					width : 430
				}, {
					id : "PSI_Goods_GoodsEditForm_editLastAdd",
					xtype : "displayfield",
					colspan : 4
				}];
	},

	onWindowBeforeUnload : function(e) {
		return (window.event.returnValue = e.returnValue = '确认离开当前页面？');
	},

	onWndShow : function() {
		var me = this;

		Ext.get(window).on('beforeunload', me.onWindowBeforeUnload);

		var categoryId = me.editCategoryId.getValue();
		var el = me.getEl();
		var unitStore = me.unitStore;
		el.mask(PSI.Const.LOADING);
		Ext.Ajax.request({
					url : me.URL("/Home/Goods/goodsInfo"),
					params : {
						id : me.adding ? null : me.getEntity().get("id"),
						categoryId : categoryId
					},
					method : "POST",
					callback : function(options, success, response) {
						unitStore.removeAll();

						if (success) {
							var data = Ext.JSON.decode(response.responseText);
							if (data.units) {
								unitStore.add(data.units);
							}

							if (!me.adding) {
								// 编辑物资信息
								me.editCategory.setIdValue(data.categoryId);
								me.editCategory.setValue(data.categoryName);
								me.editCode.setValue(data.code);
								me.editName.setValue(data.name);
								me.editPY.setValue(data.py);
								me.editSpec.setValue(data.spec);
								me.editUnit.setValue(data.unitId);
								me.editABC.setValue(data.ABC);
								me.editBarCode.setValue(data.barCode);
								me.editMemo.setValue(data.memo);
								var brandId = data.brandId;
								if (brandId) {
									var editBrand = me.editBrand;
									editBrand.setIdValue(brandId);
									editBrand.setValue(data.brandFullName);
								}
								me.editCostPriceCheckups
										.setValue(data.costPriceCheckups);
								me.editPurchasePriceUpper
										.setValue(data.purchasePriceUpper);
								me.editUseQc.setValue(parseInt(data.useQc));
								me.editQcDays.setValue(data.qcDays);

								var store = me.getUnitGroupGrid().getStore();
								store.removeAll();
								store.add(data.unitGroup);
							} else {
								// 新增物资
								if (unitStore.getCount() > 0) {
									var unitId = unitStore.getAt(0).get("id");
									me.editUnit.setValue(unitId);
								}
								if (data.categoryId) {
									me.editCategory.setIdValue(data.categoryId);
									me.editCategory.setValue(data.categoryName);
								}
							}

						}

						el.unmask();

						var editCode = me.editCode;
						editCode.focus();
						var v = editCode.getValue();
						editCode.setValue(null);
						editCode.setValue(v);
					}
				});
	},

	getUnitGroupSaveData : function() {
		var me = this;

		var result = {
			items : []
		};

		var store = me.getUnitGroupGrid().getStore();
		for (var i = 0; i < store.getCount(); i++) {
			var item = store.getAt(i);
			result.items.push({
						id : item.get("id"),
						factor : item.get("factor"),
						factorType : item.get("factorTypeValue")
					});
		}

		return Ext.JSON.encode(result);
	},

	onOK : function(thenAdd) {
		var me = this;

		var categoryId = me.editCategory.getIdValue();
		me.editCategoryId.setValue(categoryId);

		var brandId = me.editBrand.getIdValue();
		me.editBrandId.setValue(brandId);

		me.hiddenUnitGroup.setValue(me.getUnitGroupSaveData());

		var f = me.editForm;
		var el = f.getEl();
		el.mask(PSI.Const.SAVING);
		f.submit({
					url : me.URL("/Home/Goods/editGoods"),
					method : "POST",
					success : function(form, action) {
						el.unmask();
						me.__lastId = action.result.id;
						if (me.getParentForm()) {
							me.getParentForm().__lastId = me.__lastId;
						}

						PSI.MsgBox.tip("数据保存成功");
						me.focus();

						if (thenAdd) {
							me.setLastAddInfo();
							me.clearEdit();
						} else {
							me.close();
							if (me.getParentForm()) {
								me.getParentForm().freshGoodsGrid();
								me.getParentForm().refreshGoodsUnitGroup();
							}
						}
					},
					failure : function(form, action) {
						el.unmask();
						PSI.MsgBox.showInfo(action.result.msg, function() {
									me.editCode.focus();
								});
					}
				});
	},

	onEditSpecialKey : function(field, e) {
		var me = this;

		if (e.getKey() === e.ENTER) {
			var id = field.getId();
			for (var i = 0; i < me.__editorList.length; i++) {
				var editor = me.__editorList[i];
				if (id === "PSI_Goods_GoodsEditForm_editMemo") {
					// 切换tab页
					me.tabPanelMain.setActiveTab(1);
				}
				if (id === editor.getId()) {
					var edit = me.__editorList[i + 1];
					edit.focus();
					edit.setValue(edit.getValue());
				}
			}
		}
	},

	onLastEditSpecialKey : function(field, e) {
		var me = this;

		if (e.getKey() == e.ENTER) {
			var f = me.editForm;
			if (f.getForm().isValid()) {
				me.onOK(me.adding);
			}
		}
	},

	setLastAddInfo : function() {
		var me = this;
		if (!me.__lastAdd) {
			me.__lastAdd = [];
		}

		var code = me.editCode.getValue();
		var name = me.editName.getValue();
		me.__lastAdd.push({
					code : code,
					name : name
				});
		var info = Ext.String.format(
				"上次新增的记录：物资编码 <span style='color:red'>{0}</span> 品名 {1}", code,
				name);
		me.editLastAdd.setValue(info);
	},

	clearEdit : function() {
		var me = this;

		me.getUnitGroupGrid().getStore().removeAll();

		me.tabPanelMain.setActiveTab(0);
		me.editCode.focus();

		var editors = [me.editCode, me.editName, me.editPY, me.editSpec,
				me.editCostPriceCheckups, me.editPurchasePriceUpper,
				me.editBarCode, me.editMemo];
		for (var i = 0; i < editors.length; i++) {
			var edit = editors[i];
			edit.setValue(null);
			edit.clearInvalid();
		}
	},

	onWndClose : function() {
		var me = this;

		Ext.get(window).un('beforeunload', me.onWindowBeforeUnload);

		if (me.getParentForm()) {
			me.getParentForm().__lastId = me.__lastId;
			me.getParentForm().freshGoodsGrid();
		}
	},

	onUnitChange : function() {
		var me = this;
	},

	getUnitGroupGrid : function() {
		var me = this;
		if (me.__ugGrid) {
			return me.__ugGrid;
		}

		var modelName = "GoodsEditForm_PSIGoodsUnitGroup";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "name", "factor", "factorType",
							"factorTypeValue", "memo"]
				});

		me.__ugGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					viewConfig : {
						enableTextSelection : true
					},
					tbar : [{
								text : "添加单位到单位组",
								iconCls : "PSI-button-add",
								handler : me.onAddUnitToGroup,
								scope : me
							}],
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
							}, {
								header : "",
								id : "GoodsEditForm_columnActionDelete",
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
										PSI.MsgBox.confirm("请确认是否删除当前记录?",
												function() {
													var store = grid.getStore();
													store.remove(store
															.getAt(row));
												});
									},
									scope : me
								}]
							}, {
								header : "",
								id : "GoodsEditForm_columnActionAdd",
								align : "center",
								menuDisabled : true,
								draggable : false,
								width : 40,
								xtype : "actioncolumn",
								items : [{
									icon : PSI.Const.BASE_URL
											+ "Public/Images/icons/edit.png",
									tooltip : "编辑当前记录",
									handler : function(grid, row) {
										var store = grid.getStore();
										me.onEditUnitGroup(store.getAt(row));
									},
									scope : me
								}]
							}],
					store : Ext.create("Ext.data.Store", {
								model : modelName,
								autoLoad : false,
								data : []
							})
				});

		return me.__ugGrid;
	},

	onAddUnitToGroup : function() {
		var me = this;

		var form = Ext.create("PSI.Goods.UnitGroupEditForm", {
					parentForm : me
				});

		form.show();
	},

	onEditUnitGroup : function(record) {
		var me = this;

		console.log(record);

		var form = Ext.create("PSI.Goods.UnitGroupEditForm", {
					parentForm : me,
					entity : record
				});

		form.show();
	},

	onEditUseQcChange : function() {
		var me = this;
		var useQc = me.editUseQc.getValue() == 1;
		if (!useQc) {
			// 不启用保质期管理的时候，把保质期天数设置为0
			me.editQcDays.setValue(0);
		}
	}
});