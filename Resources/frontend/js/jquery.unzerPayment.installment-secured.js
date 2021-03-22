;(function ($, window) {
    'use strict';

    $.plugin('unzerPaymentInstallmentSecured', {
        defaults: {
            installmentSecuredContainerId: 'unzer-payment--installment-secured-container',
            unzerPaymentCreatePaymentUrl: '',
            basketAmount: 0.00,
            currencyIso: '',
            locale: '',
            effectiveInterest: 0.00,
            starSign: '*',
            installmentSecuredTotalElementId: '#unzer-payment-total-interest',
            installmentSecuredInterestElementId: '#unzer-payment-interest',
            installmentSecuredValueElementSelector: '.entry--value',
            birthdayElementSelector: '#unzerPaymentBirthday',
            generatedBirthdayElementSelector: '.flatpickr-input'
        },

        unzerPaymentPlugin: null,
        installmentSecured: null,

        init: function () {
            var unzerPaymentInstance;

            this.unzerPaymentPlugin = $('*[data-unzer-payment-base="true"]').data('plugin_unzerPaymentBase');
            unzerPaymentInstance = this.unzerPaymentPlugin.getUnzerPaymentInstance();

            if (!unzerPaymentInstance) {
                return;
            }

            this.installmentSecured = unzerPaymentInstance.InstallmentSecured();
            this.applyDataAttributes();
            this.registerEvents();
            this.createForm();

            $.publish('plugin/unzer/installment_secured/init', this);
        },

        registerEvents: function () {
            $.subscribe('plugin/unzer/createResource', $.proxy(this.createResource, this));
            this.installmentSecured.addEventListener('installmentSecuredEvent', $.proxy(this.onChangeInstallmentSecuredForm, this));
        },

        createForm: function() {
            var me = this;
            this.unzerPaymentPlugin.setSubmitButtonActive(false);
            this.installmentSecured.create({
                containerId: this.opts.installmentSecuredContainerId,
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
            $.publish('plugin/unzer/installment_secured/beforeCreateResource', this);

            this.installmentSecured.createResource()
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

            $.publish('plugin/unzer/installment_secured/createPayment', this, resource);

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

        onChangeInstallmentSecuredForm: function(event) {
            if (event.action === 'validate') {
                if (event.success) {
                    this.unzerPaymentPlugin.setSubmitButtonActive(true);
                } else {
                    this.unzerPaymentPlugin.setSubmitButtonActive(false);
                }
            }

            if (event.currentStep === 'plan-detail' && undefined !== this.installmentSecured.selectedInstallmentPlan) {
                var totalAmount = this.installmentSecured.selectedInstallmentPlan.totalAmount,
                    totalInterestAmount = this.installmentSecured.selectedInstallmentPlan.totalInterestAmount;

                $(this.opts.installmentSecuredTotalElementId + ' ' + this.opts.installmentSecuredValueElementSelector).text(this.unzerPaymentPlugin.formatCurrency(totalAmount, this.opts.locale, this.opts.currencyIso));
                $(this.opts.installmentSecuredInterestElementId + ' ' + this.opts.installmentSecuredValueElementSelector).text(this.unzerPaymentPlugin.formatCurrency(totalInterestAmount, this.opts.locale, this.opts.currencyIso) + this.opts.starSign);
            }
        },

        onError: function (error) {
            $.publish('plugin/unzer/installment_secured/createResourceError', this, error);

            this.unzerPaymentPlugin.redirectToErrorPage(this.unzerPaymentPlugin.getMessageFromError(error));
        }
    });

    window.StateManager.addPlugin('*[data-unzer-payment-installment-secured="true"]', 'unzerPaymentInstallmentSecured');
})(jQuery, window);
