/**
 * 供应商档案 - 新建或编辑界面
 */
Ext.define("PSI.Supplier.SupplierEditForm", {
	extend : "PSI.AFX.BaseDialogForm",

	initComponent : function() {
		var me = this;
		var entity = me.getEntity();
		this.adding = entity == null;

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

		var t = entity == null ? "新增供应商" : "编辑供应商";
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
				+ "<p style='color:#196d83'>标记 <span style='color:red;font-weight:bold'>*</span>的是必须录入数据的字段</p>";

		Ext.apply(me, {
			header : {
				title : me.formatTitle(PSI.Const.PROD_NAME),
				height : 40
			},
			width : 550,
			height : 560,
			layout : "border",
			items : [{
						region : "north",
						border : 0,
						height : 90,
						html : logoHtml
					}, {
						region : "center",
						border : 0,
						id : "PSI_Supplier_SupplierEditForm_editForm",
						xtype : "form",
						layout : {
							type : "table",
							columns : 2
						},
						height : "100%",
						bodyPadding : 5,
						defaultType : 'textfield',
						fieldDefaults : {
							labelWidth : 100,
							labelAlign : "right",
							labelSeparator : "",
							msgTarget : 'side'
						},
						items : [{
									xtype : "hidden",
									name : "id",
									value : entity == null ? null : entity
											.get("id")
								}, {
									xtype : "hidden",
									name : "categoryId",
									id : "PSI_Supplier_SupplierEditForm_editCategoryId"
								}, {
									id : "PSI_Supplier_SupplierEditForm_editCategory",
									xtype : "psi_suppliercategoryfield",
									fieldLabel : "分类",
									allowBlank : false,
									blankText : "没有输入供应商分类",
									beforeLabelTextTpl : PSI.Const.REQUIRED,
									value : null,
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "PSI_Supplier_SupplierEditForm_editCode",
									fieldLabel : "编码",
									allowBlank : false,
									blankText : "没有输入供应商编码",
									beforeLabelTextTpl : PSI.Const.REQUIRED,
									name : "code",
									value : entity == null ? null : entity
											.get("code"),
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "PSI_Supplier_SupplierEditForm_editName",
									fieldLabel : "供应商名称",
									allowBlank : false,
									blankText : "没有输入供应商名称",
									beforeLabelTextTpl : PSI.Const.REQUIRED,
									name : "name",
									value : entity == null ? null : entity
											.get("name"),
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									},
									colspan : 2,
									width : 510
								}, {
									id : "PSI_Supplier_SupplierEditForm_editAddress",
									fieldLabel : "地址",
									name : "address",
									value : entity == null ? null : entity
											.get("address"),
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									},
									colspan : 2,
									width : 510
								}, {
									id : "PSI_Supplier_SupplierEditForm_editContact01",
									fieldLabel : "联系人",
									name : "contact01",
									value : entity == null ? null : entity
											.get("contact01"),
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "PSI_Supplier_SupplierEditForm_editMobile01",
									fieldLabel : "手机",
									name : "mobile01",
									value : entity == null ? null : entity
											.get("mobile01"),
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "PSI_Supplier_SupplierEditForm_editTel01",
									fieldLabel : "固话",
									name : "tel01",
									value : entity == null ? null : entity
											.get("tel01"),
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "PSI_Supplier_SupplierEditForm_editQQ01",
									fieldLabel : "QQ",
									name : "qq01",
									value : entity == null ? null : entity
											.get("qq01"),
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "PSI_Supplier_SupplierEditForm_editContact02",
									fieldLabel : "备用联系人",
									name : "contact02",
									value : entity == null ? null : entity
											.get("contact02"),
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "PSI_Supplier_SupplierEditForm_editMobile02",
									fieldLabel : "备用联系人手机",
									name : "mobile02",
									value : entity == null ? null : entity
											.get("mobile02"),
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "PSI_Supplier_SupplierEditForm_editTel02",
									fieldLabel : "备用联系人固话",
									name : "tel02",
									value : entity == null ? null : entity
											.get("tel02"),
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "PSI_Supplier_SupplierEditForm_editQQ02",
									fieldLabel : "备用联系人QQ",
									name : "qq02",
									value : entity == null ? null : entity
											.get("qq02"),
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "PSI_Supplier_SupplierEditForm_editAddressShipping",
									fieldLabel : "发货地址",
									name : "addressShipping",
									value : entity == null ? null : entity
											.get("addressShipping"),
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									},
									colspan : 2,
									width : 510
								}, {
									id : "PSI_Supplier_SupplierEditForm_editBankName",
									fieldLabel : "开户行",
									name : "bankName",
									value : entity == null ? null : entity
											.get("bankName"),
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "PSI_Supplier_SupplierEditForm_editBankAccount",
									fieldLabel : "开户行账号",
									name : "bankAccount",
									value : entity == null ? null : entity
											.get("bankAccount"),
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "PSI_Supplier_SupplierEditForm_editTax",
									fieldLabel : "税号",
									name : "tax",
									value : entity == null ? null : entity
											.get("tax"),
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "PSI_Supplier_SupplierEditForm_editFax",
									fieldLabel : "传真",
									name : "fax",
									value : entity == null ? null : entity
											.get("fax"),
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "PSI_Supplier_SupplierEditForm_editTaxRate",
									fieldLabel : "税率",
									name : "taxRate",
									xtype : "numberfield",
									hideTrigger : true,
									allowDecimals : false,
									value : entity == null ? null : entity
											.get("taxRate"),
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									xtype : "displayfield",
									value : "%"
								}, {
									id : "PSI_Supplier_SupplierEditForm_editInitPayables",
									fieldLabel : "应付期初余额",
									name : "initPayables",
									xtype : "numberfield",
									hideTrigger : true,
									value : entity == null ? null : entity
											.get("initPayables"),
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "PSI_Supplier_SupplierEditForm_editInitPayablesDT",
									fieldLabel : "余额日期",
									name : "initPayablesDT",
									xtype : "datefield",
									format : "Y-m-d",
									value : entity == null ? null : entity
											.get("initPayablesDT"),
									listeners : {
										specialkey : {
											fn : me.onEditSpecialKey,
											scope : me
										}
									}
								}, {
									id : "PSI_Supplier_SupplierEditForm_editNote",
									fieldLabel : "备注",
									name : "note",
									value : entity == null ? null : entity
											.get("note"),
									listeners : {
										specialkey : {
											fn : me.onEditLastSpecialKey,
											scope : me
										}
									},
									width : 510,
									colspan : 2
								}, {
									id : "PSI_Supplier_SupplierEditForm_editLastAdd",
									xtype : "displayfield",
									width : 510,
									colspan : 2
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

		me.editForm = Ext.getCmp("PSI_Supplier_SupplierEditForm_editForm");

		me.editCategoryId = Ext
				.getCmp("PSI_Supplier_SupplierEditForm_editCategoryId");
		me.editCategory = Ext
				.getCmp("PSI_Supplier_SupplierEditForm_editCategory");
		me.editCode = Ext.getCmp("PSI_Supplier_SupplierEditForm_editCode");
		me.editName = Ext.getCmp("PSI_Supplier_SupplierEditForm_editName");
		me.editAddress = Ext
				.getCmp("PSI_Supplier_SupplierEditForm_editAddress");
		me.editContact01 = Ext
				.getCmp("PSI_Supplier_SupplierEditForm_editContact01");
		me.editMobile01 = Ext
				.getCmp("PSI_Supplier_SupplierEditForm_editMobile01");
		me.editTel01 = Ext.getCmp("PSI_Supplier_SupplierEditForm_editTel01");
		me.editQQ01 = Ext.getCmp("PSI_Supplier_SupplierEditForm_editQQ01");
		me.editContact02 = Ext
				.getCmp("PSI_Supplier_SupplierEditForm_editContact02");
		me.editMobile02 = Ext
				.getCmp("PSI_Supplier_SupplierEditForm_editMobile02");
		me.editTel02 = Ext.getCmp("PSI_Supplier_SupplierEditForm_editTel02");
		me.editQQ02 = Ext.getCmp("PSI_Supplier_SupplierEditForm_editQQ02");
		me.editAddressShipping = Ext
				.getCmp("PSI_Supplier_SupplierEditForm_editAddressShipping");
		me.editBankName = Ext
				.getCmp("PSI_Supplier_SupplierEditForm_editBankName");
		me.editBankAccount = Ext
				.getCmp("PSI_Supplier_SupplierEditForm_editBankAccount");
		me.editTax = Ext.getCmp("PSI_Supplier_SupplierEditForm_editTax");
		me.editFax = Ext.getCmp("PSI_Supplier_SupplierEditForm_editFax");
		me.editInitPayables = Ext
				.getCmp("PSI_Supplier_SupplierEditForm_editInitPayables");
		me.editInitPayablesDT = Ext
				.getCmp("PSI_Supplier_SupplierEditForm_editInitPayablesDT");
		me.editNote = Ext.getCmp("PSI_Supplier_SupplierEditForm_editNote");

		me.editTaxRate = Ext
				.getCmp("PSI_Supplier_SupplierEditForm_editTaxRate");

		me.editLastAdd = Ext
				.getCmp("PSI_Supplier_SupplierEditForm_editLastAdd");

		me.__editorList = [me.editCategory, me.editCode, me.editName,
				me.editAddress, me.editContact01, me.editMobile01,
				me.editTel01, me.editQQ01, me.editContact02, me.editMobile02,
				me.editTel02, me.editQQ02, me.editAddressShipping,
				me.editBankName, me.editBankAccount, me.editTax, me.editFax,
				me.editTaxRate, me.editInitPayables, me.editInitPayablesDT,
				me.editNote];
	},

	onWndShow : function() {
		var me = this;

		Ext.get(window).on('beforeunload', me.onWindowBeforeUnload);

		me.editCode.focus();
		me.editCode.setValue(me.editCode.getValue());

		if (me.adding) {
			// 新建
			if (me.getParentForm()) {
				var grid = me.getParentForm().categoryGrid;
				var item = grid.getSelectionModel().getSelection();
				if (item == null || item.length != 1) {
					return;
				}

				var c = item[0];
				me.editCategory.setIdValue(c.get("id"));
				me.editCategory.setValue(c.get("fullName"));
			} else {
				// 从其他界面调用本窗口
				me.editCategory.focus();
			}
		} else {
			// 编辑
			var el = me.getEl();
			el.mask(PSI.Const.LOADING);
			Ext.Ajax.request({
						url : me.URL("/Home/Supplier/supplierInfo"),
						params : {
							id : me.getEntity().get("id")
						},
						method : "POST",
						callback : function(options, success, response) {
							if (success) {
								var data = Ext.JSON
										.decode(response.responseText);
								me.editCategoryId.setValue(data.categoryId);
								me.editCategory.setIdValue(data.categoryId);
								me.editCategory.setValue(data.categoryName);
								me.editCode.setValue(data.code);
								me.editName.setValue(data.name);
								me.editAddress.setValue(data.address);
								me.editContact01.setValue(data.contact01);
								me.editMobile01.setValue(data.mobile01);
								me.editTel01.setValue(data.tel01);
								me.editQQ01.setValue(data.qq01);
								me.editContact02.setValue(data.contact02);
								me.editMobile02.setValue(data.mobile02);
								me.editTel02.setValue(data.tel02);
								me.editQQ02.setValue(data.qq02);
								me.editAddressShipping
										.setValue(data.addressShipping);
								me.editInitPayables.setValue(data.initPayables);
								me.editInitPayablesDT
										.setValue(data.initPayablesDT);
								me.editBankName.setValue(data.bankName);
								me.editBankAccount.setValue(data.bankAccount);
								me.editTax.setValue(data.tax);
								me.editFax.setValue(data.fax);
								me.editNote.setValue(data.note);
								me.editTaxRate.setValue(data.taxRate);
							}

							el.unmask();
						}
					});
		}

	},

	onOK : function(thenAdd) {
		var me = this;

		var taxRate = me.editTaxRate.getValue();
		if (taxRate) {
			if (taxRate < 0 || taxRate > 17) {
				PSI.MsgBox.showInfo("税率在0到17之间");
				me.editTaxRate.focus();
				return;
			}
		}

		me.editCategoryId.setValue(me.editCategory.getIdValue());

		var f = me.editForm;
		var el = f.getEl();
		el.mask(PSI.Const.SAVING);
		f.submit({
					url : me.URL("/Home/Supplier/editSupplier"),
					method : "POST",
					success : function(form, action) {
						el.unmask();
						PSI.MsgBox.tip("数据保存成功");
						me.focus();
						me.__lastId = action.result.id;
						if (thenAdd) {
							me.setLastAddInfo();
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
				});
	},

	onEditSpecialKey : function(field, e) {
		var me = this;

		if (e.getKey() === e.ENTER) {
			var id = field.getId();
			for (var i = 0; i < me.__editorList.length; i++) {
				var edit = me.__editorList[i];
				if (id == edit.getId()) {
					var edit = me.__editorList[i + 1];
					edit.focus();
					edit.setValue(edit.getValue());
				}
			}
		}
	},

	onEditLastSpecialKey : function(field, e) {
		var me = this;

		if (e.getKey() === e.ENTER) {
			var f = me.editForm;
			if (f.getForm().isValid()) {
				me.onOK(me.adding);
			}
		}
	},

	clearEdit : function() {
		var me = this;

		me.editCode.focus();

		var editors = [me.editCode, me.editName, me.editAddress,
				me.editAddressShipping, me.editContact01, me.editMobile01,
				me.editTel01, me.editQQ01, me.editContact02, me.editMobile02,
				me.editTel02, me.editQQ02, me.editInitPayables,
				me.editInitPayablesDT, me.editBankName, me.editBankAccount,
				me.editTax, me.editTaxRate, me.editFax, me.editNote];
		for (var i = 0; i < editors.length; i++) {
			var edit = editors[i];
			edit.setValue(null);
			edit.clearInvalid();
		}
	},

	onWindowBeforeUnload : function(e) {
		return (window.event.returnValue = e.returnValue = '确认离开当前页面？');
	},

	onWndClose : function() {
		var me = this;

		Ext.get(window).un('beforeunload', me.onWindowBeforeUnload);

		if (me.__lastId) {
			if (me.getParentForm()) {
				me.getParentForm().freshSupplierGrid(me.__lastId);
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
				"上次新增的记录：编码 <span style='color:red'>{0}</span> 名称 {1}", code,
				name);
		me.editLastAdd.setValue(info);
	}
});