;(function ($, window) {
    'use strict';

    $.plugin('heidelpayInvoiceGuaranteed', {
        defaults: {
            heidelpayCreatePaymentUrl: '',
            birthdayElementSelector: '#heidelpayBirthday',
            generatedBirthdayElementSelector: '.flatpickr-input',
            isB2bCustomer: false,
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

            this.heidelpayPlugin.setSubmitButtonActive(true);

            if (this.opts.isB2bCustomer) {
                this.createB2BForm();
            } else {
                this.createB2CForm();
            }

            $.publish('plugin/heidelpay/invoice_guaranteed/init', this);
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
                    me.onError(data);
                    return;
                }

                me.customerProvider.b2bCustomerEventHandler = $.proxy(me.onValidateB2bForm, me);
                me.customerProvider.initFormFields(data.customer);
                me.customerProvider.create({
                    containerId: 'heidelpay--invoice-guaranteed-container'
                });

                $.publish('plugin/heidel_invoice_guaranteed/createB2bForm', [this, this.customerProvider]);
            }).catch(function (error) {
                me.onError(error);
            });
        },

        createB2CForm: function () {
            $(this.opts.generatedBirthdayElementSelector).attr('required', 'required');
            $(this.opts.generatedBirthdayElementSelector).attr('form', 'confirm--form');

            this.heidelpayPlugin.setSubmitButtonActive(true);
            $.publish('plugin/heidel_invoice_guaranteed/createB2cForm', [this, this.customerProvider]);
        },

        registerEvents: function () {
            $.subscribe('plugin/heidelpay/createResource', $.proxy(this.createResource, this));
        },

        createResource: function () {
            var me = this;
            $.publish('plugin/heidelpay/invoice_guaranteed/beforeCreateResource', this);

            if (this.opts.isB2bCustomer) {
                this.customerProvider.updateCustomer().then(function(customer) {
                    me.customerId = customer.id;

                    me.heidelpayInvoiceGuaranteed.createResource()
                        .then($.proxy(me.onResourceCreated, me))
                        .catch($.proxy(me.onError, me));
                }).catch(function(error) {
                    me.onError(error);
                });
            } else {
                this.heidelpayInvoiceGuaranteed.createResource()
                    .then($.proxy(this.onResourceCreated, this))
                    .catch($.proxy(this.onError, this));
            }
        },

        onResourceCreated: function (resource) {
            $.publish('plugin/heidelpay/invoice_guaranteed/createPayment', this, resource);

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

        onValidateB2bForm: function (validationResult) {
            this.heidelpayPlugin.setSubmitButtonActive(validationResult.success);

            $.publish('plugin/heidelpay/invoice_guaranteed/onValidateB2bForm', [this, validationResult]);
        },

        onError: function (error) {
            var message = error.customerMessage;

            if (message === undefined) {
                message = error.message;
            }

            $.publish('plugin/heidelpay/invoice_guaranteed/createResourceError', this, error);

            this.heidelpayPlugin.redirectToErrorPage(message);
        }
    });

    window.StateManager.addPlugin('*[data-heidelpay-invoice-guaranteed="true"]', 'heidelpayInvoiceGuaranteed');
})(jQuery, window);
