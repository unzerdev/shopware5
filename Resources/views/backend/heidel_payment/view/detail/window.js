// {block name="backend/order/view/detail/window"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.UnzerPayment.view.detail.Window', {
    alias: 'widget.order-detail-unzer-payment',
    override: 'Shopware.apps.Order.view.detail.Window',

    unzerPaymentTab: null,

    initComponent: function () {
        this.callParent(arguments);
    },

    createTabPanel: function () {
        var me = this,
            tabPanel = this.callParent(arguments),
            payment = this.record.getPayment().first();

        if (!payment.get('name').startsWith('unzer')) {
            return tabPanel;
        }

        this.unzerPaymentTab = this.createUnzerPaymentTab();
        tabPanel.add(this.unzerPaymentTab);

        tabPanel.on('tabchange', function (tabPanel, newCard, oldCard, eOpts) {
            if (newCard.getId() === 'unzerPaymentDetailTab') {
                me.fireEvent('unzerPaymentOrderTabOpen', me, me.record);
            }
            return true;
        });

        return tabPanel;
    },

    createUnzerPaymentTab: function () {
        return Ext.create('Shopware.apps.UnzerPayment.view.detail.unzer', {
            orderRecord: this.record
        });
    }
});
// {/block}
