{block name="frontend_checkout_confirm_unzer_payment_frames_installment_secured"}
    <div class="unzer-payment--installment-secured-wrapper"
        {block name="frontend_checkout_confirm_unzer_payment_frames_installment_secured_data"}
        data-unzer-payment-installment-secured="true"
        data-unzerPaymentCreatePaymentUrl="{url controller=UnzerPaymentInstallmentSecured action=createPayment module=widgets}"
        data-basketAmount="{$sBasket.AmountNumeric}"
        data-currencyIso="{$sBasket.sCurrencyName}"
        data-locale="{$unzerPaymentLocale}"
        data-starSign="{s name="Star" namespace="frontend/listing/box_article"}{/s}"
        data-effectiveInterest="{$unzerPaymentEffectiveInterest}"
        {/block}>

        {block name="frontend_checkout_confirm_unzer_payment_frames_installment_secured_container"}
            {if {config name="transaction_mode" namespace="unzer_payment"} === 'test'}
                {include file="frontend/unzer_payment/frames/test_data/installment_secured.tpl"}
            {/if}
        {/block}

        {block name="frontend_checkout_confirm_unzer_payment_frames_installment_secured_container"}
            <div id="unzer-payment--installment-secured-container" class="UnzerUI form"></div>

            {block name="frontend_checkout_confirm_unzer_payment_frames_installment_secured_birthday_label"}
                <label for="unzerPaymentBirthday" class="unzer-payment--label is--block">
                    {s name="label/birthday" namespace="frontend/unzer_payment/frames"}{/s}
                </label>
            {/block}

            {block name="frontend_checkout_confirm_unzer_payment_frames_installment_secured_birthday_field"}
                <input type="text"
                       id="unzerPaymentBirthday"
                       placeholder="{s name="placeholder/birthday" namespace="frontend/unzer_payment/frames"}{/s}"
                       {if $sUserData.additional.user.birthday !== ''}value="{$sUserData.additional.user.birthday}"{/if}
                       data-datepicker="true"
                       data-allowInput="true"
                       data-altInput="false"
                       data-dateFormat="d.m.Y"
                />
            {/block}
        {/block}
    </div>
{/block}
