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

        init: function () {
            var heidelpayInstance;

            this.heidelpayPlugin = $('*[data-heidelpay-base="true"]').data('plugin_heidelpayBase');
            heidelpayInstance = this.heidelpayPlugin.getHeidelpayInstance();

            if (!heidelpayInstance) {
                return;
            }

            this.heidelpayInvoiceGuaranteed = heidelpayInstance.InvoiceGuaranteed();
            this.heidelpayPlugin.setSubmitButtonActive(true);

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
            var customerProvider = this.heidelpayPlugin.getHeidelpayInstance().B2BCustomer();

            $.ajax({
                url: this.opts.heidelpayCustomerDataUrl,
                method: 'GET',
            }).done(function (data) {
                if (!data.success) {
                    console.log("OHWEY");
                    //Error handling
                    return;
                }

                customerProvider.initFormFields(data.customer);
                customerProvider.create(
                    {
                        containerId: 'heidelpay--invoice-guaranteed-container',
                        // errorHolderId: errorFieldId,
                        // fields: ['companyInfo'],
                        // showHeader: false
                    }
                );
            });
        },

        createB2CForm: function () {
            $(this.opts.generatedBirthdayElementSelecotr).attr('required', 'required');
            $(this.opts.generatedBirthdayElementSelecotr).attr('form', 'confirm--form');
        },

        registerEvents: function () {
            $.subscribe('plugin/heidelpay/createResource', $.proxy(this.createResource, this));
        },

        createResource: function () {
            $.publish('plugin/heidelpay_invoice_guaranteed/beforeCreateResource', this);

            this.heidelpayInvoiceGuaranteed.createResource()
                .then($.proxy(this.onResourceCreated, this))
                .catch($.proxy(this.onError, this));
        },

        onResourceCreated: function (resource) {
            $.publish('plugin/heidelpay_invoice_guaranteed/createPayment', this, resource);

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

            $.publish('plugin/heidelpay_invoice_guaranteed/createResourceError', this, error);

            this.heidelpayPlugin.redirectToErrorPage(message);
        }
    });

    window.StateManager.addPlugin('*[data-heidelpay-invoice-guaranteed="true"]', 'heidelpayInvoiceGuaranteed');
})(jQuery, window);
