{block name="frontend_checkout_confirm_unzer_payment_frames_paypal"}
    <div class="unzer-payment--paypal-wrapper"
        {block name="frontend_checkout_confirm_unzer_payment_frames_paypal_wrapper_data"}
        data-unzer-payment-paypal="true"
        data-unzerPaymentCreatePaymentUrl="{url controller=UnzerPaymentPaypal module=widgets action=createPayment}"
        data-isGuest="{$sUserData.additional.user.accountmode != 0}"
        {/block}>

        {block name="frontend_checkout_confirm_unzer_payment_vault_paypal"}
            {include file="frontend/unzer_payment/frames/vault/paypal.tpl"}
        {/block}

        <input id="typeIdProvider"
               type="hidden"
               name="typeId"/>
    </div>
{/block}
