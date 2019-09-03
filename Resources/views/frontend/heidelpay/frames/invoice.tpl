{block name="frontend_checkout_confirm_heidelpay_frames_invoice"}
    <div class="heidelpay--invoice-wrapper"
         data-heidelpay-invoice="true"
         data-heidelpayCreatePaymentUrl="{url controller=HeidelpayInvoice action=createPayment module=widgets}">

        {block name="frontend_checkout_confirm_heidelpay_frames_invoice_birthday_label"}
            <label for="heidelpayBirthday">
                {s name="label/birthday"}{/s}
                <br/>
            </label>
        {/block}

        {block name="frontend_checkout_confirm_heidelpay_frames_invoice_birthday"}
            <input type="text" id="heidelpayBirthday" required="required" form="confirm--form" data-datepicker="true" placeholder="Your birthday" value="{$sUserData.additional.user.birthday}"/>
        {/block}
    </div>
{/block}
