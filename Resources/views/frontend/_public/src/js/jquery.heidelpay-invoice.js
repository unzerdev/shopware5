;(function ($, window) {
    'use strict';

    $.plugin('heidelpayInvoice', {
        defaults: {
            heidelpayCreatePaymentUrl: '',
            birthdayElementSelector: '#heidelpayBirthday'
        },

        heidelpayPlugin: null,
        heidelpayInvoice: null,

        init: function () {
            this.heidelpayPlugin = $('*[data-heidelpay-base="true"]').data('plugin_heidelpayBase');
            this.heidelpayInvoice = this.heidelpayPlugin.getHeidelpayInstance().Invoice();
            this.heidelpayPlugin.setSubmitButtonActive(true);

            this.applyDataAttributes();
            this.registerEvents();

            $.publish('plugin/heidel_invoice/init', this);
        },

        registerEvents: function () {
            $.subscribe('plugin/heidelpay/createResource', $.proxy(this.createResource, this));
        },

        createResource: function () {
            $.publish('plugin/heidelpay_invoice/beforeCreateResource', this);

            this.heidelpayInvoice.createResource()
                .then($.proxy(this.onResourceCreated, this))
                .catch($.proxy(this.onError, this));
        },

        onResourceCreated: function (resource) {
            $.publish('plugin/heidelpay_invoice/createPayment', this, resource);

            $.ajax({
                url: this.opts.heidelpayCreatePaymentUrl,
                method: 'POST',
                data: {
                    resource: resource,
                    additional: {
                        birthday: $(this.opts.birthdayElementSelector).val()
                    }
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
