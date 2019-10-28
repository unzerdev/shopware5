{block name="frontend_checkout_confirm_heidel_payment_wrapper"}
    <div class="heidelpay--panel"
         id="heidelpay-frame"
         data-heidelpay-base="true"
         data-heidelpayPublicKey="{config name="public_key" namespace="heidel_payment"}"
         data-heidelpayLocale="{$heidelpayLocale}"
         data-heidelpayErrorUrl="{url controller=checkout action=shippingPayment heidelpayMessage=''}">
        {block name="frontend_checkout_confirm_heidelpay_content"}
            <div class="panel has--border is--wide">
                {block name="frontend_checkout_confirm_heidelpay_content_title"}
                    <div class="panel--title is--underline payment--title">
                        {$sPayment.description}
                    </div>
                {/block}

                {block name="frontend_checkout_confirm_heidelpay_content_body"}
                    <div class="panel--body is--wide">
                        <div class="heidelpay--communication-error is--hidden">
                            {include file="frontend/_includes/messages.tpl" type="error" content="{s name="communicationError"}{/s}"}
                        </div>

                        <div class="heidelpay--frame">
                            {include file="frontend/heidelpay/frames/{$sPayment.embediframe}"}
                        </div>
                    </div>
                {/block}
            </div>
        {/block}
    </div>
{/block}
