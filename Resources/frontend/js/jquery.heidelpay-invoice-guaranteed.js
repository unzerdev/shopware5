;(function ($, window) {
    'use strict';

    $.plugin('heidelpayInvoiceGuaranteed', {
        defaults: {
            heidelpayCreatePaymentUrl: '',
            birthdayElementSelector: '#heidelpayBirthday',
            generatedBirthdayElementSelecotr: '.flatpickr-input',
            heidelpayIsB2bWithoutVat: false,
            heidelpayCustomerDataUrl: ''
        },

        heidelpayPlugin: null,
        heidelpayInvoiceGuaranteed: null,
        customerId: null,
        customerProvider: null,

        init: function () {
            var heidelpayInstance;

            this.heidelpayPlugin = $('*[data-heidelpay-base="true"]').data('plugin_heidelpayBase');
            heidelpayInstance = this.heidelpayPlugin.getHeidelpayInstance();

            if (!heidelpayInstance) {
                return;
            }

            this.heidelpayInvoiceGuaranteed = heidelpayInstance.InvoiceGuaranteed();

            this.applyDataAttributes();
            this.registerEvents();

            if (this.opts.heidelpayIsB2bWithoutVat) {
                this.createB2BForm();
            } else {
                this.createB2CForm();
            }

            $.publish('plugin/heidelpay_invoice_guaranteed/init', this);
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
                    containerId: 'heidelpay--invoice-guaranteed-container'
                });

                me.heidelpayPlugin.setSubmitButtonActive(true);

                $('.heidelpayUI input').on('change', function(el) {
                    me.isB2bValid();
                });
            });
        },

        createB2CForm: function () {
            $(this.opts.generatedBirthdayElementSelecotr).attr('required', 'required');
            $(this.opts.generatedBirthdayElementSelecotr).attr('form', 'confirm--form');

            this.heidelpayPlugin.setSubmitButtonActive(true);
        },

        registerEvents: function () {
            $.subscribe('plugin/heidelpay/createResource', $.proxy(this.createResource, this));
        },

        isB2bValid: function (isCheck) {
            var me = this;

            this.customerProvider.updateCustomer()
                .then(function(customer) {
                    me.customerId = customer.id;

                    me.heidelpayPlugin.setSubmitButtonActive(true);
                    me.heidelpayPlugin.setSubmitButtonLoading(false);
                }).catch(function(err) {
                    me.heidelpayPlugin.setSubmitButtonActive(false);
                    me.heidelpayPlugin.setSubmitButtonLoading(false);

                    if ($('.h-iconimg-error').length > 0) {
                        $([document.documentElement, document.body]).animate({
                            scrollTop: $('.h-iconimg-error').first().offset().top - 50
                        });
                    }

                    window.console.error(err.message);
                });
        },

        createResource: function () {
            var me = this;
            $.publish('plugin/heidelpay_invoice_guaranteed/beforeCreateResource', this);

            this.customerProvider.updateCustomer()
                .then(function(customer) {
                    me.customerId = customer.id;

                    me.heidelpayInvoiceGuaranteed.createResource()
                        .then($.proxy(me.onResourceCreated, me))
                        .catch($.proxy(me.onError, me));
                }).catch(function(err) {
                    me.heidelpayPlugin.setSubmitButtonActive(false);
                    me.heidelpayPlugin.setSubmitButtonLoading(false);

                    if ($('.h-iconimg-error').length > 0) {
                        $([document.documentElement, document.body]).animate({
                            scrollTop: $('.h-iconimg-error').first().offset().top - 50
                        });
                    }

                    window.console.error(err.message);
                });
        },

        onResourceCreated: function (resource) {
            $.publish('plugin/heidelpay_invoice_guaranteed/createPayment', this, resource);

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

            $.publish('plugin/heidelpay_invoice_guaranteed/createResourceError', this, error);

            this.heidelpayPlugin.redirectToErrorPage(message);
        }
    });

    window.StateManager.addPlugin('*[data-heidelpay-invoice-guaranteed="true"]', 'heidelpayInvoiceGuaranteed');
})(jQuery, window);
