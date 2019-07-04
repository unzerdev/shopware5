{block name="frontend_checkout_confirm_heidelpay_frames_sepa_direct_debit_guaranteed"}
    {if {config name="transaction_mode" namespace="heidel_payment"} === "test"}
        {include file="frontend/heidelpay/frames/test_data/sepa_direct_debit.tpl"}
    {/if}

    <div class="heidelpay--sepa-direct-debit-wrapper"
         data-heidelpay-sepa-direct-debit-guaranteed="true"
         data-heidelpayCreatePaymentUrl="{url controller=HeidelpaySepaDirectDebitGuaranteed action=createPayment module=widgets}">
        {block name="frontend_checkout_confirm_heidelpay_frames_spea_direct_debit_guaranteed_container"}
            <div id="heidelpay--sepa-direct-debit-guaranteed-container">
            </div>
        {/block}
    </div>
{/block}
