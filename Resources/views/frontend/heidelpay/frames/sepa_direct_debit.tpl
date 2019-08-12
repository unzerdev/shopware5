{block name="frontend_checkout_confirm_heidelpay_frames_sepa_direct_debit"}
    {if {config name="transaction_mode" namespace="heidel_payment"} === "test"}
        {include file="frontend/heidelpay/frames/test_data/sepa_direct_debit.tpl"}
    {/if}

    {include file="frontend/heidelpay/frames/sepa/mandate.tpl"}

    <div class="heidelpay--sepa-direct-debit-wrapper"
         data-heidelpay-sepa-direct-debit="true"
         data-heidelpayCreatePaymentUrl="{url controller=HeidelpaySepaDirectDebit action=createPayment module=widgets}">
        {block name="frontend_checkout_confirm_heidelpay_frames_spea_direct_debit_container"}
            <div id="heidelpay--sepa-direct-debit-container">
            </div>
        {/block}
    </div>
{/block}
