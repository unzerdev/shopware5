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
    {/if}

    {$smarty.block.parent}
{/block}
