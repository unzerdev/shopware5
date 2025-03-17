/**
 * The plugins are loaded in alphabetical order of the filename.
 * Do not remove the 0 prefix to ensure the base plugin is loaded first.
 */
;(function ($, window) {
    'use strict';

    $.plugin('unzerPaymentBase', {
        defaults: {
            unzerPaymentPublicKey: '',
            unzerPaymentErrorUrl: '',
            checkoutFormSelector: '#confirm--form',
            submitButtonSelector: 'button[form="confirm--form"]',
            resourceIdElementId: 'unzerResourceId',
            communicationErrorSelector: '.unzer-payment--communication-error',
            errorContentSelector: '.alert--content',
            unzerPaymentFrameSelector: '.unzer-payment--frame',
            unzerPaymentGenericRedirectError: 'Something went horrible wrong',
            unzerPaymentBirthdayError: 'The provided birthday is invalid',
            customerCommentSelector: '.user-comment--field'
        },

        /**
         * @type unzer
         */
        unzerPaymentInstance: null,
        isAsyncPayment: true,

        init: function () {
            this.applyDataAttributes();
            this.registerEvents();

            $.publish('plugin/unzer/init', this);
        },

        registerEvents: function () {
            var $submitButton = $(this.opts.submitButtonSelector);

            $submitButton.on('click', $.proxy(this.onSubmitCheckoutForm, this));
            $.publish('plugin/unzer/registerEvents', this);
        },

        getUnzerPaymentInstance: function () {
            if (this.unzerPaymentInstance === null) {
                try {
                    /* eslint new-cap: ["error", { "newIsCap": false }] */
                    this.unzerPaymentInstance = new unzer(this.opts.unzerPaymentPublicKey);
                } catch (e) {
                    this.setSubmitButtonActive(false);
                    this.showCommunicationError();
                }
            }

            return this.unzerPaymentInstance;
        },

        redirectToErrorPage: function (message) {
            var encodedMessage = encodeURIComponent(message);

            window.location = this.opts.unzerPaymentErrorUrl + encodedMessage;
        },

        setSubmitButtonActive: function (active) {
            var $submitButton = $(this.opts.submitButtonSelector);

            $submitButton.attr('disabled', !active);

            $.publish('plugin/unzer/setSubmitButtonActive', [this, active]);
        },

        onSubmitCheckoutForm: function (event) {
            $.publish('plugin/unzer/onSubmitCheckoutForm/before', this);

            if (this.isAsyncPayment) {
                var $submitButton = $(this.opts.submitButtonSelector),
                    preLoaderPlugin = $submitButton.data('plugin_swPreloaderButton');

                if (!this.checkFormValidity()) {
                    return;
                }

                event.preventDefault();
                preLoaderPlugin.onShowPreloader();
            }

            $.publish('plugin/unzer/onSubmitCheckoutForm/after', this);
            /** @deprecated will be removed in v1.3.0 */
            $.publish('plugin/unzer/createResource', this);
        },

        checkFormValidity: function () {
            return $(this.opts.checkoutFormSelector).get(0).checkValidity();
        },

        submitResource: function (resource) {
            $(this.opts.checkoutFormSelector).append($('<input>').attr({
                type: 'hidden',
                name: this.opts.resourceIdElementId,
                value: resource.id
            }));

            this.setSubmitButtonActive(true);
            $(this.opts.checkoutFormSelector).submit();
            $(this.opts.submitButtonSelector).show();
            $(this.opts.submitButtonSelector).click();
            this.setSubmitButtonActive(false);
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

            if (this.$el.is(':hidden')) {
                this.$el.show();
            }

            if (error !== undefined) {
                message = this.getMessageFromError(error);
                if (message !== undefined) {
                    $(this.opts.communicationErrorSelector + ' ' + this.opts.errorContentSelector).text(message);
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

        getFormattedBirthday: function (htmlTarget) {
            const datePickerElement = $(htmlTarget);
            const datePickerValue = datePickerElement.val();
            if(!datePickerValue){
                return null;
            }

            const dateValue = new Date(datePickerValue);

            if (dateValue.toString() === 'Invalid Date') {
                return null;
            }

            try {
                return datePickerValue;
            } catch (e) {
                return null;
            }
        },

        getCustomerComment: function() {
            return $(this.opts.customerCommentSelector).val();
        },

        hidePaymentFrame: function() {
            this.$el.addClass('is--hidden');
        }
    });

    window.StateManager.addPlugin('*[data-unzer-payment-base="true"]', 'unzerPaymentBase');
})(jQuery, window);
