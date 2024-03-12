;(function ($, window) {
    'use strict';

    $.plugin('unzerPaymentPaylaterDirectDebitSecured', {
        defaults: {
            unzerPaymentCreatePaymentUrl: '',
            birthdayElementSelector: '#unzerPaymentBirthday',
            birthdayContainerElementSelector: '#unzerPaymentBirthdayContainer',
            unzerPaymentContainerId: 'unzerPaymentPaylaterDirectDebitSecuredContainer',
            unzerPaymentErrorContainerId: 'unzerPaymentPaylaterDirectDebitSecuredErrorContainer',
        },

        birthdateInput: null,
        birthdateContainer: null,
        unzerPaymentPlugin: null,
        unzerPaymentPaylaterDirectDebitSecured: null,
        unzerInputsValid: null,

        init: function () {
            let unzerPaymentInstance;

            this.unzerPaymentPlugin = $('*[data-unzer-payment-base="true"]').data('plugin_unzerPaymentBase');
            unzerPaymentInstance = this.unzerPaymentPlugin.getUnzerPaymentInstance();

            if (!unzerPaymentInstance) {
                return;
            }

            this.applyDataAttributes();

            this.unzerPaymentPaylaterDirectDebitSecured = unzerPaymentInstance.PaylaterDirectDebit();
            this.birthdateInput = $(this.opts.birthdayElementSelector);
            this.birthdateContainer = $(this.opts.birthdayContainerElementSelector);
            this.unzerPaymentPlugin.setSubmitButtonActive(false);
            this.unzerInputsValid = false;

            this.registerEvents();

            if (this.birthdateInput.val()) {
                this.birthdateInput.trigger('change');
            }

            this.unzerPaymentPaylaterDirectDebitSecured.create('paylater-direct-debit', {
                containerId: this.opts.unzerPaymentContainerId
            });

            $.publish('plugin/unzer/paylater_direct_debit_secured/init', this);
            },

        registerEvents: function () {
            $.subscribe('plugin/unzer/onSubmitCheckoutForm/after', $.proxy(this.createResource, this));
            this.unzerPaymentPaylaterDirectDebitSecured.sepaEventHandler = this.handleSepaDataChange.bind(this);
            this.birthdateInput.on('change', $.proxy(this.onBirthdateInputChange, this));
        },

        /**
         * @param {Object} event
         */
        handleSepaDataChange(event) {
            let validBirthdate = this.validateBirthdate();

                this.unzerInputsValid = event.success;

                if (event.success && validBirthdate) {
                    this.unzerPaymentPlugin.setSubmitButtonActive(true);
                    document.getElementById(this.opts.unzerPaymentErrorContainerId).innerText = '';

                } else {
                    this.unzerPaymentPlugin.setSubmitButtonActive(false);
                }
        },

        createResource: function () {
            $.publish('plugin/unzer/paylater_direct_debit_secured/beforeCreateResource', this);

            let me = this;

            this.unzerPaymentPaylaterDirectDebitSecured.createResource()
                .then($.proxy(this.onResourceCreated, this))
                .catch(function (error) {
                    let errorContainer = document.getElementById(me.opts.unzerPaymentErrorContainerId);
                    errorContainer.innerText = error.customerMessage || error.message || 'Error';

                    let preLoaderPlugin = $(me.unzerPaymentPlugin.opts.submitButtonSelector).data('plugin_swPreloaderButton');

                    // we have to use a timeout since the preloader uses a timeout as well with 25ms
                    window.setTimeout(function () {
                        preLoaderPlugin.reset();
                        me.unzerPaymentPlugin.setSubmitButtonActive(false);
                    }, 50);

                });
        },

        onBirthdateInputChange: function () {
            if (this.validateBirthdate()) {
                this.birthdateInput.removeClass('has--error');
                this.birthdateContainer.removeClass('error');
            } else {
                this.birthdateInput.addClass('has--error');
                this.birthdateContainer.addClass('error');
            }

            this.unzerPaymentPlugin.setSubmitButtonActive(this.validateBirthdate() && this.unzerInputsValid);
        },

        validateBirthdate: function () {
            const birthdateInputValue = this.unzerPaymentPlugin.getFormattedBirthday(this.opts.birthdayElementSelector);

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

            return birthdate <= minAge && birthdate < maxDate;
        },

        getDateFromGermanDateString: function (dateString) {
            const split = dateString.split('.');

            if (split.length !== 3) {
                return null;
            }

            return new Date(split[2] + '-' + split[1] + '-' + split[0]);
        },

        onResourceCreated: function (resource) {
            let me = this,
                birthDate = this.unzerPaymentPlugin.getFormattedBirthday(this.opts.birthdayElementSelector);

            if (!birthDate) {
                me.onError({message: me.unzerPaymentPlugin.opts.unzerPaymentBirthdayError});

                return;
            }

            $.publish('plugin/unzer/paylater_direct_debit_secured/createPayment', this, resource);

            $.ajax({
                url: this.opts.unzerPaymentCreatePaymentUrl,
                method: 'POST',
                data: {
                    resource: resource,
                    sComment: this.unzerPaymentPlugin.getCustomerComment(),
                    additional: {
                        birthday: birthDate,
                    }
                }
            }).done(function (data) {
                if (undefined !== data.redirectUrl) {
                    window.location = data.redirectUrl;

                    return;
                }

                me.onError({message: me.unzerPaymentPlugin.opts.unzerPaymentGenericRedirectError});
            });
        },

        onError: function (error) {
            $.publish('plugin/unzer/paylater_direct_debit_secured/createResourceError', this, error);

            this.unzerPaymentPlugin.redirectToErrorPage(this.unzerPaymentPlugin.getMessageFromError(error));
        }
    });

    window.StateManager.addPlugin('*[data-unzer-payment-paylater-direct-debit-secured="true"]', 'unzerPaymentPaylaterDirectDebitSecured');
})(jQuery, window);
