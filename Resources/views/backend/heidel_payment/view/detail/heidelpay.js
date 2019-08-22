// {namespace name="backend/heidel_payment/view/detail/heidelpay}
// {block name="backend/heidel_payment/view/detail/heidelpay"}
Ext.define('Shopware.apps.HeidelPayment.view.detail.Heidelpay', {
    alias: 'widget.order-detail-heidelpay-tab',
    extend: 'Ext.form.Panel',
    title: 'Heidelpay',
    cls: Ext.baseCSSPrefix + ' shopware-form',

    autoScroll: true,
    bodyPadding: 10,

    basketTab: null,
    metadataGrid: null,
    historyTab: null,

    initComponent: function () {
        this.items = [
            this.createDetailContainer(),
            this.createTabControl()
        ];

        this.callParent(arguments);
    },

    createDetailContainer: function () {
        return Ext.create('Shopware.apps.HeidelPayment.view.detail.heidelpay.Detail');
    },

    createTabControl: function () {
        var tabPanel;

        this.historyTab = Ext.create('Shopware.apps.HeidelPayment.view.detail.heidelpay.History');
        this.basketTab = Ext.create('Shopware.apps.HeidelPayment.view.detail.heidelpay.Basket');
        this.metadataTab = Ext.create('Shopware.apps.HeidelPayment.view.detail.heidelpay.Metadata');

        tabPanel = Ext.create('Ext.tab.Panel', {
            anchor: '100%',
            border: false,
            items: [
                this.historyTab,
                this.basketTab,
                this.metadataTab
            ]
        });

        return tabPanel;
    },

    updateFields: function () {
        var record = this.getRecord(),
            basket = record.basket().first(),
            hasAuthorization = record.authorization().first() !== undefined;

        this.down('#basketAmountTotalGross').setRawValue(Ext.util.Format.currency(
            basket.get('amountTotalGross')
        ));

        this.down('#basketAmountTotalVat').setRawValue(Ext.util.Format.currency(
            basket.get('amountTotalVat')
        ));

        this.basketTab.basketGrid.reconfigure(basket.basketItems());
        this.historyTab.transactionGrid.reconfigure(record.transactions());
        this.metadataTab.metadataGrid.reconfigure(record.metadata());

        this.down('#buttonCharge').setDisabled(!hasAuthorization);

        this.historyTab.transactionGrid.getSelectionModel().select(0);

        return true;
    },

    currencyRenderer: function (value) {
        return Ext.util.Format.currency(value);
    }
});
// {/block}
