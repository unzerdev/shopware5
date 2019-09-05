;(function ($, window) {
    'use strict';

    $.plugin('heidelpayInvoiceFactoring', {
        defaults: {
            heidelpayCreatePaymentUrl: '',
            birthdayElementSelector: '#heidelpayBirthday',
            generatedBirthdayElementSelecotr: '.flatpickr-input'
        },

        heidelpayPlugin: null,
        heidelpayInvoiceFactoring: null,

        init: function () {
            this.heidelpayPlugin = $('*[data-heidelpay-base="true"]').data('plugin_heidelpayBase');
            this.heidelpayInvoiceFactoring = this.heidelpayPlugin.getHeidelpayInstance().InvoiceFactoring();
            this.heidelpayPlugin.setSubmitButtonActive(true);

            this.applyDataAttributes();
            this.registerEvents();

            $(this.opts.generatedBirthdayElementSelecotr).attr('required', 'required');
            $(this.opts.generatedBirthdayElementSelecotr).attr('form', 'confirm--form');

            $.publish('plugin/heidelpay_invoice_factoring/init', this);
        },

        registerEvents: function () {
            $.subscribe('plugin/heidelpay/createResource', $.proxy(this.createResource, this));
        },

        createResource: function () {
            $.publish('plugin/heidelpay_invoice_factoring/beforeCreateResource', this);

            this.heidelpayInvoiceFactoring.createResource()
                .then($.proxy(this.onResourceCreated, this))
                .catch($.proxy(this.onError, this));
        },

        onResourceCreated: function (resource) {
            $.publish('plugin/heidelpay_invoice_factoring/createPayment', this, resource);

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

            $.publish('plugin/heidelpay_invoice_factoring/createResourceError', this, error);

            this.heidelpayPlugin.redirectToErrorPage(message);
        }
    });

    window.StateManager.addPlugin('*[data-heidelpay-invoice-factoring="true"]', 'heidelpayInvoiceFactoring');
})(jQuery, window, heidelpay);
