;(function ($, window) {
    'use strict';

    $.plugin('unzerPaymentApplePay', {
        defaults: {
            countryCode: 'DE',
            currency: 'EUR',
            shopName: 'Unzer GmbH',
            amount: '0.0',
            applePayButtonSelector: 'apple-pay-button',
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
            var me = this,
                unzerPaymentInstance;

            this.applyDataAttributes();

            this.unzerContainer = $('*[data-unzer-payment-base="true"]');
            this.unzerPaymentPlugin = this.unzerContainer.data('plugin_unzerPaymentBase');
            unzerPaymentInstance = this.unzerPaymentPlugin.getUnzerPaymentInstance();

            if (!unzerPaymentInstance) {
                return;
            }

            if (!this.hasCapability()) {
                this.disableApplePay();

                return;
            }

            this.createScript();
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

            $.publish('plugin/unzer/apple_pay/createForm', this, this.unzerPaymentCard);
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
                total: { label: this.opts.shopName, amount: this.opts.amount }
            };

            if (!window.ApplePaySession) {
                return;
            }

            const session = new window.ApplePaySession(6, applePayPaymentRequest);
            session.onvalidatemerchant = (event) => {
                try {
                    $.ajax({
                        url: me.opts.merchantValidationUrl,
                        method: 'POST',
                        data: { merchantValidationUrl: event.validationURL }
                    }).done(function (data) {
                        session.completeMerchantValidation(JSON.parse(data.validationResponse));
                    });
                } catch(e) {
                    session.abort();
                }
            }

            session.onpaymentauthorized = (event) => {
                const paymentData = event.payment.token.paymentData;
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
                        $.loadingIndicator.open()

                        try {
                            $.ajax({
                                url: me.opts.authorizePaymentUrl,
                                method: 'POST',
                                data: createdResource
                            }).done(function (data) {

                                if (data.transactionStatus === 'pending') {
                                    session.completePayment({status: window.ApplePaySession.STATUS_SUCCESS});

                                    me.unzerPaymentPlugin.setSubmitButtonActive(false);
                                    me.unzerPaymentPlugin.submitResource(createdResource);
                                } else {
                                    $.loadingIndicator.close()
                                    session.completePayment({status: window.ApplePaySession.STATUS_FAILURE});
                                    session.abort();
                                }
                            });
                        } catch(e) {
                            $.loadingIndicator.close()
                            session.completePayment({status: window.ApplePaySession.STATUS_FAILURE});
                            session.abort();
                        }
                    })
                    .catch(() => {
                        $.loadingIndicator.close()
                        session.completePayment({status: window.ApplePaySession.STATUS_FAILURE});
                        session.abort();
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
            $('[data-unzer-payment-apple-pay]').remove();

            this.unzerPaymentPlugin.showCommunicationError({ message: this.opts.noApplePayMessage });
            this.unzerPaymentPlugin.setSubmitButtonActive(false);
        },

        createScript() {
            const script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = 'https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js';

            document.head.appendChild(script);
        }

    });

    window.StateManager.addPlugin('*[data-unzer-payment-apple-pay="true"]', 'unzerPaymentApplePay');
})(jQuery, window);
