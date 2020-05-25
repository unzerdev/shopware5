;(function ($, window) {
    'use strict';

    $.plugin('heidelpayPayPal', {
        defaults: {
            heidelpayCreatePaymentUrl: '',
            elementInvalidClass: 'is--invalid',
            elementFocusedClass: 'is--focused',
            elementHiddenClass: 'is--hidden',
            creditCardContainerSelector: '.heidelpay--paypal-container',
            radioButtonSelector: 'input:radio[name="paypalSelection"]',
            selectedRadioButtonSelector: 'input:radio[name="paypalSelection"]:checked',
            radioButtonNewSelector: '#new'
        },

        heidelpayPlugin: null,
        heidelpayPayPal: null,

        init: function () {
            var heidelpayInstance;
            this.heidelpayPlugin = $('*[data-heidelpay-base="true"]').data('plugin_heidelpayBase');
            heidelpayInstance = this.heidelpayPlugin.getHeidelpayInstance();

            if (!heidelpayInstance) {
                return;
            }

            this.heidelpayPayPal = heidelpayInstance.Paypal();

            this.registerEvents();

            if ($(this.opts.radioButtonSelector).length > 1) {
                $(this.opts.radioButtonNewSelector).prop('checked', true);

                this.heidelpayPlugin.setSubmitButtonActive(false);
            } else {
                this.heidelpayPlugin.setSubmitButtonActive(true);
            }
            $.publish('plugin/heidelpay/paypal/init', this);
        },

        registerEvents: function () {
            $.subscribe('plugin/heidelpay/onSubmitCheckoutForm/before', $.proxy(this.checkRegistered, this));
            $.subscribe('plugin/heidelpay/onSubmitCheckoutForm/after', $.proxy(this.createResource, this));
            $(this.opts.radioButtonSelector).on('change', $.proxy(this.onChangePayPalSelection, this));
        },

        checkRegistered: function () {
            var $newRadioButton = $(this.opts.radioButtonNewSelector);

            if ($newRadioButton.is(':checked')) {
                this.heidelpayPlugin.isAsyncPayment = false;
            }
        },

        createResource: function () {
            var $newRadioButton = $(this.opts.radioButtonNewSelector);

            $.publish('plugin/heidelpay/paypal/beforeCreateResource', this);
            if (!$newRadioButton.is(':checked')) {
                this.createPaymentFromVault($(this.opts.selectedRadioButtonSelector).attr('id'));
            } else {
                this.onError({
                    message: 'Something went wrong. Please choose another payment method'
                });
            }
        },

        createPaymentFromVault: function (typeId) {
            $.ajax({
                url: this.opts.heidelpayCreatePaymentUrl,
                method: 'POST',
                data: {
                    typeId: typeId
                }
            }).done(function (data) {
                window.location = data.redirectUrl;
            });
        },

        onError: function (error) {
            $.publish('plugin/heidelpay/paypal/createResourceError', this, error);

            this.heidelpayPlugin.redirectToErrorPage(this.heidelpayPlugin.getMessageFromError(error));
        },

        onChangePayPalSelection: function (event) {
            this.heidelpayPlugin.setSubmitButtonActive(true);
        }
    });

    window.StateManager.addPlugin('*[data-heidelpay-paypal="true"]', 'heidelpayPayPal');
})(jQuery, window);
