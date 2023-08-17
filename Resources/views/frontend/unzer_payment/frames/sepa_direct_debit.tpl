{block name="frontend_checkout_confirm_unzer_payment_frames_sepa_direct_debit_wrapper"}
    {assign var=hasVaultedMandates value=$unzerPaymentVault['sepa_mandate'] > 0}

    {if {config name="transaction_mode" namespace="unzer_payment"} === "test"}
        {include file="frontend/unzer_payment/frames/test_data/sepa_direct_debit.tpl"}
    {/if}

    {block name="frontend_checkout_confirm_unzer_payment_vault_sepa_direct_debit"}
        {include file="frontend/unzer_payment/frames/vault/sepa_direct_debit.tpl" mandates=$unzerPaymentVault['sepa_mandate']}
    {/block}

    {block name="frontend_checkout_confirm_unzer_payment_frames_sepa_direct_debit_body"}
        <div class="panel has--border">
            <div class="panel--body">
                {block name="frontend_checkout_confirm_unzer_payment_frames_sepa_direct_debit_body_new"}
                    <div {if !$hasVaultedMandates}class="is--hidden"{/if}>
                        <input type="radio" class="unzer-payment--radio-button" id="new" name="mandateSelection"{if !$hasVaultedMandates} checked="checked"{/if}>
                        <label for="new">{s name="label/newIban"}{/s}</label>
                        <br/>
                    </div>
                {/block}

                {block name="frontend_checkout_confirm_unzer_payment_frames_sepa_direct_debit_body_content"}
                    <div class="unzer-payment--sepa-direct-debit-wrapper"
                        {block name="frontend_checkout_confirm_unzer_payment_frames_sepa_direct_debit_wrapper_data"}
                            data-unzer-payment-sepa-direct-debit="true"
                            data-unzerPaymentCreatePaymentUrl="{url controller=UnzerPaymentSepaDirectDebit action=createPayment module=widgets}"
                        {/block}>
                        {block name="frontend_checkout_confirm_unzer_payment_frames_sepa_direct_debit_body_content_container"}
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
