// {namespace name="backend/unzer_payment/view/detail/unzer/detail}
// {block name="backend/unzer_payment/view/detail/unzer/detail"}
Ext.define('Shopware.apps.UnzerPayment.view.detail.unzer.Detail', {
    extend: 'Ext.form.FieldSet',
    alias: 'widget.order-detail-unzer-payment-detail',
    id: 'unzerPaymentDetailFieldset',
    cls: Ext.baseCSSPrefix + ' unzer-payment-field-set',
    title: '{s name=title}{/s}',
    layout: 'hbox',

    initComponent: function () {
        this.items = this.createItems();

        this.callParent(arguments);
    },

    registerEvents: function () {
        var me = this;

        me.addEvents(
            'finalize'
        );
    },

    createItems: function () {
        return [{
            xtype: 'container',
            flex: 0.5,
            defaults: {
                xtype: 'displayfield'
            },
            items: [{
                name: 'basket[amountTotalGross]',
                fieldLabel: '{s name="field/amount/label"}{/s}',
                itemId: 'basketAmountTotalGross'
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
                name: 'shortId',
                fieldLabel: '{s name="field/shortId/label"}{/s}'
            }, {
                name: 'id',
                fieldLabel: '{s name="field/id/label"}{/s}'
            }, {
                name: 'state[name]',
                fieldLabel: '{s name="field/state/label"}{/s}'
            }]
        }, {
            xtype: 'base-element-button',
            disabled: true,
            hidden: true,
            text: '{s name="button/finalize/text"}{/s}',
            cls: 'primary',
            itemId: 'buttonFinalize',
            handler: Ext.bind(this.onClickFinalizeButton, this)
        }];
    },

    onClickFinalizeButton: function () {
        this.fireEvent('finalize');
    }
});
// {/block}
