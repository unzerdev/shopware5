;(function ($, window) {
    'use strict';

    $.plugin('heidelpayInvoiceFactoring', {
        defaults: {
            heidelpayCreatePaymentUrl: '',
            birthdayElementSelector: '#heidelpayBirthday',
            generatedBirthdayElementSelector: '.flatpickr-input',
            heidelpayIsB2bWithoutVat: false,
            heidelpayCustomerDataUrl: ''
        },

        heidelpayPlugin: null,
        heidelpayInvoiceFactoring: null,
        customerId: null,
        customerProvider: null,

        init: function () {
            var heidelpayInstance;

            this.heidelpayPlugin = $('*[data-heidelpay-base="true"]').data('plugin_heidelpayBase');
            heidelpayInstance = this.heidelpayPlugin.getHeidelpayInstance();

            if (!heidelpayInstance) {
                return;
            }

            this.heidelpayInvoiceFactoring = heidelpayInstance.InvoiceFactoring();
            this.heidelpayPlugin.setSubmitButtonActive(true);

            this.applyDataAttributes();
            this.registerEvents();

            if (this.opts.heidelpayIsB2bWithoutVat) {
                this.createB2BForm();
            } else {
                this.createB2CForm();
            }

            $.publish('plugin/heidelpay_invoice_factoring/init', this);
        },

        registerEvents: function () {
            $.subscribe('plugin/heidelpay/createResource', $.proxy(this.createResource, this));
        },

        createB2BForm: function () {
            var me = this,
                heidelpayInstance = this.heidelpayPlugin.getHeidelpayInstance();

            this.customerProvider = heidelpayInstance.B2BCustomer();

            $.ajax({
                url: this.opts.heidelpayCustomerDataUrl,
                method: 'GET'
            }).done(function (data) {
                if (!data.success) {
                    window.console.warn('OHWEY');
                    me.heidelpayPlugin.setSubmitButtonActive(false);
                    // Error handling
                    return;
                }

                me.customerProvider.initFormFields(data.customer);
                me.customerProvider.create({
                    containerId: 'heidelpay--invoice-factoring-container'
                });

                me.heidelpayPlugin.setSubmitButtonActive(true);

                $('.heidelpayUI input').on('change', function(el) {
                    me.isB2bValid();
                });
            });
        },

        createB2CForm: function () {
            $(this.opts.generatedBirthdayElementSelector).attr('required', 'required');
            $(this.opts.generatedBirthdayElementSelector).attr('form', 'confirm--form');

            this.heidelpayPlugin.setSubmitButtonActive(true);
        },

        isB2bValid: function () {
            var me = this;

            this.customerProvider.updateCustomer()
                .then(function(customer) {
                    me.customerId = customer.id;

                    me.heidelpayPlugin.setSubmitButtonActive(true);
                    me.heidelpayPlugin.setSubmitButtonLoading(false);

                    return true;
                })
                .catch(function(err) {
                    me.heidelpayPlugin.setSubmitButtonActive(false);
                    me.heidelpayPlugin.setSubmitButtonLoading(false);

                    window.console.error(err.message);

                    return false;
                });
        },

        createResource: function () {
            $.publish('plugin/heidelpay_invoice_factoring/beforeCreateResource', this);

            if (this.isB2bValid()) {
                this.heidelpayInvoiceFactoring.createResource()
                    .then($.proxy(this.onResourceCreated, this))
                    .catch($.proxy(this.onError, this)
                    );
            } else {
                if ($('.h-iconimg-error').length > 0) {
                    $([document.documentElement, document.body]).animate({
                        scrollTop: $('.h-iconimg-error').first().offset().top - 50
                    });
                }
            }
        },

        onResourceCreated: function (resource) {
            $.publish('plugin/heidelpay_invoice_factoring/createPayment', this, resource);

            $.ajax({
                url: this.opts.heidelpayCreatePaymentUrl,
                method: 'POST',
                data: {
                    resource: resource,
                    additional: {
                        customerId: this.customerId,
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
})(jQuery, window);
