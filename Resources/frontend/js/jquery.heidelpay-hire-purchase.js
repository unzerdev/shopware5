;(function ($, window) {
    'use strict';

    $.plugin('heidelpayHirePurchase', {
        defaults: {
            heidelpayCreatePaymentUrl: '',
            basketAmount: 0.00,
            currencyIso: '',
            locale: '',
            effectiveInterest: 0.00,
            starSign: '*',
            installmentTotalElementId: '#heidelpay-total-interest',
            installmentInterestElementId: '#heidelpay-interest',
            installmentValueElementSelector: '.entry--value',
            birthdayElementSelector: '#heidelpayBirthday',
            generatedBirthdayElementSelector: '.flatpickr-input'
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
            this.createForm();

            $.publish('plugin/heidelpay_hire_purchase/init', this);
        },

        registerEvents: function () {
            $.subscribe('plugin/heidelpay/createResource', $.proxy(this.createResource, this));
            this.hirePurchase.addEventListener('hirePurchaseEvent', (event) => this.onChangeHirePurchaseForm(event));
        },

        createForm: function() {
            this.heidelpayPlugin.setSubmitButtonActive(false);

            this.hirePurchase.create({
                containerId: 'heidelpay--hire-purchase-container',
                amount: this.opts.basketAmount,
                currency: this.opts.currencyIso,
                effectiveInterest: this.opts.effectiveInterest
            });

            $(this.opts.generatedBirthdayElementSelector).attr('required', 'required');
            $(this.opts.generatedBirthdayElementSelector).attr('form', 'confirm--form');
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

        onChangeHirePurchaseForm: function(event) {
            if (event.action === 'validate') {
                if (event.success) {
                    this.heidelpayPlugin.setSubmitButtonActive(true);
                } else {
                    this.heidelpayPlugin.setSubmitButtonActive(false);
                }
            }

            if (event.currentStep === 'plan-detail') {
                var totalAmount = this.hirePurchase.selectedInstallmentPlan.totalAmount,
                    totalInterestAmount = this.hirePurchase.selectedInstallmentPlan.totalInterestAmount;

                $(this.opts.installmentTotalElementId + ' ' + this.opts.installmentValueElementSelector).text(this.heidelpayPlugin.formatCurrency(totalAmount, this.opts.locale, this.opts.currencyIso));
                $(this.opts.installmentInterestElementId + ' ' + this.opts.installmentValueElementSelector).text(this.heidelpayPlugin.formatCurrency(totalInterestAmount, this.opts.locale, this.opts.currencyIso) + this.opts.starSign);
            }
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
