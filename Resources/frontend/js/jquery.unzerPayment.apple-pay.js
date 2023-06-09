;(function ($, window) {
    'use strict';

    $.plugin('unzerPaymentApplePay', {
        defaults: {
            elementInvalidClass: 'is--invalid',
            elementFocusedClass: 'is--focused',
            elementHiddenClass: 'is--hidden',
            countryCode: 'DE',
            currency: 'EUR',
            shopName: 'Unzer GmbH',
            amount: '0.0',
            applePayButtonSelector: 'apple-pay-button',
            checkoutConfirmButtonSelector: 'button[form="confirm--form"]',
            applePayMethodSelector: '.unzer-payment--apple-pay-method-wrapper',
            authorizePaymentUrl: '',
            merchantValidationUrl: '',
            noApplePayMessage: '',
            supportedNetworks: ['masterCard', 'visa']
        },

        unzerPaymentPlugin: null,
        unzerApplePay: null,

        numberValid: false,
        cvcValid: false,
        expiryValid: false,

        init: function () {
            var me = this,
                unzerPaymentInstance;

            this.applyDataAttributes();

            this.unzerPaymentPlugin = $('*[data-unzer-payment-base="true"]').data('plugin_unzerPaymentBase');
            unzerPaymentInstance = this.unzerPaymentPlugin.getUnzerPaymentInstance();

            console.log(unzerPaymentInstance);

            if (!unzerPaymentInstance) {
                return;
            }

            console.log(this.hasCapability());
            if (!this.hasCapability()) {
                this.disableApplePay();
            }

            this.createScript();
            this.createForm();

            if (!this.unzerApplePay) {
                this.unzerPaymentPlugin.showCommunicationError();

                return;
            }

            this.registerEvents();

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

            console.log(window.ApplePaySession);
            if (!window.ApplePaySession) {
                return;
            }

            console.log(applePayPaymentRequest);

            const session = new window.ApplePaySession(6, applePayPaymentRequest);
            session.onvalidatemerchant = (event) => {
                try {
                    console.log(me.opts.merchantValidationUrl);
                    $.ajax({
                        url: me.opts.merchantValidationUrl,
                        method: 'POST',
                        data: { merchantValidationUrl: event.validationURL }
                    }).done(function (data) {
                        console.log(JSON.parse(data.validationResponse));
                        session.completeMerchantValidation(JSON.parse(data.validationResponse));
                    });
                } catch(e) {
                    console.log(e);
                    session.abort();
                }
            }

            session.onpaymentauthorized = (event) => {
                const paymentData = event.payment.token.paymentData;
                console.log('authorized');
                me.unzerApplePay.createResource(paymentData)
                    .then((createdResource) => {
                        me.submitting = true;
                        // PageLoadingIndicatorUtil.create();

                        try {
                            console.log(me.opts.authorizePaymentUrl);
                            $.ajax({
                                url: me.opts.authorizePaymentUrl,
                                method: 'POST',
                                data: createdResource
                            }).done(function (data) {
                                console.log(data);

                                if (data.transactionStatus === 'pending') {
                                    session.completePayment({status: window.ApplePaySession.STATUS_SUCCESS});

                                    me.unzerPaymentPlugin.setSubmitButtonActive(false);
                                    me.unzerPaymentPlugin.submitResource(createdResource);
                                } else {
                                    // PageLoadingIndicatorUtil.remove();
                                    session.completePayment({status: window.ApplePaySession.STATUS_FAILURE});
                                    session.abort();
                                }
                            });
                        } catch(e) {
                            // PageLoadingIndicatorUtil.remove();
                            session.completePayment({status: window.ApplePaySession.STATUS_FAILURE});
                            session.abort();
                        }
                    })
                    .catch(() => {
                        // PageLoadingIndicatorUtil.remove();
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
            // $(this.opts.applePayMethodSelector).remove();
            // $('[data-unzer-payment-apple-pay]').remove();

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
