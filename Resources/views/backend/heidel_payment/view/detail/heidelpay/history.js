// {namespace name="backend/heidel_payment/view/detail/heidelpay/history}
// {block name="backend/heidel_payment/view/detail/heidelpay/history"}
Ext.define('Shopware.apps.HeidelPayment.view.detail.heidelpay.History', {
    alias: 'widget.order-detail-heidelpay-tab-history',
    extend: 'Ext.form.Panel',
    title: '{s name="tab/history/title"}{/s}',
    layout: 'fit',
    border: false,

    transactionGrid: null,

    initComponent: function () {
        this.items = this.createItems();
        this.registerEvents();

        this.callParent(arguments);
    },

    registerEvents: function () {
        var me = this;

        me.addEvents(
            'refund',
            'charge'
        );
    },

    createItems: function () {
        this.transactionGrid = Ext.create('Ext.grid.Panel', {
            anchor: '100%',
            border: false,
            autoScroll: true,
            minHeight: 200,
            columns: [
                { text: '{s name="grid/history/column/type"}{/s}', dataIndex: 'type', flex: 1, renderer: this.transactionTypeRenderer },
                { text: '{s name="grid/history/column/amount"}{/s}', dataIndex: 'amount', flex: 1, renderer: this.currencyRenderer },
                { text: '{s name="grid/history/column/date"}{/s}', dataIndex: 'date', flex: 2 }
            ],
            dockedItems: [
                this.createTransactionGridToolbar()
            ],
            listeners: {
                select: Ext.bind(this.onSelectTransaction, this)
            }
        });

        return this.transactionGrid;
    },

    createTransactionGridToolbar: function () {
        return Ext.create('Ext.form.Panel', {
            dock: 'bottom',
            border: false,
            layout: {
                type: 'hbox',
                pack: 'end'
            },
            bodyPadding: 10,
            items: [
                {
                    xtype: 'base-element-number',
                    allowDecimals: true,
                    minValue: 0.01,
                    itemId: 'transactionAmount'
                },
                {
                    xtype: 'base-element-button',
                    disabled: true,
                    text: '{s name="button/charge/text"}{/s}',
                    cls: 'primary',
                    itemId: 'buttonCharge',
                    handler: Ext.bind(this.onClickChargeButton, this)
                },
                {
                    xtype: 'base-element-button',
                    disabled: true,
                    text: '{s name="button/refund/text"}{/s}',
                    cls: 'secondary',
                    itemId: 'buttonRefund',
                    handler: Ext.bind(this.onClickRefundButton, this)
                }
            ]
        });
    },

    transactionTypeRenderer: function (value) {
        switch (value) {
            case 'authorization':
                return '{s name="type/authorization"}{/s}';
            case 'charge':
                return '{s name="type/charge"}{/s}';
            case 'shipment':
                return '{s name="type/shipment"}{/s}';
            case 'cancellation':
                return '{s name="type/cancellation"}{/s}';
        }
    },

    currencyRenderer: function (value) {
        return Ext.util.Format.currency(value);
    },

    onSelectTransaction: function (row, record) {
        this.down('#buttonRefund').setDisabled(record.get('type') !== 'charge');
        this.down('#buttonCharge').setDisabled(record.get('type') !== 'authorization');

        this.down('#transactionAmount').setValue(record.get('amount'));
    },

    onClickChargeButton: function () {
        var transactionAmount = this.down('#transactionAmount').getValue();

        this.fireEvent('charge', {
            'amount': transactionAmount
        });
    },

    onClickRefundButton: function () {
        var transactionAmount = this.down('#transactionAmount').getValue(),
            charge = this.transactionGrid.getSelectionModel().getSelection()[0];

        this.fireEvent('refund', {
            'amount': transactionAmount,
            'chargeId': charge.get('id')
        });
    }
});
// {/block}
