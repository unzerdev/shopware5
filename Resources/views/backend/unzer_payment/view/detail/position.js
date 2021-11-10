// This is an important comment, because shopware removes the first written line!

Ext.define('Shopware.apps.UnzerPayment.view.detail.Position', {
    override: 'Shopware.apps.Order.view.detail.Position',

    initComponent: function () {
        var me = this;

        me.isUnzerPayment = false;

        me.record.getPaymentStore.data.items.forEach(function (payment) {
            if (payment.data.name.toLowerCase().includes('unzer')) {
                me.isUnzerPayment = true;
            }
        });

        me.callParent(arguments);
    },

    /**
     * Overrides the getColumns function of the order position grid which is defined in view/list/position.js
     */
    getColumns: function (grid) {
        var me = this,
            columns = me.callParent(arguments);

        if (me.isUnzerPayment) {
            columns.forEach(function (column) {
                if (column.xtype !== 'actioncolumn') {
                    return;
                }

                column.items.forEach(function (button, index) {
                    if (button.iconCls === 'sprite-minus-circle-frame' && button.action === 'deletePosition') {
                        column.items.splice(index, 1);
                    }
                });
            });
        }

        return columns;
    },

    /**
     * Creates the position grid for the position tab panel.
     * The position grid is already defined in backend/order/view/list/position.js.
     * The grid in the position tab is an small extension of the original grid.
     *
     * @return Ext.grid.Panel
     */
    createPositionGrid: function() {
        var me = this;

        if (me.isUnzerPayment) {
            me.orderPositionGrid = Ext.create('Shopware.order.position.grid', {
                name: 'order-position-grid',
                store: me.record.getPositions(),
                plugins: [],
                style: {
                    borderTop: '1px solid #A4B5C0'
                },
                viewConfig: {
                    enableTextSelection: false
                },
                getColumns: function() {
                    return me.getColumns(this);
                }
            });

            return me.orderPositionGrid;
        }

        return me.callParent(arguments);
    },

    traceGridEvents: function() {
        var me = this;

        if (me.isUnzerPayment) {
            return;
        }

        return me.callParent(arguments);
    }
});
