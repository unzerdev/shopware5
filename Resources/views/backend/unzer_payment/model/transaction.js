// {block name="backend/unzer_payment/model/transaction"}
Ext.define('Shopware.apps.UnzerPayment.model.Transaction', {

    /**
     * @type { String }
     */
    extend: 'Ext.data.Model',

    /**
     * The fields used for this model
     * @type { Array }
     */
    fields: [
        // {block name="backend/unzer_payment/model/transaction/fields"}{/block}
        { name: 'id', type: 'string' },
        { name: 'amount', type: 'float' },
        { name: 'remainingAmount', type: 'float', useNull: true },
        { name: 'date', type: 'string' },
        { name: 'type', type: 'string' },
        { name: 'shortId', type: 'string', useNull: true }
    ]
});
// {/block}
