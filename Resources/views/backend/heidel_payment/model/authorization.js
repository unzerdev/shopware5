// {block name="backend/heidel_payment/model/authorization"}
Ext.define('Shopware.apps.HeidelPayment.model.Authorization', {

    /**
     * @type { String }
     */
    extend: 'Ext.data.Model',

    /**
     * The fields used for this model
     * @type { Array }
     */
    fields: [
        // {block name="backend/heidel_payment/model/authorization/fields"}{/block}
        { name: 'id', type: 'string' },
        { name: 'amount', type: 'float' },
        { name: 'currency', type: 'string' },
        { name: 'orderId', type: 'string' },
    ],
});
// {/block}
