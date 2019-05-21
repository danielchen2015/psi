/**
 * 员工管理 - 主界面
 */
Ext.define("PSI.Employee.MainForm", {
	extend : "PSI.AFX.BaseMainExForm",

	config : {
		pAddUser : null,
		pEditUser : null,
		pDeleteUser : null
	},

	/**
	 * 初始化组件
	 */
	initComponent : function() {
		var me = this;

		Ext.apply(me, {
					tbar : [{
								text : "新增员工",
								disabled : me.getPAddUser() == "0",
								handler : me.onAddUser,
								scope : me
							}, {
								text : "编辑员工",
								disabled : me.getPEditUser() == "0",
								handler : me.onEditUser,
								scope : me
							}, {
								text : "删除员工",
								disabled : me.getPDeleteUser() == "0",
								handler : me.onDeleteUser,
								scope : me
							}, "-", {
								text : "关闭",
								handler : function() {
									me.closeWindow();
								}
							}],
					items : [{
                                id : "panelQueryCmp",
                                region : "north",
                                border : 0,
                                height : 35,
                                header : false,
                                collapsible : true,
                                collapseMode : "mini",
                                layout : {
                                    type : "table",
                                    columns : 4
                                },
                                items : me.getQueryCmp()
                            },{
								region : "center",
								xtype : "panel",
								layout : "fit",
								border : 0,
								items : [me.getUserGrid()]
							}]
				});

		me.callParent(arguments);

		me.grid = me.getUserGrid();
	},


    getQueryCmp : function() {
        var me = this;
        return [{
            id : "editQueryName",
            labelWidth : 60,
            labelAlign : "right",
            labelSeparator : "",
            fieldLabel : "姓名",
            margin : "5, 0, 0, 0",
            xtype : "textfield"
        },{
            id : "editQueryLoginName",
            labelWidth : 60,
            labelAlign : "right",
            labelSeparator : "",
            fieldLabel : "身份证号",
            margin : "5, 0, 0, 0",
            xtype : "textfield"
        },  {
            xtype : "container",
            items : [{
                xtype : "button",
                text : "查询",
                width : 100,
                height : 26,
                margin : "5, 0, 0, 20",
                handler : me.onQuery,
                scope : me
            }, {
                xtype : "button",
                text : "清空查询条件",
                width : 100,
                height : 26,
                margin : "5, 0, 0, 5",
                handler : me.onClearQuery,
                scope : me
            }, {
                xtype : "button",
                text : "隐藏查询条件栏",
                width : 130,
                height : 26,
                iconCls : "PSI-button-hide",
                margin : "5 0 0 10",
                handler : function() {
                    Ext.getCmp("panelQueryCmp").collapse();
                },
                scope : me
            }]
        }];
    },

	getUserGrid : function() {
		var me = this;

		if (me.__employeeGrid) {
			return me.__employeeGrid;
		}

		var modelName = "PSIEmployee";
		Ext.define(modelName, {
					extend : "Ext.data.Model",
					fields : ["id", "name", "emplyee_no", "level", "job",
							"gender", "starttime", "jobtime", "birthday",
                            "age", "contactno", "degree", "married",
                            "healthstart", "healthend", "origin", "address",
                            "id_card", "id_card_end"]
				});
		var storeGrid = Ext.create("Ext.data.Store", {
					autoLoad : false,
					model : modelName,
					data : [],
					pageSize : 20,
					proxy : {
						type : "ajax",
						actionMethods : {
							read : "POST"
						},
						url : me.URL("Home/Employee/lists"),
						reader : {
							root : 'dataList',
							totalProperty : 'totalCount'
						}
					}
				});
		storeGrid.on("beforeload", function() {
					//storeGrid.proxy.extraParams = me.getUserParam();
				});

		me.__employeeGrid = Ext.create("Ext.grid.Panel", {
					cls : "PSI",
					header : {
						height : 30,
						title : me.formatGridHeaderTitle("员工列表")
					},
					viewConfig : {
						enableTextSelection : true
					},
					columnLines : true,
					columns : [ {
                                header : "序号",
                                dataIndex : "id",
                                menuDisabled : true,
                                sortable : false,
                                width : 50
                            }, {
								header : "姓名",
								dataIndex : "name",
								menuDisabled : true,
								sortable : false
							}, {
								header : "工号",
								dataIndex : "emplyee_no",
								menuDisabled : true,
								sortable : false,
								width : 50
							}, {
								header : "职级",
								dataIndex : "level",
								menuDisabled : true,
								sortable : false
							}, {
                                header : "岗位",
                                dataIndex : "job",
                                menuDisabled : true,
                                sortable : false
                            }, {
								header : "性别",
								dataIndex : "gender",
								menuDisabled : true,
								sortable : false,
								width : 70
							}, {
								header : "入职日",
								dataIndex : "starttime",
								menuDisabled : true,
								sortable : false
							}, {
								header : "工龄/年",
								dataIndex : "jobtime",
								menuDisabled : true,
								sortable : false,
								width : 200
							}, {
								header : "出生年月",
								dataIndex : "birthday",
								menuDisabled : true,
								sortable : false
							}, {
								header : "年龄",
								dataIndex : "age",
								menuDisabled : true,
								sortable : false
							}, {
                                header : "联系方式",
                                dataIndex : "contactno",
                                menuDisabled : true,
                                sortable : false
                            }, {
                                header : "文化程度",
                                dataIndex : "degree",
                                menuDisabled : true,
                                sortable : false
                            }, {
                                header : "婚姻状况",
                                dataIndex : "married",
                                menuDisabled : true,
                                sortable : false
                            }, {
                                header : "发证日",
                                dataIndex : "healthstart",
                                menuDisabled : true,
                                sortable : false
                            }, {
                                header : "到期日",
                                dataIndex : "healthend",
                                menuDisabled : true,
                                sortable : false
                            }, {
                                header : "籍贯",
                                dataIndex : "origin",
                                menuDisabled : true,
                                sortable : false
                            }, {
                                header : "身份证地址",
                                dataIndex : "address",
                                menuDisabled : true,
                                sortable : false
                            }, {
                                header : "身份证号",
                                dataIndex : "id_card",
                                menuDisabled : true,
                                sortable : false
                            }, {
								header : "身份证到期日",
								dataIndex : "id_card_end",
								menuDisabled : true,
								sortable : false,
								width : 100
							}],
					store : storeGrid,
					listeners : {
						itemdblclick : {
							fn : me.onEditUser,
							scope : me
						}
					},
					bbar : ["->", {
								id : "pagingToolbar",
								border : 0,
								xtype : "pagingtoolbar",
								store : storeGrid
							}, "-", {
								xtype : "displayfield",
								value : "每页显示"
							}, {
								id : "comboCountPerPage",
								xtype : "combobox",
								editable : false,
								width : 60,
								store : Ext.create("Ext.data.ArrayStore", {
											fields : ["text"],
											data : [["20"], ["50"], ["100"],
													["300"], ["1000"]]
										}),
								value : 20,
								listeners : {
									change : {
										fn : function() {
											storeGrid.pageSize = Ext
													.getCmp("comboCountPerPage")
													.getValue();
											storeGrid.currentPage = 1;
											Ext.getCmp("pagingToolbar")
													.doRefresh();
										},
										scope : me
									}
								}
							}, {
								xtype : "displayfield",
								value : "条记录"
							}]
				});

		return me.__employeeGrid;
	},

	getGrid : function() {
		return this.grid;
	},

	freshUserGrid : function() {
		var me = this;

		var tree = me.getOrgGrid();
		var item = tree.getSelectionModel().getSelection();
		if (item === null || item.length !== 1) {
			return;
		}

		me.onOrgTreeNodeSelect(item[0]);
	},

	/**
	 * 新增员工
	 */
	onAddUser : function() {
		var me = this;

		var tree = me.getOrgGrid();
		var item = tree.getSelectionModel().getSelection();
		var org = null;
		if (item != null && item.length > 0) {
			org = item[0];
		}

		var form = Ext.create("PSI.Employee.UserEditForm", {
					parentForm : me,
					defaultOrg : org
				});
		form.show();
	},

	/**
	 * 编辑员工
	 */
	onEditUser : function() {
		var me = this;
		if (me.getPEditUser() == "0") {
			return;
		}

		var item = me.getUserGrid().getSelectionModel().getSelection();
		if (item === null || item.length !== 1) {
			me.showInfo("请选择要编辑的员工");
			return;
		}

		var user = item[0].data;

		var tree = me.orgTree;
		var node = tree.getSelectionModel().getSelection();
		if (node && node.length === 1) {
			var org = node[0].data;

			user.orgId = org.id;
			user.orgName = org.fullName;
		}

		var form = Ext.create("PSI.User.UserEditForm", {
					parentForm : me,
					entity : user
				});
		form.show();
	},

	/**
	 * 删除员工
	 */
	onDeleteUser : function() {
		var me = this;
		var item = me.getUserGrid().getSelectionModel().getSelection();
		if (item === null || item.length !== 1) {
			me.showInfo("请选择要删除的员工");
			return;
		}

		var user = item[0].getData();

		var funcConfirm = function() {
			Ext.getBody().mask("正在删除中...");
			var r = {
				url : me.URL("Home/User/deleteUser"),
				params : {
					id : user.id
				},
				callback : function(options, success, response) {
					Ext.getBody().unmask();

					if (success) {
						var data = me.decodeJSON(response.responseText);
						if (data.success) {
							me.showInfo("成功完成删除操作", function() {
										me.freshUserGrid();
									});
						} else {
							me.showInfo(data.msg);
						}
					}
				}
			};
			me.ajax(r);
		};

		var info = "请确认是否删除员工 <span style='color:red'>" + user.name
				+ "</span> ?";
		me.confirm(info, funcConfirm);
	},

	getUserParam : function() {
		var me = this;
		var item = me.getOrgGrid().getSelectionModel().getSelection();
		if (item == null || item.length == 0) {
			return {};
		}

		var org = item[0];

		var queryLoginName = null;
		var editLoginName = Ext.getCmp("editQueryLoginName");
		if (editLoginName) {
			queryLoginName = editLoginName.getValue();
		}

		var queryName = null;
		var editQueryName = Ext.getCmp("editQueryName");
		if (editQueryName) {
			queryName = editQueryName.getValue();
		}

		return {
			orgId : org.get("id"),
			queryLoginName : queryLoginName,
			queryName : queryName
		}
	},

	onClearQuery : function() {
		var me = this;

		Ext.getCmp("editQueryLoginName").setValue(null);
		Ext.getCmp("editQueryName").setValue(null);

		me.onQuery();
	},

	onQuery : function() {
		var me = this;

		me.getUserGrid().getStore().removeAll();

		me.freshOrgGrid();
	},

	getQueryParamForCategory : function() {
		var queryLoginName = null;
		var editLoginName = Ext.getCmp("editQueryLoginName");
		if (editLoginName) {
			queryLoginName = editLoginName.getValue();
		}

		var queryName = null;
		var editQueryName = Ext.getCmp("editQueryName");
		if (editQueryName) {
			queryName = editQueryName.getValue();
		}

		return {
			queryLoginName : queryLoginName,
			queryName : queryName
		};
	}
});