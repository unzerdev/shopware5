;(function ($, window) {
    'use strict';

    $.plugin('unzerPaymentPaylaterInvoice', {
        defaults: {
            unzerPaymentCreatePaymentUrl: '',
            birthdayElementSelector: '#unzerPaymentBirthday',
            companyTypeElementSelector: '#unzerPaymentCompanyType',
            companyTypeContainerElementSelector: '#unzerPaymentCompanyTypeContainer',
            isB2bCustomer: false,
        },

        unzerPaymentPlugin: null,
        unzerPaymentPaylaterInvoice: null,
        customerId: null,

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


            this.unzerPaymentPaylaterInvoice.create({
                containerId: 'unzer-payment--paylater-invoice-opt-in-container',
                customerType: this.opts.isB2bCustomer ? 'B2B' : 'B2C',
            });

            $.publish('plugin/unzer/paylater_invoice/init', this);
        },

        registerEvents: function () {
            $.subscribe('plugin/unzer/onSubmitCheckoutForm/after', $.proxy(this.createResource, this));
        },

        createResource: function () {
            $.publish('plugin/unzer/paylater_invoice/beforeCreateResource', this);

            this.unzerPaymentPaylaterInvoice.createResource()
                .then($.proxy(this.onResourceCreated, this))
                .catch($.proxy(this.onError, this));
        },

        getCompanyType: function () {
            if (!$(this.opts.companyTypeContainerElementSelector).is(':visible')) {
                return null;
            }

            return $(this.opts.companyTypeElementSelector).val();
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
                        birthday: birthDate,
                        companyType: this.getCompanyType(),
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

        onError: function (error) {
            $.publish('plugin/unzer/paylater_invoice/createResourceError', this, error);

            this.unzerPaymentPlugin.redirectToErrorPage(this.unzerPaymentPlugin.getMessageFromError(error));
        }
    });

    window.StateManager.addPlugin('*[data-unzer-payment-paylater-invoice="true"]', 'unzerPaymentPaylaterInvoice');
})(jQuery, window);
