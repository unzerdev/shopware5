;(function ($, window) {
    'use strict';

    $.plugin('unzerPaymentCreditCard', {
        defaults: {
            unzerPaymentCreatePaymentUrl: '',
            cvcLabelSelector: '#card-element-label-cvc',
            elementInvalidClass: 'is--invalid',
            elementFocusedClass: 'is--focused',
            elementHiddenClass: 'is--hidden',
            creditCardContainerSelector: '.unzer-payment--credit-card-container',
            radioButtonSelector: 'input:radio[name="cardSelection"]',
            selectedRadioButtonSelector: 'input:radio[name="cardSelection"]:checked',
            radioButtonNewSelector: '#new',
            rememberCreditCardSelector: 'input[name="rememberCreditCard"]'
        },

        unzerPaymentPlugin: null,
        unzerPaymentCard: null,

        numberValid: false,
        holderValid: false,
        cvcValid: false,
        expiryValid: false,

        init: function () {
            var me = this,
                unzerPaymentInstance;

            this.unzerPaymentPlugin = $('*[data-unzer-payment-base="true"]').data('plugin_unzerPaymentBase');
            unzerPaymentInstance = this.unzerPaymentPlugin.getUnzerPaymentInstance();

            if (!unzerPaymentInstance) {
                return;
            }

            this.unzerPaymentCard = unzerPaymentInstance.Card();

            if (!this.unzerPaymentCard) {
                this.unzerPaymentPlugin.showCommunicationError();

                return;
            }

            this.applyDataAttributes();
            this.registerEvents();
            this.createForm();
            const $selectCardRadioButtons = $(this.opts.radioButtonSelector);
            if ($selectCardRadioButtons.length === 1) {
                $(this.opts.radioButtonNewSelector).prop('checked', true);
                this.applySelectionState('new');
            } else if ($selectCardRadioButtons.length > 1) {
                const $selectedRadioButton = $selectCardRadioButtons.filter(':checked');
                if ($selectedRadioButton.length === 1) {
                    this.applySelectionState($selectedRadioButton.attr('id'));
                }else{
                    this.unzerPaymentPlugin.setSubmitButtonActive(true);
                }
            } else {
                this.unzerPaymentPlugin.setSubmitButtonActive(true);
            }

            $.publish('plugin/unzer/credit_card/init', this);
        },

        registerEvents: function () {
            $.subscribe('plugin/unzer/onSubmitCheckoutForm/after', $.proxy(this.createResource, this));
            $(this.opts.radioButtonSelector).on('change', $.proxy(this.onChangeCardSelection, this));
        },

        createForm: function () {
            this.unzerPaymentCard.create('holder', {
                containerId: 'card-element-id-holder',
                onlyIframe: true
            });

            this.unzerPaymentCard.create('number', {
                containerId: 'card-element-id-number',
                onlyIframe: true
            });

            this.unzerPaymentCard.create('expiry', {
                containerId: 'card-element-id-expiry',
                onlyIframe: true
            });

            this.unzerPaymentCard.create('cvc', {
                containerId: 'card-element-id-cvc',
                onlyIframe: true
            });

            this.unzerPaymentCard.addEventListener('change', $.proxy(this.onFormChange, this));

            $.publish('plugin/unzer/credit_card/createForm', this, this.unzerPaymentCard);
        },

        createResource: function () {
            var $newRadioButton = $(this.opts.radioButtonNewSelector);

            $.publish('plugin/unzer/credit_card/beforeCreateResource', this);

            if ($newRadioButton.is(':checked')) {
                this.unzerPaymentCard.createResource()
                    .then($.proxy(this.onResourceCreated, this))
                    .catch($.proxy(this.onError, this));
            } else {
                this.createPaymentFromVault($(this.opts.selectedRadioButtonSelector).attr('id'));
            }
        },

        updateCvcLabel: function (newLabel) {
            var $label = $(this.opts.cvcLabelSelector);

            $label.text(newLabel);
        },

        setValidationError: function (type, message) {
            var $element = this.getEventElement(type),
                $elementLabel = $('#card-element-error-' + type + '-label');

            if (message) {
                $elementLabel.removeClass(this.opts.elementHiddenClass);
                $elementLabel.text(message);

                $element.addClass(this.opts.elementInvalidClass);
            } else {
                $elementLabel.addClass(this.opts.elementHiddenClass);

                $element.removeClass(this.opts.elementInvalidClass);
            }
        },

        applySelectionState: function (state) {
            var $creditCardContainer = $(this.opts.creditCardContainerSelector);

            if (state === 'new') {
                $creditCardContainer.removeClass(this.opts.elementHiddenClass);

                this.unzerPaymentPlugin.setSubmitButtonActive(
                    this.cvcValid === true &&
                    this.numberValid === true &&
                    this.holderValid === true &&
                    this.expiryValid === true
                );
            } else {
                $creditCardContainer.addClass(this.opts.elementHiddenClass);

                this.unzerPaymentPlugin.setSubmitButtonActive(true);
            }
        },

        getEventElement: function (type) {
            return $('*[data-type="' + type + '"]');
        },

        onFormChange: function (event) {
            var $element = this.getEventElement(event.type);

            if (!$element) {
                return;
            }

            //handle saved cards
            const $selectedCard = $(this.opts.selectedRadioButtonSelector);
            if ($selectedCard.length === 1 && $selectedCard.attr('id') !== 'new') {
                return;
            }

            if (event.type === 'cvc') {
                this.cvcValid = event.success;
            } else if (event.type === 'number') {
                this.numberValid = event.success;
            } else if (event.type === 'expiry') {
                this.expiryValid = event.success;
            } else if (event.type === 'holder') {
                this.holderValid = event.success;
            }

            this.setValidationError(event.type, event.error);

            if (event.focus === true) {
                $element.addClass(this.opts.elementFocusedClass);
            } else if (event.blur === true) {
                $element.removeClass(this.opts.elementFocusedClass);
            }

            if (event.cardType) {
                this.updateCvcLabel(event.cardType.code.name);
            }

            this.unzerPaymentPlugin.setSubmitButtonActive(
                this.cvcValid === true &&
                this.numberValid === true &&
                this.holderValid === true &&
                this.expiryValid === true
            );

            $.publish('plugin/unzer/credit_card/changeForm', this, event);
        },

        onResourceCreated: function (resource) {
            var me = this;
            $.publish('plugin/unzer/credit_card/createPayment', this, resource);

            $.ajax({
                url: this.opts.unzerPaymentCreatePaymentUrl,
                method: 'POST',
                data: {
                    sComment: this.unzerPaymentPlugin.getCustomerComment(),
                    resource: resource,
                    rememberCreditCard: $(this.opts.rememberCreditCardSelector).is(':checked')
                }
            }).done(function (data) {
                if (undefined !== data.redirectUrl) {
                    window.location = data.redirectUrl;

                    return;
                }

                me.onError({ message: me.unzerPaymentPlugin.opts.unzerPaymentGenericRedirectError });
            });
        },

        createPaymentFromVault: function (typeId) {
            var me = this;
            $.ajax({
                url: this.opts.unzerPaymentCreatePaymentUrl,
                method: 'POST',
                data: {
                    typeId: typeId
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
            $.publish('plugin/unzer/credit_card/createResourceError', this, error);

            this.unzerPaymentPlugin.redirectToErrorPage(this.unzerPaymentPlugin.getMessageFromError(error));
        },

        onChangeCardSelection: function (event) {
            this.applySelectionState(event.target.id);
        }
    });

    window.StateManager.addPlugin('*[data-unzer-payment-credit-card="true"]', 'unzerPaymentCreditCard');
})(jQuery, window);
