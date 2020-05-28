{block name="frontend_checkout_confirm_heidelpay_frames_paypal_vault"}
    <div class="heidelpay--paypal-vault">
        {block name="frontend_checkout_confirm_heidelpay_frames_paypal_vault_message"}
            <div class="heidelpay--vault-message">
                {s name="customerMessage"}{/s}
            </div>
        {/block}

        {block name="frontend_checkout_confirm_heidelpay_frames_paypal_new"}
            <input type="radio" class="heidelpay--radio-button" id="new" name="paypalSelection" {if $heidelpayVault['paypal']|count === 0} checked="checked"{/if}>
            <label for="new">{s name="label/newPayPal"}{/s}</label>
        {/block}

        {foreach $heidelpayVault['paypal'] as $paypalAccount}
            {block name="frontend_checkout_confirm_heidelpay_frames_paypal_vault_card"}
                <div class="heidelpay--paypal-vault-item">
                    {block name="frontend_checkout_confirm_heidelpay_frames_paypal_vault_radio_input"}
                        <input type="radio" id="{$paypalAccount->getTypeId()}"
                               name="paypalSelection"{if $paypalAccount@first} checked="checked"{/if}>
                    {/block}
                    {block name="frontend_checkout_confirm_heidelpay_frames_paypal_vault_label"}
                        <label for="{$paypalAccount->getTypeId()}">
                            {if $paypalAccount->getEmail() === ''}
                                {s name="label/emailNotFound"}{/s}
                            {else}
                                {$paypalAccount->getEmail()}
                            {/if}
                        </label>
                    {/block}
                </div>
            {/block}
        {/foreach}
    </div>
{/block}
