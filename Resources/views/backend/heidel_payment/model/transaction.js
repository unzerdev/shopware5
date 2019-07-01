// {block name="backend/heidel_payment/model/transaction"}
Ext.define('Shopware.apps.HeidelPayment.model.Transaction', {

    /**
     * @type { String }
     */
    extend: 'Ext.data.Model',

    /**
     * The fields used for this model
     * @type { Array }
     */
    fields: [
        // {block name="backend/heidel_payment/model/transaction/fields"}{/block}
        { name: 'id', type: 'string' },
        { name: 'amount', type: 'float' },
        { name: 'date', type: 'string' },
        { name: 'type', type: 'string' }
    ]
});
// {/block}
