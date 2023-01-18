;(function ($, window) {
    'use strict';

    $.plugin('unzerPaymentPaylaterInvoice', {
        defaults: {
            unzerPaymentCreatePaymentUrl: '',
            birthdayElementSelector: '#unzerPaymentBirthday',
            generatedBirthdayElementSelector: '.flatpickr-input',
            isB2bCustomer: false,
            unzerPaymentCustomerDataUrl: ''
        },

        unzerPaymentPlugin: null,
        unzerPaymentPaylaterInvoice: null,
        customerId: null,
        customerProvider: null,

        init: function () {
            var unzerPaymentInstance;

            this.unzerPaymentPlugin = $('*[data-unzer-payment-base="true"]').data('plugin_unzerPaymentBase');
            unzerPaymentInstance = this.unzerPaymentPlugin.getUnzerPaymentInstance();

            if (!unzerPaymentInstance) {
                return;
            }

            this.unzerPaymentPaylaterInvoice = unzerPaymentInstance.PaylaterInvoice();

            this.applyDataAttributes();
            this.registerEvents();

            this.unzerPaymentPlugin.setSubmitButtonActive(false);

            if (this.opts.isB2bCustomer) {
                this.createB2BForm();
            } else {
                this.createB2CForm();
            }

            $.publish('plugin/unzer/paylater_invoice/init', this);
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
                        containerId: 'unzer-payment--paylater-invoice-container',
                        paymentTypeName: 'paylater-invoice'
                    });

                    me.unzerPaymentPaylaterInvoice.create({
                        containerId: 'unzer-payment--paylater-invoice-opt-in-container',
                        customerType: 'B2B',
                        errorHolderId: 'error-holder',
                    })

                    $.publish('plugin/unzer/paylater_invoice/createB2bForm', [this, this.customerProvider]);
                }
            });
        },

        createB2CForm: function () {
            $(this.opts.generatedBirthdayElementSelector).attr('required', 'required');
            $(this.opts.generatedBirthdayElementSelector).attr('form', 'confirm--form');

            var unzerPaymentInstance = this.unzerPaymentPlugin.getUnzerPaymentInstance();

            this.customerProvider = unzerPaymentInstance.Customer();

            this.unzerPaymentPlugin.setSubmitButtonActive(true);
            this.unzerPaymentPaylaterInvoice.create({
                containerId: 'unzer-payment--paylater-invoice-opt-in-container',
                customerType: 'B2C',
                errorHolderId: 'error-holder',
            })

            $.publish('plugin/unzer/paylater_invoice/createB2cForm', [this, this.customerProvider]);
        },

        registerEvents: function () {
            $.subscribe('plugin/unzer/onSubmitCheckoutForm/after', $.proxy(this.createResource, this));
        },

        createResource: function () {
            var me = this;
            $.publish('plugin/unzer/paylater_invoice/beforeCreateResource', this);

            if (this.opts.isB2bCustomer) {
                this.customerProvider.updateCustomer().then(function(customer) {
                    me.customerId = customer.id;

                    me.unzerPaymentPaylaterInvoice.createResource()
                        .then($.proxy(me.onResourceCreated, me))
                        .catch($.proxy(me.onError, me));
                }).catch(function(error) {
                    me.onError(error);
                });
            } else {
                this.unzerPaymentPaylaterInvoice.createResource()
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

            $.publish('plugin/unzer/paylater_invoice/createPayment', this, resource);

            $.ajax({
                url: this.opts.unzerPaymentCreatePaymentUrl,
                method: 'POST',
                data: {
                    resource: resource,
                    sComment: this.unzerPaymentPlugin.getCustomerComment(),
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

            $.publish('plugin/unzer/paylater_invoice/onValidateB2bForm', [this, validationResult]);
        },

        onError: function (error) {
            $.publish('plugin/unzer/paylater_invoice/createResourceError', this, error);

            this.unzerPaymentPlugin.redirectToErrorPage(this.unzerPaymentPlugin.getMessageFromError(error));
        }
    });

    window.StateManager.addPlugin('*[data-unzer-payment-paylater-invoice="true"]', 'unzerPaymentPaylaterInvoice');
})(jQuery, window);
