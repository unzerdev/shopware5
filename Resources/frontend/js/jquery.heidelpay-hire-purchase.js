;(function ($, window) {
    'use strict';

    $.plugin('heidelpayHirePurchase', {
        defaults: {
            heidelpayCreatePaymentUrl: '',
            basketAmount: 0.00,
            currencyIso: '',
            effectiveInterest: 0.00,
            birthdayElementSelector: '#heidelpayBirthday',
            generatedBirthdayElementSelector: '.flatpickr-input',
        },

        heidelpayPlugin: null,
        hirePurchase: null,

        init: function () {
            var heidelpayInstance;

            this.heidelpayPlugin = $('*[data-heidelpay-base="true"]').data('plugin_heidelpayBase');
            heidelpayInstance = this.heidelpayPlugin.getHeidelpayInstance();
            this.hirePurchase = heidelpayInstance.HirePurchase();

            if (!heidelpayInstance) {
                return;
            }

            this.applyDataAttributes();
            this.registerEvents();

            this.createHeidelPayForm();

            $.publish('plugin/heidelpay_hire_purchase/init', this);
        },

        registerEvents: function () {
            $.subscribe('plugin/heidelpay/createResource', $.proxy(this.createResource, this));
        },

        createHeidelPayForm: function() {
            this.hirePurchase.create({
                containerId: 'heidelpay--hire-purchase-container',
                amount: this.opts.basketAmount,
                currency: this.opts.currencyIso,
                effectiveInterest: this.opts.effectiveInterest
            }).then(() => {
                $(this.opts.generatedBirthdayElementSelector).attr('required', 'required');
                $(this.opts.generatedBirthdayElementSelector).attr('form', 'confirm--form');
            }).catch(function(error) {
                window.console.error(error);
            });
        },

        createResource: function () {
            $.publish('plugin/heidelpay_hire_purchase/beforeCreateResource', this);

            this.hirePurchase.createResource()
                .then($.proxy(this.onResourceCreated, this))
                .catch($.proxy(this.onError, this));
        },

        onResourceCreated: function (resource) {
            $.publish('plugin/heidelpay_hire_purchase/createPayment', this, resource);

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

            $.publish('plugin/heidelpay_hire_purchase/createResourceError', this, error);

            this.heidelpayPlugin.redirectToErrorPage(message);
        }
    });

    window.StateManager.addPlugin('*[data-heidelpay-hire-purchase="true"]', 'heidelpayHirePurchase');
})(jQuery, window);
