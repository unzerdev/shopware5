{block name="frontend_checkout_confirm_unzer_payment_frames_bancontact"}
    <div class="unzer-payment--bancontact-wrapper"
        {block name="frontend_checkout_confirm_unzer_payment_frames_bancontact_wrapper_data"}
            data-unzer-payment-bancontact="true"
            data-unzerPaymentCreatePaymentUrl="{url controller=UnzerPaymentBancontact action=createPayment module=widgets}"
        {/block}>

        {block name="frontend_checkout_confirm_unzer_payment_frames_bancontact_container_label"}
            <label for="unzer-payment--bancontact-container">
                {s name="label/holder" namespace="frontend/unzer_payment/frames"}{/s}
            </label>
        {/block}

        {block name="frontend_checkout_confirm_unzer_payment_frames_bancontact_container"}
            <div id="unzer-payment--bancontact-container" class="unzer-payment--b2c-form">
                <input type="text"
                       id="unzerPaymentHolder"
                       placeholder="{s name="placeholder/holder" namespace="frontend/unzer_payment/frames"}{/s}"
                />
            </div>
        {/block}
    </div>
{/block}
