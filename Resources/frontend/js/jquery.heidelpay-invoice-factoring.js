;(function ($, window) {
    'use strict';

    $.plugin('heidelpayInvoiceFactoring', {
        defaults: {
            heidelpayCreatePaymentUrl: '',
            birthdayElementSelector: '#heidelpayBirthday',
            generatedBirthdayElementSelector: '.flatpickr-input',
            isB2bCustomer: false,
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

            if (this.opts.isB2bCustomer) {
                this.createB2BForm();
            } else {
                this.createB2CForm();
            }

            $.publish('plugin/heidel_invoice_factoring/init', this);
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
                if (data.success) {
                    me.customerProvider.initFormFields(data.customer);
                }

                me.customerProvider.create({
                    containerId: 'heidelpay--invoice-factoring-container'
                });
                me.customerProvider.b2bCustomerEventHandler = $.proxy(me.onValidateB2bForm, me);
                me.customerProvider.validateAllFields();

                $.publish('plugin/heidel_invoice_factoring/createForm', this, this.customerProvider);
            });
        },

        createB2CForm: function () {
            $(this.opts.generatedBirthdayElementSelector).attr('required', 'required');
            $(this.opts.generatedBirthdayElementSelector).attr('form', 'confirm--form');

            this.heidelpayPlugin.setSubmitButtonActive(true);
        },

        createResource: function () {
            var me = this;
            $.publish('plugin/heidel_invoice_factoring/beforeCreateResource', this);

            if (this.opts.isB2bCustomer) {
                this.customerProvider.updateCustomer().then(function(customer) {
                    me.customerId = customer.id;

                    me.heidelpayInvoiceGuaranteed.createResource()
                        .then($.proxy(me.onResourceCreated, me))
                        .catch($.proxy(me.onError, me));
                }).catch(function(err) {
                    window.console.error(err.message);
                });
            } else {
                this.heidelpayInvoiceGuaranteed.createResource()
                    .then($.proxy(this.onResourceCreated, this))
                    .catch($.proxy(this.onError, this));
            }
        },

        onResourceCreated: function (resource) {
            $.publish('plugin/heidel_invoice_factoring/createPayment', this, resource);

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

        onValidateB2bForm: function (message) {
            this.heidelpayPlugin.setSubmitButtonActive(message.success);

            $.publish('plugin/heidel_invoice_factoring/onValidateB2bForm', this);
        },

        onError: function (error) {
            var message = error.customerMessage;

            if (message === undefined) {
                message = error.message;
            }

            $.publish('plugin/heidel_invoice_factoring/createResourceError', this, error);

            this.heidelpayPlugin.redirectToErrorPage(message);
        }
    });

    window.StateManager.addPlugin('*[data-heidelpay-invoice-factoring="true"]', 'heidelpayInvoiceFactoring');
})(jQuery, window);
