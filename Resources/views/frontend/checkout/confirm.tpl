{extends file="parent:frontend/checkout/confirm.tpl"}

{block name="frontend_checkout_confirm_information_wrapper"}
    {$smarty.block.parent}

    {if $unzerPaymentFrame}
        {include file="frontend/unzer_payment/checkout/confirm.tpl"}
    {/if}
{/block}

{block name='frontend_checkout_confirm_submit'}
    {if $unzerApplePaySelected}
        <div data-unzer-payment-apple-pay="true"
             data-countryCode="{$sCountry.countryiso}"
             data-currency="{$sBasket.sCurrencyName}"
             data-shopName="{$sShopname}"
             data-amount="{$sAmount}"
             data-authorizePaymentUrl="{url controller=UnzerPaymentApplePay action=authorizePayment module=widgets}"
             data-merchantValidationUrl="{url controller=UnzerPaymentApplePay action=validateMerchant module=widgets}"
             data-noApplePayMessage="{s name="noApplePayMessage" namespace="frontend/unzer_payment/frames/apple_pay"}{/s}"
             data-supportedNetworks='["masterCard", "visa"]'>

            {if {config name="transaction_mode" namespace="unzer_payment"} === 'test'}
                {include file="frontend/unzer_payment/frames/test_data/apple_pay.tpl"}
            {/if}

            <apple-pay-button buttonstyle="black" type="buy" locale="{$Locale}" style="--apple-pay-button-width: 100%;"></apple-pay-button>
        </div>
    {elseif $unzerGooglePaySelected}
        <div data-unzer-payment-google-pay="true"
             data-merchantName="{config name='google_pay_merchant_name' namespace='unzer_payment'}"
             data-merchantId="{config name='google_pay_merchant_id' namespace='unzer_payment'}"
             data-gatewayMerchantId="{$unzerGooglePayGatewayMerchantId}"
             data-currency="{$sBasket.sCurrencyName}"
             data-amount="{$sAmount}"
             data-countryCode="{config name='google_pay_country_code' namespace='unzer_payment'}"
             data-allowedCardNetworks='{$unzerGooglePayAllowedCardNetworks|json_encode}'
             data-allowCreditCards="{if {config name='google_pay_credit_cards_allowed' namespace='unzer_payment'} === '1'}true{else}false{/if}"
             data-allowPrepaidCards="{if {config name='google_pay_prepaid_cards_allowed' namespace='unzer_payment'} === '1'}true{else}false{/if}"
             data-buttonColor="{config name='google_pay_button_color' namespace='unzer_payment'}"
             data-buttonSizeMode="{config name='google_pay_button_size_mode' namespace='unzer_payment'}"
             {if {config name='google_pay_button_size_mode' namespace='unzer_payment'} != 'fill'}class="right"{/if}
        >
            <div id="unzer-google-pay-button"></div>
        </div>
    {/if}

    {$smarty.block.parent}
{/block}
