{block name="frontend_checkout_confirm_unzer_payment_frames_ideal"}
    <div class="unzer-payment--ideal-wrapper"
        {block name="frontend_checkout_confirm_unzer_payment_frames_ideal_wrapper_data"}
             data-unzer-payment-ideal="true"
             data-unzerPaymentCreatePaymentUrl="{url controller=UnzerPaymentIdeal action=createPayment module=widgets}"
            {/block}>

        {block name="frontend_checkout_confirm_unzer_payment_frames_ideal_container_label"}
            <label for="unzer-payment--ideal-container">
                {s name="label/bankSelection"}{/s}
            </label>
        {/block}

        {block name="frontend_checkout_confirm_unzer_payment_frames_ideal_container"}
            <div id="unzer-payment--ideal-container" class="UnzerUI form">
            </div>
        {/block}
    </div>
{/block}
