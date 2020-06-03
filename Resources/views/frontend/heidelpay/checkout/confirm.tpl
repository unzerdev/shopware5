{block name='frontend_checkout_confirm_information_wrapper_js'}
    <script type="text/javascript" src="https://static.heidelpay.com/v1/heidelpay.js"></script>
{/block}

{block name="frontend_checkout_confirm_information_wrapper_css"}
    <link rel="stylesheet" href="https://static.heidelpay.com/v1/heidelpay.css" />
{/block}

{block name="frontend_checkout_confirm_heidel_payment_wrapper"}
    <div id="heidelpay-frame"
         class="heidelpay--panel"
         data-heidelpay-base="true"
         data-heidelpayPublicKey="{config name="public_key" namespace="heidel_payment"}"
         data-heidelpayErrorUrl="{url controller=checkout action=shippingPayment heidelpayMessage=''}"
         data-heidelpayGenericRedirectError="{s name="genericRedirectError"}{/s}">
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

                        {if "frontend/heidelpay/frames/{$heidelpayFrame}"|template_exists}
                            <div class="heidelpay--frame">
                                {include file="frontend/heidelpay/frames/{$heidelpayFrame}"}
                            </div>
                        {/if}
                    </div>
                {/block}
            </div>
        {/block}
    </div>
{/block}
