//
// {namespace name="backend/config/view/document"}
// {block name="backend/config/view/form/document"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.Config.view.form.DocumentUnzerPayment', {
    override: 'Shopware.apps.Config.view.form.Document',
    alias: 'widget.config-form-document-unzer-payment',

    initComponent: function() {
        var me = this;
        me.callParent(arguments);
    },

    /**
     * Overrides the getFormItems method and appends the unzer payment form item
     * @return { Array }
     */
    getFormItems: function() {
        var formItems = this.callParent(arguments);

        var elementFieldSetIndex = -1;
        formItems.forEach(function(item, index) {
            if (item && item.name === 'elementFieldSet') {
                elementFieldSetIndex = index;

                return false;
            }
        });

        if (elementFieldSetIndex === -1) {
            return formItems;
        }

        formItems[elementFieldSetIndex].items.push({
            xtype: 'tinymce',
            fieldLabel: '{s name="unzerPayment/info_label_content"}{/s}',
            labelWidth: 100,
            name: 'UnzerPayment_Info_Value',
            hidden: true,
            translatable: true
        }, {
            xtype: 'textarea',
            fieldLabel: '{s name="unzerPayment/info_label_style"}{/s}',
            labelWidth: 100,
            name: 'UnzerPayment_Info_Style',
            hidden: true,
            translatable: true
        }, {
            xtype: 'tinymce',
            fieldLabel: '{s name="unzerPayment/footer_label_content"}{/s}',
            labelWidth: 100,
            name: 'UnzerPayment_Footer_Value',
            hidden: true,
            translatable: true
        }, {
            xtype: 'textarea',
            fieldLabel: '{s name="unzerPayment/footer_label_style"}{/s}',
            labelWidth: 100,
            name: 'UnzerPayment_Footer_Style',
            hidden: true,
            translatable: true
        });

        return formItems;
    }
});
// {/block}
