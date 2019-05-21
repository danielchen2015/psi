/**
 * 采购模板 - 新增或编辑界面
 * 
 * @author 李静波
 */
Ext.define("PSI.PurchaseContract.PCTemplateEditForm", {
	extend : "PSI.AFX.BaseDialogForm",

	config : {
		pcBill : null
	},

	/**
	 * 初始化组件
	 */
	initComponent : function() {
		var me = this;
		var entity = me.getEntity();
		this.adding = entity == null;

		var title = entity == null ? "新建采购模板" : "编辑采购模板";
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
						id : "PCTemplateEditForm_buttonSave",
						iconCls : "PSI-button-ok",
						handler : me.onOK,
						scope : me
					}, "-", {
						text : "取消",
						id : "PCTemplateEditForm_buttonCancel",
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
						layout : "border",
						border : 0,
						items : [{
									region : "center",
									layout : "fit",
									border : 0,
									bodyPadding : 5,
									items : [me.getGoodsGrid()]
								}, {
									region : "east",
									layout : "fit",
									border : 0,
									width : 400,
									bodyPadding : 5,
									items : [me.getPCTemplateOrgGrid()]
								}]
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
									id : "PCTemplateEditForm_hiddenId",
									value : entity == null ? null : entity
											.get("id")
								}, {
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "供应合同",
									xtype : "textfield",
									readOnly : true,
									value : me.getPcBill().get("ref")
								}, {
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "供应商",
									xtype : "textfield",
									readOnly : true,
									colspan : 3,
									width : 710,
									value : me.getPcBill().get("supplierName")
								}, {
									id : "PCTemplateEditForm_editRef",
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "模板编号",
									allowBlank : false,
									blankText : "没有输入模板编号",
									beforeLabelTextTpl : PSI.Const.REQUIRED,
									xtype : "textfield",
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "PCTemplateEditForm_editBillMemo",
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
								}, {
									id : "PCTemplateEditForm_editBillStatus",
									xtype : "combo",
									queryMode : "local",
									editable : false,
									valueField : "id",
									labelWidth : 60,
									labelAlign : "right",
									labelSeparator : "",
									fieldLabel : "状态",
									store : Ext.create("Ext.data.ArrayStore", {
												fields : ["id", "text"],
												data : [[0, "停用"], [1000, "启用"]]
											}),
									value : 1000
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

		me.hiddenId = Ext.getCmp("PCTemplateEditForm_hiddenId");

		me.buttonSave = Ext.getCmp("PCTemplateEditForm_buttonSave");
		me.buttonCancel = Ext.getCmp("PCTemplateEditForm_buttonCancel");

		me.editRef = Ext.getCmp("PCTemplateEditForm_editRef");
		me.editBillMemo = Ext.getCmp("PCTemplateEditForm_editBillMemo");

		me.editBillStatus = Ext.getCmp("PCTemplateEditForm_editBillStatus");

		me.__editorList = ["PCTemplateEditForm_editRef",
				"PCTemplateEditForm_editBillMemo"];
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
							+ "Home/PurchaseContract/pcTemplateInfo",
					params : {
						pcBillId : me.getPcBill().get("id"),
						id : me.hiddenId.getValue()
					},
					method : "POST",
					callback : function(options, success, response) {
						el.unmask();

						if (success) {
							var data = Ext.JSON.decode(response.responseText);

							if (data.ref) {
								me.editRef.setValue(data.ref);
								me.editBillMemo.setValue(data.billMemo);
								me.editBillStatus
										.setValue(parseInt(data.billStatus));
							}

							var store = me.getGoodsGrid().getStore();
							store.removeAll();
							if (data.items) {
								store.add(data.items);
							}

							var store = me.getPCTemplateOrgGrid().getStore();
							store.removeAll();
							if (data.orgs) {
								store.add(data.orgs);
							}
						}
					}
				});
	},

	onOK : function() {
		var me = this;

		var ref = me.editRef.getValue();
		if (!ref) {
			PSI.MsgBox.showInfo("没有输入模板编号", function() {
						me.editRef.focus();
					});
			return;
		}

		if (me.getGoodsGrid().getStore().getCount() == 0) {
			PSI.MsgBox.showInfo("没有设置物资", function() {
						me.editRef.focus();
					});
			return;
		}

		if (me.getPCTemplateOrgGrid().getStore().getCount() == 0) {
			PSI.MsgBox.showInfo("没有设置组织机构", function() {
						me.editRef.focus();
					});
			return;
		}

		Ext.getBody().mask("正在保存中...");
		Ext.Ajax.request({
					url : PSI.Const.BASE_URL
							+ "Home/PurchaseContract/editPCTemplate",
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
											me
													.getParentForm()
													.refreshPCTemplateGrid(data.id);
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
		// do nothing
	},

	getGoodsGrid : function() {
		var me = this;
		if (me.__goodsGrid) {
			return me.__goodsGrid;
		}
		var modelName = "PSIPCTemplate_EditForm";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "pcDetailId", "goodsId", "goodsCode",
							"goodsName", "goodsSpec", "unitId", "unitName"]
				});
		var store = Ext.create("Ext.data.Store", {
					autoLoad : false,
					model : modelName,
					data : []
				});

		me.__goodsGrid = Ext.create("Ext.grid.Panel", {
					viewConfig : {
						enableTextSelection : true
					},
					columnLines : true,
					cls : "PSI",
					tbar : [{
								text : "设置物资",
								iconCls : "PSI-button-add",
								handler : me.onSettingGoods,
								scope : me
							}],
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

	getSaveData : function() {
		var me = this;

		var result = {
			id : me.hiddenId.getValue(),
			ref : me.editRef.getValue(),
			billMemo : me.editBillMemo.getValue(),
			billStatus : me.editBillStatus.getValue(),
			pcBillId : me.getPcBill().get("id"),
			items : [],
			orgs : []
		};

		var store = me.getGoodsGrid().getStore();
		for (var i = 0; i < store.getCount(); i++) {
			var item = store.getAt(i);
			result.items.push({
						pcDetailId : item.get("pcDetailId"),
						goodsId : item.get("goodsId"),
						unitId : item.get("unitId")
					});
		}

		var store = me.getPCTemplateOrgGrid().getStore();
		for (var i = 0; i < store.getCount(); i++) {
			var item = store.getAt(i);
			result.orgs.push({
						id : item.get("id")
					});
		}

		return Ext.JSON.encode(result);
	},

	getPCTemplateOrgGrid : function() {
		var me = this;
		if (me.__pcTemplateOrgGrid) {
			return me.__pcTemplateOrgGrid;
		}

		var modelName = "PSIPCTemplateOrg_EditForm";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "orgName"]
				});
		var store = Ext.create("Ext.data.Store", {
					autoLoad : false,
					model : modelName,
					data : []
				});

		me.__pcTemplateOrgGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					viewConfig : {
						enableTextSelection : true
					},
					tbar : [{
								text : "设置组织机构",
								iconCls : "PSI-button-add",
								handler : me.onOrgs,
								scope : me
							}],
					columnLines : true,
					columns : [{
								header : "使用本模板的组织机构",
								dataIndex : "orgName",
								menuDisabled : true,
								sortable : false,
								width : 280
							}, {
								header : "",
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
									},
									scope : me
								}]
							}],
					store : store
				});

		return me.__pcTemplateOrgGrid;
	},

	onOrgs : function() {
		var me = this;

		var form = Ext.create("PSI.PurchaseContract.SelectOrgForm", {
					parentForm : me
				});
		form.show();
	},

	// 由PSI.PurchaseContract.SelectOrgForm回调
	setSelectedOrg : function(items) {
		var me = this;

		var store = me.getPCTemplateOrgGrid().getStore();

		for (var i = 0; i < items.length; i++) {
			var item = items[i];
			var id = item.get("id");
			if (!store.findRecord("id", id)) {
				store.add(item);
			}
		}
	},

	onSettingGoods : function() {
		var me = this;

		var form = Ext.create("PSI.PurchaseContract.SelectGoodsForm", {
					parentForm : me,
					pcBillId : me.getPcBill().get("id")
				});
		form.show();
	},

	// 由PSI.PurchaseContract.SelectGoodsForm回调
	setSelectedGoods : function(items) {
		var me = this;

		var store = me.getGoodsGrid().getStore();

		for (var i = 0; i < items.length; i++) {
			var item = items[i];
			var id = item.get("pcDetailId");
			if (!store.findRecord("pcDetailId", id)) {
				store.add(item);
			}
		}
	}
});