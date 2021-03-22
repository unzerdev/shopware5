{block name="frontend_checkout_confirm_unzer_payment_frames_eps"}
    <div class="unzer-payment--eps-wrapper"
        {block name="frontend_checkout_confirm_unzer_payment_frames_ideal_wrapper_data"}
            data-unzer-payment-eps="true"
            data-unzerPaymentCreatePaymentUrl="{url controller=UnzerPaymentEps action=createPayment module=widgets}"
        {/block}>

        {block name="frontend_checkout_confirm_unzer_payment_frames_eps_container_label"}
            <label for="unzer-payment--eps-container">
                {s name="label/bankSelection"}{/s}
            </label>
        {/block}

        {block name="frontend_checkout_confirm_unzer_payment_frames_eps_container"}
            <div id="unzer-payment--eps-container" class="unzerUI form">
            </div>
        {/block}
    </div>
{/block}
