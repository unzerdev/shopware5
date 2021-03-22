{block name="frontend_checkout_confirm_unzer_payment_frames_invoice_secured"}
    <div class="unzer-payment--invoice-wrapper"
        {block name="frontend_checkout_confirm_unzer_payment_frames_invoice_secured_wrapper_data"}
            data-unzer-payment-invoice-guaranteed="true"
            data-unzerPaymentCreatePaymentUrl="{url controller=UnzerPaymentInvoiceSecured action=createPayment module=widgets}"
            data-isB2bCustomer="{$sUserData.billingaddress.company}"
            data-unzerPaymentCustomerDataUrl="{url controller=UnzerPayment action=getCustomerData module=frontend}"
        {/block}>

        {if !$sUserData.billingaddress.company}
            <div class="unzer-payment--b2c-form">
                {block name="frontend_checkout_confirm_unzer_payment_frames_invoice_secured_birthday_label"}
                    <label for="unzerPaymentBirthday" class="unzer-payment--label is--block">
                        {s name="label/birthday" namespace="frontend/unzer_payment/frames"}{/s}
                    </label>
                {/block}

                {block name="frontend_checkout_confirm_unzer_payment_frames_invoice_secured_birthday"}
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
            </div>
        {/if}

        {block name="frontend_checkout_confirm_unzer_payment_frames_invoice_secured_b2b_fields"}
            <div id="unzer-payment--invoice-guaranteed-container" class="unzer-payment-b2b-form UnzerUI form">
            </div>
        {/block}
    </div>
{/block}
