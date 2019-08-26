{block name="frontend_checkout_confirm_heidelpay_frames_sepa_vault"}
    <div class="heidelpay--sepa-vault">
        {block name="frontend_checkout_confirm_heidelpay_frames_sepa_vault_vault_message"}
            <div class="heidelpay--vault-message">
                {s name="customerMessage"}{/s}
            </div>
        {/block}
        {foreach $heidelpayVault['sepa_mandate_guaranteed'] as $sepaMandate}
            {block name="frontend_checkout_confirm_heidelpay_frames_sepa_vault_vault_mandate"}
                <div class="heidelpay--sepa-vault-item panel has--border">
                    <div class="panel--body">
                        {block name="frontend_checkout_confirm_heidelpay_frames_sepa_vault_radio_input"}
                            <input type="radio" id="{$sepaMandate->getTypeId()}" name="mandateSelection"{if $sepaMandate@first} checked="checked"{/if}>
                        {/block}
                        {block name="frontend_checkout_confirm_heidelpay_frames_sepa_vault_radio_input_label"}
                            <label for="{$sepaMandate->getTypeId()}">
                                {$sepaMandate->getIban()}
                            </label>
                        {/block}
                    </div>
                </div>
            {/block}
        {/foreach}
    </div>
{/block}
