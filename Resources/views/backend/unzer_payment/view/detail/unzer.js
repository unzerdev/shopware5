// {namespace name="backend/unzer_payment/view/detail/unzer}
// {block name="backend/unzer_payment/view/detail/unzer"}
Ext.define('Shopware.apps.UnzerPayment.view.detail.unzer', {
    alias: 'widget.order-detail-unzer-payment-tab',
    id: 'unzerPaymentDetailTab',
    extend: 'Ext.form.Panel',
    title: 'Unzer Payment',
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
        return Ext.create('Shopware.apps.UnzerPayment.view.detail.unzer.Detail');
    },

    createTabControl: function () {
        var tabPanel;

        this.historyTab = Ext.create('Shopware.apps.UnzerPayment.view.detail.unzer.History');
        this.basketTab = Ext.create('Shopware.apps.UnzerPayment.view.detail.unzer.Basket');
        this.metadataTab = Ext.create('Shopware.apps.UnzerPayment.view.detail.unzer.Metadata');

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
            historyLength = record.transactions().data.items.length,
            hasAuthorization = record.authorization().first() !== undefined,
            finalizeButton = this.getComponent('unzerPaymentDetailFieldset').getComponent('buttonFinalize'),
            amount = parseFloat(basket.get('amountTotalGross')) - parseFloat(basket.get('amountTotalDiscount'));

        if (amount < 0) {
            amount = 0;
        }

        this.down('#basketAmountTotalGross').setRawValue(Ext.util.Format.currency(
            amount
        ));

        this.down('#basketAmountTotalVat').setRawValue(Ext.util.Format.currency(
            basket.get('amountTotalVat')
        ));

        this.basketTab.basketGrid.reconfigure(basket.basketItems());
        this.historyTab.transactionGrid.reconfigure(record.transactions());
        this.metadataTab.metadataGrid.reconfigure(record.metadata());

        this.down('#buttonCharge').setDisabled(!hasAuthorization);

        finalizeButton.setVisible(record.get('isFinalizeAllowed'));
        finalizeButton.setDisabled(!record.get('isFinalizeAllowed'));

        this.historyTab.transactionGrid.store.sort([
            {
                property: 'date',
                direction: 'ASC'
            }
        ]);

        this.historyTab.transactionGrid.getSelectionModel().select(historyLength - 1);

        return true;
    },

    currencyRenderer: function (value) {
        return Ext.util.Format.currency(value);
    }
});
// {/block}
