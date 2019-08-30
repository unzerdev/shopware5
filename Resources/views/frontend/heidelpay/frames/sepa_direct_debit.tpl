{block name="frontend_checkout_confirm_heidelpay_frames_sepa_direct_debit_wrapper"}
    {assign var=hasVaultedMandates value=$heidelpayVault['sepa_mandate'] > 0}

    {if {config name="transaction_mode" namespace="heidel_payment"} === "test"}
        {include file="frontend/heidelpay/frames/test_data/sepa_direct_debit.tpl"}
    {/if}

    {block name="frontend_checkout_confirm_heidelpay_vault_sepa_direct_debit"}
        {if {config name="direct_debit_bookingmode" namespace="heidel_payment"} === "registerCharge"}
            {include file="frontend/heidelpay/frames/vault/sepa_direct_debit.tpl" mandates=$heidelpayVault['sepa_mandate']}
        {/if}
    {/block}

    {block name="frontend_checkout_confirm_heidelpay_frames_sepa_direct_debit_body"}
        <div class="panel has--border">
            <div class="panel--body">
                {block name="frontend_checkout_confirm_heidelpay_frames_sepa_direct_debit_body_new"}
                    {if {config name="direct_debit_bookingmode" namespace="heidel_payment"} === "registerCharge"}
                        <input type="radio" class="heidelpay--radio-button" id="new" name="mandateSelection"{if !$hasVaultedMandates} checked="checked"{/if}>
                        <label for="new">{s name="label/newIban"}{/s}</label>

                        <br/>
                    {/if}
                {/block}

                {block name="frontend_checkout_confirm_heidelpay_frames_sepa_direct_debit_body_content"}
                    <div class="heidelpay--sepa-direct-debit-wrapper"
                         data-heidelpay-sepa-direct-debit="true"
                         data-heidelpayCreatePaymentUrl="{url controller=HeidelpaySepaDirectDebit action=createPayment module=widgets}">
                        {block name="frontend_checkout_confirm_heidelpay_frames_sepa_direct_debit_body_content_container"}
                            <div id="heidelpay--sepa-direct-debit-container">
                            </div>
                        {/block}
                    </div>
                {/block}

                {include file="frontend/heidelpay/frames/sepa/mandate.tpl"}
            </div>
        </div>
    {/block}
{/block}
