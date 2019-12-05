// {namespace name="backend/heidel_payment/view/detail/heidelpay/basket}
// {block name="backend/heidel_payment/view/detail/heidelpay/basket"}
Ext.define('Shopware.apps.HeidelPayment.view.detail.heidelpay.Basket', {
    extend: 'Ext.form.Panel',
    layout: 'fit',
    border: false,
    title: '{s name="tab/basket/title"}{/s}',

    basketGrid: null,

    initComponent: function () {
        this.items = this.createItems();

        this.callParent(arguments);
    },

    createItems: function () {
        this.basketGrid = Ext.create('Ext.grid.Panel', {
            border: false,
            autoScroll: true,
            minHeight: 200,
            columns: [
                { text: '{s name="grid/basket/column/quantity"}{/s}', dataIndex: 'quantity', flex: 1 },
                { text: '{s name="grid/basket/column/title"}{/s}', dataIndex: 'title', flex: 2 },
                { text: '{s name="grid/basket/column/amount"}{/s}', dataIndex: 'amountGross', flex: 1, renderer: this.currencyRenderer },
                { text: '{s name="grid/basket/column/amountNet"}{/s}', dataIndex: 'amountNet', flex: 1, renderer: this.currencyRenderer },
                { text: '{s name="grid/basket/column/type"}{/s}', dataIndex: 'type', flex: 1, renderer: this.typeRenderer }
            ]
        });

        return this.basketGrid;
    },

    currencyRenderer: function (value, event, record) {
        var outputValue = value;

        if (record.get('type') === 'voucher') {
            outputValue *= -1;
        }

        return Ext.util.Format.currency(outputValue);
    },

    typeRenderer: function (value) {
        switch (value) {
            case 'goods':
                return '{s name=type/goods}{/s}';
            case 'voucher':
                return '{s name=type/voucher}{/s}';
            case 'digital':
                return '{s name=type/digital}{/s}';
            case 'shipment':
                return '{s name=type/shipment}{/s}';
        }

        return value;
    }
});
// {/block}
