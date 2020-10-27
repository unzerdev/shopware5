;(function ($, window) {
    'use strict';

    $.plugin('unzerPaymentSepaDirectDebit', {
        defaults: {
            unzerPaymentCreatePaymentUrl: '',
            mandateCheckboxSelector: '#acceptMandate',
            radioButtonNewSelector: '#new',
            radioButtonSelector: 'input:radio[name="mandateSelection"]',
            selectedRadioButtonSelector: 'input:radio[name="mandateSelection"]:checked'
        },

        unzerPaymentPlugin: null,
        unzerPaymentSepaDirectDebit: null,
        newRadioButton: null,
        ibanValid: false,

        init: function () {
            var unzerPaymentInstance;

            this.unzerPaymentPlugin = $('*[data-unzer-payment-base="true"]').data('plugin_unzerPaymentBase');
            unzerPaymentInstance = this.unzerPaymentPlugin.getUnzerPaymentInstance();

            if (!unzerPaymentInstance) {
                return;
            }

            this.unzerPaymentSepaDirectDebit = unzerPaymentInstance.SepaDirectDebit();

            this.applyDataAttributes();
            this.registerEvents();
            this.createForm();

            this.newRadioButton = $(this.opts.radioButtonNewSelector);

            if (this.newRadioButton.length === 0 || this.newRadioButton.prop('checked')) {
                this.unzerPaymentPlugin.setSubmitButtonActive(false);
            } else {
                $(this.opts.mandateCheckboxSelector).removeAttr('required');
            }

            $.publish('plugin/heidelpay/sepa_direct_debit/init', this);
        },

        createForm: function () {
            this.unzerPaymentSepaDirectDebit.create('sepa-direct-debit', {
                containerId: 'unzer-payment--sepa-direct-debit-container'
            });

            this.unzerPaymentSepaDirectDebit.addEventListener('change', $.proxy(this.onFormChange, this));

            $.publish('plugin/heidelpay/sepa_direct_debit/createForm', this, this.unzerPaymentSepaDirectDebit);
        },

        registerEvents: function () {
            $.subscribe('plugin/heidelpay/onSubmitCheckoutForm/after', $.proxy(this.createResource, this));
            $(this.opts.radioButtonSelector).on('change', $.proxy(this.onChangeMandateSelection, this));
        },

        createResource: function () {
            $.publish('plugin/heidelpay/sepa_direct_debit/beforeCreateResource', this);

            if (this.newRadioButton.length === 0 || this.newRadioButton.prop('checked')) {
                this.unzerPaymentSepaDirectDebit.createResource()
                    .then($.proxy(this.onResourceCreated, this))
                    .catch($.proxy(this.onError, this));
            } else {
                this.createPaymentFromVault($(this.opts.selectedRadioButtonSelector).attr('id'));
            }
        },

        createPaymentFromVault: function (typeId) {
            var me = this;

            $.ajax({
                url: this.opts.unzerPaymentCreatePaymentUrl,
                method: 'POST',
                data: {
                    typeId: typeId,
                    isPaymentFromVault: true
                }
            }).done(function (data) {
                if (undefined !== data.redirectUrl) {
                    window.location = data.redirectUrl;

                    return;
                }

                me.onError({ message: me.unzerPaymentPlugin.opts.unzerPaymentGenericRedirectError });
            });
        },

        onFormChange: function (event) {
            if (!this.newRadioButton) {
                return;
            }

            this.newRadioButton.prop('checked', true);
            this.unzerPaymentPlugin.setSubmitButtonActive(event.success);
            this.ibanValid = event.success;
            $(this.opts.mandateCheckboxSelector).prop('required', 'required');
        },

        onChangeMandateSelection: function (event) {
            if (event.target.id === 'new') {
                this.unzerPaymentPlugin.setSubmitButtonActive(this.ibanValid);
                $(this.opts.mandateCheckboxSelector).prop('required', 'required');
            } else {
                this.unzerPaymentPlugin.setSubmitButtonActive(true);
                $(this.opts.mandateCheckboxSelector).removeAttr('required');
            }
        },

        onResourceCreated: function (resource) {
            var me = this,
                mandateAccepted = $(this.opts.mandateCheckboxSelector).is(':checked');

            $.publish('plugin/heidelpay/sepa_direct_debit/createPayment', this, resource);

            $.ajax({
                url: this.opts.unzerPaymentCreatePaymentUrl,
                method: 'POST',
                data: {
                    resource: resource,
                    mandateAccepted: mandateAccepted
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
            $.publish('plugin/heidelpay/sepa_direct_debit/createResourceError', this, error);

            this.unzerPaymentPlugin.redirectToErrorPage(this.unzerPaymentPlugin.getMessageFromError(error));
        }
    });

    window.StateManager.addPlugin('*[data-unzer-payment-sepa-direct-debit="true"]', 'unzerPaymentSepaDirectDebit');
})(jQuery, window);
