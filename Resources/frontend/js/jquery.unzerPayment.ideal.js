;(function ($, window) {
    'use strict';

    $.plugin('unzerPaymentIdeal', {
        defaults: {
            unzerPaymentCreatePaymentUrl: ''
        },

        unzerPaymentPlugin: null,
        unzerPaymentIdeal: null,

        selectedBank: null,

        init: function () {
            var unzerPaymentInstance;

            this.unzerPaymentPlugin = $('*[data-unzer-payment-base="true"]').data('plugin_unzerPaymentBase');
            unzerPaymentInstance = this.unzerPaymentPlugin.getUnzerPaymentInstance();

            if (!unzerPaymentInstance) {
                return;
            }

            this.unzerPaymentIdeal = unzerPaymentInstance.Ideal();
            this.unzerPaymentPlugin.setSubmitButtonActive(false);

            this.applyDataAttributes();
            this.registerEvents();
            this.createForm();

            $.publish('plugin/heidelpay/ideal/init', this);
        },

        createForm: function () {
            this.unzerPaymentIdeal.create('ideal', {
                containerId: 'unzer-payment--ideal-container'
            });

            this.unzerPaymentIdeal.addEventListener('change', $.proxy(this.onFormChange, this));

            $.publish('plugin/heidelpay/ideal/createForm', this, this.unzerPaymentIdeal);
        },

        registerEvents: function () {
            $.subscribe('plugin/heidelpay/onSubmitCheckoutForm/after', $.proxy(this.createResource, this));
        },

        createResource: function () {
            $.publish('plugin/heidelpay/ideal/beforeCreateResource', this);

            this.unzerPaymentIdeal.createResource()
                .then($.proxy(this.onResourceCreated, this))
                .catch($.proxy(this.onError, this));
        },

        onFormChange: function (event) {
            if (event.value) {
                this.unzerPaymentPlugin.setSubmitButtonActive(true);
            }
        },

        onResourceCreated: function (resource) {
            var me = this;
            $.publish('plugin/heidelpay/ideal/createPayment', this, resource);

            $.ajax({
                url: this.opts.unzerPaymentCreatePaymentUrl,
                method: 'POST',
                data: {
                    resource: resource
                }
            }).done(function (data) {
                if (undefined !== data.redirectUrl) {
                    window.location = data.redirectUrl;

                    return;
                }

                me.onError({ message: me.unzerPaymentPlugin.opts.unzerPaymentGenericRedirectError });
            });
        },

        onError: function (error) {
            $.publish('plugin/heidelpay/ideal/createResourceError', this, error);

            this.unzerPaymentPlugin.redirectToErrorPage(this.unzerPaymentPlugin.getMessageFromError(error));
        }
    });

    window.StateManager.addPlugin('*[data-unzer-payment-ideal="true"]', 'unzerPaymentIdeal');
})(jQuery, window);
