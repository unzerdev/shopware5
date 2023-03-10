{block name="frontend_checkout_confirm_unzer_payment_frames_paylater_invoice"}
    {if $unzerPaymentFraudPreventionSessionId}
        <script type="text/javascript" async
                src="https://h.online-metrix.net/fp/tags.js?org_id=363t8kgq&session_id={$unzerPaymentFraudPreventionSessionId}">
        </script>
    {/if}
    <div class="unzer-payment--paylater-invoice-wrapper"
        {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_invoice_wrapper_data"}
            data-unzer-payment-paylater-invoice="true"
            data-unzerPaymentCreatePaymentUrl="{url controller=UnzerPaymentPaylaterInvoice action=createPayment module=widgets}"
            data-isB2bCustomer="{$sUserData.billingaddress.company}"
            data-unzerPaymentCustomerDataUrl="{url controller=UnzerPayment action=getCustomerData module=frontend}"
        {/block}>

        {if (!$sUserData.billingaddress.company)}
            <div class="unzer-payment--b2c-form">
                {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_invoice_birthday_label"}
                    <label for="unzerPaymentBirthday" class="unzer-payment-label is--block">
                        {s name="label/birthday" namespace="frontend/unzer_payment/frames"}{/s}
                    </label>
                {/block}

                {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_invoice_birthday"}
                    <input type="text"
                           id="unzerPaymentBirthday"
                           placeholder="{s name="placeholder/birthday" namespace="frontend/unzer_payment/frames"}{/s}"
                           {if $sUserData.additional.user.birthday !== ''}value="{$sUserData.additional.user.birthday|date_format:"%d.%m.%Y"}"{/if}
                           data-datepicker="true"
                           data-allowInput="true"
                           data-altInput="false"
                           data-dateFormat="d.m.Y"
                    />
                {/block}
            </div>
        {/if}

        {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_invoice_b2b_fields"}
            <div id="unzer-payment--paylater-invoice-container" class="unzer-payment-b2b-form unzerUI form">
            </div>
        {/block}
        {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_invoice_opt_in_field"}
            <div id="unzer-payment--paylater-invoice-opt-in-container"></div>
            <div id="error-holder" class="field" style="color: #9f3a38">
                <!-- Errors will be inserted here -->
            </div>
        {/block}
    </div>
{/block}
