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
            birthdateContainerIdSelector: '#unzerPaymentBirthdayContainer',
            birthdayElementSelector: '#unzerPaymentBirthday',
            generatedBirthdayElementSelector: '.flatpickr-input'
        },

        unzerPaymentPlugin: null,
        installmentSecured: null,
        birthdateContainer: null,
        birthdateInput: null,
        unzerInputsValid: false,

        init: function () {
            var unzerPaymentInstance;

            this.unzerPaymentPlugin = $('*[data-unzer-payment-base="true"]').data('plugin_unzerPaymentBase');
            unzerPaymentInstance = this.unzerPaymentPlugin.getUnzerPaymentInstance();

            if (!unzerPaymentInstance) {
                return;
            }

            this.installmentSecured = unzerPaymentInstance.InstallmentSecured();
            this.birthdateContainer = $(this.opts.birthdateContainerIdSelector);
            this.birthdateInput = $(this.opts.birthdayElementSelector);
            this.applyDataAttributes();
            this.registerEvents();
            this.createForm();

            $.publish('plugin/unzer/installment_secured/init', this);
        },

        registerEvents: function () {
            $.subscribe('plugin/unzer/createResource', $.proxy(this.createResource, this));
            $.subscribe('plugin/swDatePicker/onPickerChange', $.proxy(this.onBirthdateInputChange, this));
            this.birthdateInput.on('change', $.proxy(this.onBirthdateInputChange, this));
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
                this.unzerInputsValid = event.success;
                if (this.unzerInputsValid && this.validateBirthdate()) {
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
        },

        onBirthdateInputChange: function() {
            const buttonStatus = this.validateBirthdate() && this.unzerInputsValid;
            this.unzerPaymentPlugin.setSubmitButtonActive(buttonStatus);
        },

        validateBirthdate: function() {
            var birthdateInputValue = this.unzerPaymentPlugin.getFormattedBirthday(this.opts.birthdayElementSelector);
            if (birthdateInputValue === null) {
                return false;
            }

            const birthdate = this.getDateFromGermanDateString(birthdateInputValue),
                maxDate = new Date(),
                minAge = new Date()
            ;

            if (birthdate === null) {
                return false;
            }

            // normalize times
            birthdate.setHours(0, 0, 0, 0);
            maxDate.setHours(0, 0, 0, 0);
            minAge.setHours(0, 0, 0, 0);

            // update maxDate and minAge to relevant values
            maxDate.setDate(maxDate.getDate() + 1);
            minAge.setFullYear(minAge.getFullYear() - 18);

            const isValid = birthdate <= minAge && birthdate < maxDate;

            if (isValid) {
                this.birthdateContainer.removeClass('error');
            } else {
                this.birthdateContainer.addClass('error');
            }

            return isValid;
        },

        getDateFromGermanDateString: function(dateString) {
            var splitted = dateString.split('.');

            if (splitted.length !== 3) {
                return null;
            }

            return new Date(splitted[2] + '-' + splitted[1] + '-' + splitted[0]);
        }
    });

    window.StateManager.addPlugin('*[data-unzer-payment-installment-secured="true"]', 'unzerPaymentInstallmentSecured');
})(jQuery, window);
