{block name="frontend_checkout_confirm_heidelpay_frames_eps"}
    <div class="heidelpay--eps-wrapper"
         data-heidelpay-eps="true"
         data-heidelpayCreatePaymentUrl="{url controller=HeidelpayEps action=createPayment module=widgets}">

        {block name="frontend_checkout_confirm_heidelpay_frames_eps_container_label"}
            <label for="heidelpay--eps-container">
                {s name="label/bankSelection"}{/s}
            </label>
        {/block}

        {block name="frontend_checkout_confirm_heidelpay_frames_eps_container"}
            <div id="heidelpay--eps-container">
            </div>
        {/block}
    </div>
{/block}
