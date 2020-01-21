;(function ($, window) {
    'use strict';

    $.plugin('heidelpaySepaDirectDebit', {
        defaults: {
            heidelpayCreatePaymentUrl: '',
            mandateCheckboxSelector: '#acceptMandate',
            radioButtonNewSelector: '#new',
            radioButtonSelector: 'input:radio[name="mandateSelection"]',
            selectedRadioButtonSelector: 'input:radio[name="mandateSelection"]:checked'
        },

        heidelpayPlugin: null,
        heidelpaySepaDirectDebit: null,
        newRadioButton: null,
        ibanValid: false,

        init: function () {
            var heidelpayInstance;

            this.heidelpayPlugin = $('*[data-heidelpay-base="true"]').data('plugin_heidelpayBase');
            heidelpayInstance = this.heidelpayPlugin.getHeidelpayInstance();

            if (!heidelpayInstance) {
                return;
            }

            this.heidelpaySepaDirectDebit = heidelpayInstance.SepaDirectDebit();

            this.applyDataAttributes();
            this.registerEvents();
            this.createForm();

            this.newRadioButton = $(this.opts.radioButtonNewSelector);

            if (this.newRadioButton.length === 0 || this.newRadioButton.prop('checked')) {
                this.heidelpayPlugin.setSubmitButtonActive(false);
            } else {
                $(this.opts.mandateCheckboxSelector).removeAttr('required');
            }

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
            $(this.opts.radioButtonSelector).on('change', $.proxy(this.onChangeMandateSelection, this));
        },

        createResource: function () {
            $.publish('plugin/heidel_sepa_direct_debit/beforeCreateResource', this);

            if (this.newRadioButton.length === 0 || this.newRadioButton.prop('checked')) {
                this.heidelpaySepaDirectDebit.createResource()
                    .then($.proxy(this.onResourceCreated, this))
                    .catch($.proxy(this.onError, this));
            } else {
                this.createPaymentFromVault($(this.opts.selectedRadioButtonSelector).attr('id'));
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

        onFormChange: function (event) {
            if (!this.newRadioButton) {
                return;
            }

            this.newRadioButton.prop('checked', true);
            this.heidelpayPlugin.setSubmitButtonActive(event.success);
            this.ibanValid = event.success;
            $(this.opts.mandateCheckboxSelector).prop('required', 'required');
        },

        onChangeMandateSelection: function (event) {
            if (event.target.id === 'new') {
                this.heidelpayPlugin.setSubmitButtonActive(this.ibanValid);
                $(this.opts.mandateCheckboxSelector).prop('required', 'required');
            } else {
                this.heidelpayPlugin.setSubmitButtonActive(true);
                $(this.opts.mandateCheckboxSelector).removeAttr('required');
            }
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
