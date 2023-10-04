// {namespace name="backend/unzer_payment/view/detail/unzer/history}
// {block name="backend/unzer_payment/view/detail/unzer/history"}
Ext.define('Shopware.apps.UnzerPayment.view.detail.unzer.History', {
    alias: 'widget.order-detail-unzer-payment-tab-history',
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
            'charge',
            'cancel'
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
                    hidden: true,
                    text: '{s name="button/refund/text"}{/s}',
                    cls: 'secondary',
                    itemId: 'buttonRefund',
                    handler: Ext.bind(this.onClickRefundButton, this)
                },
                {
                    xtype: 'base-element-button',
                    disabled: true,
                    hidden: true,
                    text: '{s name="button/cancel/text"}{/s}',
                    cls: 'secondary',
                    itemId: 'buttonCancel',
                    handler: Ext.bind(this.onClickCancelButton, this)
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
        const isAuthorization = record.get('type') === 'authorization';
        const isCharge = record.get('type') === 'charge';

        this.down('#buttonRefund').setDisabled(!isCharge);
        this.down('#buttonRefund').setVisible(isCharge);

        this.down('#buttonCancel').setDisabled(!isAuthorization);
        this.down('#buttonCancel').setVisible(isAuthorization);

        this.down('#buttonCharge').setDisabled(!isAuthorization);

        this.down('#transactionAmount').setValue(record.get('amount'));
    },

    onClickChargeButton: function () {
        var transactionAmount = this.down('#transactionAmount').getValue();

        this.fireEvent('charge', {
            amount: transactionAmount
        });
    },

    onClickRefundButton: function () {
        var transactionAmount = this.down('#transactionAmount').getValue(),
            charge = this.transactionGrid.getSelectionModel().getSelection()[0];

        this.fireEvent('refund', {
            amount: transactionAmount,
            chargeId: charge.get('id')
        });
    },

    onClickCancelButton: function () {
        var me = this;
        var transactionAmount = me.down('#transactionAmount').getValue();

        Ext.MessageBox.confirm(
            '{s name="confirm/cancellation/title"}Execute cancellation?{/s}',
            '{s name="confirm/cancellation/message"}Do you really want to cancel the authorizazion of the selected amount?{/s}<br>{s name="grid/history/column/amount"}{/s}: <b>' + me.currencyRenderer(transactionAmount) + '</b>',
            Ext.bind(me.onConfirmCancel, this)
        );
    },

    onConfirmCancel: function(response) {
        var me = this;
        var transactionAmount = me.down('#transactionAmount').getValue();

        if (response === 'yes') {
            this.fireEvent('cancel', {
                amount: transactionAmount
            });
        }
    }
});
// {/block}