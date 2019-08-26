{namespace name="frontend/heidelpay/frames/sepa_direct_debit"}

{block name="frontend_checkout_confirm_heidelpay_frames_sepa_direct_debit"}
    {assign var=hasVaultedMandates value=$heidelpayVault['sepa_mandate'] > 0}

    {if {config name="transaction_mode" namespace="heidel_payment"} === "test"}
        {include file="frontend/heidelpay/frames/test_data/sepa_direct_debit.tpl"}
    {/if}

    {block name="frontend_checkout_confirm_heidelpay_vault_sepa_direct_debit"}
        {if {config name="direct_debit_bookingmode" namespace="heidel_payment"} === "registerCharge"}
            {include file="frontend/heidelpay/frames/vault/sepa_direct_debit_guaranteed.tpl"}
        {/if}
    {/block}

    {block name="frontend_checkout_confirm_heidelpay_vault_sepa_direct_debit_new"}
        <div class="panel has--border">
            <div class="panel--body">
                {if {config name="direct_debit_bookingmode" namespace="heidel_payment"} === "registerCharge"}
                    <input type="radio" class="heidelpay--radio-button" id="new" name="mandateSelection"{if !$hasVaultedMandates} checked="checked"{/if}>
                    <label for="new">{s name="label/newIban"}{/s}</label>

                    <br/>
                {/if}

                <div class="heidelpay--sepa-direct-debit-wrapper"
                     data-heidelpay-sepa-direct-debit-guaranteed="true"
                     data-heidelpayCreatePaymentUrl="{url controller=HeidelpaySepaDirectDebitGuaranteed action=createPayment module=widgets}">
                    {block name="frontend_checkout_confirm_heidelpay_frames_spea_direct_debit_container"}
                        <div id="heidelpay--sepa-direct-debit-container">
                        </div>
                    {/block}
                </div>

                {include file="frontend/heidelpay/frames/sepa/mandate.tpl"}
            </div>
        </div>
    {/block}
{/block}
