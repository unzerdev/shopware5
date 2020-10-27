{block name='frontend_checkout_confirm_information_wrapper_js'}
    <script type="text/javascript" src="https://static.heidelpay.com/v1/heidelpay.js"></script>
{/block}

{block name="frontend_checkout_confirm_information_wrapper_css"}
    <link rel="stylesheet" href="https://static.heidelpay.com/v1/heidelpay.css" />
{/block}

{block name="frontend_checkout_confirm_unzer_payment_wrapper"}
    <div id="unzer-payment-frame"
         class="unzer-payment--panel"
         data-unzer-payment-base="true"
         data-unzerPaymentPublicKey="{config name="public_key" namespace="unzer_payment"}"
         data-unzerPaymentErrorUrl="{url controller=checkout action=shippingPayment unzerPaymentMessage=''}"
         data-unzerPaymentGenericError="{s name="genericRedirectError"}{/s}"
         data-unzerPaymentBirthdayError="{s name="invalid/birthday" namespace="frontend/unzer_payment/frames"}{/s}">
        {block name="frontend_checkout_confirm_unzer_payment_content"}
            <div class="panel has--border is--wide">
                {block name="frontend_checkout_confirm_unzer_payment_content_title"}
                    <div class="panel--title is--underline payment--title">
                        {$sPayment.description}
                    </div>
                {/block}

                {block name="frontend_checkout_confirm_unzer_payment_content_body"}
                    <div class="panel--body is--wide">
                        <div class="unzer-payment--communication-error is--hidden">
                            {include file="frontend/_includes/messages.tpl" type="error" content="{s name="communicationError"}{/s}"}
                        </div>

                        {if "frontend/unzer_payment/frames/{$unzerPaymentFrame}"|template_exists}
                            <div class="unzer-payment--frame">
                                {include file="frontend/unzer_payment/frames/{$unzerPaymentFrame}"}
                            </div>
                        {/if}
                    </div>
                {/block}
            </div>
        {/block}
    </div>
{/block}
