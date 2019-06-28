// {namespace name="backend/heidel_payment/view/detail/heidelpay}
// {block name="backend/heidel_payment/view/detail/heidelpay"}
Ext.define('Shopware.apps.HeidelPayment.view.detail.Heidelpay', {
    alias: 'widget.order-detail-heidelpay-tab',
    extend: 'Ext.form.Panel',
    title: 'Heidelpay',
    cls: Ext.baseCSSPrefix + ' shopware-form',

    autoScroll: true,
    bodyPadding: 10,

    basketGrid: null,
    metadataGrid: null,
    transactionGrid: null,

    initComponent: function () {
        this.items = [
            this.createDetailContainer(),
            this.createTabControl()
        ];

        this.callParent(arguments);
    },

    registerEvents: function () {
        var me = this;

        me.addEvents(
            'refund',
            'charge'
        );
    },

    createDetailContainer: function () {
        return Ext.create('Ext.form.FieldSet', {
            cls: Ext.baseCSSPrefix + 'heidelpay-field-set',
            title: 'Details',
            layout: 'hbox',
            items: this.createDetailContainerItems()
        });
    },

    createDetailContainerItems: function () {
        return [{
            xtype: 'container',
            flex: 0.5,
            defaults: {
                xtype: 'displayfield'
            },
            items: [{
                name: 'basket[amountTotal]',
                fieldLabel: '{s name="field/amount/label"}{/s}',
                itemId: 'basketAmountTotal'
            }, {
                name: 'basket[amountTotalVat]',
                fieldLabel: '{s name="field/totalVat/label"}{/s}',
                itemId: 'basketAmountTotalVat'
            }, {
                name: 'currency',
                fieldLabel: '{s name="field/currency/label"}{/s}'
            }]
        }, {
            xtype: 'container',
            defaults: {
                xtype: 'displayfield'
            },
            flex: 1,
            items: [{
                name: 'orderId',
                fieldLabel: '{s name="field/orderId/label"}{/s}'
            }, {
                name: 'state[name]',
                fieldLabel: '{s name="field/state/label"}{/s}'
            }]
        }];
    },

    createTabControl: function () {
        var tabPanel;

        tabPanel = Ext.create('Ext.tab.Panel', {
            anchor: '100%',
            border: false,
            items: [
                Ext.create('Ext.form.Panel', {
                    layout: 'fit',
                    border: false,
                    title: '{s name="tab/history/title"}{/s}',
                    items: [this.createTransactionGrid()]
                }),
                Ext.create('Ext.form.Panel', {
                    layout: 'fit',
                    border: false,
                    title: '{s name="tab/basket/title"}{/s}',
                    items: [this.createBasketGrid()]
                }),
                Ext.create('Ext.form.Panel', {
                    layout: 'fit',
                    border: false,
                    title: '{s name="tab/metadata/title"}{/s}',
                    items: [this.createMetadataGrid()]
                })
            ]
        });

        return tabPanel;
    },

    createTransactionGrid: function () {
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

    createBasketGrid: function () {
        this.basketGrid = Ext.create('Ext.grid.Panel', {
            border: false,
            autoScroll: true,
            minHeight: 200,
            columns: [
                { text: '{s name="grid/basket/column/quantity"}{/s}', dataIndex: 'quantity', flex: 1 },
                { text: '{s name="grid/basket/column/title"}{/s}', dataIndex: 'title', flex: 2 },
                { text: '{s name="grid/basket/column/amount"}{/s}', dataIndex: 'amountGross', flex: 1, renderer: this.currencyRenderer },
                { text: '{s name="grid/basket/column/amountNet"}{/s}', dataIndex: 'amountNet', flex: 1, renderer: this.currencyRenderer }
            ]
        });

        return this.basketGrid;
    },

    createMetadataGrid: function () {
        this.metadataGrid = Ext.create('Ext.grid.Panel', {
            minHeight: 200,
            border: false,
            autoScroll: true,
            columns: [
                { text: '{s name="grid/metadata/column/key"}{/s}', dataIndex: 'key', flex: 1 },
                { text: '{s name="grid/metadata/column/value"}{/s}', dataIndex: 'value', flex: 1 }
            ]
        });

        return this.metadataGrid;
    },

    updateFields: function () {
        var record = this.getRecord(),
            basket = record.basket().first(),
            hasAuthorization = record.authorization().first() !== undefined;

        this.down('#basketAmountTotal').setRawValue(Ext.util.Format.currency(
            basket.get('amountTotal')
        ));

        this.down('#basketAmountTotalVat').setRawValue(Ext.util.Format.currency(
            basket.get('amountTotalVat')
        ));

        this.basketGrid.reconfigure(basket.basketItems());
        this.transactionGrid.reconfigure(record.transactions());
        this.metadataGrid.reconfigure(record.metadata());

        this.down('#buttonCharge').setDisabled(!hasAuthorization);

        this.transactionGrid.getSelectionModel().select(0);
        return true;
    },

    onSelectTransaction: function (row, record) {
        this.down('#buttonRefund').setDisabled(record.get('type') !== 'charge');
        this.down('#buttonCharge').setDisabled(record.get('type') !== 'authorization');

        this.down('#transactionAmount').setValue(record.get('amount'));
    },

    currencyRenderer: function (value) {
        return Ext.util.Format.currency(value);
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
