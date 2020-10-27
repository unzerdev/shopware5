{block name="frontend_checkout_confirm_unzer_payment_frames_hire_purchase"}
    <div class="unzer-payment--hire-purchase-wrapper"
        {block name="frontend_checkout_confirm_unzer_payment_frames_hire_purchase_data"}
        data-unzer-payment-hire-purchase="true"
        data-unzerPaymentCreatePaymentUrl="{url controller=UnzerPaymentHirePurchase action=createPayment module=widgets}"
        data-basketAmount="{$sBasket.AmountNumeric}"
        data-currencyIso="{$sBasket.sCurrencyName}"
        data-locale="{$unzerPaymentLocale}"
        data-starSign="{s name="Star" namespace="frontend/listing/box_article"}{/s}"
        data-effectiveInterest="{$unzerPaymentEffectiveInterest}"
        {/block}>

        {block name="frontend_checkout_confirm_unzer_payment_frames_hire_purchase_container"}
            {if {config name="transaction_mode" namespace="unzer_payment"} === 'test'}
                {include file="frontend/unzer_payment/frames/test_data/hire_purchase.tpl"}
            {/if}
        {/block}

        {block name="frontend_checkout_confirm_unzer_payment_frames_hire_purchase_container"}
            <div id="unzer-payment--hire-purchase-container" class="heidelpayUI form"></div>

            {block name="frontend_checkout_confirm_unzer_payment_frames_hire_purchase_birthday_label"}
                <label for="unzerPaymentBirthday" class="is--block">
                    {s name="label/birthday" namespace="frontend/unzer_payment/frames"}{/s}
                </label>
            {/block}

            {block name="frontend_checkout_confirm_unzer_payment_frames_hire_purchase_birthday_field"}
                <input type="text"
                       id="unzerPaymentBirthday"
                       placeholder="{s name="placeholder/birthday" namespace="frontend/unzer_payment/frames"}{/s}"
                       {if $sUserData.additional.user.birthday !== ''}value="{$sUserData.additional.user.birthday}"{/if}
                       data-datepicker="true"
                       data-allowInput="true"/>
            {/block}
        {/block}
    </div>
{/block}
