// {block name="backend/heidel_payment/model/charge"}
Ext.define('Shopware.apps.HeidelPayment.model.Charge', {

    /**
     * @type { String }
     */
    extend: 'Ext.data.Model',

    /**
     * The fields used for this model
     * @type { Array }
     */
    fields: [
        // {block name="backend/heidel_payment/model/charge/fields"}{/block}
        { name: 'id', type: 'string' },
        { name: 'amount', type: 'float' }
    ]
});
// {/block}
