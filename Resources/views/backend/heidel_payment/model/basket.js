// {block name="backend/unzer_payment/model/basket"}
Ext.define('Shopware.apps.UnzerPayment.model.Basket', {

    /**
     * @type { String }
     */
    extend: 'Ext.data.Model',

    /**
     * The fields used for this model
     * @type { Array }
     */
    fields: [
        // {block name="backend/unzer_payment/model/basket/fields"}{/block}
        { name: 'id', type: 'string' },
        { name: 'amountTotalGross', type: 'float' },
        { name: 'amountTotalDiscount', type: 'float' },
        { name: 'amountTotalVat', type: 'float' },
        { name: 'currencyCode', type: 'string' },
        { name: 'orderId', type: 'string' }
    ],

    hasMany: [
        // {block name="backend/unzer_payment/model/basket/associations"}{/block}
        {
            name: 'basketItems',
            model: 'Shopware.apps.UnzerPayment.model.BasketItem',
            associationKey: 'basketItems'
        }
    ]
});
// {/block}
