;(function ($, window) {
    'use strict';

    $.plugin('heidelpaySepaDirectDebit', {
        defaults: {
            heidelpayCreatePaymentUrl: '',
            mandateCheckboxSelector: '#acceptMandate'
        },

        heidelpayPlugin: null,
        heidelpaySepaDirectDebit: null,

        init: function () {
            this.heidelpayPlugin = $('*[data-heidelpay-base="true"]').data('plugin_heidelpayBase');
            this.heidelpaySepaDirectDebit = this.heidelpayPlugin.getHeidelpayInstance().SepaDirectDebit();
            this.heidelpayPlugin.setSubmitButtonActive(false);

            this.applyDataAttributes();
            this.registerEvents();
            this.createForm();

            $.publish('plugin/heidel_sepa_direct_debit/init', this);
        },

        createForm: function () {
            this.heidelpaySepaDirectDebit.create('sepa-direct-debit', {
                containerId: 'heidelpay--sepa-direct-debit-container'
            });

            this.heidelpaySepaDirectDebit.addEventListener('change', $.proxy(this.onFormChange, this));

            $.publish('plugin/heidel_sepa_direct_debit/createForm', this, this.heidelpaySepaDirectDebit);
        },

        registerEvents: function () {
            $.subscribe('plugin/heidelpay/createResource', $.proxy(this.createResource, this));
        },

        createResource: function () {
            $.publish('plugin/heidel_sepa_direct_debit/beforeCreateResource', this);

            this.heidelpaySepaDirectDebit.createResource()
                .then($.proxy(this.onResourceCreated, this))
                .catch($.proxy(this.onError, this));
        },

        onFormChange: function (event) {
            this.heidelpayPlugin.setSubmitButtonActive(event.success);
        },

        onResourceCreated: function (resource) {
            var mandateAccepted = $(this.opts.mandateCheckboxSelector).is(':checked');

            $.publish('plugin/heidel_sepa_direct_debit/createPayment', this, resource);

            $.ajax({
                url: this.opts.heidelpayCreatePaymentUrl,
                method: 'POST',
                data: {
                    resource: resource,
                    mandateAccepted: mandateAccepted
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

            $.publish('plugin/heidel_sepa_direct_debit/createResourceError', this, error);

            this.heidelpayPlugin.redirectToErrorPage(message);
        }
    });

    window.StateManager.addPlugin('*[data-heidelpay-sepa-direct-debit="true"]', 'heidelpaySepaDirectDebit');
})(jQuery, window);
