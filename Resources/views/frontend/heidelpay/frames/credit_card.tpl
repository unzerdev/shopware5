{block name="frontend_checkout_confirm_heidelpay_frames_credit_card"}
    <div class="heidelpay--credit-card-wrapper"
         data-heidelpay-credit-card="true"
         data-heidelpayCreatePaymentUrl="{url controller=HeidelpayCreditCard module=widgets action=createPayment}">

        {block name="frontend_checkout_confirm_heidelpay_frames_credit_card_number"}
            <label for="card-element-id-number" id="card-element-label-number">{s name=label/number}{/s}</label>
            <div id="card-element-id-number" class="heidelpay--input-field" data-type="number">
                <!-- Card number UI Element will be inserted here. -->
            </div>

            <div id="card-element-error-number">
                <label for="card-element-error-number" id="card-element-error-number-label" class="heidelpay--error-label is--hidden"></label>
            </div>
        {/block}

        {block name="frontend_checkout_confirm_heidelpay_frames_credit_card_expiry"}
            <label for="card-element-id-expiry" id="card-element-label-expiry">{s name=label/expiry}{/s}</label>
            <div id="card-element-id-expiry" class="heidelpay--input-field" data-type="expiry">
                <!-- Card expiry date UI Element will be inserted here. -->
            </div>

            <div id="card-element-error-expiry">
                <label for="card-element-error-expiry" id="card-element-error-expiry-label" class="heidelpay--error-label is--hidden"></label>
            </div>
        {/block}

        {block name="frontend_checkout_confirm_heidelpay_frames_credit_card_cvc"}
            <label for="card-element-id-cvc" id="card-element-label-cvc">{s name=label/cvc}{/s}</label>
            <div id="card-element-id-cvc" class="heidelpay--input-field" data-type="cvc">
                <!-- Card CVC UI Element will be inserted here. -->
            </div>

            <div id="card-element-error-cvc">
                <label for="card-element-error-cvc" id="card-element-error-cvc-label" class="heidelpay--error-label is--hidden"></label>
            </div>
        {/block}

        {block name="frontend_checkout_confirm_heidelpay_frames_credit_card_icon"}
            <div id="card-element-card-icon" class="heidelpay--card-icon"></div>
        {/block}
    </div>
{/block}
