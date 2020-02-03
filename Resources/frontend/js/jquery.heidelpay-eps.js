;(function ($, window) {
    'use strict';

    $.plugin('heidelpayEps', {
        defaults: {
            heidelpayCreatePaymentUrl: ''
        },

        heidelpayPlugin: null,
        heidelpayEps: null,

        selectedBank: null,

        init: function () {
            var heidelpayInstance;

            this.heidelpayPlugin = $('*[data-heidelpay-base="true"]').data('plugin_heidelpayBase');
            heidelpayInstance = this.heidelpayPlugin.getHeidelpayInstance();

            if (!heidelpayInstance) {
                return;
            }

            this.heidelpayEps = heidelpayInstance.EPS();
            this.heidelpayPlugin.setSubmitButtonActive(false);

            this.applyDataAttributes();
            this.registerEvents();
            this.createForm();

            $.publish('plugin/heidelpay/eps/init', this);
        },

        createForm: function () {
            var me = this;
            this.heidelpayEps.create('eps', {
                containerId: 'heidelpay--eps-container'
            }).then(() => {
                me.heidelpayEps.addEventListener('change', $.proxy(me.onFormChange, this));
            }).catch(function() {
                me.heidelpayPlugin.showCommunicationError();
            });

            $.publish('plugin/heidelpay/eps/createForm', this, this.heidelpayEps);
        },

        registerEvents: function () {
            $.subscribe('plugin/heidelpay/createResource', $.proxy(this.createResource, this));
        },

        createResource: function () {
            $.publish('plugin/heidelpay/eps/beforeCreateResource', this);

            this.heidelpayEps.createResource()
                .then($.proxy(this.onResourceCreated, this))
                .catch($.proxy(this.onError, this));
        },

        onFormChange: function (event) {
            if (event.value) {
                this.heidelpayPlugin.setSubmitButtonActive(true);
            }
        },

        onResourceCreated: function (resource) {
            $.publish('plugin/heidelpay/eps/createPayment', this, resource);

            $.ajax({
                url: this.opts.heidelpayCreatePaymentUrl,
                method: 'POST',
                data: {
                    resource: resource
                }
            }).done(function (data) {
                window.location = data.redirectUrl;
            });
        },

        onError: function (error) {
            var message = error.customerMessage;

            if (message === undefined) {
                message = error.message;
            }

            $.publish('plugin/heidelpay/eps/createResourceError', this, error);

            this.heidelpayPlugin.redirectToErrorPage(message);
        }
    });

    window.StateManager.addPlugin('*[data-heidelpay-eps="true"]', 'heidelpayEps');
})(jQuery, window);
