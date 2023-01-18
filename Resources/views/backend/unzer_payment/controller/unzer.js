// {namespace name=backend/unzer_payment/controller/unzer}
// {block name="backend/unzer_payment/controller/unzer"}
Ext.define('Shopware.apps.UnzerPayment.controller.unzer', {
    extend: 'Enlight.app.Controller',
    override: 'Shopware.apps.Order.controller.Main',

    /**
     * @type { Array }
     */
    refs: [
        { ref: 'unzerPaymentTab', selector: 'order-detail-unzer-payment-tab' },
        { ref: 'historyTab', selector: 'order-detail-unzer-payment-tab-history' },
        { ref: 'detailView', selector: 'order-detail-unzer-payment-detail' }
    ],

    paymentDetailsUrl: '{url controller=UnzerPayment action=paymentDetails module=backend}',
    loadTransactionUrl: '{url controller=UnzerPayment action=loadPaymentTransaction module=backend}',
    chargeUrl: '{url controller=UnzerPayment action=charge module=backend}',
    refundUrl: '{url controller=UnzerPayment action=refund module=backend}',
    finalizeUrl: '{url controller=UnzerPayment action=finalize module=backend}',

    orderRecord: null,
    paymentStore: null,
    payment: null,

    init: function () {
        this.createComponentControl();

        this.callParent(arguments);
    },

    onOpenUnzerPaymentTab: function(window, record) {
        this.orderRecord = record;
        this.showUnzerPayment();
    },

    createComponentControl: function () {
        this.control({
            'order-detail-unzer-payment-tab-history': {
                charge: Ext.bind(this.onCharge, this),
                refund: Ext.bind(this.onRefund, this)
            },
            'order-detail-unzer-payment-detail': {
                finalize: Ext.bind(this.onFinalize, this)
            },
            'order-detail-unzer-payment': {
                unzerPaymentOrderTabOpen: this.onOpenUnzerPaymentTab
            },
            'order-detail-window': {
                unzerPaymentOrderTabOpen: this.onOpenUnzerPaymentTab
            }
        });
    },

    showOrder: function (record) {
        this.callParent(arguments);

        this.orderRecord = record;
    },

    showUnzerPayment: function () {
        var paymentName = this.orderRecord.getPayment().first().get('name');

        /** Legacy support */
        if (!paymentName.startsWith('heidel') && !paymentName.startsWith('unzer')) {
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
                transactionId: this.orderRecord.get('transactionId'),
                shopId: this.orderRecord.get('languageIso'),
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

        this.populatePaymentDetails(responseObject.data, true);
    },

    populatePaymentDetails: function (payment, loadTransactions) {
        var unzerPaymentTab = this.getUnzerPaymentTab(),
            paymentStore = Ext.create('Ext.data.Store', {
                model: 'Shopware.apps.UnzerPayment.model.Payment',
                proxy: {
                    type: 'memory',
                    reader: {
                        type: 'json'
                    }
                }
            });

        this.paymentStore = paymentStore;
        this.paymentStore.loadRawData(payment);
        this.paymentRecord = this.paymentStore.first();

        unzerPaymentTab.loadRecord(this.paymentRecord);
        unzerPaymentTab.updateFields();

        this.showLoadingIndicator(false);

        if (loadTransactions) {
            this.loadTransactions();
        }
    },

    loadTransactions: function () {
        var me = this,
            unzerPaymentId = this.paymentRecord.getId(),
            requestsDone = 0,
            requestsToDo = this.paymentRecord.raw.transactions.length;

        this.getHistoryTab().setDisabled(true);
        this.getHistoryTab().setLoading(true);

        this.paymentRecord.raw.transactions.forEach(function (element) {
            if (element.type === 'authorization') {
                requestsDone++;

                if (requestsToDo === requestsDone) {
                    me.getHistoryTab().setDisabled(false);
                    me.getHistoryTab().setLoading(false);
                }

                return;
            }

            Ext.Ajax.request({
                url: me.loadTransactionUrl,
                params: {
                    unzerPaymentId: unzerPaymentId,
                    transactionType: element.type,
                    transactionId: element.id,
                    shopId: me.orderRecord.get('languageIso')
                },
                success: function (response) {
                    var responseObject = Ext.JSON.decode(response.responseText);
                    requestsDone++;

                    requestsDone === requestsToDo && me.allRequestsDone();

                    if (!responseObject.success) {
                        me.onRequestFailed(responseObject.data);
                        return;
                    }

                    me.transactionLoaded(responseObject, me.paymentStore.first());

                    requestsDone === requestsToDo && me.allRequestsDone();
                },
                error: function () {
                    me.onRequestFailed();
                }
            });
        });
    },

    transactionLoaded: function(responseObject, record) {
        var transactionsStore = record.transactionsStore,
            originalTransaction = transactionsStore.getById(responseObject.data.id);

        originalTransaction.set('date', responseObject.data.date);
        originalTransaction.set('type', responseObject.data.type);
        originalTransaction.set('amount', responseObject.data.amount);
        originalTransaction.set('shortId', responseObject.data.shortId);
        originalTransaction.setDirty(false);
        originalTransaction.commit(true);
    },

    allRequestsDone: function() {
        var latestShortId = this.getLatestShortId();

        if (latestShortId !== null) {
            this.paymentRecord.set('shortId', latestShortId);
            this.paymentRecord.setDirty(false);
            this.paymentRecord.commit(true);
        }

        this.populatePaymentDetails(this.paymentRecord, false);
        this.getHistoryTab().setDisabled(false);
        this.getHistoryTab().setLoading(false);
    },

    getLatestShortId: function () {
        var latestDate = null,
            latestShortId = '';

        this.paymentRecord.transactionsStore.each(function (record) {
            if (record.get('shortId') === undefined || record.get('shortId') === '') {
                return;
            }

            if (latestDate === null) {
                latestDate = Date.parse(record.get('date'));
                latestShortId = record.get('shortId');

                return;
            }

            if (latestDate < Date.parse(record.get('date'))) {
                latestDate = Date.parse(record.get('date'));
                latestShortId = record.get('shortId');
            }
        });

        return latestShortId;
    },

    showPopupMessage: function (message) {
        Shopware.Notification.createGrowlMessage('{s name="growl/title"}{/s}', message, '{s name="growl/caller"}{/s}');
    },

    showLoadingIndicator: function (message) {
        var unzerPaymentTab = this.getUnzerPaymentTab();

        if (!unzerPaymentTab) {
            return;
        }

        this.getUnzerPaymentTab().setDisabled(message !== false);
        this.getUnzerPaymentTab().setLoading(message);
    },

    onCharge: function (data) {
        this.showLoadingIndicator('{s name="loading/chargingPayment"}{/s}');

        let receipt = this.orderRecord.getReceipt().first();
        if (!receipt?.data.documentId) {
            this.showPopupMessage('{s name="growl/charge/noInvoice"}{/s}');
            this.showLoadingIndicator(false);
            return;
        }

        Ext.Ajax.request({
            url: this.chargeUrl,
            params: {
                paymentId: this.paymentRecord.get('id'),
                shopId: this.orderRecord.get('languageIso'),
                amount: data.amount,
                invoiceId: receipt.data.documentId
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
                shopId: this.orderRecord.get('languageIso'),
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
                orderId: this.orderRecord.get('id'),
                shopId: this.orderRecord.get('languageIso')
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
// {/block}
