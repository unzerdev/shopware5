{block name="frontend_checkout_confirm_heidel_payment_wrapper"}
    <div class="heidelpay--panel"
         data-heidelpay="true"
         data-heidelpayPublicKey="{config name="public_key" namespace="heidel_payment"}"
         data-heidelpayLocale="{$heidelLocale}"
         data-heidelpayErrorUrl="{url controller=checkout action=shippingPayment heidelpayMessage=''}">
        {block name="frontend_checkout_confirm_heidelpay_content"}
            <div class="panel has--border is--wide">
                {block name="frontend_checkout_confirm_heidelpay_content_title"}
                    <div class="panel--title is--underline payment--title">
                        {$sPayment.description}
                    </div>
                {/block}

                {block name="frontend_checkout_confirm_heidelpay_content_body"}
                    <div class="panel--body is--wide payment--content">
                        {include file="frontend/heidelpay/frames/{$sPayment.embediframe}"}
                    </div>
                {/block}
            </div>
        {/block}
    </div>
{/block}
