{block name="frontend_checkout_confirm_heidelpay_frames_credit_card_vault"}
    <div class="heidelpay--credit-card-vault">
        {block name="frontend_checkout_confirm_heidelpay_frames_credit_card_vault_message"}
            <div class="heidelpay--credit-card-vault-message">
                {s name="customerMessage"}{/s}
            </div>
        {/block}
        {foreach $heidelpayVault['credit_card'] as $creditCard}
            {block name="frontend_checkout_confirm_heidelpay_frames_credit_card_vault_card"}
                <div class="heidelpay--credit-card-vault-item">
                    {block name="frontend_checkout_confirm_heidelpay_frames_credit_card_vault_card_radio_input"}
                        <input type="radio" id="{$creditCard->getTypeId()}" name="cardSelection"{if $creditCard@first} checked="checked"{/if}>
                    {/block}
                    {block name="frontend_checkout_confirm_heidelpay_frames_credit_card_vault_card_label"}
                        <label for="{$creditCard->getTypeId()}">
                            {$creditCard->getNumber()} {if $creditCard->getHolder()}({$creditCard->getHolder()}){/if}
                        </label>
                    {/block}
                </div>
            {/block}
        {/foreach}
    </div>
{/block}
