{block name="frontend_checkout_confirm_unzer_payment_frames_paylater_direct_debit_secured"}
    {if $unzerPaymentFraudPreventionSessionId}
        <script type="text/javascript" async
                src="https://h.online-metrix.net/fp/tags.js?org_id=363t8kgq&session_id={$unzerPaymentFraudPreventionSessionId}">
        </script>
    {/if}
    <div class="unzer-payment--paylater-direct-debit-secured-wrapper"
            {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_direct_debit_secured_wrapper_data"}
        data-unzer-payment-paylater-direct-debit-secured="true"
        data-unzerPaymentCreatePaymentUrl="{url controller=UnzerPaymentPaylaterDirectDebitSecured action=createPayment module=widgets}"
        data-unzerPaymentAmount="{$sAmount}"
        data-unzerPaymentCurrency="{$sBasket.sCurrencyName}"
        data-unzerPaymentCountryIso="{$sUserData.additional.country.countryiso}"
            {/block}>

        {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_direct_debit_secured_container"}
            {if {config name="transaction_mode" namespace="unzer_payment"} === 'test'}
                {include file="frontend/unzer_payment/frames/test_data/paylater_direct_debit_secured.tpl"}
            {/if}
        {/block}

        <div id="unzerPaymentPaylaterDirectDebitSecuredContainer">
        </div>

        <div id="unzerPaymentBirthdayContainer" class="unzer-payment-birthday">
            {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_direct_debit_secured_birthday_label"}
                <label for="unzerPaymentBirthday" class="unzer-payment-label is--block">
                    {s name="label/birthday" namespace="frontend/unzer_payment/frames"}{/s}
                </label>
            {/block}

            {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_direct_debit_secured_birthday_input"}
                <input type="text"
                       id="unzerPaymentBirthday"
                       required="required"
                       form="confirm--form"
                       placeholder="{s name="placeholder/birthday" namespace="frontend/unzer_payment/frames"}{/s}"
                       {if $sUserData.additional.user.birthday !== ''}value="{$sUserData.additional.user.birthday|date_format:"%d.%m.%Y"}"{/if}
                       data-datepicker="true"
                       data-allowInput="true"
                       data-altInput="false"
                       data-dateFormat="d.m.Y"
                       data-maxDate="{"-18 years"|date_format:"%d.%m.%Y"}"
                />
            {/block}

            {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_direct_debit_secured_birthday_error"}
                <div id="unzerPaymentBirthdayError" class="unzer-payment-birthday-error">
                    {s name="invalid/age" namespace="frontend/unzer_payment/frames"}{/s}
                </div>
            {/block}
        </div>

        <div id="unzerPaymentPaylaterDirectDebitSecuredErrorContainer" class="field" style="color: #d0021b">
        </div>
    </div>
{/block}
