;(function ($, window) {
    'use strict';

    $.plugin('unzerPaymentEps', {
        defaults: {
            unzerPaymentCreatePaymentUrl: ''
        },

        unzerPaymentPlugin: null,
        unzerPaymentEps: null,

        selectedBank: null,

        init: function () {
            var unzerPaymentInstance;

            this.unzerPaymentPlugin = $('*[data-unzer-payment-base="true"]').data('plugin_unzerPaymentBase');
            unzerPaymentInstance = this.unzerPaymentPlugin.getUnzerPaymentInstance();

            if (!unzerPaymentInstance) {
                return;
            }

            this.unzerPaymentEps = unzerPaymentInstance.EPS();
            this.unzerPaymentPlugin.setSubmitButtonActive(false);

            this.applyDataAttributes();
            this.registerEvents();
            this.createForm();

            $.publish('plugin/unzer/eps/init', this);
        },

        createForm: function () {
            this.unzerPaymentEps.create('eps', {
                containerId: 'unzer-payment--eps-container'
            });

            this.unzerPaymentEps.addEventListener('change', $.proxy(this.onFormChange, this));

            $.publish('plugin/unzer/eps/createForm', this, this.unzerPaymentEps);
        },

        registerEvents: function () {
            $.subscribe('plugin/unzer/onSubmitCheckoutForm/after', $.proxy(this.createResource, this));
        },

        createResource: function () {
            $.publish('plugin/unzer/eps/beforeCreateResource', this);

            this.unzerPaymentEps.createResource()
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
            $.publish('plugin/unzer/eps/createPayment', this, resource);

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
            $.publish('plugin/unzer/eps/createResourceError', this, error);

            this.unzerPaymentPlugin.redirectToErrorPage(this.unzerPaymentPlugin.getMessageFromError(error));
        }
    });

    window.StateManager.addPlugin('*[data-unzer-payment-eps="true"]', 'unzerPaymentEps');
})(jQuery, window);
