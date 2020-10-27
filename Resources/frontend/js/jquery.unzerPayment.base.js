;(function ($, window) {
    'use strict';

    $.plugin('unzerPaymentBase', {
        defaults: {
            unzerPaymentPublicKey: '',
            unzerPaymentErrorUrl: '',
            checkoutFormSelector: '#confirm--form',
            submitButtonSelector: 'button[form="confirm--form"]',
            communicationErrorSelector: '.unzer-payment--communication-error',
            errorContentSelector: '.alert--content',
            unzerPaymentFrameSelector: '.unzer-payment--frame',
            unzerPaymentGenericRedirectError: 'Something went horrible wrong',
            unzerPaymentBirthdayError: 'The provided birthday is invalid'
        },

        /**
         * @type heidelpay
         */
        unzerPaymentInstance: null,
        isAsyncPayment: true,

        init: function () {
            this.applyDataAttributes();
            this.registerEvents();

            $.publish('plugin/heidelpay/init', this);
        },

        registerEvents: function () {
            var $submitButton = $(this.opts.submitButtonSelector);

            $submitButton.on('click', $.proxy(this.onSubmitCheckoutForm, this));
            $.publish('plugin/heidelpay/registerEvents', this);
        },

        getUnzerPaymentInstance: function () {
            if (this.unzerPaymentInstance === null) {
                try {
                    /* eslint new-cap: ["error", { "newIsCap": false }] */
                    this.unzerPaymentInstance = new heidelpay(this.opts.unzerPaymentPublicKey);
                } catch (e) {
                    this.setSubmitButtonActive(false);
                    this.showCommunicationError();
                }
            }

            return this.unzerPaymentInstance;
        },

        redirectToErrorPage: function (message) {
            var encodedMessage = encodeURIComponent(message);

            window.location = `${this.opts.unzerPaymentErrorUrl}${encodedMessage}`;
        },

        setSubmitButtonActive: function (active) {
            var $submitButton = $(this.opts.submitButtonSelector);

            $submitButton.attr('disabled', !active);

            $.publish('plugin/heidelpay/setSubmitButtonActive', [this, active]);
        },

        onSubmitCheckoutForm: function (event) {
            $.publish('plugin/heidelpay/onSubmitCheckoutForm/before', this);

            if (this.isAsyncPayment) {
                var $submitButton = $(this.opts.submitButtonSelector),
                    preLoaderPlugin = $submitButton.data('plugin_swPreloaderButton');
                var isFormValid = $(this.opts.checkoutFormSelector).get(0).checkValidity();
                if (!isFormValid) {
                    return;
                }
                event.preventDefault();
                preLoaderPlugin.onShowPreloader();
            }

            $.publish('plugin/heidelpay/onSubmitCheckoutForm/after', this);
            /** @deprecated will be removed in v1.3.0 */
            $.publish('plugin/heidelpay/createResource', this);
        },

        formatCurrency: function (amount, locale, currency) {
            return amount.toLocaleString(locale, {
                style: 'currency',
                currency: currency,
                currencyDisplay: 'symbol',
                useGrouping: true
            });
        },

        showCommunicationError: function (error) {
            var $errorContainer = $(this.opts.communicationErrorSelector),
                $unzerPaymentFrame = $(this.opts.unzerPaymentFrameSelector),
                message = null;

            $errorContainer.removeClass('is--hidden');
            $unzerPaymentFrame.addClass('is--hidden');

            if (error !== undefined) {
                message = this.getMessageFromError(error);

                if (message !== undefined) {
                    $(this.opts.communicationErrorSelector + this.opts.errorContentSelector).val(message);
                }
            }
        },

        getMessageFromError: function (error) {
            var message = error.customerMessage;

            if (message === undefined) {
                message = error.message;
            }

            return message;
        },

        getFormattedBirthday(htmlTarget) {
            var datePickerPlugin = $(htmlTarget).data('plugin_swDatePicker'),
                flatpickr = null,
                currentValue = null,
                formattedDate = null,
                dateValue = null;

            if (!datePickerPlugin) {
                return null;
            }

            flatpickr = datePickerPlugin.flatpickr;

            if (!flatpickr) {
                return null;
            }

            currentValue = datePickerPlugin.currentValue;

            if (!currentValue || currentValue.length < 1) {
                currentValue = $(datePickerPlugin.flatpickr._input).val();
            }

            dateValue = new Date(currentValue);

            if (dateValue.toString() === 'Invalid Date') {
                if (currentValue.includes('.')) {
                    var splitted = currentValue.split('.');

                    if (splitted.length === 3) {
                        dateValue = new Date(`${splitted[2]}-${splitted[1]}-${splitted[0]}`);

                        if (dateValue.toString() !== 'Invalid Date') {
                            try {
                                formattedDate = flatpickr.formatDate(dateValue, datePickerPlugin.opts.dateFormat);
                            } catch (e) {
                                return null;
                            }
                        }
                    }
                }
            } else {
                try {
                    formattedDate = flatpickr.formatDate(dateValue, datePickerPlugin.opts.dateFormat);
                } catch (e) {
                    return null;
                }
            }

            if (new Date(formattedDate).toString() === 'Invalid Date') {
                return null;
            }

            return formattedDate;
        }
    });

    window.StateManager.addPlugin('*[data-unzer-payment-base="true"]', 'unzerPaymentBase');
})(jQuery, window);
