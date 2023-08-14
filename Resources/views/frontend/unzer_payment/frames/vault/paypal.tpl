{block name="frontend_checkout_confirm_unzer_payment_frames_paypal_vault"}
    <div class="unzer-payment--paypal-vault">
        {if $unzerPaymentVault['paypal']|count > 0}
            {block name="frontend_checkout_confirm_unzer_payment_frames_paypal_vault_message"}
                <div class="unzer-payment--vault-message">
                    {s name="customerMessage"}{/s}
                </div>
            {/block}

            {foreach $unzerPaymentVault['paypal'] as $paypalAccount}
                {block name="frontend_checkout_confirm_unzer_payment_frames_paypal_vault_card"}
                    <div class="unzer-payment--paypal-vault-item">
                        {block name="frontend_checkout_confirm_unzer_payment_frames_paypal_vault_radio_input"}
                            <input type="radio" id="{$paypalAccount->getTypeId()}"
                                   name="paypalSelection"{if $paypalAccount@first} checked="checked"{/if}>
                        {/block}
                        {block name="frontend_checkout_confirm_unzer_payment_frames_paypal_vault_label"}
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
        {/if}

        {block name="frontend_checkout_confirm_unzer_payment_frames_paypal_new"}
            <div class="unzer-payment--paypal-vault-new{if $unzerPaymentVault['paypal']|count === 0} is--hidden{/if}">
                <input type="radio" class="unzer-payment--radio-button" id="new" name="paypalSelection" {if $unzerPaymentVault['paypal']|count === 0} checked="checked"{/if}>
                <label for="new">{s name="label/newPayPal"}{/s}</label>
            </div>
        {/block}

        {block name="frontend_checkout_confirm_unzer_payment_frames_paypal_remember"}
            {if $sUserData.additional.user.accountmode == 0}
                <div class="unzer-payment--paypal-vault-remember{if $unzerPaymentVault['paypal']|count > 0} is--hidden{/if}" >
                    <input type="checkbox" id="rememberPayPal" name="rememberPayPal">
                    <label for="rememberPayPal">{s name="label/remember"}{/s}</label>
                </div>
            {/if}
        {/block}
    </div>
{/block}
