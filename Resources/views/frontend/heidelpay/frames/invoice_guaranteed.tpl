{block name="frontend_checkout_confirm_heidelpay_frames_invoice_guaranteed"}
    <div class="heidelpay--invoice-wrapper"
         data-heidelpay-invoice-guaranteed="true"
         data-heidelpayCreatePaymentUrl="{url controller=HeidelpayInvoiceGuaranteed action=createPayment module=widgets}">

        {block name="frontend_checkout_confirm_heidelpay_frames_invoice_guaranteed_birthday_label"}
            <label for="heidelpayBirthday">
                {s name="label/birthday" namespace="frontend/heidelpay/frames/invoice"}{/s}
                <br/>
            </label>
        {/block}

        {block name="frontend_checkout_confirm_heidelpay_frames_invoice_guaranteed_birthday"}
            <input type="text" id="heidelpayBirthday" required="required" form="confirm--form" data-datepicker="true" placeholder="Your birthday" value="{$sUserData.additional.user.birthday}"/>
        {/block}
    </div>
{/block}
