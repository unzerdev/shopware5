// {block name="backend/unzer_payment/model/payment"}
Ext.define('Shopware.apps.UnzerPayment.model.Payment', {

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
        // {block name="backend/unzer_payment/model/payment/fields"}{/block}
        { name: 'id', type: 'string' },
        { name: 'orderId', type: 'string' },
        { name: 'currency', type: 'string' },
        { name: 'shortId', type: 'string' },
        { name: 'descriptor', type: 'string' },
        { name: 'isFinalizeAllowed', type: 'boolean' }
    ],

    hasMany: [
        // {block name="backend/unzer_payment/model/payment/associations"}{/block}
        {
            name: 'basket',
            associationKey: 'basket',
            model: 'Shopware.apps.UnzerPayment.model.Basket'
        },
        {
            name: 'authorization',
            associationKey: 'authorization',
            model: 'Shopware.apps.UnzerPayment.model.Authorization'
        },
        {
            name: 'charges',
            associationKey: 'charges',
            model: 'Shopware.apps.UnzerPayment.model.Charge'
        },
        {
            name: 'state',
            associationKey: 'state',
            model: 'Shopware.apps.UnzerPayment.model.State'
        },
        {
            name: 'transactions',
            associationKey: 'transactions',
            model: 'Shopware.apps.UnzerPayment.model.Transaction'
        },
        {
            name: 'metadata',
            associationKey: 'metadata',
            model: 'Shopware.apps.UnzerPayment.model.Metadata'
        }
    ]
});
// {/block}
