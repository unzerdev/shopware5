{block name="frontend_checkout_confirm_heidelpay_frames_paypal"}
    {if {config name="paypal_bookingmode" namespace="heidel_payment"} === "registerCharge" || {config name="paypal_bookingmode" namespace="heidel_payment"} === "registerAuthorize"}
        <div class="heidelpay--paypal-wrapper"
                {block name="frontend_checkout_confirm_heidelpay_frames_paypal_wrapper_data"}
            data-heidelpay-paypal="true"
            data-heidelpayCreatePaymentUrl="{url controller=HeidelpayPaypal module=widgets action=createPayment}"
                {/block}>
            {block name="frontend_checkout_confirm_heidelpay_vault_paypal"}
                {include file="frontend/heidelpay/frames/vault/paypal.tpl"}
            {/block}
            <div id="paypal-form" class="hidden"></div>
        </div>
    {/if}
{/block}
