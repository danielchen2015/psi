/**
 * 选择组织机构
 */
Ext.define("PSI.US.SelectOrgForm", {
			extend : "PSI.AFX.BaseDialogForm",

			config : {
				parentForm : null
			},

			title : "选择组织机构",
			width : 600,
			height : 500,
			modal : true,
			layout : "fit",

			initComponent : function() {
				var me = this;
				var modelName = "PSIUSTemplate_SelectOrgForm";
				Ext.define(modelName, {
							extend : "Ext.data.Model",
							fields : ["id", "orgName", "orgType"]
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
								title : me.formatGridHeaderTitle("组织机构")
							},
							padding : 5,
							selModel : {
								mode : "MULTI"
							},
							selType : "checkboxmodel",
							store : userStore,
							columnLines : true,
							columns : [{
										header : "组织机构",
										dataIndex : "orgName",
										width : 300,
										menuDisabled : true
									}, {
										header : "性质",
										dataIndex : "orgType",
										width : 120,
										menuDisabled : true
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
									+ "Home/US/selectOrgForUSTemplate",
							params : {},
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
					PSI.MsgBox.showInfo("没有选择组织机构");

					return;
				}

				if (me.getParentForm()) {
					me.getParentForm().setSelectedOrg(items);
				}

				me.close();
			}
		});