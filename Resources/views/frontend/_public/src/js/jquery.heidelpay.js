;(function ($, window, heidelpay) {
    'use strict';

    $.plugin('heidelpay', {
        defaults: {
            publicKey: '',
            locale: 'en-GB',
            errorUrl: '',
            checkoutFormSelector: '#confirm--form',
            submitButtonSelector: '#heidelpay-submit-button',
        },

        /**
         * @type hidelpay
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
                this.heidelpayInstance = new heidelpay(this.opts.publicKey, {
                    locale: this.opts.locale,
                });
            }

            return this.heidelpayInstance;
        },

        redirectToErrorPage: function (message) {
            $(location).attr('href', `${this.opts.errorUrl}${message}`)
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

    window.StateManager.addPlugin('*[data-heidelpay="true"]', 'heidelpay');

})(jQuery, window, heidelpay);
