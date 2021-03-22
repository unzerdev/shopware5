;(function ($, window) {
    'use strict';

    $.plugin('unzerPaymentHirePurchase', {
        defaults: {
            hirePurchaseContainerId: 'unzer-payment--hire-purchase-container',
            unzerPaymentCreatePaymentUrl: '',
            basketAmount: 0.00,
            currencyIso: '',
            locale: '',
            effectiveInterest: 0.00,
            starSign: '*',
            hirePurchaseTotalElementId: '#unzer-payment-total-interest',
            hirePurchaseInterestElementId: '#unzer-payment-interest',
            hirePurchaseValueElementSelector: '.entry--value',
            birthdayElementSelector: '#unzerPaymentBirthday',
            generatedBirthdayElementSelector: '.flatpickr-input'
        },

        unzerPaymentPlugin: null,
        hirePurchase: null,

        init: function () {
            var unzerPaymentInstance;

            this.unzerPaymentPlugin = $('*[data-unzer-payment-base="true"]').data('plugin_unzerPaymentBase');
            unzerPaymentInstance = this.unzerPaymentPlugin.getUnzerPaymentInstance();

            if (!unzerPaymentInstance) {
                return;
            }

            this.hirePurchase = unzerPaymentInstance.HirePurchase();
            this.applyDataAttributes();
            this.registerEvents();
            this.createForm();

            $.publish('plugin/unzer/hire_purchase/init', this);
        },

        registerEvents: function () {
            $.subscribe('plugin/unzer/createResource', $.proxy(this.createResource, this));
            this.hirePurchase.addEventListener('hirePurchaseEvent', $.proxy(this.onChangeHirePurchaseForm, this));
        },

        createForm: function() {
            var me = this;
            this.unzerPaymentPlugin.setSubmitButtonActive(false);
            this.hirePurchase.create({
                containerId: this.opts.hirePurchaseContainerId,
                amount: this.opts.basketAmount,
                currency: this.opts.currencyIso,
                effectiveInterest: this.opts.effectiveInterest
            }).then(function() {
                $(me.opts.generatedBirthdayElementSelector).attr('required', 'required');
                $(me.opts.generatedBirthdayElementSelector).attr('form', 'confirm--form');
            }).catch(function() {
                me.unzerPaymentPlugin.showCommunicationError();
            });
        },

        createResource: function () {
            $.publish('plugin/unzer/hire_purchase/beforeCreateResource', this);

            this.hirePurchase.createResource()
                .then($.proxy(this.onResourceCreated, this))
                .catch($.proxy(this.onError, this));
        },

        onResourceCreated: function (resource) {
            var me = this,
                birthDate = this.unzerPaymentPlugin.getFormattedBirthday(this.opts.birthdayElementSelector);

            if (!birthDate) {
                me.onError({ message: me.unzerPaymentPlugin.opts.unzerPaymentBirthdayError });

                return;
            }

            $.publish('plugin/unzer/hire_purchase/createPayment', this, resource);

            $.ajax({
                url: this.opts.unzerPaymentCreatePaymentUrl,
                method: 'POST',
                data: {
                    resource: resource,
                    additional: {
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

        onChangeHirePurchaseForm: function(event) {
            if (event.action === 'validate') {
                if (event.success) {
                    this.unzerPaymentPlugin.setSubmitButtonActive(true);
                } else {
                    this.unzerPaymentPlugin.setSubmitButtonActive(false);
                }
            }

            if (event.currentStep === 'plan-detail' && undefined !== this.hirePurchase.selectedInstallmentPlan) {
                var totalAmount = this.hirePurchase.selectedInstallmentPlan.totalAmount,
                    totalInterestAmount = this.hirePurchase.selectedInstallmentPlan.totalInterestAmount;

                $(this.opts.hirePurchaseTotalElementId + ' ' + this.opts.hirePurchaseValueElementSelector).text(this.unzerPaymentPlugin.formatCurrency(totalAmount, this.opts.locale, this.opts.currencyIso));
                $(this.opts.hirePurchaseInterestElementId + ' ' + this.opts.hirePurchaseValueElementSelector).text(this.unzerPaymentPlugin.formatCurrency(totalInterestAmount, this.opts.locale, this.opts.currencyIso) + this.opts.starSign);
            }
        },

        onError: function (error) {
            $.publish('plugin/unzer/hire_purchase/createResourceError', this, error);

            this.unzerPaymentPlugin.redirectToErrorPage(this.unzerPaymentPlugin.getMessageFromError(error));
        }
    });

    window.StateManager.addPlugin('*[data-unzer-payment-hire-purchase="true"]', 'unzerPaymentHirePurchase');
})(jQuery, window);
