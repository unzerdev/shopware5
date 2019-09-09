;(function ($, window, heidelpay) {
    'use strict';

    $.plugin('heidelpayBase', {
        defaults: {
            heidelpayPublicKey: '',
            heidelpayLocale: 'en-GB',
            heidelpayErrorUrl: '',
            checkoutFormSelector: '#confirm--form',
            submitButtonSelector: 'button[form="confirm--form"]'
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
        },

        getHeidelpayInstance: function () {
            if (this.heidelpayInstance === null) {
                /* eslint new-cap: ["error", { "newIsCap": false }] */
                this.heidelpayInstance = new heidelpay(this.opts.heidelpayPublicKey, {
                    locale: this.opts.heidelpayLocale
                });
            }

            return this.heidelpayInstance;
        },

        redirectToErrorPage: function (message) {
            message = btoa(message);

            window.location = `${this.opts.heidelpayErrorUrl}${message}`;
        },

        setSubmitButtonActive: function (active) {
            var $submitButton = $(this.opts.submitButtonSelector);

            $submitButton.attr('disabled', !active);
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
        }
    });

    window.StateManager.addPlugin('*[data-heidelpay-base="true"]', 'heidelpayBase');

})(jQuery, window, heidelpay);
