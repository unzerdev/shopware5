;(function ($, window) {
    'use strict';

    $.plugin('unzerPaymentSepaDirectDebitSecured', {
        defaults: {
            unzerPaymentCreatePaymentUrl: '',
            mandateCheckboxSelector: '#acceptMandate',
            radioButtonNewSelector: '#new',
            radioButtonSelector: 'input:radio[name="mandateSelection"]',
            selectedRadioButtonSelector: 'input:radio[name="mandateSelection"]:checked',
            birthdayElementSelector: '#unzerPaymentBirthday',
            generatedBirthdayElementSelecotr: '.flatpickr-input'
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

            this.unzerPaymentSepaDirectDebit = unzerPaymentInstance.SepaDirectDebitSecured();

            this.applyDataAttributes();
            this.registerEvents();
            this.createForm();

            this.newRadioButton = $(this.opts.radioButtonNewSelector);

            if (this.newRadioButton.length === 0 || this.newRadioButton.prop('checked')) {
                this.unzerPaymentPlugin.setSubmitButtonActive(false);

                $(this.opts.generatedBirthdayElementSelecotr).attr('required', 'required');
                $(this.opts.generatedBirthdayElementSelecotr).attr('form', 'confirm--form');
            } else {
                $(this.opts.mandateCheckboxSelector).removeAttr('required');
                $(this.opts.generatedBirthdayElementSelecotr).removeAttr('required');
            }

            $.publish('plugin/unzer/sepa_direct_debit_secured/init', this);
        },

        createForm: function () {
            this.unzerPaymentSepaDirectDebit.create('sepa-direct-debit-guaranteed', {
                containerId: 'unzer-payment--sepa-direct-debit-container'
            });

            this.unzerPaymentSepaDirectDebit.addEventListener('change', $.proxy(this.onFormChange, this));

            $.publish('plugin/unzer/sepa_direct_debit_secured/createForm', this, this.unzerPaymentSepaDirectDebit);
        },

        registerEvents: function () {
            $.subscribe('plugin/unzer/onSubmitCheckoutForm/after', $.proxy(this.createResource, this));
            $(this.opts.radioButtonSelector).on('change', $.proxy(this.onChangeMandateSelection, this));
        },

        createResource: function () {
            $.publish('plugin/unzer/sepa_direct_debit_secured/beforeCreateResource', this);

            if (this.newRadioButton.length === 0 || this.newRadioButton.prop('checked')) {
                this.unzerPaymentSepaDirectDebit.createResource()
                    .then($.proxy(this.onResourceCreated, this))
                    .catch($.proxy(this.onError, this));
            } else {
                this.createPaymentFromVault($(this.opts.selectedRadioButtonSelector).attr('id'));
            }
        },

        createPaymentFromVault: function (typeId) {
            var me = this,
                birthDateTarget = '#' + typeId + '_birthDate',
                birthDate = $(birthDateTarget).val();

            if (!birthDate) {
                me.onError({ message: me.unzerPaymentPlugin.opts.unzerPaymentBirthdayError });

                return;
            }

            $.ajax({
                url: this.opts.unzerPaymentCreatePaymentUrl,
                method: 'POST',
                data: {
                    typeId: typeId,
                    additional: {
                        isPaymentFromVault: true,
                        birthday: birthDate
                    }
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

            $(this.opts.generatedBirthdayElementSelecotr).attr('required', 'required');
            $(this.opts.generatedBirthdayElementSelecotr).attr('form', 'confirm--form');
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
                birthDate = this.unzerPaymentPlugin.getFormattedBirthday(this.opts.birthdayElementSelector);

            if (!birthDate) {
                me.onError({ message: me.unzerPaymentPlugin.opts.unzerPaymentBirthdayError });

                return;
            }

            $.publish('plugin/unzer/sepa_direct_debit_secured/createPayment', this, resource);

            $.ajax({
                url: this.opts.unzerPaymentCreatePaymentUrl,
                method: 'POST',
                data: {
                    sComment: this.unzerPaymentPlugin.getCustomerComment(),
                    resource: resource,
                    additional: {
                        mandateAccepted: $(this.opts.mandateCheckboxSelector).is(':checked'),
                        birthday: birthDate
                    }
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
            $.publish('plugin/unzer/sepa_direct_debit_secured/createResourceError', this, error);

            this.unzerPaymentPlugin.redirectToErrorPage(this.unzerPaymentPlugin.getMessageFromError(error));
        }
    });

    window.StateManager.addPlugin('*[data-unzer-payment-sepa-direct-debit-guaranteed="true"]', 'unzerPaymentSepaDirectDebitSecured');
})(jQuery, window);
