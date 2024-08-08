;(function ($, window) {
    'use strict';

    $.plugin('unzerPaymentGooglePay', {
        defaults: {
            googlePayButtonId: 'unzer-google-pay-button',
            checkoutConfirmButtonSelector: 'button[form="confirm--form"]',
            merchantName: '',
            merchantId: '',
            gatewayMerchantId: '',
            currency: 'EUR',
            amount: '0.0',
            countryCode: 'DE',
            allowedCardNetworks: [],
            allowCreditCards: true,
            allowPrepaidCards: true,
            buttonColor: 'default',
            buttonSizeMode: 'fill',
        },

        unzerPaymentPlugin: null,
        unzerPaymentInstance: null,
        unzerGooglePay: null,
        unzerContainer: null,

        numberValid: false,
        cvcValid: false,
        expiryValid: false,

        init: function () {
            this.applyDataAttributes();

            this.unzerContainer = $('*[data-unzer-payment-base="true"]');
            this.unzerPaymentPlugin = this.unzerContainer.data('plugin_unzerPaymentBase');
            this.unzerPaymentInstance = this.unzerPaymentPlugin.getUnzerPaymentInstance();

            if (!this.unzerPaymentInstance) {
                return;
            }

            this.createScript(()=>{
                this.createForm();

                if (!this.unzerGooglePay) {
                    this.unzerPaymentPlugin.showCommunicationError();

                    return;
                }

                this._registerGooglePayButton();

                // We only need the container to display messages.
                this.unzerContainer.hide();

                $.publish('plugin/unzer/google_pay/init', this);
            });
        },

        createScript(callback) {
            const script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = 'https://pay.google.com/gp/p/js/pay.js';
            script.onload = callback;
            document.head.appendChild(script);

        },
        createForm: function () {
            this.unzerGooglePay = this.unzerPaymentInstance.Googlepay();
            $(this.opts.checkoutConfirmButtonSelector).hide();
            $.publish('plugin/unzer/google_pay/createForm', this, this.unzerGooglePay);
        },

        _registerGooglePayButton() {
            const me = this;

            const paymentDataRequestObject = this.unzerGooglePay.initPaymentDataRequestObject(
                {
                    gatewayMerchantId: this.opts.gatewayMerchantId,
                    merchantInfo: {
                        merchantName: this.opts.merchantName,
                        merchantId: this.opts.merchantId,
                    },
                    transactionInfo: {
                        currencyCode: this.opts.currency,
                        countryCode: this.opts.countryCode,
                        totalPriceStatus: 'ESTIMATED',
                        totalPrice: String(this.opts.amount),
                    },
                    buttonOptions: {
                        buttonColor: this.opts.buttonColor,
                        buttonSizeMode: this.opts.buttonSizeMode,

                    },
                    allowedCardNetworks: this.opts.allowedCardNetworks,
                    allowCreditCards: this.opts.allowCreditCards,
                    allowPrepaidCards: this.opts.allowPrepaidCards,

                    onPaymentAuthorizedCallback: (paymentData) => {
                        const googlePayButton = document.getElementById(me.opts.googlePayButtonId);
                        googlePayButton.style.display = 'none';
                        return me.unzerGooglePay.createResource(paymentData)
                            .then(
                                (createdResource) => {
                                    // checkout started
                                    if (this.unzerPaymentPlugin.checkFormValidity()) {
                                        me.unzerPaymentPlugin.setSubmitButtonActive(false);
                                        me.unzerPaymentPlugin.submitResource(createdResource);
                                    } else {
                                        googlePayButton.style.display = '';
                                    }
                                    return {
                                        status: 'success'
                                    };
                                }
                            )
                            .catch(
                                (error) => {
                                    //checkout failed
                                    googlePayButton.style.display = '';
                                    const publicError = error;
                                    publicError.message = error.customerMessage || error.message || 'Error';
                                    this.unzerPaymentPlugin.showCommunicationError(publicError.message);
                                    return {
                                        status: 'error',
                                        message: publicError.message || 'Unexpected error'
                                    }
                                }
                            )
                    }
                }
            );
            this.unzerGooglePay.create(
                {
                    containerId: me.opts.googlePayButtonId,
                },
                paymentDataRequestObject
            );
        },


    });

    window.StateManager.addPlugin('*[data-unzer-payment-google-pay="true"]', 'unzerPaymentGooglePay');
})(jQuery, window);
