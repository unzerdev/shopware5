// {block name="backend/unzer_payment/model/metadata"}
Ext.define('Shopware.apps.UnzerPayment.model.Metadata', {
    /**
     * @type { String }
     */
    extend: 'Ext.data.Model',

    /**
     * The fields used for this model
     * @type { Array }
     */
    fields: [
        // {block name="backend/unzer_payment/model/metadata/fields"}{/block}
        { name: 'key', type: 'string' },
        { name: 'value', type: 'string' }
    ]
});
// {/block}
