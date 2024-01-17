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
        data-unzerPaymentCurrentCompanyType="{$unzerPaymentCurrentCompanyType}"
            {/block}>
        {block name="unzer_payment_frame_paylater_invoice_company_types" }
            {if $sUserData.billingaddress.company }
                <div id="unzer-payment-b2b-form">
                    <div id="unzerPaymentCompanyTypeContainer">
                        {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_invoice_company_type_label"}
                            <label for="unzerPaymentCompanyType" class="unzer-payment-label is--block">
                                {s name="companyType/label" namespace="frontend/unzer_payment/frames"}{/s}
                            </label>
                        {/block}
                        {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_invoice_company_name_input"}
                            <select required="required"
                                    form="confirm--form"
                                    id="unzerPaymentCompanyType">
                                <option value=""
                                        disabled="disabled"
                                        hidden="hidden"
                                        {if empty($unzerPaymentCurrentCompanyType)}
                                            selected="selected"
                                        {/if}
                                >
                                    {s name="companyType/placeholder" namespace="frontend/unzer_payment/frames"}{/s}
                                </option>
                                {foreach $unzerPaymentCompanyTypes as $companyType}
                                    <option value="{$companyType}"
                                            {if $unzerPaymentCurrentCompanyType == $companyType}
                                                selected="selected"
                                            {/if}
                                    >
                                        {$name = "companyType/"|cat:$companyType}
                                        {$namespace = "frontend/unzer_payment/frames"}
                                        {$companyType|snippet:$name:$namespace}
                                    </option>
                                {/foreach}
                            </select>
                        {/block}
                    </div>
                </div>
            {/if}
        {/block}

        <div class="unzer-payment--b2c-form">
            <div id="unzerPaymentBirthdayContainer">
                {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_invoice_birthday_label"}
                    <label for="unzerPaymentBirthday" class="unzer-payment-label is--block">
                        {s name="label/birthday" namespace="frontend/unzer_payment/frames"}{/s}
                    </label>
                {/block}

                {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_invoice_birthday_input"}
                    <input type="text"
                           id="unzerPaymentBirthday"
                           required="required"
                           form="confirm--form"
                           placeholder="{s name="placeholder/birthday" namespace="frontend/unzer_payment/frames"}{/s}"
                           {if $sUserData.additional.user.birthday !== ''}value="{$sUserData.additional.user.birthday|date_format:"%d.%m.%Y"}"{/if}
                           data-datepicker="true"
                           data-allowInput="true"
                           data-altInput="false"
                           data-dateFormat="d.m.Y"
                           data-maxDate="{"-18 years"|date_format:"%d.%m.%Y"}"
                    />
                {/block}
            </div>
        </div>

        {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_invoice_opt_in_field"}
            <div id="unzer-payment--paylater-invoice-opt-in-container"></div>
        {/block}
    </div>
{/block}
