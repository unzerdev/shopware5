{block name="frontend_checkout_confirm_heidelpay_frames_hire_purchase"}
    <div class="heidelpay--hire-purchase-wrapper"
            {block name="frontend_checkout_confirm_heidelpay_frames_hire_purchase_data"}
        data-heidelpay-hire-purchase="true"
        data-heidelpayCreatePaymentUrl="{url controller=HeidelpayHirePurchase action=createPayment module=widgets}"
        data-basketAmount="{$sBasket.AmountNumeric}"
        data-currencyIso="{$sBasket.sCurrencyName}"
        data-effectiveInterest="{$heidelpayEffectiveInterest}"
            {/block}>
        {block name="frontend_checkout_confirm_heidelpay_frames_hire_purchase_container"}
            <div id="heidelpay--hire-purchase-container" class="heidelpayUI form">
            </div>
        {/block}
    </div>
{/block}
