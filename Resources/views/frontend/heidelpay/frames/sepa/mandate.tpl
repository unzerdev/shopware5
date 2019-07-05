{block name="frontend_checkout_confirm_heidelpay_frames_sepa_direct_debit_mandate"}
    <div class="heidelpay--sepa-mandate-container">
        {block name="frontend_checkout_confirm_heidelpay_frames_sepa_direct_debit_mandate_body"}
            <div class="heidelpay--sepa-mandate-container-body">
                <h2>{s name="title"}{/s}</h2>
                {s name="text"}{/s}
            </div>
        {/block}

        {block name="frontend_checkout_confirm_heidelpay_frames_sepa_direct_debit_mandate_actions"}
            <div class="heidelpay--sepa-mandate-container-actions">
                <input id="acceptMandate" type="checkbox" required="required" aria-required="true" form="confirm--form">
                <label for="acceptMandate">
                    {s name="input/approve"}{/s}
                </label>
            </div>
        {/block}
    </div>
{/block}
