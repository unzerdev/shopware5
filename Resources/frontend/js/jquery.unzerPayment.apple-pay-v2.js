;(function ($, window) {
    'use strict';

    $.plugin('unzerPaymentApplePayV2', {
        defaults: {
            countryCode: 'DE',
            currency: 'EUR',
            shopName: 'Unzer GmbH',
            amount: '0.0',
            applePayButtonSelector: '.apple-pay-button',
            checkoutConfirmButtonSelector: 'button[form="confirm--form"]',
            authorizePaymentUrl: '',
            merchantValidationUrl: '',
            noApplePayMessage: '',
            supportedNetworks: ['masterCard', 'visa']
        },

        unzerPaymentPlugin: null,
        unzerApplePay: null,
        unzerContainer: null,

        numberValid: false,
        cvcValid: false,
        expiryValid: false,

        init: function () {
            this.applyDataAttributes();

            this.unzerContainer = $('*[data-unzer-payment-base="true"]');
            this.unzerPaymentPlugin = this.unzerContainer.data('plugin_unzerPaymentBase');

            if (!this.unzerPaymentPlugin.getUnzerPaymentInstance()) {
                return;
            }

            if (!this.hasCapability()) {
                this.disableApplePay();

                return;
            }

            this.createForm();

            if (!this.unzerApplePay) {
                this.unzerPaymentPlugin.showCommunicationError();

                return;
            }

            this.registerEvents();

            // We only need the container to display messages.
            this.unzerContainer.hide();

            $.publish('plugin/unzer/apple_pay/init', this);
        },

        registerEvents: function () {
            const applePayButton = $(this.opts.applePayButtonSelector);

            applePayButton.on('click', this.startPayment.bind(this));
        },

        createForm: function () {
            this.unzerApplePay = this.unzerPaymentPlugin.getUnzerPaymentInstance().ApplePay();

            $(this.opts.checkoutConfirmButtonSelector).hide();

            $.publish('plugin/unzer/apple_pay/createForm', this, this.unzerApplePay);
        },

        startPayment() {
            if (!this.unzerPaymentPlugin.checkFormValidity()) {
                return;
            }

            const me = this;
            const applePayPaymentRequest = {
                countryCode: this.opts.countryCode,
                currencyCode: this.opts.currency,
                supportedNetworks: this.opts.supportedNetworks,
                merchantCapabilities: ['supports3DS'],
                total: {label: this.opts.shopName, amount: this.opts.amount}
            };

            if (!window.ApplePaySession) {
                return;
            }
            const session = this.unzerApplePay.initApplePaySession(applePayPaymentRequest);


            session.onpaymentauthorized = (event) => {
                const paymentData = event.payment.token.paymentData;

                $.publish('plugin/unzer/apple_pay/beforeCreateResource', this, event);

                me.unzerApplePay.createResource(paymentData)
                    .then((createdResource) => {
                        if (createdResource.isError) {
                            if (createdResource.errors[0].customerMessage) {
                                this.unzerPaymentPlugin.showCommunicationError(createdResource.errors[0])
                            }

                            session.completePayment({status: window.ApplePaySession.STATUS_FAILURE});

                            return;
                        }

                        me.submitting = true;
                        $.loadingIndicator.open();
                        try {
                            me.unzerPaymentPlugin.setSubmitButtonActive(false);
                            me.unzerPaymentPlugin.submitResource(createdResource);
                        } catch (e) {
                            $.loadingIndicator.close()
                            session.abort();
                            $.publish('plugin/unzer/apple_pay/submitPaymentError', this, e);
                        }
                    })
                    .catch(() => {
                        $.loadingIndicator.close()
                        session.abort();
                        $.publish('plugin/unzer/apple_pay/createResourceError', this);
                    })
                    .finally(() => {
                        me.unzerPaymentPlugin.setSubmitButtonActive(true);
                        me.submitting = false;
                    });
            }

            session.begin();
        },

        hasCapability() {
            return window.ApplePaySession && window.ApplePaySession.canMakePayments() && window.ApplePaySession.supportsVersion(6)
        },

        disableApplePay() {
            $('[data-unzer-payment-apple-pay-v2]').remove();

            this.unzerPaymentPlugin.showCommunicationError({message: this.opts.noApplePayMessage});
            this.unzerPaymentPlugin.setSubmitButtonActive(false);
        },

    });

    window.StateManager.addPlugin('*[data-unzer-payment-apple-pay-v2="true"]', 'unzerPaymentApplePayV2');
})(jQuery, window);
