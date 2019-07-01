// {namespace name="backend/heidel_payment/view/detail/heidelpay/metadata}
// {block name="backend/heidel_payment/view/detail/heidelpay/metadata"}
Ext.define('Shopware.apps.HeidelPayment.view.detail.heidelpay.Metadata', {
    extend: 'Ext.form.Panel',
    layout: 'fit',
    border: false,
    title: '{s name="tab/metadata/title"}{/s}',

    metadataGrid: null,

    initComponent: function () {
        this.items = this.createItems();

        this.callParent(arguments);
    },

    createItems: function () {
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
    }
});
// {/block}
