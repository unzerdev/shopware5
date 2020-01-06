// {namespace name="backend/heidel_payment/view/detail/heidelpay/detail}
// {block name="backend/heidel_payment/view/detail/heidelpay/detail"}
Ext.define('Shopware.apps.HeidelPayment.view.detail.heidelpay.Detail', {
    extend: 'Ext.form.FieldSet',
    alias: 'widget.order-detail-heidelpay-detail',
    id: 'heidelpayDetailFieldset',
    cls: Ext.baseCSSPrefix + ' heidelpay-field-set',
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
