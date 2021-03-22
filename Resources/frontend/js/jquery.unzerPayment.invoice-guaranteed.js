;(function ($, window) {
    'use strict';

    $.plugin('unzerPaymentInvoiceGuaranteed', {
        defaults: {
            unzerPaymentCreatePaymentUrl: '',
            birthdayElementSelector: '#unzerPaymentBirthday',
            generatedBirthdayElementSelector: '.flatpickr-input',
            isB2bCustomer: false,
            unzerPaymentCustomerDataUrl: ''
        },

        unzerPaymentPlugin: null,
        unzerPaymentInvoiceGuaranteed: null,
        customerId: null,
        customerProvider: null,

        init: function () {
            var unzerPaymentInstance;

            this.unzerPaymentPlugin = $('*[data-unzer-payment-base="true"]').data('plugin_unzerPaymentBase');
            unzerPaymentInstance = this.unzerPaymentPlugin.getUnzerPaymentInstance();

            if (!unzerPaymentInstance) {
                return;
            }

            this.unzerPaymentInvoiceGuaranteed = unzerPaymentInstance.InvoiceGuaranteed();

            this.applyDataAttributes();
            this.registerEvents();

            this.unzerPaymentPlugin.setSubmitButtonActive(false);

            if (this.opts.isB2bCustomer) {
                this.createB2BForm();
            } else {
                this.createB2CForm();
            }

            $.publish('plugin/heidelpay/invoice_guaranteed/init', this);
        },

        createB2BForm: function () {
            var me = this,
                unzerPaymentInstance = this.unzerPaymentPlugin.getUnzerPaymentInstance();

            this.customerProvider = unzerPaymentInstance.B2BCustomer();

            $.ajax({
                url: this.opts.unzerPaymentCustomerDataUrl,
                method: 'GET',
                success: function (data) {
                    if (data.success) {
                        me.customerProvider.b2bCustomerEventHandler = $.proxy(me.onValidateB2bForm, me);
                        me.customerProvider.initFormFields(data.customer);
                    }
                },
                complete: function () {
                    me.customerProvider.create({
                        containerId: 'unzer-payment--invoice-guaranteed-container'
                    });

                    $.publish('plugin/heidel_invoice_guaranteed/createB2bForm', [this, this.customerProvider]);
                }
            });
        },

        createB2CForm: function () {
            $(this.opts.generatedBirthdayElementSelector).attr('required', 'required');
            $(this.opts.generatedBirthdayElementSelector).attr('form', 'confirm--form');

            this.unzerPaymentPlugin.setSubmitButtonActive(true);
            $.publish('plugin/heidel_invoice_guaranteed/createB2cForm', [this, this.customerProvider]);
        },

        registerEvents: function () {
            $.subscribe('plugin/heidelpay/onSubmitCheckoutForm/after', $.proxy(this.createResource, this));
        },

        createResource: function () {
            var me = this;
            $.publish('plugin/heidelpay/invoice_guaranteed/beforeCreateResource', this);

            if (this.opts.isB2bCustomer) {
                this.customerProvider.updateCustomer().then(function(customer) {
                    me.customerId = customer.id;

                    me.unzerPaymentInvoiceGuaranteed.createResource()
                        .then($.proxy(me.onResourceCreated, me))
                        .catch($.proxy(me.onError, me));
                }).catch(function(error) {
                    me.onError(error);
                });
            } else {
                this.unzerPaymentInvoiceGuaranteed.createResource()
                    .then($.proxy(this.onResourceCreated, this))
                    .catch($.proxy(this.onError, this));
            }
        },

        onResourceCreated: function (resource) {
            var me = this,
                birthDate = this.unzerPaymentPlugin.getFormattedBirthday(this.opts.birthdayElementSelector);

            if (!birthDate && !this.opts.isB2bCustomer) {
                me.onError({ message: me.unzerPaymentPlugin.opts.unzerPaymentBirthdayError });

                return;
            }

            $.publish('plugin/heidelpay/invoice_guaranteed/createPayment', this, resource);

            $.ajax({
                url: this.opts.unzerPaymentCreatePaymentUrl,
                method: 'POST',
                data: {
                    resource: resource,
                    additional: {
                        customerId: this.customerId,
                        birthday: birthDate
                    }
                }
            }).done(function (data) {
                if (undefined !== data.redirectUrl) {
                    window.location = data.redirectUrl;

                    return;
                }

                me.onError({ message: me.unzerPaymentPlugin.opts.unzerPaymentGenericRedirectError });
            });
        },

        onValidateB2bForm: function (validationResult) {
            this.unzerPaymentPlugin.setSubmitButtonActive(validationResult.success);

            $.publish('plugin/heidelpay/invoice_guaranteed/onValidateB2bForm', [this, validationResult]);
        },

        onError: function (error) {
            $.publish('plugin/heidelpay/invoice_guaranteed/createResourceError', this, error);

            this.unzerPaymentPlugin.redirectToErrorPage(this.unzerPaymentPlugin.getMessageFromError(error));
        }
    });

    window.StateManager.addPlugin('*[data-unzer-payment-invoice-guaranteed="true"]', 'unzerPaymentInvoiceGuaranteed');
})(jQuery, window);
