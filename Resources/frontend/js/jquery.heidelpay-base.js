;(function ($, window) {
    'use strict';

    $.plugin('heidelpayBase', {
        defaults: {
            heidelpayPublicKey: '',
            heidelpayErrorUrl: '',
            checkoutFormSelector: '#confirm--form',
            submitButtonSelector: 'button[form="confirm--form"]',
            communicationErrorSelector: '.heidelpay--communication-error',
            errorContentSelector: '.alert--content',
            heidelpayFrameSelector: '.heidelpay--frame'
        },

        /**
         * @type heidelpay
         */
        heidelpayInstance: null,

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

        getHeidelpayInstance: function () {
            if (this.heidelpayInstance === null) {
                try {
                    /* eslint new-cap: ["error", { "newIsCap": false }] */
                    this.heidelpayInstance = new heidelpay(this.opts.heidelpayPublicKey);
                } catch (e) {
                    this.setSubmitButtonActive(false);
                    this.showCommunicationError();
                }
            }

            return this.heidelpayInstance;
        },

        redirectToErrorPage: function (message) {
            var encodedMessage = encodeURIComponent(message);

            window.location = `${this.opts.heidelpayErrorUrl}${encodedMessage}`;
        },

        setSubmitButtonActive: function (active) {
            var $submitButton = $(this.opts.submitButtonSelector);

            $submitButton.attr('disabled', !active);

            $.publish('plugin/heidelpay/setSubmitButtonActive', [this, active]);
        },

        onSubmitCheckoutForm: function (event) {
            var $submitButton = $(this.opts.submitButtonSelector),
                preLoaderPlugin = $submitButton.data('plugin_swPreloaderButton');

            var isFormValid = $(this.opts.checkoutFormSelector).get(0).checkValidity();
            if (!isFormValid) {
                return;
            }

            event.preventDefault();
            preLoaderPlugin.onShowPreloader();

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
                $heidelpayFrame = $(this.opts.heidelpayFrameSelector),
                message = null;

            $errorContainer.removeClass('is--hidden');
            $heidelpayFrame.addClass('is--hidden');

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
        }
    });

    window.StateManager.addPlugin('*[data-heidelpay-base="true"]', 'heidelpayBase');
})(jQuery, window);
