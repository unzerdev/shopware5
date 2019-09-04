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
            <input type="text"
                   id="heidelpayBirthday"
                   placeholder="{s name="placeholder/birthday"}{/s}"
                   data-datepicker="true"
                   data-allowInput="true"
                   {if $sUserData.additional.user.birthday !== ''}value="{$sUserData.additional.user.birthday}"{/if}/>
        {/block}
    </div>
{/block}
