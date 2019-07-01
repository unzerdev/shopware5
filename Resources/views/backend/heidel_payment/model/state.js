// {block name="backend/heidel_payment/model/state"}
Ext.define('Shopware.apps.HeidelPayment.model.State', {

    /**
     * @type { String }
     */
    extend: 'Ext.data.Model',

    /**
     * The fields used for this model
     * @type { Array }
     */
    fields: [
        // {block name="backend/heidel_payment/model/state/fields"}{/block}
        { name: 'id', type: 'int' },
        { name: 'name', type: 'string' }
    ]
});
// {/block}
