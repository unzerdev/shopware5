;(function ($, window, heidelpay) {
    'use strict';

    $.plugin('heidelpayIdeal', {
        defaults: {
            heidelpayCreatePaymentUrl: ''
        },

        heidelpayPlugin: null,
        heidelpayIdeal: null,

        selectedBank: null,

        init: function () {
            this.heidelpayPlugin = $('*[data-heidelpay-base="true"]').data('plugin_heidelpayBase');
            this.heidelpayIdeal = this.heidelpayPlugin.getHeidelpayInstance().Ideal();
            this.heidelpayPlugin.setSubmitButtonActive(false);

            this.applyDataAttributes();
            this.registerEvents();
            this.createForm();

            $.publish('plugin/heidel_ideal/init', this);
        },

        createForm: function () {
            this.heidelpayIdeal.create('ideal', {
                containerId: 'heidelpay--ideal-container'
            });

            this.heidelpayIdeal.addEventListener('change', $.proxy(this.onFormChange, this));

            $.publish('plugin/heidel_ideal/createForm', this, this.heidelpayIdeal);
        },

        registerEvents: function () {
            $.subscribe('plugin/heidelpay/createResource', $.proxy(this.createResource, this));
        },

        createResource: function () {
            $.publish('plugin/heidelpay_ideal/beforeCreateResource', this);

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
            $.publish('plugin/heidelpay_ideal/createPayment', this, resource);

            $.ajax({
                url: this.opts.heidelpayCreatePaymentUrl,
                method: 'POST',
                data: {
                    resource: resource
                }
            }).done(function (data) {
               console.log(data);
                // window.location = data.redirectUrl;
            });
        },

        onError: function (error) {
            console.error('error', error);

            var message = error.customerMessage;

            if (message === undefined) {
                message = error.message;
            }

            $.publish('plugin/heidelpay_ideal/createResourceError', this, error);

            this.heidelpayPlugin.redirectToErrorPage(message);
        }
    });

    window.StateManager.addPlugin('*[data-heidelpay-ideal="true"]', 'heidelpayIdeal');
})(jQuery, window, heidelpay);
