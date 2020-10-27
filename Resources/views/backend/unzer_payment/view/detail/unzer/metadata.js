// {namespace name="backend/unzer_payment/view/detail/unzer/metadata}
// {block name="backend/unzer_payment/view/detail/unzer/metadata"}
Ext.define('Shopware.apps.UnzerPayment.view.detail.unzer.Metadata', {
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
