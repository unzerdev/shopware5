{block name="frontend_checkout_confirm_unzer_payment_frames_paylater_installment"}
    {if $unzerPaymentFraudPreventionSessionId}
        <script type="text/javascript" async
                src="https://h.online-metrix.net/fp/tags.js?org_id=363t8kgq&session_id={$unzerPaymentFraudPreventionSessionId}">
        </script>
    {/if}
    <div class="unzer-payment--paylater-installment-wrapper"
            {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_installment_wrapper_data"}
        data-unzer-payment-paylater-installment="true"
        data-unzerPaymentCreatePaymentUrl="{url controller=UnzerPaymentPaylaterInstallment action=createPayment module=widgets}"
        data-unzerPaymentAmount="{$sAmount}"
        data-unzerPaymentCurrency="{$sBasket.sCurrencyName}"
        data-unzerPaymentCountryIso="{$sUserData.additional.country.countryiso}"
            {/block}>

        {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_installment_container"}
            {if {config name="transaction_mode" namespace="unzer_payment"} === 'test'}
                {include file="frontend/unzer_payment/frames/test_data/paylater_installment.tpl"}
            {/if}
        {/block}

        <div id="unzerPaymentPaylaterInstallmentContainer">
        </div>

        <div id="unzerPaymentBirthdayContainer" class="unzer-payment-birthday">
            {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_installment_birthday_label"}
                <label for="unzerPaymentBirthday" class="unzer-payment-label is--block">
                    {s name="label/birthday" namespace="frontend/unzer_payment/frames"}{/s}
                </label>
            {/block}

            {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_installment_birthday_input"}
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

            {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_installment_birthday_error"}
                <div id="unzerPaymentBirthdayError" class="unzer-payment-birthday-error">
                    {s name="invalid/age" namespace="frontend/unzer_payment/frames"}{/s}
                </div>
            {/block}
        </div>

        <div id="unzerPaymentPaylaterInstallmentErrorContainer" class="field" style="color: #d0021b">
        </div>
    </div>
{/block}
