;(function ($, window) {
    'use strict';

    $.plugin('unzerPaymentInvoiceSecured', {
        defaults: {
            unzerPaymentCreatePaymentUrl: '',
            birthdayElementSelector: '#unzerPaymentBirthday',
            generatedBirthdayElementSelector: '.flatpickr-input',
            isB2bCustomer: false,
            unzerPaymentCustomerDataUrl: ''
        },

        unzerPaymentPlugin: null,
        unzerPaymentInvoiceSecured: null,
        customerId: null,
        customerProvider: null,

        init: function () {
            var unzerPaymentInstance;

            this.unzerPaymentPlugin = $('*[data-unzer-payment-base="true"]').data('plugin_unzerPaymentBase');
            unzerPaymentInstance = this.unzerPaymentPlugin.getUnzerPaymentInstance();

            if (!unzerPaymentInstance) {
                return;
            }

            this.unzerPaymentInvoiceSecured = unzerPaymentInstance.InvoiceSecured();

            this.applyDataAttributes();
            this.registerEvents();

            this.unzerPaymentPlugin.setSubmitButtonActive(false);

            if (this.opts.isB2bCustomer) {
                this.createB2BForm();
            } else {
                this.createB2CForm();
            }

            $.publish('plugin/unzer/invoice_secured/init', this);
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

                    $.publish('plugin/unzer/invoice_guaranteed/createB2bForm', [this, this.customerProvider]);
                }
            });
        },

        createB2CForm: function () {
            $(this.opts.generatedBirthdayElementSelector).attr('required', 'required');
            $(this.opts.generatedBirthdayElementSelector).attr('form', 'confirm--form');

            this.unzerPaymentPlugin.setSubmitButtonActive(true);
            $.publish('plugin/unzer/invoice_guaranteed/createB2cForm', [this, this.customerProvider]);
        },

        registerEvents: function () {
            $.subscribe('plugin/unzer/onSubmitCheckoutForm/after', $.proxy(this.createResource, this));
        },

        createResource: function () {
            var me = this;
            $.publish('plugin/unzer/invoice_secured/beforeCreateResource', this);

            if (this.opts.isB2bCustomer) {
                this.customerProvider.updateCustomer().then(function(customer) {
                    me.customerId = customer.id;

                    me.unzerPaymentInvoiceSecured.createResource()
                        .then($.proxy(me.onResourceCreated, me))
                        .catch($.proxy(me.onError, me));
                }).catch(function(error) {
                    me.onError(error);
                });
            } else {
                this.unzerPaymentInvoiceSecured.createResource()
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

            $.publish('plugin/unzer/invoice_secured/createPayment', this, resource);

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

            $.publish('plugin/unzer/invoice_secured/onValidateB2bForm', [this, validationResult]);
        },

        onError: function (error) {
            $.publish('plugin/unzer/invoice_secured/createResourceError', this, error);

            this.unzerPaymentPlugin.redirectToErrorPage(this.unzerPaymentPlugin.getMessageFromError(error));
        }
    });

    window.StateManager.addPlugin('*[data-unzer-payment-invoice-guaranteed="true"]', 'unzerPaymentInvoiceSecured');
})(jQuery, window);
