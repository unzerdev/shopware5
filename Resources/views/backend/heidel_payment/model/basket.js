// {block name="backend/heidel_payment/model/basket"}
Ext.define('Shopware.apps.HeidelPayment.model.Basket', {

    /**
     * @type { String }
     */
    extend: 'Ext.data.Model',

    /**
     * The fields used for this model
     * @type { Array }
     */
    fields: [
        // {block name="backend/heidel_payment/model/basket/fields"}{/block}
        { name: 'id', type: 'string' },
        { name: 'amountTotal', type: 'float' },
        { name: 'amountTotalDiscount', type: 'float' },
        { name: 'amountTotalVat', type: 'float' },
        { name: 'currencyCode', type: 'string' },
        { name: 'orderId', type: 'string' }
    ],

    hasMany: [{
        name: 'basketItems',
        model: 'Shopware.apps.HeidelPayment.model.BasketItem',
        associationKey: 'basketItems'
    }]
});
// {/block}
