// {block name="backend/heidel_payment/model/basket_item"}
Ext.define('Shopware.apps.HeidelPayment.model.BasketItem', {

    /**
     * @type { String }
     */
    extend: 'Ext.data.Model',

    /**
     * The fields used for this model
     * @type { Array }
     */
    fields: [
        // {block name="backend/heidel_payment/model/basket_item/fields"}{/block}
        { name: 'title', type: 'string' },
        { name: 'amountDiscount', type: 'float' },
        { name: 'amountNet', type: 'float' },
        { name: 'amountGross', type: 'float' },
        { name: 'amountPerUnit', type: 'float' },
        { name: 'amountVat', type: 'float' },
        { name: 'basketItemReferenceId', type: 'string' },
        { name: 'quantity', type: 'int' },
        { name: 'vat', type: 'float' }
    ]
});
// {/block}
