{block name="frontend_checkout_confirm_unzer_payment_frames_invoice_factoring"}
    <div class="unzer-payment--invoice-wrapper"
        {block name="frontend_checkout_confirm_unzer_payment_frames_invoice_factoring_wrapper_data"}
            data-unzer-payment-invoice-factoring="true"
            data-unzerPaymentCreatePaymentUrl="{url controller=UnzerPaymentInvoiceFactoring action=createPayment module=widgets}"
            data-isB2bCustomer="{$sUserData.billingaddress.company}"
            data-unzerPaymentCustomerDataUrl="{url controller=UnzerPayment action=getCustomerData module=frontend}"
        {/block}>

        {if !$sUserData.billingaddress.company}
            <div class="unzer-payment--b2c-form">
                {block name="frontend_checkout_confirm_unzer_payment_frames_invoice_factoring_birthday_label"}
                    <label for="unzerPaymentBirthday" class="is--block">
                        {s name="label/birthday" namespace="frontend/unzer_payment/frames"}{/s}
                    </label>
                {/block}

                {block name="frontend_checkout_confirm_unzer_payment_frames_invoice_factoring_birthday"}
                    <input type="text"
                           id="unzerPaymentBirthday"
                           placeholder="{s name="placeholder/birthday" namespace="frontend/unzer_payment/frames"}{/s}"
                           {if $sUserData.additional.user.birthday !== ''}value="{$sUserData.additional.user.birthday}"{/if}
                           data-datepicker="true"
                           data-allowInput="true"
                           data-altInput="false"/>
                {/block}
            </div>
        {/if}

        {block name="frontend_checkout_confirm_unzer_payment_frames_invoice_factoring_b2b_fields"}
            <div id="unzer-payment--invoice-factoring-container" class="unzer-payment-b2b-form heidelpayUI form">
            </div>
        {/block}
    </div>
{/block}
