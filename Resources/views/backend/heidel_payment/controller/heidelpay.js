// {namespace name=backend/heidel_payment/controller/heidelpay}
// {block name="backend/heidel_payment/controller/heidelpay"}
Ext.define('Shopware.apps.HeidelPayment.controller.Heidelpay', {
    extend: 'Enlight.app.Controller',
    override: 'Shopware.apps.Order.controller.Main',

    /**
     * @type { Array }
     */
    refs: [
        { ref: 'heidelpayTab', selector: 'order-detail-heidelpay-tab' },
        { ref: 'historyTab', selector: 'order-detail-heidelpay-tab-history' }
    ],

    paymentDetailsUrl: '{url controller=heidelpay action=paymentDetails module=backend}',
    chargeUrl: '{url controller=heidelpay action=charge module=backend}',
    refundUrl: '{url controller=heidelpay action=refund module=backend}',
    finalizeUrl: '{url controller=heidelpay action=finalize module=backend}',

    orderRecord: null,
    payment: null,

    init: function () {
        this.createComponentControl();

        this.callParent(arguments);
    },

    onOpenHeidelTab: function(window, record) {
        this.orderRecord = record;
        this.showHeidelPayment();
    },

    createComponentControl: function () {
        this.control({
            'order-detail-heidelpay-tab-history': {
                'charge': Ext.bind(this.onCharge, this),
                'refund': Ext.bind(this.onRefund, this)
            },
            'order-detail-heidelpay-detail': {
                'finalize': Ext.bind(this.onFinalize, this)
            },
            'order-detail-heidelpay': {
                'heidelOrderTabOpen': this.onOpenHeidelTab
            },
            'order-detail-window': {
                'heidelOrderTabOpen': this.onOpenHeidelTab
            }
        });
    },

    showOrder: function (record) {
        this.callParent(arguments);

        this.orderRecord = record;
    },

    showHeidelPayment: function () {
        var paymentName = this.orderRecord.getPayment().first().get('name');

        if (!paymentName.startsWith('heidel')) {
            return;
        }

        this.requestPaymentDetails(paymentName);
    },

    requestPaymentDetails: function (paymentName) {
        this.showLoadingIndicator('{s name="loading/requestingPaymentDetails"}{/s}');

        Ext.Ajax.request({
            url: this.paymentDetailsUrl,
            params: {
                orderId: this.orderRecord.get('id'),
                shopId: this.orderRecord.getShop().first().get('id'),
                paymentName: paymentName
            },
            success: Ext.bind(this.onLoadPaymentDetails, this),
            error: Ext.bind(this.onRequestFailed, this)
        });
    },

    onLoadPaymentDetails: function (response) {
        var responseObject = Ext.JSON.decode(response.responseText);

        if (!responseObject.success) {
            this.showPopupMessage(responseObject.message);
            this.showLoadingIndicator(false);

            return;
        }

        this.populatePaymentDetails(responseObject.data);
    },

    populatePaymentDetails: function (payment) {
        var heidelpayTab = this.getHeidelpayTab(),
            paymentStore = Ext.create('Ext.data.Store', {
                model: 'Shopware.apps.HeidelPayment.model.Payment',
                proxy: {
                    type: 'memory',
                    reader: {
                        type: 'json'
                    }
                }
            });

        paymentStore.loadRawData(payment);
        this.paymentRecord = paymentStore.first();

        heidelpayTab.loadRecord(this.paymentRecord);
        heidelpayTab.updateFields();

        this.showLoadingIndicator(false);
    },

    showPopupMessage: function (message) {
        Shopware.Notification.createGrowlMessage('{s name="growl/title"}{/s}', message, '{s name=growl/caller}{/s}');
    },

    showLoadingIndicator: function (message) {
        var heidelpayTab = this.getHeidelpayTab();

        if (!heidelpayTab) {
            return;
        }

        this.getHeidelpayTab().setDisabled(message !== false);
        this.getHeidelpayTab().setLoading(message);
    },

    onCharge: function (data) {
        this.showLoadingIndicator('{s name="loading/chargingPayment"}{/s}');

        Ext.Ajax.request({
            url: this.chargeUrl,
            params: {
                paymentId: this.paymentRecord.get('id'),
                shopId: this.orderRecord.getShop().first().get('id'),
                amount: data.amount
            },
            success: Ext.bind(this.onRequestSuccess, this),
            error: Ext.bind(this.onRequestFailed, this)
        });
    },

    onRefund: function (data) {
        this.showLoadingIndicator('{s name="loading/refundingPayment"}{/s}');

        Ext.Ajax.request({
            url: this.refundUrl,
            params: {
                paymentId: this.paymentRecord.get('id'),
                chargeId: data.chargeId,
                shopId: this.orderRecord.getShop().first().get('id'),
                amount: data.amount
            },
            success: Ext.bind(this.onRequestSuccess, this),
            error: Ext.bind(this.onRequestFailed, this)
        });
    },

    onFinalize: function () {
        this.showLoadingIndicator('{s name="loading/finalizingPayment"}{/s}');

        Ext.Ajax.request({
            url: this.finalizeUrl,
            params: {
                paymentId: this.paymentRecord.get('id'),
                orderId: this.orderRecord.get('id')
            },
            success: Ext.bind(this.onRequestSuccess, this),
            error: Ext.bind(this.onRequestFailed, this)
        });
    },

    onRequestSuccess: function (response) {
        var responseObject = Ext.JSON.decode(response.responseText);

        this.showPopupMessage(responseObject.message);
        this.showLoadingIndicator(false);

        if (!responseObject.success) {
            return;
        }

        this.requestPaymentDetails(null);
    },

    onRequestFailed: function (error) {
        this.showPopupMessage(error);
        this.showLoadingIndicator(false);
    }
});
//{/block}
