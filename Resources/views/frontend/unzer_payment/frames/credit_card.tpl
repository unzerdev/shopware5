{block name="frontend_checkout_confirm_unzer_payment_vault_credit_card_test_data_wrapper"}
    {if {config name="transaction_mode" namespace="unzer_payment"} === 'test'}
        {include file="frontend/unzer_payment/frames/test_data/credit_card.tpl"}
    {/if}
{/block}

{block name="frontend_checkout_confirm_unzer_payment_vault_credit_card"}
    {include file="frontend/unzer_payment/frames/vault/credit_card.tpl"}
{/block}

{block name="frontend_checkout_confirm_unzer_payment_frames_credit_card"}
    <div class="unzer-payment--credit-card-wrapper"
        {block name="frontend_checkout_confirm_unzer_payment_frames_ideal_wrapper_data"}
            data-unzer-payment-credit-card="true"
            data-unzerPaymentCreatePaymentUrl="{url controller=UnzerPaymentCreditCard module=widgets action=createPayment}"
        {/block}>

        {block name="frontend_checkout_confirm_unzer_payment_frames_credit_card_new"}
            <input type="radio" class="unzer-payment--radio-button" id="new" name="cardSelection">
            <label for="new">{s name="label/newCard"}{/s}</label>
        {/block}

        <div class="unzer-payment--credit-card-container is--hidden">
            {block name="frontend_checkout_confirm_unzer_payment_frames_credit_card_number"}
                <label for="card-element-id-number" id="card-element-label-number">{s name="label/number"}{/s}</label>
                <div id="card-element-id-number" class="unzer-payment--input-field" data-type="number">
                    <!-- Card number UI Element will be inserted here. -->
                </div>

                <div id="card-element-error-number">
                    <label for="card-element-error-number" id="card-element-error-number-label" class="unzer-payment--error-label is--hidden"></label>
                </div>
            {/block}

            {block name="frontend_checkout_confirm_unzer_payment_frames_credit_card_expiry"}
                <label for="card-element-id-expiry" id="card-element-label-expiry">{s name="label/expiry"}{/s}</label>
                <div id="card-element-id-expiry" class="unzer-payment--input-field" data-type="expiry">
                    <!-- Card expiry date UI Element will be inserted here. -->
                </div>

                <div id="card-element-error-expiry">
                    <label for="card-element-error-expiry" id="card-element-error-expiry-label" class="unzer-payment--error-label is--hidden"></label>
                </div>
            {/block}

            {block name="frontend_checkout_confirm_unzer_payment_frames_credit_card_cvc"}
                <label for="card-element-id-cvc" id="card-element-label-cvc">{s name="label/cvc"}{/s}</label>
                <div id="card-element-id-cvc" class="unzer-payment--input-field" data-type="cvc">
                    <!-- Card CVC UI Element will be inserted here. -->
                </div>

                <div id="card-element-error-cvc">
                    <label for="card-element-error-cvc" id="card-element-error-cvc-label" class="unzer-payment--error-label is--hidden"></label>
                </div>
            {/block}

            {block name="frontend_checkout_confirm_unzer_payment_frames_credit_card_remember"}
                {if $sUserData.additional.user.accountmode == 0}
                    <input name="rememberCreditCard" type="checkbox" id="card-element-id-remember"/>
                    <label for="card-element-id-remember" id="card-element-label-remember">{s name="label/remember"}{/s}</label>
                {/if}
            {/block}
        </div>
    </div>
{/block}
