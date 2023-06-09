{extends file="parent:frontend/checkout/confirm.tpl"}

{block name="frontend_checkout_confirm_information_wrapper"}
    {$smarty.block.parent}

    {if $unzerPaymentFrame}
        {include file="frontend/unzer_payment/checkout/confirm.tpl"}
    {/if}
{/block}

{block name='frontend_checkout_confirm_submit'}
    <div data-unzer-payment-apple-pay="true"
         data-countryCode="{$sCountry.countryiso}"
         data-currency="{$sBasket.sCurrencyName}"
         data-shopName="{$sShopname}"
         data-amount="{$sAmount}"
         data-authorizePaymentUrl="{url controller=UnzerPaymentApplePay action=authorizePayment module=widgets}"
         data-merchantValidationUrl="{url controller=UnzerPaymentApplePay action=validateMerchant module=widgets}"
         data-noApplePayMessage="test"
         data-supportedNetworks='["masterCard", "visa"]'>

        <apple-pay-button buttonstyle="black" type="buy" locale="{$Locale}" style="--apple-pay-button-width: 100%;"></apple-pay-button>
    </div>

    {$smarty.block.parent}
{/block}
