;(function ($, window) {
    'use strict';

    $.plugin('heidelpayIdeal', {
        defaults: {
            heidelpayCreatePaymentUrl: ''
        },

        heidelpayPlugin: null,
        heidelpayIdeal: null,

        selectedBank: null,

        init: function () {
            var heidelpayInstance;

            this.heidelpayPlugin = $('*[data-heidelpay-base="true"]').data('plugin_heidelpayBase');
            heidelpayInstance = this.heidelpayPlugin.getHeidelpayInstance();

            if (!heidelpayInstance) {
                return;
            }

            this.heidelpayIdeal = heidelpayInstance.Ideal();
            this.heidelpayPlugin.setSubmitButtonActive(false);

            this.applyDataAttributes();
            this.registerEvents();
            this.createForm();

            $.publish('plugin/heidelpay/ideal/init', this);
        },

        createForm: function () {
            this.heidelpayIdeal.create('ideal', {
                containerId: 'heidelpay--ideal-container'
            });

            this.heidelpayIdeal.addEventListener('change', $.proxy(this.onFormChange, this));

            $.publish('plugin/heidelpay/ideal/createForm', this, this.heidelpayIdeal);
        },

        registerEvents: function () {
            $.subscribe('plugin/heidelpay/onSubmitCheckoutForm/after', $.proxy(this.createResource, this));
        },

        createResource: function () {
            $.publish('plugin/heidelpay/ideal/beforeCreateResource', this);

            this.heidelpayIdeal.createResource()
                .then($.proxy(this.onResourceCreated, this))
                .catch($.proxy(this.onError, this));
        },

        onFormChange: function (event) {
            if (event.value) {
                this.heidelpayPlugin.setSubmitButtonActive(true);
            }
        },

        onResourceCreated: function (resource) {
            var me = this;
            $.publish('plugin/heidelpay/ideal/createPayment', this, resource);

            $.ajax({
                url: this.opts.heidelpayCreatePaymentUrl,
                method: 'POST',
                data: {
                    resource: resource
                }
            }).done(function (data) {
                if (undefined !== data.redirectUrl) {
                    window.location = data.redirectUrl;

                    return;
                }

                me.onError({ message: me.heidelpayPlugin.opts.heidelpayGenericRedirectError });
            });
        },

        onError: function (error) {
            $.publish('plugin/heidelpay/ideal/createResourceError', this, error);

            this.heidelpayPlugin.redirectToErrorPage(this.heidelpayPlugin.getMessageFromError(error));
        }
    });

    window.StateManager.addPlugin('*[data-heidelpay-ideal="true"]', 'heidelpayIdeal');
})(jQuery, window);
