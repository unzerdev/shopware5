{block name="frontend_checkout_confirm_heidelpay_frames_invoice_factoring"}
    <div class="heidelpay--invoice-wrapper"
         data-heidelpay-invoice-factoring="true"
         data-heidelpayCreatePaymentUrl="{url controller=HeidelpayInvoiceFactoring action=createPayment module=widgets}"
         data-isB2bCustomer="{$sUserData.billingaddress.company}"
         data-heidelpayCustomerDataUrl="{url controller=HeidelpayCustomerData action=getCustomerData module=widgets}">

        {if !$sUserData.billingaddress.company}
            {block name="frontend_checkout_confirm_heidelpay_frames_invoice_factoring_birthday_label"}
                <label for="heidelpayBirthday" class="is--block">
                    {s name="label/birthday" namespace="frontend/heidelpay/frames/invoice"}{/s}
                </label>
            {/block}

            {block name="frontend_checkout_confirm_heidelpay_frames_invoice_factoring_birthday"}
                <input type="text"
                       id="heidelpayBirthday"
                       placeholder="{s name="placeholder/birthday" namespace="frontend/heidelpay/frames/invoice"}{/s}"
                       {if $sUserData.additional.user.birthday !== ''}value="{$sUserData.additional.user.birthday}"{/if}
                       data-datepicker="true"
                       data-allowInput="true"
                       required="required"/>
            {/block}
        {/if}

        {block name="frontend_checkout_confirm_heidelpay_frames_invoice_guaranteed_b2b_fields"}
            <div id="heidelpay--invoice-factoring-container" class="heidelpayUI form">
            </div>
        {/block}
    </div>
{/block}
