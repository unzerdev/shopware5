//{block name="backend/order/view/detail/window"}
//{$smarty.block.parent}
Ext.define('Shopware.apps.HeidelPayment.view.detail.Window', {
    alias: 'widget.order-detail-heidelpay',
    override: 'Shopware.apps.Order.view.detail.Window',

    heidelpayTab: null,

    initComponent: function () {
        this.callParent(arguments);
    },

    createTabPanel: function () {
        var tabPanel = this.callParent(arguments),
            payment = this.record.getPayment().first();

        if (!payment.get('name').startsWith('heidel')) {
            return tabPanel;
        }

        this.heidelpayTab = this.createHeidelpayTab();
        tabPanel.add(this.heidelpayTab);

        return tabPanel;
    },

    createHeidelpayTab: function () {
        return Ext.create('Shopware.apps.HeidelPayment.view.detail.Heidelpay', {
            orderRecord: this.record,
        });
    },
});
//{/block}
