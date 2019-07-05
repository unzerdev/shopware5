;(function ($, window) {
    'use strict';

    $.plugin('heidelpaySepaDirectDebitGuaranteed', {
        defaults: {
            heidelpayCreatePaymentUrl: '',
            mandateCheckboxSelector: '#acceptMandate'
        },

        heidelpayPlugin: null,
        heidelpaySepaDirectDebitGuaranteed: null,

        init: function () {
            this.heidelpayPlugin = $('*[data-heidelpay-base="true"]').data('plugin_heidelpayBase');
            this.heidelpaySepaDirectDebitGuaranteed = this.heidelpayPlugin.getHeidelpayInstance().SepaDirectDebitGuaranteed();
            this.heidelpayPlugin.setSubmitButtonActive(false);

            this.applyDataAttributes();
            this.registerEvents();
            this.createForm();

            $.publish('plugin/heidel_sepa_direct_debit_guaranteed/init', this);
        },

        createForm: function () {
            this.heidelpaySepaDirectDebitGuaranteed.create('sepa-direct-debit-guaranteed', {
                containerId: 'heidelpay--sepa-direct-debit-guaranteed-container'
            });

            this.heidelpaySepaDirectDebitGuaranteed.addEventListener('change', $.proxy(this.onFormChange, this));

            $.publish('plugin/heidel_sepa_direct_debit_guaranteed/createForm', this, this.heidelpaySepaDirectDebitGuaranteed);
        },

        registerEvents: function () {
            $.subscribe('plugin/heidelpay/createResource', $.proxy(this.createResource, this));
        },

        createResource: function () {
            $.publish('plugin/heidel_sepa_direct_debit_guaranteed/beforeCreateResource', this);

            this.heidelpaySepaDirectDebitGuaranteed.createResource()
                .then($.proxy(this.onResourceCreated, this))
                .catch($.proxy(this.onError, this));
        },

        onFormChange: function (event) {
            this.heidelpayPlugin.setSubmitButtonActive(event.success);
        },

        onResourceCreated: function (resource) {
            var mandateAccepted = $(this.opts.mandateCheckboxSelector).is(':checked');

            $.publish('plugin/heidel_sepa_direct_debit_guaranteed/createPayment', this, resource);

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

            $.publish('plugin/heidel_sepa_direct_debit_guaranteed/createResourceError', this, error);

            this.heidelpayPlugin.redirectToErrorPage(message);
        }
    });

    window.StateManager.addPlugin('*[data-heidelpay-sepa-direct-debit-guaranteed="true"]', 'heidelpaySepaDirectDebitGuaranteed');
})(jQuery, window);
