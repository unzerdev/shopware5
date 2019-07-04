;(function ($, window, heidelpay) {
    'use strict';
    console.log("invoice");
    $.plugin('heidelpayInvoice', {
        // defaults: {
        //     heidelpayCreatePaymentUrl: ''
        // },

        heidelpayPlugin: null,
        heidelpayInvoice: null,

        init: function () {
            this.heidelpayPlugin = $('*[data-heidelpay-base="true"]').data('plugin_heidelpayBase');
            this.heidelpayInvoice = this.heidelpayPlugin.getHeidelpayInstance().Invoice();
            // this.heidelpayPlugin.setSubmitButtonActive(false);

            // this.applyDataAttributes();
            this.registerEvents();
            // this.createForm();

            $.publish('plugin/heidel_invoice/init', this);
        },

        // createForm: function () {
        //     this.heidelpayInvoice.create('invoice', {
        //         containerId: 'heidelpay--ideal-container'
        //     });
        //
        //     this.heidelpayInvoice.addEventListener('change', $.proxy(this.onFormChange, this));
        //
        //     $.publish('plugin/heidel_invoice/createForm', this, this.heidelpayInvoice);
        // },

        registerEvents: function () {
            $.subscribe('plugin/heidelpay/createResource', $.proxy(this.createResource, this));
        },

        createResource: function () {
            $.publish('plugin/heidelpay_invoice/beforeCreateResource', this);

            this.heidelpayInvoice.createResource()
                .then($.proxy(this.onResourceCreated, this))
                .catch($.proxy(this.onError, this));
        },

        // onFormChange: function (event) {
        //     if (event.value) {
        //         this.heidelpayPlugin.setSubmitButtonActive(true);
        //     }
        // },

        onResourceCreated: function (resource) {
            $.publish('plugin/heidelpay_invoice/createPayment', this, resource);

            $.ajax({
                url: this.opts.heidelpayCreatePaymentUrl,
                method: 'POST',
                data: {
                    resource: resource
                }
            }).done(function (data) {
                window.location = data.redirectUrl;
            });
        },

        onError: function (error) {
            var message = error.customerMessage;

            if (message === undefined) {
                message = error.message;
            }

            $.publish('plugin/heidelpay_invoice/createResourceError', this, error);

            this.heidelpayPlugin.redirectToErrorPage(message);
        }
    });

    window.StateManager.addPlugin('*[data-heidelpay-invoice="true"]', 'heidelpayInvoice');
})(jQuery, window, heidelpay);
