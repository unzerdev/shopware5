{block name="frontend_checkout_confirm_heidelpay_frames_invoice_factoring"}
    <div class="heidelpay--invoice-wrapper"
         data-heidelpay-invoice-factoring="true"
         data-heidelpayCreatePaymentUrl="{url controller=HeidelpayInvoiceFactoring action=createPayment module=widgets}">

        {block name="frontend_checkout_confirm_heidelpay_frames_invoice_factoring_birthday_label"}
            <label for="heidelpayBirthday">
                {s name="label/birthday" namespace="frontend/heidelpay/frames/invoice"}{/s}
                <br/>
            </label>
        {/block}

        {block name="frontend_checkout_confirm_heidelpay_frames_invoice_factoring_birthday"}
            <input type="text"
                   id="heidelpayBirthday"
                   placeholder="{s name="placeholder/birthday" namespace="frontend/heidelpay/frames/invoice"}{/s}"
                   {if $sUserData.additional.user.birthday !== ''}value="{$sUserData.additional.user.birthday}"{/if}
                   data-datepicker="true"
                   data-allowInput="true"/>

        {/block}
    </div>
{/block}
