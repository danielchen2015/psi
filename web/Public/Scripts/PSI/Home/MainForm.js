/**
 * 首页
 */
Ext.define("PSI.Home.MainForm", {
			extend : "Ext.panel.Panel",

			config : {
				productionName : "PSI"
			},

			border : 0,
			bodyPadding : 5,

			initComponent : function() {
				var me = this;

				Ext.apply(me, {
							html : "<h1>欢迎使用" + me.getProductionName()
									+ "</h1>"
						});

				me.callParent(arguments);
			}
		});