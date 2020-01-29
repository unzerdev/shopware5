;(function ($, window) {
    'use strict';

    $.plugin('heidelpayBase', {
        defaults: {
            heidelpayPublicKey: '',
            heidelpayErrorUrl: '',
            checkoutFormSelector: '#confirm--form',
            submitButtonSelector: 'button[form="confirm--form"]',
            communicationErrorSelector: '.heidelpay--communication-error',
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
            var utf8Bytes = encodeURIComponent(message).replace(/%([0-9A-F]{2})/g, function(match, p1) {
                    return String.fromCharCode('0x' + p1);
                }), encodedMessage = btoa(utf8Bytes);

            window.location = `${this.opts.heidelpayErrorUrl}${encodedMessage}`;
        },

        setSubmitButtonActive: function (active) {
            var $submitButton = $(this.opts.submitButtonSelector);

            $submitButton.attr('disabled', !active);

            $.publish('plugin/heidelpay/setSubmitButtonActive', [this, active]);
        },

        onSubmitCheckoutForm: function (event) {
            var isFormValid = $(this.opts.checkoutFormSelector).get(0).checkValidity();

            event.preventDefault();

            if (!isFormValid) {
                return;
            }

            $.publish('plugin/heidelpay/createResource', this);
        },

        showCommunicationError: function () {
            var $errorContainer = $(this.opts.communicationErrorSelector),
                $heidelpayFrame = $(this.opts.heidelpayFrameSelector);

            $errorContainer.removeClass('is--hidden');
            $heidelpayFrame.addClass('is--hidden');
        }
    });

    window.StateManager.addPlugin('*[data-heidelpay-base="true"]', 'heidelpayBase');
})(jQuery, window);
