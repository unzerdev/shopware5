{block name="frontend_checkout_confirm_unzer_payment_frames_sepa_direct_debit_guaranteed_wrapper"}
    {assign var=hasVaultedMandates value=$unzerPaymentVault['sepa_mandate_g'] > 0}

    {if {config name="transaction_mode" namespace="unzer_payment"} === "test"}
        {include file="frontend/unzer_payment/frames/test_data/sepa_direct_debit.tpl"}
    {/if}

    {block name="frontend_checkout_confirm_unzer_payment_vault_sepa_direct_debit_guaranteed"}
        {if {config name="direct_debit_bookingmode" namespace="unzer_payment"} === "registerCharge"}
            {include file="frontend/unzer_payment/frames/vault/sepa_direct_debit.tpl" mandates=$unzerPaymentVault['sepa_mandate_g']}
        {/if}
    {/block}

    {block name="frontend_checkout_confirm_unzer_payment_frames_sepa_direct_debit_guaranteed_body"}
        <div class="panel has--border">
            <div class="panel--body">
                {block name="frontend_checkout_confirm_unzer_payment_frames_sepa_direct_debit_guaranteed_body_new"}
                    {if {config name="direct_debit_bookingmode" namespace="unzer_payment"} === "registerCharge"}
                        <input type="radio" class="unzer-payment--radio-button" id="new" name="mandateSelection"{if !$hasVaultedMandates} checked="checked"{/if}>
                        <label for="new">{s name="label/newIban"}{/s}</label>
                        <br/>
                    {/if}
                {/block}

                {block name="frontend_checkout_confirm_unzer_payment_frames_sepa_direct_debit_guaranteed_body_content"}
                    <div class="unzer-payment--sepa-direct-debit-wrapper"
                        {block name="frontend_checkout_confirm_unzer_payment_frames_sepa_direct_debit_guaranteed_wrapper_data"}
                            data-unzer-payment-sepa-direct-debit-guaranteed="true"
                            data-unzerPaymentCreatePaymentUrl="{url controller=UnzerPaymentSepaDirectDebitGuaranteed action=createPayment module=widgets}"
                        {/block}>
                        {block name="frontend_checkout_confirm_unzer_payment_frames_sepa_direct_debit_guaranteed_body_content_container"}
                            <div class="unzer-payment--sepa-birthday">
                                {block name="frontend_checkout_confirm_unzer_payment_frames_sepa_direct_debit_guaranteed_birthday_label"}
                                    <label for="unzerPaymentBirthday" class="unzer-payment--label is--block">
                                        {s name="label/birthday" namespace="frontend/unzer_payment/frames"}{/s}
                                        <br/>
                                    </label>
                                {/block}

                                {block name="frontend_checkout_confirm_unzer_payment_frames_sepa_direct_debit_guaranteed_birthday_input"}
                                    <input type="text"
                                           id="unzerPaymentBirthday"
                                           placeholder="{s name="placeholder/birthday" namespace="frontend/unzer_payment/frames"}{/s}"
                                           {if $sUserData.additional.user.birthday !== ''}value="{$sUserData.additional.user.birthday}"{/if}
                                           data-datepicker="true"
                                           data-allowInput="true"
                                           data-altInput="false"/>
                                {/block}
                            </div>

                            <div id="unzer-payment--sepa-direct-debit-container">
                            </div>
                        {/block}
                    </div>
                {/block}

                {include file="frontend/unzer_payment/frames/sepa/mandate.tpl"}
            </div>
        </div>
    {/block}
{/block}
