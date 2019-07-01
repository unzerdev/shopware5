;(function ($, window, undefined) {
    'use strict';

    $.plugin('heidelpayCreditCard', {
        defaults: {
            heidelpayCreatePaymentUrl: '',
            cvcLabelSelector: '#card-element-label-cvc',
            elementInvalidClass: 'is--invalid',
            elementFocusedClass: 'is--focused',
            elementHiddenClass: 'is--hidden'
        },

        heidelpayPlugin: null,
        heidelpayCard: null,

        numberValid: false,
        cvcValid: false,
        expiryValid: false,

        init: function () {
            this.heidelpayPlugin = $('*[data-heidelpay-base="true"]').data('plugin_heidelpayBase');
            this.heidelpayCard = this.heidelpayPlugin.getHeidelpayInstance().Card();

            this.heidelpayPlugin.setSubmitButtonActive(false);
            this.applyDataAttributes();
            this.registerEvents();
            this.createForm();

            $.publish('plugin/heidelpay_credit_card/init', this);
        },

        registerEvents: function () {
            $.subscribe('plugin/heidelpay/createResource', $.proxy(this.createResource, this));
        },

        createForm: function () {
            this.heidelpayCard.create('number', {
                containerId: 'card-element-id-number',
                onlyIframe: true
            });

            this.heidelpayCard.create('expiry', {
                containerId: 'card-element-id-expiry',
                onlyIframe: true
            });

            this.heidelpayCard.create('cvc', {
                containerId: 'card-element-id-cvc',
                onlyIframe: true
            });

            this.heidelpayCard.addEventListener('change', $.proxy(this.onFormChange, this));

            $.publish('plugin/heidelpay_credit_card/createForm', this, this.heidelpayCard);
        },

        onFormChange: function (event) {
            var $element = this.getEventElement(event.type);

            if (!$element) {
                return;
            }

            if (event.type === 'cvc') {
                this.cvcValid = event.success;
            } else if (event.type === 'number') {
                this.numberValid = event.success;
            } else if (event.type === 'expiry') {
                this.expiryValid = event.success;
            }

            this.setValidationError(event.type, event.error);

            if (event.focus === true) {
                $element.addClass(this.opts.elementFocusedClass);
            } else if (event.blur === true) {
                $element.removeClass(this.opts.elementFocusedClass);
            }

            if (event.cardType) {
                this.updateCreditCardIcon(event.cardType.imgName);
                this.updateCvcLabel(event.cardType.code.name);
            }

            this.heidelpayPlugin.setSubmitButtonActive(
                this.cvcValid === true &&
                this.numberValid === true &&
                this.expiryValid === true
            );

            $.publish('plugin/heidelpay_credit_card/changeForm', this, event);
        },

        /**
         * TODO: Get the actual image running - I don't know where to get them.
         */
        updateCreditCardIcon: function (icon) {
            var $cardIconElement = $('#card-element-card-icon');

            $cardIconElement.addClass(icon);
        },

        updateCvcLabel: function (newLabel) {
            var $label = $(this.opts.cvcLabelSelector);

            $label.text(newLabel);
        },

        setValidationError: function (type, message) {
            var $element = this.getEventElement(type),
                $elementLabel = $(`#card-element-error-${type}-label`);

            if (message) {
                $elementLabel.removeClass(this.opts.elementHiddenClass);
                $elementLabel.text(message);

                $element.addClass(this.opts.elementInvalidClass);
            } else {
                $elementLabel.addClass(this.opts.elementHiddenClass);

                $element.removeClass(this.opts.elementInvalidClass);
            }
        },

        getEventElement: function (type) {
            return $(`*[data-type="${type}"]`);
        },

        createResource: function () {
            $.publish('plugin/heidelpay_credit_card/beforeCreateResource', this);

            this.heidelpayCard.createResource()
                .then($.proxy(this.onResourceCreated, this))
                .catch($.proxy(this.onError, this));
        },

        onResourceCreated: function (resource) {
            $.publish('plugin/heidelpay_credit_card/createPayment', this, resource);

            $.ajax({
                url: this.opts.heidelpayCreatePaymentUrl,
                method: 'POST',
                data: {
                    resource: resource
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

            $.publish('plugin/heidelpay_credit_card/createResourceError', this, error);

            this.heidelpayPlugin.redirectToErrorPage(message);
        }
    });

    window.StateManager.addPlugin('*[data-heidelpay-credit-card="true"]', 'heidelpayCreditCard');
})(jQuery, window, undefined);
