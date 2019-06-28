// {block name="backend/heidel_payment/model/payment"}
Ext.define('Shopware.apps.HeidelPayment.model.Payment', {

    /**
     * @type { String }
     */
    extend: 'Ext.data.Model',

    /**
     * The fields used for this model
     *
     * @type { Array }
     */
    fields: [
        // {block name="backend/heidel_payment/model/payment/fields"}{/block}
        { name: 'id', type: 'string' },
        { name: 'orderId', type: 'string' },
        { name: 'currency', type: 'string' }
    ],

    hasMany: [{
        name: 'basket',
        associationKey: 'basket',
        model: 'Shopware.apps.HeidelPayment.model.Basket'
    }, {
        name: 'authorization',
        associationKey: 'authorization',
        model: 'Shopware.apps.HeidelPayment.model.Authorization'
    }, {
        name: 'charges',
        associationKey: 'charges',
        model: 'Shopware.apps.HeidelPayment.model.Charge'
    }, {
        name: 'state',
        associationKey: 'state',
        model: 'Shopware.apps.HeidelPayment.model.State'
    }, {
        name: 'transactions',
        associationKey: 'transactions',
        model: 'Shopware.apps.HeidelPayment.model.Transaction'
    }, {
        name: 'metadata',
        associationKey: 'metadata',
        model: 'Shopware.apps.HeidelPayment.model.Metadata'
    }]
});
// {/block}
